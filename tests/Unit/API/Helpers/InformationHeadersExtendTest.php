<?php
namespace ICANID\Tests\unit\API\Helpers;

use ICANID\SDK\API\Helpers\ApiClient;
use ICANID\SDK\API\Helpers\InformationHeaders;
use ICANID\Tests\unit\API\Authentication\MockAuthenticationApi;
use ICANID\Tests\unit\API\Management\MockManagementApi;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Class InformationHeadersExtendTest
 *
 * @package ICANID\Tests\unit\Api\Helpers
 */
class InformationHeadersExtendTest extends TestCase
{

    public static function tearDownAfterClass(): void
    {
        $reset_headers = new InformationHeaders;
        $reset_headers->setCorePackage();
        ApiClient::setInfoHeadersData($reset_headers);
        parent::tearDownAfterClass();
    }

    /**
     * Test that extending the headers works for Management API calls.
     *
     * @throws \Exception Unsuccessful HTTP call or empty mock history queue.
     */
    public function testThatExtendedHeadersAreUsedForManagementApiCalls()
    {
        $new_headers = self::setExtendedHeaders('test-extend-sdk-2', '2.3.4');

        $api = new MockManagementApi( [ new Response( 200 ) ] );
        $api->call()->connections()->getAll();
        $headers = $api->getHistoryHeaders();

        $this->assertEquals( $new_headers->build(), $headers['ICANID-Client'][0] );
    }

    /**
     * Test that extending the headers works for Management API calls.
     *
     * @throws \Exception Unsuccessful HTTP call or empty mock history queue.
     */
    public function testThatExtendedHeadersAreUsedForAuthenticationApiCalls()
    {
        $new_headers = self::setExtendedHeaders('test-extend-sdk-3', '3.4.5');

        $api = new MockAuthenticationApi( [
            new Response( 200, [ 'Content-Type' => 'application/json' ], '{}' )
        ] );

        $api->call()->oauth_token( [ 'grant_type' => uniqid() ] );
        $headers = $api->getHistoryHeaders();

        $this->assertEquals( $new_headers->build(), $headers['ICANID-Client'][0] );
    }

    /*
     * Test helper methods.
     */

    /**
     * Reset and extend telemetry headers.
     *
     * @param string $name New SDK name.
     * @param string $version New SDK version.
     *
     * @return InformationHeaders
     */
    public static function setExtendedHeaders($name, $version)
    {
        $reset_headers = new InformationHeaders;
        $reset_headers->setCorePackage();
        ApiClient::setInfoHeadersData($reset_headers);

        $headers     = ApiClient::getInfoHeadersData();
        $new_headers = InformationHeaders::Extend($headers);
        $new_headers->setPackage($name, $version);
        ApiClient::setInfoHeadersData($new_headers);
        return $new_headers;
    }
}
