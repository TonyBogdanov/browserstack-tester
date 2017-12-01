<?php

namespace BST\Device;

abstract class AbstractIdentifier
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $simpleName;

    /**
     * @var string|null
     */
    protected $version;

    /**
     * @var string|null
     */
    protected $simpleVersion;

    /**
     * AbstractIdentifier constructor.
     *
     * @param string $name
     * @param string|null $version
     */
    public function __construct(string $name = null, string $version = null)
    {
        $this->setName($name);
        $this->setSimpleName(strtolower(trim($name)));

        if (isset($version)) {
            $this->setVersion($version);
            $this->setSimpleVersion(preg_replace('/^(.*?)(\.0)*$/', '$1', $version));
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return AbstractIdentifier
     */
    public function setName(string $name): AbstractIdentifier
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getSimpleName(): string
    {
        return $this->simpleName;
    }

    /**
     * @param string $simpleName
     * @return AbstractIdentifier
     */
    public function setSimpleName(string $simpleName): AbstractIdentifier
    {
        $this->simpleName = $simpleName;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param null|string $version
     * @return AbstractIdentifier
     */
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getSimpleVersion()
    {
        return $this->simpleVersion;
    }

    /**
     * @param null|string $simpleVersion
     * @return AbstractIdentifier
     */
    public function setSimpleVersion($simpleVersion)
    {
        $this->simpleVersion = $simpleVersion;
        return $this;
    }
}