<?php
namespace ICANID\Tests\unit;

use ICANID\SDK\ICANID;
use ICANID\SDK\Exception\ApiException;
use ICANID\SDK\Exception\CoreException;
use ICANID\SDK\Exception\InvalidTokenException;
use ICANID\SDK\Store\SessionStore;
use ICANID\Tests\unit\Helpers\Tokens\AsymmetricVerifierTest;
use ICANID\Tests\unit\Helpers\Tokens\SymmetricVerifierTest;
use ICANID\Tests\Traits\ErrorHelpers;
use Cache\Adapter\PHPArray\ArrayCachePool;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Class ICANIDTest
 *
 * @package ICANID\Tests\unit
 */
class ICANIDTest extends TestCase
{

    use ErrorHelpers;

    /**
     * Basic ICANID class config options.
     *
     * @var array
     */
    public static $baseConfig;

    /**
     * Default request headers.
     *
     * @var array
     */
    protected static $headers = [ 'content-type' => 'json' ];

    /**
     * Runs after each test completes.
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$baseConfig = [
            'domain'        => '__test_domain__',
            'client_id'     => '__test_client_id__',
            'client_secret' => '__test_client_secret__',
            'redirect_uri'  => '__test_redirect_uri__',
            'store' => false,
            'transient_store' => new SessionStore(),
        ];

        if (! session_id()) {
            session_start();
        }
    }

    /**
     * Runs after each test completes.
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $_GET     = [];
        $_SESSION = [];
    }

    /**
     * Test that the exchange call returns false before making any HTTP calls if no code is present.
     *
     * @throws ApiException
     * @throws CoreException
     */
    public function testThatExchangeReturnsFalseIfNoCodePresent()
    {
        $icanid = new ICANID( self::$baseConfig );
        $this->assertFalse( $icanid->exchange() );
    }

    /**
     * Test that the exchanges fails when there is not a stored nonce value.
     *
     * @throws ApiException
     * @throws CoreException
     */
    public function testThatExchangeFailsWithNoStoredNonce()
    {
        $id_token      = self::getIdToken();
        $response_body = '{"access_token":"1.2.3","id_token":"'.$id_token.'","refresh_token":"4.5.6"}';

        $mock = new MockHandler( [ new Response( 200, self::$headers, $response_body ) ] );

        $add_config               = [
            'skip_userinfo' => true,
            'id_token_alg' => 'HS256',
            'guzzle_options' => [
                'handler' => HandlerStack::create($mock)
            ]
        ];
        $icanid                    = new ICANID( self::$baseConfig + $add_config );
        $_GET['code']             = uniqid();
        $_GET['state']            = '__test_state__';
        $_SESSION['icanid__state'] = '__test_state__';

        $this->expectExceptionMessage('Nonce value not found in application store');
        $icanid->exchange();
    }

    /**
     * Test that the exchanges fails when there is not a stored nonce value.
     *
     * @throws ApiException
     * @throws CoreException
     */
    public function testThatExchangeFailsWhenPkceIsEnabledAndNoCodeVerifierWasFound()
    {
        $add_config               = [
            'enable_pkce' => true,
        ];
        $icanid                    = new ICANID( self::$baseConfig + $add_config );

        $_GET['code']             = uniqid();
        $_GET['state']            = '__test_state__';
        $_SESSION['icanid__state'] = '__test_state__';
        $_SESSION['icanid__code_verifier'] = null;

        $this->expectException(CoreException::class);
        $this->expectExceptionMessage('Missing code_verifier');
        $icanid->exchange();
    }

    /**
     * Test that the exchanges succeeds when there is both and access token and an ID token present.
     *
     * @throws ApiException
     * @throws CoreException
     */
    public function testThatExchangeSucceedsWithIdToken()
    {
        $id_token      = self::getIdToken();
        $response_body = '{"access_token":"1.2.3","id_token":"'.$id_token.'","refresh_token":"4.5.6"}';

        $mock = new MockHandler( [
            // Code exchange response.
            new Response( 200, self::$headers, $response_body ),
            // Userinfo response.
            new Response( 200, self::$headers, json_encode( ['sub' => '__test_sub__'] ) ),
        ] );

        $add_config               = [
            'skip_userinfo' => false,
            'id_token_alg' => 'HS256',
            'guzzle_options' => [
                'handler' => HandlerStack::create($mock)
            ]
        ];
        $icanid                    = new ICANID( self::$baseConfig + $add_config );
        $_GET['code']             = uniqid();
        $_SESSION['icanid__nonce'] = '__test_nonce__';
        $_GET['state']            = '__test_state__';
        $_SESSION['icanid__state'] = '__test_state__';

        $this->assertTrue( $icanid->exchange() );
        $this->assertEquals( ['sub' => '__test_sub__'], $icanid->getUser() );
        $this->assertEquals( $id_token, $icanid->getIdToken() );
        $this->assertEquals( '1.2.3', $icanid->getAccessToken() );
        $this->assertEquals( '4.5.6', $icanid->getRefreshToken() );
    }

