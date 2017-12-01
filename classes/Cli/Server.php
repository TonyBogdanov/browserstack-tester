<?php

namespace BST\Cli;

use BST\Utils;
use BST\WebServer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Server extends Command
{
    const SECRET = 'IAe4dprq9L';

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('web-server')
            ->setDescription('Starts a web server (internal).')
            ->setHelp('This is an internal command, do not invoke it directly.')
            ->addArgument('secret', InputArgument::REQUIRED)
            ->addArgument('root', InputArgument::REQUIRED);
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // quantize the output to ensure de-buffering
        // this is OK since this should only ever be called by the process worker
        $io = new SymfonyStyle($input, $output);

        if (self::SECRET !== $input->getArgument('secret')) {
            $io->error(Utils::quantize('This command is internal.'));
            return 1;
        }

        $server = new WebServer(__DIR__ . '/../..', $input->getArgument('root'));
        $server->start(function (array $result) use ($io) {
            $io->write(Utils::quantize(json_encode($result)));
        });

        return 0;
    }
}