<?php
namespace ICANID\Tests\unit\API\Management;

use ICANID\SDK\API\Helpers\InformationHeaders;
use ICANID\Tests\API\ApiTests;
use ICANID\Tests\Traits\ErrorHelpers;
use GuzzleHttp\Psr7\Response;

/**
 * Class GuardianTest.
 *
 * @package ICANID\Tests\unit\API\Management
 */
class GuardianTest extends ApiTests
{

    use ErrorHelpers;

    /**
     * Expected telemetry value.
     *
     * @var string
     */
    protected static $telemetry;

    /**
     * Runs before test suite starts.
     */
    public static function setUpBeforeClass(): void
    {
        $infoHeadersData = new InformationHeaders;
        $infoHeadersData->setCorePackage();
        self::$telemetry = $infoHeadersData->build();
    }

    /**
     * Test that getFactors requests properly.
     *
     * @return void
     *
     * @throws \Exception Should not be thrown in this test.
     */
    public function testGuardianGetFactor()
    {
        $api = new MockManagementApi( [ new Response( 200 ) ] );

        $api->call()->guardian()->getFactors();

        $this->assertEquals( 'GET', $api->getHistoryMethod() );
        $this->assertEquals( 'https://api.test.local/api/v2/guardian/factors', $api->getHistoryUrl() );
        $this->assertEmpty( $api->getHistoryQuery() );

        $headers = $api->getHistoryHeaders();
        $this->assertEquals( 'Bearer __api_token__', $headers['Authorization'][0] );
        $this->assertEquals( self::$telemetry, $headers['ICANID-Client'][0] );
    }

    /**
     * Test that getEnrollment requests properly.
     *
     * @return void
     *
     * @throws \Exception Should not be thrown in this test.
     */
    public function testGuardianGetEnrollment()
    {
        $api = new MockManagementApi( [ new Response( 200 ) ] );

        $api->call()->guardian()->getEnrollment('__test_factor_id__');

        $this->assertEquals( 'GET', $api->getHistoryMethod() );
        $this->assertEquals(
            'https://api.test.local/api/v2/guardian/enrollments/__test_factor_id__',
            $api->getHistoryUrl()
        );
        $this->assertEmpty( $api->getHistoryQuery() );

        $headers = $api->getHistoryHeaders();
        $this->assertEquals( 'Bearer __api_token__', $headers['Authorization'][0] );
        $this->assertEquals( self::$telemetry, $headers['ICANID-Client'][0] );
    }

    /**
     * Test that deleteEnrollment requests properly.
     *
     * @return void
     *
     * @throws \Exception Should not be thrown in this test.
     */
    public function testGuardianDeleteEnrollment()
    {
        $api = new MockManagementApi( [ new Response( 200 ) ] );

        $api->call()->guardian()->deleteEnrollment('__test_factor_id__');

        $this->assertEquals( 'DELETE', $api->getHistoryMethod() );
        $this->assertEquals(
            'https://api.test.local/api/v2/guardian/enrollments/__test_factor_id__',
            $api->getHistoryUrl()
        );
        $this->assertEmpty( $api->getHistoryQuery() );

        $headers = $api->getHistoryHeaders();
        $this->assertEquals( 'Bearer __api_token__', $headers['Authorization'][0] );
        $this->assertEquals( self::$telemetry, $headers['ICANID-Client'][0] );
    }

}
