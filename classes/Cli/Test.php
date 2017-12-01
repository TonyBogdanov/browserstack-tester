<?php

namespace BST\Cli;

use BST\Bootstrap;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Test extends Command
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('test')
            ->setDescription('Runs tests against BrowserStack using the specified configuration file.')
            ->addArgument('config-file', InputArgument::OPTIONAL, 'Path to a JSON configuration file.',
                'browserstack.json');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $configPath = $input->getArgument('config-file');

        if (!is_file($configPath) || !is_readable($configPath)) {
            $io->error('Invalid or unreadable configuration file: ' . $configPath);
            return 1;
        }

        try {
            $bootstrap = new Bootstrap($io, $configPath);
            $bootstrap->bootstrap();

            $failures = $bootstrap->run();

            if (!empty($failures)) {
                $i = 1;

                $io->error('Test suite failed' . PHP_EOL . implode(PHP_EOL, array_map(function (string $message)
                    use (&$i) {
                        return $i++ . '. ' . $message;
                    }, $failures)));

                return 1;
            }
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return 1;
        }

        $io->success('Test suite succeeded');
        return 0;
    }
}