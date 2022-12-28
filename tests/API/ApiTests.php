<?php
namespace ICANID\Tests\API;

use ICANID\SDK\API\Authentication;
use ICANID\Tests\Traits\ErrorHelpers;
use josegonzalez\Dotenv\Loader;
use PHPUnit\Framework\TestCase;

/**
 * Class ApiTests.
 * Extend to test API endpoints with a live or mock API.
 *
 * @package ICANID\Tests\API
 */
class ApiTests extends TestCase
{
    use ErrorHelpers;

    /**
     * Environment variables.
     *
     * @var array
     */
    protected static $env = [];

    /**
     * Get all test suite environment variables.
     *
     * @return array
     *
     * @throws \ICANID\SDK\Exception\ApiException
     */
    protected static function getEnv()
    {
        if (self::$env) {
            return self::$env;
        }

        $env_path = '.env';

        if (file_exists($env_path)) {
            $loader = new Loader($env_path);
            $loader->parse()->putenv(true);
        }

        $env = getenv();

        self::$env = [
            'DOMAIN'                  => $env['DOMAIN'] ?? false,
            'APP_CLIENT_ID'           => $env['APP_CLIENT_ID'] ?? false,
            'APP_CLIENT_SECRET'       => $env['APP_CLIENT_SECRET'] ?? false,
            'API_TOKEN'               => $env['API_TOKEN'] ?? false,
            'ICANID_API_REQUEST_DELAY' => (int) ($env['ICANID_API_REQUEST_DELAY'] ?? 0),
        ];

        if (self::$env['ICANID_API_REQUEST_DELAY'] <= 0) {
            self::$env['ICANID_API_REQUEST_DELAY'] = 200000;
        }

        if (! isset($env['API_TOKEN']) && $env['APP_CLIENT_SECRET']) {
            $auth_api               = new Authentication( $env['DOMAIN'], $env['APP_CLIENT_ID'], $env['APP_CLIENT_SECRET'] );
            $response               = $auth_api->client_credentials( [ 'audience' => 'https://'.$env['DOMAIN'].'/api/v2/' ] );
            self::$env['API_TOKEN'] = $response['access_token'];
        }

        return self::$env;
    }

    protected static function sleep(?int $microseconds = null)
    {
        usleep($microseconds ?? self::$env['ICANID_API_REQUEST_DELAY']);
    }
}
