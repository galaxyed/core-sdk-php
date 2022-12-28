<?php
namespace ICANID\SDK\API\Header;

class AuthorizationBasic extends Header
{

    /**
     * AuthorizationBasic constructor.
     *
     * @param string $token
     */
    public function __construct($token)
    {
        parent::__construct('Authorization', "Basic $token");
    }
}
