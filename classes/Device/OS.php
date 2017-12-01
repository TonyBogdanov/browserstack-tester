<?php

namespace BST\Device;

class OS extends AbstractIdentifier
{
    /**
     * @param array $data
     * @return OS
     * @throws \Exception
     */
    public static function fromArray(array $data): OS
    {
        if (!isset($data['os']) || empty($data['os'])) {
            throw new \Exception('Missing required parameter: "os" (os name)');
        }

        return new self(
            $data['os'],
            isset($data['os_version']) && is_string($data['os_version']) && !empty($data['os_version']) ?
                $data['os_version'] : null
        );
    }
}