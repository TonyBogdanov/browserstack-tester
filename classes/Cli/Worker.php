<?php

namespace BST\Cli;

use BST\WebServer;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Worker extends Command
{
    const SECRET = 'kwCdznu03F';

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('worker')
            ->setDescription('Starts a selenium web driver worker (internal).')
            ->setHelp('This is an internal command, do not invoke it directly.')
            ->addArgument('secret', InputArgument::REQUIRED)
            ->addArgument('options', InputArgument::REQUIRED);
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if (self::SECRET !== $input->getArgument('secret')) {
            $io->error('This command is internal.');
            return 1;
        }

        $options = json_decode(base64_decode($input->getArgument('options')), true);
        if (!is_array($options)) {
            $io->error('Invalid input data');
            return 1;
        }

        // todo make timeouts configurable?
        $connectionTimeout = 300;
        $requestTimeout = 300;
        $scriptTimeout = 120;
        $pageTimeout = 30;

        try {
            $driver = RemoteWebDriver::create(
                'https://' . $options['username'] . ':' . $options['key'] . '@hub-cloud.browserstack.com/wd/hub',
                [
                    'build' => $options['build'],
                    'browser' => $options['browser'],
                    'browser_version' => $options['browser_version'],
                    'os' => $options['os'],
                    'os_version' => $options['os_version'],
                    'device' => $options['device'],
                    'real_mobile' => $options['real_mobile'],
                    'acceptSslCert' => true,
                    'browserstack.debug' => true,
                    'browserstack.local' => true
                ],
                $connectionTimeout * 1000,
                $requestTimeout * 1000
            );

            // set timeouts
            $driver->manage()->timeouts()
                ->setScriptTimeout($scriptTimeout)
                ->implicitlyWait($pageTimeout)
                ->pageLoadTimeout($pageTimeout);

            // try to load in sequence
            $session = $driver->getSessionID();
            $addresses = [
                'localhost',
                '127.0.0.1',
                $options['ip']
            ];
            $prefix = 'http://';
            $suffix = ':' . WebServer::PORT . $options['entry'] . '?_s=' . $session;

            for ($i = 0, $count = count($addresses);; $i++) {
                // end of address list reached?
                if ($i >= $count) {
                    throw new \Exception('Unable to load test suite, tried with ' . implode(', ', $addresses));
                }

                // request page load
                $driver->get($prefix . $addresses[$i] . $suffix);

                // wait for page load indicated by the presence of a #bst_reporter element
                // if page load times out, or an error page is loaded, continue with the next address
                try {
                    $driver->wait($pageTimeout)->until(WebDriverExpectedCondition::presenceOfElementLocated(
                        WebDriverBy::id('bst_reporter')
                    ));

                    // page is loaded successfully, trigger the reporter and finish up
                    $driver->executeAsyncScript(file_get_contents(__DIR__ . '/../../assets/webdriver-blocker.min.js'));
                    break;
                } catch (\Exception $e) {}
            }

            $driver->takeScreenshot();
            $driver->quit();
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return 1;
        }

        return 0;
    }
}