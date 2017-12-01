<?php

namespace BST;

class ProcessWorkerTicker
{
    /**
     * @var ProcessWorker[]
     */
    protected $queue = [];

    /**
     * @var int|null
     */
    protected $limit;

    /**
     * @var bool
     */
    protected $mustWait = false;

    /**
     * @var bool
     */
    protected $started = false;

    /**
     * @var bool
     */
    protected $finished = false;

    /**
     * @param array ...$tickers
     */
    public static function waitAndTick(...$tickers)
    {
        while (true) {
            $running = false;

            /** @var ProcessWorkerTicker $ticker */
            foreach ($tickers as $ticker) {
                $ticker->tick();

                if ($ticker->isMustWait() && !$ticker->isFinished()) {
                    $running = true;
                }
            }

            if (!$running) {
                break;
            }

            usleep(50000);
        }
    }

    /**
     * @return ProcessWorkerTicker
     */
    protected function updateState(): ProcessWorkerTicker
    {
        $pending = null;
        $running = 0;

        foreach ($this->getQueue() as $processWorker) {
            if ($processWorker->isFinished()) {
                continue;
            }
            if ($processWorker->isRunning()) {
                $running++;
            } else if (!isset($pending)) {
                $pending = $processWorker;
            }
        }

        if (0 === $running && !$pending) {
            $this->finished = true;
            return $this;
        }

        if ((!$this->hasLimit() || 0 >= $this->getLimit() || $running < $this->getLimit()) && $pending) {
            $pending->start();
        }

        return $this;
    }

    /**
     * ProcessWorkerTicker constructor.
     *
     * @param array $queue
     * @param int|null $limit
     * @param bool $mustWait
     */
    public function __construct(array $queue, int $limit = null, bool $mustWait = false)
    {
        $this->setQueue($queue);
        $this->setLimit($limit);
        $this->setMustWait($mustWait);
    }

    /**
     * @return ProcessWorkerTicker
     * @throws \Exception
     */
    public function start(): ProcessWorkerTicker
    {
        if ($this->isStarted()) {
            throw new \Exception('Cannot start process worker ticker, already started.');
        }
        if ($this->isFinished()) {
            throw new \Exception('Cannot start process worker ticker, already finished.');
        }

        $this->started = true;

        foreach ($this->getQueue() as $processWorker) {
            $processWorker
                ->getEmitter()
                ->on(ProcessWorker::EVENT_START, function () {
                    $this->updateState();
                })->on(ProcessWorker::EVENT_STOP, function () {
                    $this->updateState();
                });
        }

        current($this->getQueue())->start();

        return $this;
    }

    /**
     * @return ProcessWorkerTicker
     */
    public function tick(): ProcessWorkerTicker
    {
        foreach ($this->getQueue() as $processWorker) {
            $processWorker->isRunning();
        }

        return $this;
    }

    /**
     * @return ProcessWorker[]
     */
    public function getQueue(): array
    {
        return $this->queue;
    }

    /**
     * @param ProcessWorker[] $queue
     * @return ProcessWorkerTicker
     */
    public function setQueue(array $queue): ProcessWorkerTicker
    {
        $this->queue = $queue;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasLimit(): bool
    {
        return null !== $this->getLimit();
    }

    /**
     * @return int|null
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int|null $limit
     * @return ProcessWorkerTicker
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @return bool
     */
    public function isMustWait(): bool
    {
        return $this->mustWait;
    }

    /**
     * @param bool $mustWait
     * @return ProcessWorkerTicker
     */
    public function setMustWait(bool $mustWait): ProcessWorkerTicker
    {
        $this->mustWait = $mustWait;
        return $this;
    }

    /**
     * @return bool
     */
    public function isStarted(): bool
    {
        return $this->started;
    }

    /**
     * @return bool
     */
    public function isFinished(): bool
    {
        return $this->finished;
    }
}