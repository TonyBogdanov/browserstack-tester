<?php

namespace BST;

use Symfony\Component\Console\Style\SymfonyStyle;

class Logger
{
    /**
     * @var SymfonyStyle
     */
    protected $io;

    /**
     * @var int
     */
    protected $level = 0;

    /**
     * Logger constructor.
     *
     * @param SymfonyStyle $io
     */
    public function __construct(SymfonyStyle $io)
    {
        $this->io = $io;
    }

    /**
     * @return Logger
     */
    public function inc(): Logger
    {
        $this->level++;
        return $this;
    }

    /**
     * @return Logger
     */
    public function dec(): Logger
    {
        $this->level--;
        return $this;
    }

    /**
     * @param string $message
     * @param string|null $color
     * @return Logger
     */
    public function log(string $message, string $color = null): Logger
    {
        $colors = [
            'cyan',
            'yellow',
            'white'
        ];

        if (!isset($color)) {
            $color = $colors[min($this->level, count($colors) - 1)];
        }

        $this->io->write(
            str_pad('', max(0, $this->level * 2), ' ', STR_PAD_LEFT) .
            '<fg=' . $color . '>' . $message . '</>' .
            PHP_EOL
        );

        return $this;
    }

    /**
     * @param string $message
     * @return Logger
     */
    public function error(string $message): Logger
    {
        $this->io->error($message);
        return $this;
    }
}