    /**
     * Test that the exchanges succeeds when there is only an access token.
     *
     * @throws ApiException
     * @throws CoreException
     */
    public function testThatExchangeSucceedsWithNoIdToken()
    {
        $mock = new MockHandler( [
            // Code exchange response.
            // Respond with no ID token, access token with correct number of segments.
            new Response( 200, self::$headers, '{"access_token":"1.2.3","refresh_token":"4.5.6"}' ),
            // Userinfo response.
            new Response( 200, self::$headers, '{"sub":"123"}' ),
        ] );

        $add_config               = [
            'skip_userinfo' => false,
            'scope' => 'offline_access read:messages',
            'audience' => 'https://api.identifier',
            'guzzle_options' => [ 'handler' => HandlerStack::create($mock) ]
        ];
        $icanid                    = new ICANID( self::$baseConfig + $add_config );
        $_GET['code']             = uniqid();
        $_GET['state']            = '__test_state__';
        $_SESSION['icanid__state'] = '__test_state__';

        $this->assertTrue( $icanid->exchange() );
        $this->assertEquals( ['sub' => '123'], $icanid->getUser() );
        $this->assertEquals( '1.2.3', $icanid->getAccessToken() );
        $this->assertEquals( '4.5.6', $icanid->getRefreshToken() );
    }

    /**
     * Test that the exchanges succeeds when PKCE is enabled.
     *
     * @throws ApiException
     * @throws CoreException
     */
    public function testThatExchangeSucceedsWithPkceEnabled()
    {
        $mock = new MockHandler( [
            // Code exchange response.
            // Respond with no ID token, access token with correct number of segments.
            new Response( 200, self::$headers, '{"access_token":"1.2.3","refresh_token":"4.5.6"}' ),
            // Userinfo response.
            new Response( 200, self::$headers, '{"sub":"__test_sub__"}' ),
        ] );

        $add_config                       = [
            'skip_userinfo' => false,
            'enable_pkce' => true,
            'guzzle_options' => [
                'handler' => HandlerStack::create($mock)
            ]
        ];
        $icanid                            = new ICANID( self::$baseConfig + $add_config );
        $_GET['code']                     = uniqid();
        $_SESSION['icanid__nonce']         = '__test_nonce__';
        $_GET['state']                    = '__test_state__';
        $_SESSION['icanid__state']         = '__test_state__';
        $_SESSION['icanid__code_verifier'] = '__test_code_verifier__';

        $this->assertTrue( $icanid->exchange() );
        $this->assertEquals( ['sub' => '__test_sub__'], $icanid->getUser() );
        $this->assertEquals( '1.2.3', $icanid->getAccessToken() );
        $this->assertEquals( '4.5.6', $icanid->getRefreshToken() );
    }

    /**
     * Test that the exchanges succeeds when PKCE is disabled.
     *
     * @throws ApiException
     * @throws CoreException
     */
    public function testThatExchangeSucceedsWithoutPkceEnabled()
    {
        $mock = new MockHandler( [
            // Code exchange response.
            // Respond with no ID token, access token with correct number of segments.
            new Response( 200, self::$headers, '{"access_token":"1.2.3","refresh_token":"4.5.6"}' ),
            // Userinfo response.
            new Response( 200, self::$headers, '{"sub":"__test_sub__"}' ),
        ] );

        $add_config                       = [
            'skip_userinfo' => false,
            'enable_pkce' => false,
            'guzzle_options' => [
                'handler' => HandlerStack::create($mock)
            ]
        ];
        $icanid                            = new ICANID( self::$baseConfig + $add_config );
        $_GET['code']                     = uniqid();
        $_SESSION['icanid__nonce']         = '__test_nonce__';
        $_GET['state']                    = '__test_state__';
        $_SESSION['icanid__state']         = '__test_state__';

        $this->assertTrue( $icanid->exchange() );
        $this->assertEquals( ['sub' => '__test_sub__'], $icanid->getUser() );
        $this->assertEquals( '1.2.3', $icanid->getAccessToken() );
        $this->assertEquals( '4.5.6', $icanid->getRefreshToken() );
    }

