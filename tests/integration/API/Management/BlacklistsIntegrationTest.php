<?php
namespace ICANID\Tests\integration\API\Management;

use ICANID\SDK\API\Helpers\InformationHeaders;
use ICANID\SDK\API\Management;
use ICANID\Tests\API\ApiTests;
use GuzzleHttp\Psr7\Response;

class BlacklistsIntegrationTest extends ApiTests
{

    /**
     * @throws \ICANID\SDK\Exception\ApiException
     */
    public function testBlacklistAndGet()
    {
        $env = self::getEnv();

        if (! $env['API_TOKEN']) {
            $this->markTestSkipped( 'No client secret; integration test skipped' );
        }

        $api      = new Management($env['API_TOKEN'], $env['DOMAIN']);
        $test_jti = uniqid().uniqid().uniqid();

        $api->blacklists()->blacklist($env['APP_CLIENT_ID'], $test_jti);
        $this->sleep();

        $blacklisted = $api->blacklists()->getAll($env['APP_CLIENT_ID']);
        $this->sleep();

        $this->assertGreaterThan( 0, count( $blacklisted ) );
        $this->assertEquals( $env['APP_CLIENT_ID'], $blacklisted[0]['aud'] );

        $found = false;
        foreach ($blacklisted as $token) {
            if ($test_jti === $token['jti']) {
                $found = true;
                break;
            }
        }

        $this->assertTrue( $found );
    }
}
