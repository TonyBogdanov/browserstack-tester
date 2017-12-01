<?php

namespace BST;

class ProcessWorkerArgument
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string[]
     */
    protected $values;

    /**
     * ProcessWorkerArgument constructor.
     *
     * @param string $name
     * @param string[] $values
     */
    public function __construct(string $name, ...$values)
    {
        $this->setName($name);
        $this->setValues($values);
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
     * @return ProcessWorkerArgument
     */
    public function setName(string $name): ProcessWorkerArgument
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @param string[] $values
     * @return ProcessWorkerArgument
     */
    public function setValues(array $values): ProcessWorkerArgument
    {
        $this->values = $values;
        return $this;
    }
}