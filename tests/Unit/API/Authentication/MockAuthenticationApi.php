<?php
namespace ICANID\Tests\unit\API\Authentication;

use ICANID\SDK\API\Authentication;
use ICANID\Tests\unit\MockApi;

/**
 * Class MockAuthenticationApi
 *
 * @package ICANID\Tests\unit\API\Authentication
 */
class MockAuthenticationApi extends MockApi
{

    /**
     * @var Authentication
     */
    protected $client;

    /**
     * @param array $guzzleOptions
     * @param array $config
     */
    public function setClient(array $guzzleOptions, array $config = [])
    {
        $this->client = new Authentication(
            'test-domain.auth0.com',
            '__test_client_id__',
            '__test_client_secret__',
            null,
            null,
            $guzzleOptions
        );
    }
}