    /**
     * Test that the skip_userinfo config option uses the ID token instead of calling /userinfo.
     *
     * @throws ApiException
     * @throws CoreException
     */
    public function testThatExchangeSkipsUserinfo()
    {
        $id_token = self::getIdToken();

        $mock = new MockHandler( [
            // Code exchange response.
            new Response( 200, self::$headers, '{"access_token":"1.2.3","id_token":"'.$id_token.'"}' ),
        ] );

        $add_config               = [
            'scope' => 'openid',
            'skip_userinfo' => true,
            'id_token_alg' => 'HS256',
            'guzzle_options' => [ 'handler' => HandlerStack::create($mock) ]
        ];
        $icanid                    = new ICANID( self::$baseConfig + $add_config );
        $_GET['code']             = uniqid();
        $_SESSION['icanid__nonce'] = '__test_nonce__';
        $_GET['state']            = '__test_state__';
        $_SESSION['icanid__state'] = '__test_state__';

        $this->assertTrue( $icanid->exchange() );

        $this->assertEquals( '__test_sub__', $icanid->getUser()['sub'] );
        $this->assertEquals( $id_token, $icanid->getIdToken() );
        $this->assertEquals( '1.2.3', $icanid->getAccessToken() );
    }

    /**
     * Test that renewTokens fails if there is no refresh_token stored.
     *
     * @throws ApiException Should not be thrown in this test.
     * @throws CoreException Should not be thrown in this test.
     */
    public function testThatRenewTokensFailsIfThereIsNoRefreshToken()
    {
        $mock = new MockHandler( [
            // Code exchange response.
            new Response( 200, self::$headers, '{"access_token":"1.2.3"}' ),
        ] );

        $add_config = [
            'skip_userinfo' => true,
            'persist_access_token' => true,
            'guzzle_options' => [ 'handler' => HandlerStack::create($mock) ]
        ];
        $icanid      = new ICANID( self::$baseConfig + $add_config );

        $_GET['code']             = uniqid();
        $_GET['state']            = '__test_state__';
        $_SESSION['icanid__state'] = '__test_state__';

        $this->assertTrue( $icanid->exchange() );

        try {
            $caught_exception = false;
            $icanid->renewTokens();
        } catch (CoreException $e) {
            $caught_exception = $this->errorHasString(
                $e,
                "Can't renew the access token if there isn't a refresh token available" );
        }

        $this->assertTrue( $caught_exception );
    }

    /**
     * Test that renewTokens fails if the API response is invalid.
     *
     * @throws ApiException Should not be thrown in this test.
     * @throws CoreException Should not be thrown in this test.
     */
    public function testThatRenewTokensFailsIfNoAccessTokenReturned()
    {
        $mock = new MockHandler( [
            // Code exchange response.
            new Response( 200, self::$headers, '{"access_token":"1.2.3","refresh_token":"2.3.4"}' ),
            // Refresh token response without access token.
            new Response( 200, self::$headers, '{}' ),
        ] );

        $add_config = [
            'skip_userinfo' => true,
            'persist_access_token' => true,
            'guzzle_options' => [ 'handler' => HandlerStack::create($mock) ]
        ];
        $icanid      = new ICANID( self::$baseConfig + $add_config );

        $_GET['code']             = uniqid();
        $_GET['state']            = '__test_state__';
        $_SESSION['icanid__state'] = '__test_state__';

        $this->assertTrue( $icanid->exchange() );

        $this->expectExceptionMessage('Token did not refresh correctly. Access token not returned.');
        $icanid->renewTokens();
    }

