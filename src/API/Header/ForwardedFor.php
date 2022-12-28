<?php
namespace ICANID\SDK\API\Header;

class ForwardedFor extends Header
{

    /**
     * ForwardedFor constructor.
     *
     * @param string $ipAddress
     */
    public function __construct($ipAddress)
    {
        parent::__construct('ICANID-Forwarded-For', $ipAddress);
    }
}
