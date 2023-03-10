<?php

namespace ICANID\Tests\unit\API\Management;

use ICANID\SDK\API\Helpers\InformationHeaders;
use ICANID\SDK\API\Management;
use ICANID\Tests\Traits\ErrorHelpers;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Class EmailTemplatesMockedTest
 *
 * @package ICANID\Tests\unit\API\Management
 */
class EmailTemplatesMockedTest extends TestCase
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
    public function testThatGetTemplateRequestIsFormattedProperly()
    {
        $api = new MockManagementApi( [ new Response( 200, self::$headers ) ] );
        $api->call()->emailTemplates()->get( Management\EmailTemplates::TEMPLATE_VERIFY_EMAIL );

        $this->assertEquals( 'GET', $api->getHistoryMethod() );
        $this->assertEquals( 'https://api.test.local/api/v2/email-templates/verify_email', $api->getHistoryUrl() );

        $headers = $api->getHistoryHeaders();
        $this->assertEquals( 'Bearer __api_token__', $headers['Authorization'][0] );
        $this->assertEquals( self::$expectedTelemetry, $headers['ICANID-Client'][0] );
    }

    /**
     * @throws \Exception Should not be thrown in this test.
     */
    public function testThatPatchTemplateRequestIsFormattedProperly()
    {
        $api        = new MockManagementApi( [ new Response( 200, self::$headers ) ] );
        $patch_data = [
            'body' => '__test_email_body__',
            'from' => 'test@auth0.com',
            'resultUrl' => 'https://auth0.com',
            'subject' => '__test_email_subject__',
        ];

        $api->call()->emailTemplates()->patch( Management\EmailTemplates::TEMPLATE_RESET_EMAIL, $patch_data );

        $this->assertEquals( 'PATCH', $api->getHistoryMethod() );
        $this->assertEquals( 'https://api.test.local/api/v2/email-templates/reset_email', $api->getHistoryUrl() );

        $headers = $api->getHistoryHeaders();
        $this->assertEquals( 'Bearer __api_token__', $headers['Authorization'][0] );
        $this->assertEquals( 'application/json', $headers['Content-Type'][0] );
        $this->assertEquals( self::$expectedTelemetry, $headers['ICANID-Client'][0] );

        $this->assertEquals( $patch_data, $api->getHistoryBody() );
    }

    /**
     * @throws \Exception Should not be thrown in this test.
     */
    public function testThatCreateTemplateRequestIsFormattedProperly()
    {
        $api = new MockManagementApi( [ new Response( 200, self::$headers ) ] );

        $api->call()->emailTemplates()->create(
            Management\EmailTemplates::TEMPLATE_WELCOME_EMAIL,
            true,
            'test@auth0.com',
            '__test_email_subject__',
            '__test_email_body__'
        );

        $this->assertEquals( 'POST', $api->getHistoryMethod() );
        $this->assertEquals( 'https://api.test.local/api/v2/email-templates', $api->getHistoryUrl() );

        $headers = $api->getHistoryHeaders();
        $this->assertEquals( 'Bearer __api_token__', $headers['Authorization'][0] );
        $this->assertEquals( 'application/json', $headers['Content-Type'][0] );
        $this->assertEquals( self::$expectedTelemetry, $headers['ICANID-Client'][0] );

        $body = $api->getHistoryBody();
        $this->assertArrayHasKey( 'template', $body );
        $this->assertEquals( 'welcome_email', $body['template'] );
        $this->assertArrayHasKey( 'enabled', $body );
        $this->assertEquals( true, $body['enabled'] );
        $this->assertArrayHasKey( 'from', $body );
        $this->assertEquals( 'test@auth0.com', $body['from'] );
        $this->assertArrayHasKey( 'subject', $body );
        $this->assertEquals( '__test_email_subject__', $body['subject'] );
        $this->assertArrayHasKey( 'body', $body );
        $this->assertEquals( '__test_email_body__', $body['body'] );
        $this->assertArrayHasKey( 'syntax', $body );
        $this->assertEquals( 'liquid', $body['syntax'] );
        $this->assertArrayHasKey( 'urlLifetimeInSeconds', $body );
        $this->assertEquals( 0, $body['urlLifetimeInSeconds'] );
        $this->assertArrayNotHasKey( 'resultUrl', $body );
    }
}