    /**
     * Test that renewTokens succeeds with non-empty access_token and refresh_token stored.
     *
     * @throws ApiException Should not be thrown in this test.
     * @throws CoreException Should not be thrown in this test.
     */
    public function testThatRenewTokensSucceeds()
    {
        $id_token = self::getIdToken();
        $request_history = [];

        $mock = new MockHandler( [
            // Code exchange response.
            new Response( 200, self::$headers, '{"access_token":"1.2.3","refresh_token":"2.3.4","id_token":"'.$id_token.'"}' ),
            // Refresh token response.
            new Response( 200, self::$headers, '{"access_token":"__test_access_token__","id_token":"'.$id_token.'"}' ),
        ] );
        $handler         = HandlerStack::create($mock);
        $handler->push( Middleware::history($request_history) );

        $add_config = [
            'id_token_alg' => 'HS256',
            'skip_userinfo' => true,
            'persist_access_token' => true,
            'guzzle_options' => [ 'handler' => $handler ]
        ];
        $icanid      = new ICANID( self::$baseConfig + $add_config );

        $_GET['code']             = uniqid();
        $_SESSION['icanid__nonce'] = '__test_nonce__';
        $_GET['state']            = '__test_state__';
        $_SESSION['icanid__state'] = '__test_state__';

        $this->assertTrue( $icanid->exchange() );

        $this->assertArrayNotHasKey('icanid__nonce', $_SESSION);
        $this->assertArrayNotHasKey('icanid__state', $_SESSION);

        $icanid->renewTokens(['scope' => 'openid']);

        $this->assertEquals( '__test_access_token__', $icanid->getAccessToken() );
        $this->assertEquals( $id_token, $icanid->getIdToken() );

        $renew_request = $request_history[1]['request'];
        $renew_body    = json_decode($renew_request->getBody(), true);
        $this->assertEquals( 'openid', $renew_body['scope'] );
        $this->assertEquals( '__test_client_secret__', $renew_body['client_secret'] );
        $this->assertEquals( '__test_client_id__', $renew_body['client_id'] );
        $this->assertEquals( '2.3.4', $renew_body['refresh_token'] );
        $this->assertEquals( 'https://__test_domain__/oauth2/token', (string) $renew_request->getUri() );
    }

    public function testThatGetLoginUrlUsesDefaultValues()
    {
        $icanid = new ICANID( self::$baseConfig );

        $parsed_url = parse_url( $icanid->getLoginUrl() );

        $this->assertEquals( 'https', $parsed_url['scheme'] );
        $this->assertEquals( '__test_domain__', $parsed_url['host'] );
        $this->assertEquals( '/authorize', $parsed_url['path'] );

        $url_query = explode( '&', $parsed_url['query'] );

        $this->assertContains( 'scope=openid%20offline', $url_query );
        $this->assertContains( 'response_type=code', $url_query );
        $this->assertContains( 'redirect_uri=__test_redirect_uri__', $url_query );
        $this->assertContains( 'client_id=__test_client_id__', $url_query );
    }

    public function testThatGetLoginUrlAddsValues()
    {
        $icanid = new ICANID( self::$baseConfig );

        $custom_params = [
            'connection' => '__test_connection__',
            'prompt' => 'none',
            'audience' => '__test_audience__',
            'state' => '__test_state__',
            'invitation' => '__test_invitation__'
        ];

        $auth_url         = $icanid->getLoginUrl( $custom_params );
        $parsed_url_query = parse_url( $auth_url, PHP_URL_QUERY );
        $url_query        = explode( '&', $parsed_url_query );

        $this->assertContains( 'redirect_uri=__test_redirect_uri__', $url_query );
        $this->assertContains( 'client_id=__test_client_id__', $url_query );
        $this->assertContains( 'connection=__test_connection__', $url_query );
        $this->assertContains( 'prompt=none', $url_query );
        $this->assertContains( 'audience=__test_audience__', $url_query );
        $this->assertContains( 'state=__test_state__', $url_query );
        $this->assertContains( 'invitation=__test_invitation__', $url_query );
    }

    public function testThatGetLoginUrlOverridesDefaultValues()
    {
        $icanid = new ICANID( self::$baseConfig );

        $override_params = [
            'scope' => 'openid offline',
            'response_type' => 'id_token',
            'response_mode' => 'form_post',
        ];

        $auth_url         = $icanid->getLoginUrl( $override_params );
        $parsed_url_query = parse_url( $auth_url, PHP_URL_QUERY );
        $url_query        = explode( '&', $parsed_url_query );

        $this->assertContains( 'scope=openid%20offline', $url_query );
        $this->assertContains( 'response_type=id_token', $url_query );
        $this->assertContains( 'response_mode=form_post', $url_query );
        $this->assertContains( 'redirect_uri=__test_redirect_uri__', $url_query );
        $this->assertContains( 'client_id=__test_client_id__', $url_query );
    }

