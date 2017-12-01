<?php

namespace BST;

use BST\Device\BrowserStack;

class Config
{
    const FRAMEWORK_MOCHA = 'mocha';

    const DEFAULT_TIMEOUT = 300;

    /**
     * @var string
     */
    protected $project;

    /**
     * @var string
     */
    protected $root;

    /**
     * @var string
     */
    protected $entry;

    /**
     * @var string
     */
    protected $framework;

    /**
     * @var BrowserStack
     */
    protected $browsers;

    /**
     * @var int
     */
    protected $timeout;

    /**
     * @param string $key
     * @param string $name
     * @return \Exception
     */
    public static function invalidEntry(string $key, string $name): \Exception
    {
        return new \Exception(sprintf('Missing or invalid required configuration: "%s" (%s)', $key, $name));
    }

    /**
     * @param array $data
     * @return Config
     * @throws \Exception
     */
    public static function fromArray(array $data): Config
    {
        if (
            !isset($data['project']) ||
            empty($data['project'])
        ) {
            throw self::invalidEntry('project', 'project name');
        }

        if (
            !isset($data['root']) ||
            !($data['root'] = realpath($data['root'])) ||
            !is_dir($data['root'])
        ) {
            throw self::invalidEntry('root', 'project root path');
        }

        if (
            !isset($data['entry']) ||
            !($data['entry'] = realpath($data['entry'])) ||
            !is_file($data['entry']) ||
            $data['root'] !== substr($data['entry'], 0, strlen($data['root'])) ||
            !($data['entry'] = str_replace('\\', '/', substr($data['entry'], strlen($data['root']))))
        ) {
            throw self::invalidEntry('entry', 'testing entry point');
        }

        if (
            !isset($data['framework']) ||
            !in_array($data['framework'], [
                self::FRAMEWORK_MOCHA
            ])
        ) {
            throw self::invalidEntry('framework', 'testing framework');
        }

        if (
            !isset($data['browsers']) ||
            !is_array($data['browsers']) ||
            empty($data['browsers']) ||
            !($data['browsers'] = BrowserStack::fromArray($data['browsers'])->sort())
        ) {
            throw self::invalidEntry('browsers', 'testing browsers');
        }

        if (
            !isset($data['timeout']) ||
            !is_int($data['timeout']) ||
            0 > $data['timeout']
        ) {
            $data['timeout'] = self::DEFAULT_TIMEOUT;
        }

        return (new Config())
            ->setProject($data['project'])
            ->setRoot($data['root'])
            ->setEntry($data['entry'])
            ->setFramework($data['framework'])
            ->setBrowsers($data['browsers'])
            ->setTimeout($data['timeout']);
    }

    /**
     * @param string $json
     * @return Config
     * @throws \Exception
     */
    public static function fromJson(string $json): Config
    {
        $array = json_decode($json, true);
        if (!is_array($array)) {
            throw new \Exception('Supplied configuration content is not a valid JSON string');
        }

        return self::fromArray($array);
    }

    /**
     * @param string $path
     * @return Config
     * @throws \Exception
     */
    public static function fromFile(string $path): Config
    {
        $json = file_get_contents($path);
        if (!$json) {
            throw new \Exception('Could not read ' . $path);
        }

        return self::fromJson($json);
    }

    /**
     * @return string
     */
    public function getRoot(): string
    {
        return $this->root;
    }

    /**
     * @param string $root
     * @return Config
     */
    public function setRoot(string $root): Config
    {
        $this->root = $root;
        return $this;
    }

    /**
     * @return string
     */
    public function getEntry(): string
    {
        return $this->entry;
    }

    /**
     * @param string $entry
     * @return Config
     */
    public function setEntry(string $entry): Config
    {
        $this->entry = $entry;
        return $this;
    }

    /**
     * @return string
     */
    public function getProject(): string
    {
        return $this->project;
    }

    /**
     * @param string $project
     * @return Config
     */
    public function setProject(string $project): Config
    {
        $this->project = $project;
        return $this;
    }

    /**
     * @return string
     */
    public function getFramework(): string
    {
        return $this->framework;
    }

    /**
     * @param string $framework
     * @return Config
     */
    public function setFramework(string $framework): Config
    {
        $this->framework = $framework;
        return $this;
    }

    /**
     * @return BrowserStack
     */
    public function getBrowsers(): BrowserStack
    {
        return $this->browsers;
    }

    /**
     * @param BrowserStack $browsers
     * @return Config
     */
    public function setBrowsers(BrowserStack $browsers): Config
    {
        $this->browsers = $browsers;
        return $this;
    }

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     * @return Config
     */
    public function setTimeout(int $timeout): Config
    {
        $this->timeout = $timeout;
        return $this;
    }
}