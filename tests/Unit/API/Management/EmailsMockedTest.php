<?php

namespace ICANID\Tests\unit\API\Management;

use ICANID\SDK\API\Helpers\InformationHeaders;
use ICANID\Tests\Traits\ErrorHelpers;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Class EmailsMockedTest
 *
 * @package ICANID\Tests\unit\API\Management
 */
class EmailsMockedTest extends TestCase
{

    use ErrorHelpers;

    /**
     * Expected telemetry value.
     *
     * @var string
     */
    protected static $expectedTelemetry;

    /**
     * Default request headers.
     *
     * @var array
     */
    protected static $headers = [ 'content-type' => 'json' ];

    /**
     * Runs before test suite starts.
     */
    public static function setUpBeforeClass(): void
    {
        $infoHeadersData = new InformationHeaders;
        $infoHeadersData->setCorePackage();
        self::$expectedTelemetry = $infoHeadersData->build();
    }

    /**
     * @throws \Exception Should not be thrown in this test.
     */
    public function testThatGetEmailProviderRequestIsFormattedProperly()
    {
        $api = new MockManagementApi( [ new Response( 200, self::$headers ) ] );
        $api->call()->emails()->getEmailProvider();

        $this->assertEquals( 'GET', $api->getHistoryMethod() );
        $this->assertEquals( 'https://api.test.local/api/v2/emails/provider', $api->getHistoryUrl() );

        $headers = $api->getHistoryHeaders();
        $this->assertEquals( 'Bearer __api_token__', $headers['Authorization'][0] );
        $this->assertEquals( self::$expectedTelemetry, $headers['ICANID-Client'][0] );
    }

    /**
     * @throws \Exception Should not be thrown in this test.
     */
    public function testThatGetEmailProviderRequestAddsFieldsParams()
    {
        $api = new MockManagementApi( [ new Response( 200, self::$headers ) ] );
        $api->call()->emails()->getEmailProvider( [ 'name', 'credentials' ], true );

        $this->assertEquals( 'GET', $api->getHistoryMethod() );
        $this->assertStringStartsWith( 'https://api.test.local/api/v2/emails/provider', $api->getHistoryUrl() );

        $params = $api->getHistoryQuery();
        $this->assertStringContainsString( 'fields=name,credentials', $params );
        $this->assertStringContainsString( 'include_fields=true', $params );
    }
}