    public function testThatGetLoginUrlGeneratesStateAndNonce()
    {
        $custom_config = self::$baseConfig;

        $icanid = new ICANID( $custom_config );

        $auth_url = $icanid->getLoginUrl();

        $parsed_url_query = parse_url( $auth_url, PHP_URL_QUERY );
        $url_query        = explode( '&', $parsed_url_query );

        $this->assertArrayHasKey( 'icanid__state', $_SESSION );
        $this->assertContains( 'state='.$_SESSION['icanid__state'], $url_query );
        $this->assertArrayHasKey( 'icanid__nonce', $_SESSION );
        $this->assertContains( 'nonce='.$_SESSION['icanid__nonce'], $url_query );
    }

    public function testThatGetLoginUrlGeneratesChallengeAndChallengeMethodWhenPkceIsEnabled()
    {
        $add_config = [
            'enable_pkce' =>  true,
        ];
        $icanid = new ICANID( self::$baseConfig + $add_config );

        $auth_url = $icanid->getLoginUrl();

        $parsed_url_query = parse_url( $auth_url, PHP_URL_QUERY );
        $url_query        = explode( '&', $parsed_url_query );

        $this->assertArrayHasKey( 'icanid__code_verifier', $_SESSION );
        $this->assertStringContainsString( 'code_challenge=', $parsed_url_query );
        $this->assertContains( 'code_challenge_method=S256', $url_query );
    }

    public function testThatInvitationParametersAreExtracted()
    {
        $icanid = new ICANID( self::$baseConfig );

        $_GET['invitation'] = '__test_invitation__';
        $_GET['organization'] = '__test_organization__';
        $_GET['organization_name'] = '__test_organization_name__';

        $extracted = $icanid->getInvitationParameters();

        $this->assertIsObject($extracted, 'Invitation parameters were not extracted from the $_GET (environment variable seeded with query parameters during a GET request) successfully.');

        $this->assertObjectHasAttribute('invitation', $extracted);
        $this->assertObjectHasAttribute('organization', $extracted);
        $this->assertObjectHasAttribute('organizationName', $extracted);

        $this->assertEquals($extracted->invitation, '__test_invitation__');
        $this->assertEquals($extracted->organization, '__test_organization__');
        $this->assertEquals($extracted->organizationName, '__test_organization_name__');
    }

    public function testThatInvitationParametersArentExtractedWhenIncomplete()
    {
        $icanid = new ICANID( self::$baseConfig );

        $_GET['invitation'] = '__test_invitation__';

        $extracted = $icanid->getInvitationParameters();

        $this->assertIsNotObject($extracted);
    }

    /**
     * @throws ApiException Should not be thrown in this test.
     * @throws CoreException Should not be thrown in this test.
     */
    public function testThatClientSecretIsNotDecodedByDefault()
    {
        $request_history = [];
        $mock            = new MockHandler([
            new Response(200, [ 'Content-Type' => 'json' ], '{"access_token":"1.2.3"}'),
        ]);
        $handler         = HandlerStack::create($mock);
        $handler->push( Middleware::history($request_history) );

        $custom_config = array_merge(self::$baseConfig, [
            'skip_userinfo' => true,
            'guzzle_options' => [
                'handler' => $handler,
            ]
        ]);
        $icanid         = new ICANID( $custom_config );

        $_GET['code']             = uniqid();
        $_GET['state']            = '__test_state__';
        $_SESSION['icanid__state'] = '__test_state__';

        $icanid->exchange();

        $request_body = $request_history[0]['request']->getBody()->getContents();
        $request_body = json_decode($request_body, true);

        $this->assertArrayHasKey( 'client_secret', $request_body );
        $this->assertEquals( '__test_client_secret__', $request_body['client_secret'] );
    }

    /**
     * @throws ApiException Should not be thrown in this test.
     * @throws CoreException Should not be thrown in this test.
     */
    public function testThatClientSecretIsDecodedBeforeSending()
    {
        $request_history = [];
        $mock            = new MockHandler([
            new Response(200, [ 'Content-Type' => 'json' ], '{"access_token":"1.2.3"}'),
        ]);
        $handler         = HandlerStack::create($mock);
        $handler->push( Middleware::history($request_history) );

        $_GET['code']  = uniqid();
        $custom_config = array_merge(self::$baseConfig, [
            'client_secret' => base64_encode( '__test_encoded_client_secret__' ),
            'secret_base64_encoded' => true,
            'skip_userinfo' => true,
            'guzzle_options' => [
                'handler' => $handler,
            ]
        ]);

        $icanid = new ICANID( $custom_config );

        $_GET['state']            = '__test_state__';
        $_SESSION['icanid__state'] = '__test_state__';

        $icanid->exchange();

        $request_body = $request_history[0]['request']->getBody()->getContents();
        $request_body = json_decode($request_body, true);

        $this->assertArrayHasKey( 'client_secret', $request_body );
        $this->assertEquals( '__test_encoded_client_secret__', $request_body['client_secret'] );
    }

