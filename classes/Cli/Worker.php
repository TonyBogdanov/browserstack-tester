<?php

namespace BST\Cli;

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

        try {
            $driver = \RemoteWebDriver::create(
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
                $connectionTimeout,
                $requestTimeout
            );

            $driver->manage()->timeouts()->setScriptTimeout($scriptTimeout);
            $driver->get('http://localhost:4000' . $options['entry'] . '?_s=' . $driver->getSessionID());

            // wait until the report is available, then take a screen shot
            $driver->executeAsyncScript(file_get_contents(__DIR__ . '/../../assets/webdriver-blocker.min.js'));
            $driver->takeScreenshot();

            $driver->quit();
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return 1;
        }

        return 0;
    }
}