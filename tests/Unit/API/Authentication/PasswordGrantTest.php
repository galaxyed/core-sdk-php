<?php
namespace ICANID\Tests\unit\API\Authentication;

use ICANID\SDK\API\Authentication;
use ICANID\SDK\API\Helpers\InformationHeaders;
use ICANID\SDK\Exception\ApiException;
use ICANID\Tests\API\ApiTests;
use ICANID\Tests\Traits\ErrorHelpers;
use GuzzleHttp\Psr7\Response;

/**
 * Class PasswordGrantTest
 * Tests the Authentication API class, specifically password grants.
 *
 * @package ICANID\Tests\unit\API\Authentication
 */
class PasswordGrantTest extends ApiTests
{

    use ErrorHelpers;

    /**
     * Expected telemetry value.
     *
     * @var string
     */
    protected static $expectedTelemetry;

    /**
     * Runs before test suite starts.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $infoHeadersData = new InformationHeaders;
        $infoHeadersData->setCorePackage();
        self::$expectedTelemetry = $infoHeadersData->build();
    }

    public function testThatPasswordGrantLoginEnforcesUsername()
    {
        $api = new Authentication( 'test-domain.auth0.com', '__test_client_id__' );

        try {
            $caught_exception = false;
            $api->login_with_default_directory( [] );
        } catch (ApiException $e) {
            $caught_exception = $this->errorHasString( $e, 'username is mandatory' );
        }

        $this->assertTrue( $caught_exception );

        try {
            $caught_exception = false;
            $api->login( [] );
        } catch (ApiException $e) {
            $caught_exception = $this->errorHasString( $e, 'username is mandatory' );
        }

        $this->assertTrue( $caught_exception );
    }

    public function testThatPasswordGrantLoginEnforcesPassword()
    {
        $api = new Authentication( 'test-domain.auth0.com', '__test_client_id__' );

        try {
            $caught_exception = false;
            $api->login_with_default_directory( [ 'username' => uniqid() ]);
        } catch (ApiException $e) {
            $caught_exception = $this->errorHasString( $e, 'password is mandatory' );
        }

        $this->assertTrue( $caught_exception );

        try {
            $caught_exception = false;
            $api->login( [ 'username' => uniqid() ]);
        } catch (ApiException $e) {
            $caught_exception = $this->errorHasString( $e, 'password is mandatory' );
        }

        $this->assertTrue( $caught_exception );
    }

    public function testThatPasswordGrantRealmLoginEnforcesRealm()
    {
        $api = new Authentication( 'test-domain.auth0.com', '__test_client_id__' );

        try {
            $caught_exception = false;
            $api->login( [ 'username' => uniqid(), 'password' => uniqid() ]);
        } catch (ApiException $e) {
            $caught_exception = $this->errorHasString( $e, 'realm is mandatory' );
        }

        $this->assertTrue( $caught_exception );
    }

    /**
     * Test that a basic password grant request includes the correct URL, body, and headers.
     *
     * @throws ApiException If username or password is missing.
     */
    public function testThatPasswordGrantLoginSendsBasicRequestCorrectly()
    {
        $api = new MockAuthenticationApi( [
            new Response( 200, [ 'Content-Type' => 'application/json' ], '{}' )
        ] );

        $api->call()->login_with_default_directory( [
            'username' => 'the_username',
            'password' => 'the_password',
        ] );

        $this->assertEquals( 'https://test-domain.auth0.com/oauth2/token', $api->getHistoryUrl() );

        $request_headers = $api->getHistoryHeaders();
        $this->assertArrayHasKey( 'ICANID-Client', $request_headers );
        $this->assertEquals( self::$expectedTelemetry, $request_headers['ICANID-Client'][0] );
        $this->assertArrayHasKey( 'Content-Type', $request_headers );
        $this->assertEquals( 'application/json', $request_headers['Content-Type'][0] );

        $request_body = $api->getHistoryBody();
        $this->assertEquals( 'the_username', $request_body['username'] );
        $this->assertEquals( 'the_password', $request_body['password'] );
        $this->assertEquals( 'password', $request_body['grant_type'] );
        $this->assertEquals( '__test_client_id__', $request_body['client_id'] );
        $this->assertEquals( '__test_client_secret__', $request_body['client_secret'] );
    }