    public function testThatMaxAgeIsSetInLoginUrlFromInitialConfig()
    {
        $custom_config            = self::$baseConfig;
        $custom_config['max_age'] = 1000;
        $icanid                    = new ICANID( $custom_config );

        $auth_url = $icanid->getLoginUrl();

        $parsed_url_query = parse_url( $auth_url, PHP_URL_QUERY );
        $url_query        = explode( '&', $parsed_url_query );

        $this->assertContains( 'max_age=1000', $url_query );
        $this->assertArrayHasKey( 'icanid__max_age', $_SESSION );
        $this->assertEquals( 1000, $_SESSION['icanid__max_age'] );
    }

    public function testThatMaxAgeIsOverriddenInLoginUrl()
    {
        $custom_config            = self::$baseConfig;
        $custom_config['max_age'] = 1000;
        $icanid                    = new ICANID( $custom_config );

        $auth_url = $icanid->getLoginUrl(['max_age' => 1001]);

        $parsed_url_query = parse_url( $auth_url, PHP_URL_QUERY );
        $url_query        = explode( '&', $parsed_url_query );

        $this->assertContains( 'max_age=1001', $url_query );
        $this->assertArrayHasKey( 'icanid__max_age', $_SESSION );
        $this->assertEquals( 1001, $_SESSION['icanid__max_age'] );
    }

    /**
     * @throws ApiException
     * @throws CoreException
     * @throws InvalidTokenException
     */
    public function testThatIdTokenIsPersistedWhenSet()
    {
        $custom_config = array_merge( self::$baseConfig, [
            'id_token_alg' => 'HS256',
            'persist_id_token' => true,
            'store' => new SessionStore(),
        ]);

        $icanid    = new ICANID( $custom_config );
        $id_token = self::getIdToken();

        $_SESSION['icanid__nonce']   = '__test_nonce__';
        $_SESSION['icanid__max_age'] = 1000;
        $icanid->setIdToken( $id_token );

        $this->assertEquals($id_token, $icanid->getIdToken());
        $this->assertEquals($id_token, $_SESSION['icanid__id_token']);
    }

    /**
     * @throws CoreException
     */
    public function testThatIdTokenNonceIsCheckedWhenSet()
    {
        $custom_config = self::$baseConfig + ['id_token_alg' => 'HS256'];
        $icanid         = new ICANID( $custom_config );
        $id_token      = self::getIdToken();

        $_SESSION['icanid__nonce'] = '__invalid_nonce__';
        $e_message                = 'No exception caught';
        try {
            $icanid->setIdToken( $id_token );
        } catch (InvalidTokenException $e) {
            $e_message = $e->getMessage();
        }

        $this->assertStringStartsWith('Nonce (nonce) claim mismatch in the ID token', $e_message);
    }

    /**
     * @throws CoreException
     */
    public function testThatIdTokenAuthTimeIsCheckedWhenSet()
    {
        $custom_config = self::$baseConfig + ['id_token_alg' => 'HS256', 'max_age' => 10 ];
        $icanid         = new ICANID( $custom_config );
        $id_token      = self::getIdToken();

        $_SESSION['icanid__nonce'] = '__test_nonce__';
        $e_message                = 'No exception caught';
        try {
            $icanid->setIdToken( $id_token );
        } catch (InvalidTokenException $e) {
            $e_message = $e->getMessage();
        }

        $this->assertStringStartsWith(
            'Authentication Time (auth_time) claim in the ID token indicates that too much time has passed',
            $e_message
        );
    }

    /**
     * @throws CoreException
     */
    public function testThatIdTokenOrganizationIsCheckedWhenSet()
    {
        $icanid    = new ICANID( self::$baseConfig + [ 'id_token_alg' => 'HS256', 'organization' => '__test_organization__' ] );

        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Organization Id (org_id) claim must be a string present in the ID token');

        $icanid->decodeIdToken( self::getIdToken() );
    }

