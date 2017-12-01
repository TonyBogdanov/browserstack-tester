<?php

namespace BST\Device;

class Browser extends AbstractIdentifier
{
    /**
     * @var string|null
     */
    protected $device;

    /**
     * @var bool|null
     */
    protected $realMobile;

    /**
     * @var OS|null
     */
    protected $os;

    /**
     * @param array $data
     * @return Browser
     * @throws \Exception
     */
    public static function fromArray(array $data): Browser
    {
        if (!isset($data['browser']) || empty($data['browser'])) {
            throw new \Exception('Missing required parameter: "browser" (browser name)');
        }

        try {
            $os = OS::fromArray($data);
        } catch (\Exception $e) {
            $os = null;
        }

        return new self(
            $data['browser'],
            isset($data['browser_version']) && is_string($data['browser_version']) && !empty($data['browser_version']) ?
                $data['browser_version'] : null,
            isset($data['device']) && is_string($data['device']) && !empty($data['device']) ? $data['device'] : null,
            isset($data['real_mobile']) && is_bool($data['real_mobile']) ? $data['real_mobile'] : null,
            $os
        );
    }

    /**
     * Browser constructor.
     *
     * @param string $name
     * @param string|null $version
     * @param string|null $device
     * @param bool|null $realMobile
     * @param OS|null $os
     */
    public function __construct(
        string $name,
        string $version = null,
        string $device = null,
        bool $realMobile = null,
        OS $os = null
    ) {
        parent::__construct($name, $version);

        if (isset($device)) {
            $this->setDevice($device);
        }
        if (isset($realMobile)) {
            $this->setRealMobile($realMobile);
        }

        $this->setOs($os);
    }

    /**
     * @return null|string
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * @param null|string $device
     * @return Browser
     */
    public function setDevice($device)
    {
        $this->device = $device;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getRealMobile()
    {
        return $this->realMobile;
    }

    /**
     * @param bool|null $realMobile
     * @return Browser
     */
    public function setRealMobile($realMobile)
    {
        $this->realMobile = $realMobile;
        return $this;
    }

    /**
     * @return OS|null
     */
    public function getOs()
    {
        return $this->os;
    }

    /**
     * @param OS|null $os
     * @return Browser
     */
    public function setOs($os)
    {
        $this->os = $os;
        return $this;
    }
}