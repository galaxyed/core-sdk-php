<?php
namespace ICANID\Tests\unit\Helpers;

use ICANID\SDK\Helpers\JWKFetcher;
use ICANID\Tests\unit\MockApi;
use Psr\SimpleCache\CacheInterface;


/**
 * Class MockJwks
 *
 * @package ICANID\SDK\Helpers\JWKFetcher
 */
class MockJwks extends MockApi
{

    /**
     * @var JWKFetcher
     */
    protected $client;

    /**
     * @param array $guzzleOptions
     * @param array $config
     */
    public function setClient(array $guzzleOptions, array $config = [])
    {
        $cache = isset( $config['cache'] ) && $config['cache'] instanceof CacheInterface ? $config['cache'] : null;
        $this->client = new JWKFetcher( $cache, $guzzleOptions );
    }
}
