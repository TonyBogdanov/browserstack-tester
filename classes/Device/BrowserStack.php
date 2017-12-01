<?php

namespace BST\Device;

class BrowserStack implements \Countable
{
    /**
     * @var Browser[]
     */
    protected $browsers;

    /**
     * @param array $data
     * @return BrowserStack
     * @throws \Exception
     */
    public static function fromArray(array $data): BrowserStack
    {
        $browsers = [];

        foreach ($data as $entry) {
            if (!is_array($entry)) {
                throw new \Exception('Supplied browser entry is not a valid array');
            }

            $browsers[] = Browser::fromArray($entry);
        }

        return new self($browsers);
    }

    /**
     * BrowserStack constructor.
     *
     * @param Browser[] $browsers
     */
    public function __construct(array $browsers = [])
    {
        $this->setBrowsers($browsers);
    }

    /**
     * @return BrowserStack
     */
    public function sort(): BrowserStack
    {
        usort($this->browsers, function (Browser $left, Browser $right) {
            $cmp = $left->getName() <=> $right->getName();
            if (0 !== $cmp) {
                return $cmp;
            }

            $cmp = version_compare($left->getVersion(), $right->getVersion());
            if (0 !== $cmp) {
                return -1 * $cmp;
            }

            $cmp = (null === $left->getOs() ? null : $left->getOs()->getName()) <=>
                (null === $right->getOs() ? null : $right->getOs()->getName());
            if (0 !== $cmp) {
                return $cmp;
            }

            $cmp = version_compare(
                (null === $left->getOs() ? '' : $left->getOs()->getVersion()),
                (null === $right->getOs() ? '' : $right->getOs()->getVersion())
            );
            if (0 !== $cmp) {
                return -1 * $cmp;
            }

            return 0;
        });

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        return count($this->getBrowsers());
    }

    /**
     * @return Browser[]
     */
    public function getBrowsers(): array
    {
        return $this->browsers;
    }

    /**
     * @param Browser[] $browsers
     * @return BrowserStack
     */
    public function setBrowsers(array $browsers): BrowserStack
    {
        $this->browsers = $browsers;
        return $this;
    }
}