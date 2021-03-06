<?php

namespace BST;

use BST\Cli\Server;
use BST\Cli\Worker;
use GuzzleHttp\Client;
use Symfony\Component\Console\Style\SymfonyStyle;

class Bootstrap
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var string
     */
    protected $configPath;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var API
     */
    protected $api;

    /**
     * @var string
     */
    protected $ip;

    /**
     * @var bool
     */
    protected $bootstrapped = false;

    /**
     * Bootstrap constructor.
     *
     * @param SymfonyStyle $io
     * @param string $configPath
     * @throws \Exception
     */
    public function __construct(SymfonyStyle $io, string $configPath)
    {
        $this->setConfigPath($configPath);
        $this->setLogger(new Logger($io));
        $this->setApi(new API($this));
    }

    /**
     * @return Bootstrap
     * @throws \Exception
     */
    public function bootstrap(): Bootstrap
    {
        $this->getLogger()->log('Bootstrapping BST')->inc();

        $this->getLogger()->log('Loading config file: ' . $this->getConfigPath())->inc();
        $this->setConfig(Config::fromFile($this->getConfigPath()));
        $this->getLogger()->dec();

        $this->getLogger()->log('Detecting public IP')->inc();

        try {
            $ip = @json_decode((new Client([
                'timeout' => 5
            ]))->get('https://api.ipify.org?format=json')->getBody(), true);

            if (!is_array($ip) || !isset($ip['ip'])) {
                throw new \Exception('');
            }

            $this->setIp($ip['ip']);
        } catch (\Exception $e) {
            throw new \Exception('Cannot determine public IP address of the current machine');
        }

        $this->getLogger()->log('Detected as: ' . $this->getIp())->dec();

        $this->getLogger()->log('Bootstrapping APIs')->inc();
        $this->getApi()->bootstrap();
        $this->getLogger()->dec();

        $this->bootstrapped = true;
        $this->getLogger()->dec();

        return $this;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function run(): array
    {
        if (!$this->bootstrapped) {
            throw new \Exception('Cannot run a non-bootstrapped configuration');
        }

        $this->getLogger()->log('Running BST');
        $this->getLogger()->inc();

        $browserCount = count($this->getConfig()->getBrowsers());
        $build = $this->getConfig()->getProject() . ': ' . date('H:i:s - d.m.Y');
        $buildFailures = [];

        $tunnel = new Tunnel($this->getLogger(), $this->getApi()->getKey());

        $server = new ProcessWorker(
            'php',
            __DIR__ . '/../bin/bst.php',
            'web-server',
            Server::SECRET,
            $this->getConfig()->getRoot()
        );
        $server
            ->getEmitter()
            ->on(ProcessWorker::EVENT_START, function () {
                $this->getLogger()->log('WebServer running on port: ' . WebServer::PORT);
            })->on(ProcessWorker::EVENT_STOP, function (int $code, string $text) {
                $this->getLogger()->log(
                    'WebServer stopped [' . $code . ']: ' . $text,
                    0 === $code ? 'green' : 'red'
                );
            })->on(ProcessWorker::EVENT_OUTPUT, function (string $output) use (&$buildFailures) {
                $report = json_decode($output, true);
                if (!is_array($report)) {
                    throw new \Exception('Report is not a valid JSON: ' . $output);
                }

                $failures = isset($report['failures']) && !empty($report['failures']) ? $report['failures'] : [];
                $status = empty($failures);

                $this->getLogger()->log(
                    'WebServer received report [' . ($status ? 'OK' : 'FAILED') . '] ' . $report['session'],
                    $status ? 'green' : 'red'
                )->log('Updating worker session');

                if (!$status) {
                    $buildFailures = array_merge($buildFailures, $failures);
                }

                $this->getApi()->notify($report['session'], $status, implode(PHP_EOL, $failures));
            })->on(ProcessWorker::EVENT_ERROR, function (string $output) {
                $this->getLogger()->log(
                    'WebServer caught error: ' . $output,
                    'red'
                );
            });

        $workers = [];
        foreach ($this->getConfig()->getBrowsers()->getBrowsers() as $index => $browser) {
            ($worker = new ProcessWorker(
                'php',
                __DIR__ . '/../bin/bst.php',
                'worker',
                Worker::SECRET,
                base64_encode(json_encode([
                    'username' => $this->getApi()->getUsername(),
                    'key' => $this->getApi()->getKey(),
                    'browser' => $browser->getName(),
                    'browser_version' => $browser->getVersion(),
                    'os' => null === $browser->getOs() ? null : $browser->getOs()->getName(),
                    'os_version' => null === $browser->getOs() ? null : $browser->getOs()->getVersion(),
                    'device' => $browser->getDevice(),
                    'real_mobile' => $browser->getRealMobile(),
                    'build' => $build,
                    'entry' => $this->getConfig()->getEntry(),
                    'ip' => $this->getIp()
                ]))
            ))->getEmitter()
                ->on(ProcessWorker::EVENT_START, function () use ($index) {
                    $this->getLogger()->log('Worker #' . $index . ' started');
                })->on(ProcessWorker::EVENT_STOP, function (int $code, string $text) use ($index) {
                    $this->getLogger()->log(
                        'Worker #' . $index . ' stopped [' . $code . ']: ' . $text,
                        0 === $code ? 'green' : 'red'
                    );
                })->on(ProcessWorker::EVENT_OUTPUT, function (string $output) {
                    $this->getLogger()->log($output);
                });
            $workers[] = $worker;
        }

        $serverTicker = new ProcessWorkerTicker([$server], null, false);
        $workersTicker = new ProcessWorkerTicker($workers, $this->getApi()->queryParallelLimit(), true);

        $this->getLogger()->log('Booting up local connection tunnel');
        $this->getLogger()->inc();

        $tunnel->start();

        $this->getLogger()->dec();

        $this->getLogger()->log('Booting up web server and ' . $browserCount . ' workers');
        $this->getLogger()->inc();

        $serverTicker->start();
        $workersTicker->start();

        $this->getLogger()
            ->dec()
            ->log('BST running, waiting for reports')
            ->inc();

        ProcessWorkerTicker::waitAndTick($serverTicker, $workersTicker);

        $tunnel->stop();

        $this->getLogger()->dec();
        $this->getLogger()->log('BST work finished');

        $this->getLogger()->dec();
        $this->getLogger()->log('Terminating');

        return $buildFailures;
    }

    /**
     * @return Logger
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }

    /**
     * @param Logger $logger
     * @return Bootstrap
     */
    public function setLogger(Logger $logger): Bootstrap
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @return string
     */
    public function getConfigPath(): string
    {
        return $this->configPath;
    }

    /**
     * @param string $configPath
     * @return Bootstrap
     */
    public function setConfigPath(string $configPath): Bootstrap
    {
        $this->configPath = $configPath;
        return $this;
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @param Config $config
     * @return Bootstrap
     */
    public function setConfig(Config $config): Bootstrap
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @return API
     */
    public function getApi(): API
    {
        return $this->api;
    }

    /**
     * @param API $api
     * @return Bootstrap
     */
    public function setApi(API $api): Bootstrap
    {
        $this->api = $api;
        return $this;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     * @return Bootstrap
     */
    public function setIp(string $ip): Bootstrap
    {
        $this->ip = $ip;
        return $this;
    }
}