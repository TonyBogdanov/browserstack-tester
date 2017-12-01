<?php

namespace BST;

use GuzzleHttp\Client;

class Tunnel
{
    /**
     * @var Logger
     */
    protected $logger;
    
    /**
     * @var string
     */
    protected $key;

    /**
     * @var ProcessWorker
     */
    protected $process;

    /**
     * @var string
     */
    protected $binaryFilename;

    /**
     * @var string
     */
    protected $binaryPath;

    /**
     * @var bool
     */
    protected $ready = false;

    /**
     * @return string
     */
    protected function getBinaryFilename(): string
    {
        if (!isset($this->binaryFilename)) {
            $this->binaryFilename = 'WIN' === strtoupper(substr(PHP_OS, 0, 3)) ?
                'BrowserStackLocal.exe' : 'BrowserStackLocal';
        }

        return $this->binaryFilename;
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function getBinaryPath(): string
    {
        if (!isset($this->binaryPath)) {
            $home = getenv('HOME');
            if (!$home || !is_writable($home)) {
                $home = sys_get_temp_dir();
                if (!$home || !is_writable($home)) {
                    throw new \Exception('HOME variable is either missing, does not point to a writable directory,' .
                        ' or the system temp directory isn\'t writable');
                }
            }

            $this->binaryPath = $home . DIRECTORY_SEPARATOR . $this->getBinaryFilename();
        }

        return $this->binaryPath;
    }

    /**
     * @param string $path
     * @return bool
     * @throws \Exception
     */
    protected function downloadBinary(string $path): bool
    {
        switch (true) {
            case 'Darwin' === PHP_OS:
                $url = 'https://s3.amazonaws.com/browserStack/browserstack-local/BrowserStackLocal-darwin-x64';
                break;

            case 'WIN' === strtoupper(substr(PHP_OS, 0, 3)):
                $url = 'https://s3.amazonaws.com/browserStack/browserstack-local/BrowserStackLocal.exe';
                break;

            case 'LINUX' === strtoupper(PHP_OS):
                $url = 64 === 8 * PHP_INT_SIZE ?
                    'https://s3.amazonaws.com/browserStack/browserstack-local/BrowserStackLocal-linux-x64' :
                    'https://s3.amazonaws.com/browserStack/browserstack-local/BrowserStackLocal-linux-ia32';
                break;

            default:
                throw new \Exception('Unsupported operating system');
        }

        $client = new Client();
        $response = $client->get($url);

        if (!file_put_contents($path, (string) $response->getBody())) {
            return false;
        }

        if (!chmod($path, 0777)) {
            return false;
        }

        return true;
    }

    /**
     * Tunnel constructor.
     *
     * @param Logger $logger
     * @param string $key
     */
    public function __construct(Logger $logger, string $key)
    {
        $this->setLogger($logger);
        $this->setKey($key);
        $this->setProcess(new ProcessWorker(
            $this->getBinaryPath(),
            new ProcessWorkerArgument('--key ', $key)
        ));
    }

    /**
     * @return Tunnel
     * @throws \Exception
     */
    public function start(): Tunnel
    {
        $binary = $this->getBinaryPath();
        if (!is_file($binary)) {
            $this->getLogger()->log('BrowserStackLocal binary file missing, downloading to ' . $binary);

            if (!$this->downloadBinary($binary)) {
                throw new \Exception('Could not download BrowserStackLocal binary');
            }

            $this->getLogger()->log('Download successful, proceeding');
        }

        $this->getProcess()->start();

        // there is no reliable way of testing when BrowserStackLocal becomes active
        sleep(10);

        return $this;
    }

    /**
     * @return Tunnel
     */
    public function stop(): Tunnel
    {
        $this->getProcess()->stop();

        return $this;
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
     * @return Tunnel
     */
    public function setLogger(Logger $logger): Tunnel
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     * @return Tunnel
     */
    public function setKey(string $key): Tunnel
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return ProcessWorker
     */
    public function getProcess(): ProcessWorker
    {
        return $this->process;
    }

    /**
     * @param ProcessWorker $process
     * @return Tunnel
     */
    public function setProcess(ProcessWorker $process): Tunnel
    {
        $this->process = $process;
        return $this;
    }
}