    /**
     * Test that a basic password grant realm request includes the realm.
     *
     * @throws ApiException If username or password is missing.
     */
    public function testThatPasswordGrantRealmLoginSendsBasicRequestCorrectly()
    {
        $api = new MockAuthenticationApi( [
            new Response( 200, [ 'Content-Type' => 'application/json' ], '{}' )
        ] );

        $api->call()->login( [
            'username' => 'the_username',
            'password' => 'the_password',
            'realm'    => 'the_realm',
        ] );

        $this->assertEquals( 'https://test-domain.auth0.com/oauth2/token', $api->getHistoryUrl() );

        $request_body = $api->getHistoryBody();
        $this->assertEquals( 'the_realm', $request_body['realm'] );
        $this->assertEquals( 'http://auth0.com/oauth/grant-type/password-realm', $request_body['grant_type'] );
    }

    /**
     * Test that a password grant request including an IP address sets the correct header.
     *
     * @throws ApiException If username or password is missing.
     */
    public function testThatPasswordGrantLoginSetsForwardedForHeader()
    {
        $api = new MockAuthenticationApi( [
            new Response( 200, [ 'Content-Type' => 'application/json' ], '{}' ),
            new Response( 200, [ 'Content-Type' => 'application/json' ], '{}' ),
        ] );

        $api->call()->login_with_default_directory(
            [
                'username' => uniqid(),
                'password' => uniqid(),
            ],
            '1.2.3.4'
        );

        $request_headers = $api->getHistoryHeaders();
        $this->assertArrayHasKey( 'ICANID-Forwarded-For', $request_headers );
        $this->assertEquals( '1.2.3.4', $request_headers['ICANID-Forwarded-For'][0] );

        $api->call()->login_with_default_directory( [
            'username' => uniqid(),
            'password' => uniqid(),
            'icanid_forwarded_for' => '1.2.3.4'
        ] );

        $request_headers = $api->getHistoryHeaders();
        $this->assertArrayHasKey( 'ICANID-Forwarded-For', $request_headers );
        $this->assertEquals( '1.2.3.4', $request_headers['ICANID-Forwarded-For'][0] );
    }

    /**
     * Test that a password grant request including an IP address sets the correct header.
     *
     * @throws ApiException If username or password is missing.
     */
    public function testThatPasswordGrantRealmLoginSetsForwardedForHeader()
    {
        $api = new MockAuthenticationApi( [
            new Response( 200, [ 'Content-Type' => 'application/json' ], '{}' ),
            new Response( 200, [ 'Content-Type' => 'application/json' ], '{}' ),
        ] );

        $api->call()->login(
            [
                'username' => uniqid(),
                'password' => uniqid(),
                'realm' => uniqid(),
            ],
            '5.6.7.8'
        );

        $request_headers = $api->getHistoryHeaders();
        $this->assertArrayHasKey( 'ICANID-Forwarded-For', $request_headers );
        $this->assertEquals( '5.6.7.8', $request_headers['ICANID-Forwarded-For'][0] );

        $api->call()->login( [
            'username' => uniqid(),
            'password' => uniqid(),
            'realm' => uniqid(),
            'icanid_forwarded_for' => '5.6.7.8'
        ] );

        $request_headers = $api->getHistoryHeaders();
        $this->assertArrayHasKey( 'ICANID-Forwarded-For', $request_headers );
        $this->assertEquals( '5.6.7.8', $request_headers['ICANID-Forwarded-For'][0] );
    }
}