    /**
     * @throws CoreException
     */
    public function testThatIdTokenOrganizationSucceesWhenMatched()
    {
        $icanid = new ICANID( self::$baseConfig + [ 'id_token_alg' => 'HS256', 'organization' => '__test_organization__' ] );

        $decodedToken = $icanid->decodeIdToken( self::getIdToken([ 'org_id' => '__test_organization__' ]) );

        $this->assertArrayHasKey( 'org_id', $decodedToken );
        $this->assertEquals( '__test_organization__', $decodedToken['org_id'] );
    }

    /**
     * @throws CoreException
     */
    public function testThatIdTokenOrganizationFailsWhenMismatched()
    {
        $icanid = new ICANID( self::$baseConfig + [ 'id_token_alg' => 'HS256', 'organization' => '__test_organization__' ] );

        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Organization Id (org_id) claim value mismatch in the ID token; expected "__test_organization__", found "__bad_test_organization__"');

        $icanid->decodeIdToken( self::getIdToken(['org_id' => '__bad_test_organization__',]) );
    }

    public function testThatDecodeIdTokenOptionsAreUsed()
    {
        $icanid                    = new ICANID( self::$baseConfig + ['id_token_alg' => 'HS256'] );
        $_SESSION['icanid__nonce'] = '__test_nonce__';
        $e_message                = 'No exception caught';
        try {
            $icanid->decodeIdToken( self::getIdToken(), ['max_age' => 10 ] );
        } catch (InvalidTokenException $e) {
            $e_message = $e->getMessage();
        }

        $this->assertStringStartsWith(
            'Authentication Time (auth_time) claim in the ID token indicates that too much time has passed',
            $e_message
        );
    }

    /**
     * @throws ApiException
     * @throws CoreException
     * @throws InvalidTokenException
     */
    public function testThatIdTokenLeewayFromConstructorIsUsed()
    {
        $custom_config = self::$baseConfig + ['id_token_leeway' => 120, 'id_token_alg' => 'HS256'];
        $icanid         = new ICANID( $custom_config );

        // Set the token expiration time past the default leeway of 60 seconds.
        $id_token = self::getIdToken(['exp' => time() - 100]);

        $_SESSION['icanid__nonce'] = '__test_nonce__';

        $icanid->setIdToken( $id_token );
        $this->assertEquals( $id_token, $icanid->getIdToken() );
    }

    public function testThatCacheHandlerCanBeSet()
    {
        $request_history = [];
        $mock            = new MockHandler([
            new Response( 200, [ 'Content-Type' => 'application/json' ], '{"keys":[{"kid":"__test_kid__","x5c":["123"]}]}' ),
        ]);
        $handler = HandlerStack::create($mock);
        $handler->push( Middleware::history($request_history) );

        $pool = new ArrayCachePool();
        $icanid = new ICANID([
            'domain' => 'test.auth0.com',
            'client_id' => uniqid(),
            'redirect_uri' => uniqid(),
            'cache_handler' => $pool,
            'transient_store' => new SessionStore(),
            'guzzle_options' => [
                'handler' => $handler
            ]
        ]);
        $_SESSION['icanid__nonce'] = '__test_nonce__';

        try {
            @$icanid->setIdToken(AsymmetricVerifierTest::getToken());
        } catch ( \Exception $e ) {
            // ...
        }

        $stored_jwks = $pool->get(md5('https://test.auth0.com/.well-known/jwks.json'));

        $this->assertNotEmpty($stored_jwks);
        $this->assertArrayHasKey('__test_kid__', $stored_jwks);
        $this->assertEquals("-----BEGIN CERTIFICATE-----\n123\n-----END CERTIFICATE-----\n", $stored_jwks['__test_kid__']);
    }

    /*
     * Test helper methods.
     */

    public static function getIdToken(array $overrides = [])
    {
        $defaults = [
            'sub' => '__test_sub__',
            'iss' => 'https://__test_domain__/',
            'aud' => '__test_client_id__',
            'nonce' => '__test_nonce__',
            'auth_time' => time() - 100,
            'exp' => time() + 1000,
            'iat' => time() - 1000,
        ];
        $builder  = SymmetricVerifierTest::getTokenBuilder();

        foreach (array_merge($defaults, $overrides) as $claim => $value) {
            $builder->withClaim($claim, $value);
        }

        return SymmetricVerifierTest::getToken('__test_client_secret__', $builder);
    }
}
