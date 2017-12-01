<?php

namespace BST;

use Evenement\EventEmitter;
use Symfony\Component\Process\Process;

class ProcessWorker
{
    const EVENT_START = 0;
    const EVENT_STOP = 1;
    const EVENT_OUTPUT = 2;
    const EVENT_ERROR = 3;

    /**
     * @var bool
     */
    protected $stopAnnounced = false;

    /**
     * @var Process
     */
    protected $process;

    /**
     * @var EventEmitter
     */
    protected $emitter;

    /**
     * @param array $workers
     * @param array $keepAlive
     * @param int $loopPause
     */
    public static function waitForAll(array $workers, array $keepAlive = [], int $loopPause = 10000)
    {
        do {
            $running = false;

            /** @var ProcessWorker $worker */
            foreach ($workers as $worker) {
                if ($worker->isRunning()) {
                    $running = true;
                }
            }

            /** @var ProcessWorker $worker */
            foreach ($keepAlive as $worker) {
                $worker->isRunning();
            }

            if ($running) {
                usleep($loopPause);
            }
        } while ($running);
    }

    /**
     * ProcessWorker constructor.
     *
     * @param string $executable
     * @param string[] ...$arguments
     */
    public function __construct(string $executable, ...$arguments)
    {
        $this->setProcess(new Process(
            implode(' ', array_merge([$executable], array_map(function ($argument) {
                if ($argument instanceof ProcessWorkerArgument) {
                    $arguments = implode(' ', array_map('escapeshellarg', $argument->getValues()));
                    return $argument->getName() . (empty($arguments) ? '' : ' ' . $arguments);
                }
                return escapeshellarg($argument);
            }, $arguments))),
            null,
            null,
            null,
            null
        ));
        $this->setEmitter(new EventEmitter());
    }

    /**
     * @return bool
     */
    public function isRunning(): bool
    {
        if ($this->getProcess()->isTerminated()) {
            if (!$this->stopAnnounced) {
                $this->stopAnnounced = true;
                $this->getEmitter()->emit(self::EVENT_STOP, [
                    $this->getProcess()->getExitCode(),
                    $this->getProcess()->getExitCodeText(),
                    $this
                ]);
            }

            return false;
        }

        return $this->getProcess()->isRunning();
    }

    /**
     * @return bool
     */
    public function isFinished(): bool
    {
        return $this->getProcess()->isTerminated();
    }

    /**
     * @return ProcessWorker
     */
    public function start(): ProcessWorker
    {
        $this->getProcess()->start(function (string $type, string $output) {
            /** @var string $item */
            foreach (Utils::dequantize($output) as $item) {
                $this->getEmitter()->emit(Process::ERR === $type ? self::EVENT_ERROR : self::EVENT_OUTPUT, [
                    $item,
                    $this
                ]);
            }
        });
        $this->getEmitter()->emit(self::EVENT_START, [$this]);

        return $this;
    }

    /**
     * @return ProcessWorker
     */
    public function stop(): ProcessWorker
    {
        $this->getProcess()->stop(0, 2);

        if (!$this->stopAnnounced) {
            $this->stopAnnounced = true;
            $this->getEmitter()->emit(self::EVENT_STOP, [0, 'OK', $this]);
        }

        return $this;
    }

    /**
     * @return Process
     */
    public function getProcess(): Process
    {
        return $this->process;
    }

    /**
     * @param Process $process
     * @return ProcessWorker
     */
    public function setProcess(Process $process): ProcessWorker
    {
        $this->process = $process;
        return $this;
    }

    /**
     * @return EventEmitter
     */
    public function getEmitter(): EventEmitter
    {
        return $this->emitter;
    }

    /**
     * @param EventEmitter $emitter
     * @return ProcessWorker
     */
    public function setEmitter(EventEmitter $emitter): ProcessWorker
    {
        $this->emitter = $emitter;
        return $this;
    }
}