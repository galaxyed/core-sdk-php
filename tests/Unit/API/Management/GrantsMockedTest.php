<?php
namespace ICANID\Tests\unit\API\Management;

use ICANID\SDK\API\Helpers\InformationHeaders;
use ICANID\SDK\API\Management;
use ICANID\SDK\Exception\CoreException;
use ICANID\Tests\Traits\ErrorHelpers;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Class GrantsTestMocked.
 *
 * @package ICANID\Tests\unit\API\Management
 */
class GrantsTestMocked extends TestCase
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
     * Test that getAll requests properly.
     *
     * @return void
     *
     * @throws \Exception Unsuccessful HTTP call.
     */
    public function testGrantsGetAll()
    {
        $api = new MockManagementApi( [ new Response( 200 ) ] );

        $api->call()->grants()->getAll();

        $this->assertEquals( 'GET', $api->getHistoryMethod() );
        $this->assertEquals( 'https://api.test.local/api/v2/grants', $api->getHistoryUrl() );
        $this->assertEmpty( $api->getHistoryQuery() );

        $headers = $api->getHistoryHeaders();
        $this->assertEquals( 'Bearer __api_token__', $headers['Authorization'][0] );
        $this->assertEquals( self::$telemetry, $headers['ICANID-Client'][0] );
    }

    /**
     * Test that getAll sends pagination parameters with the request.
     *
     * @return void
     *
     * @throws \Exception Unsuccessful HTTP call.
     */
    public function testThatGrantsGetAllPaginates()
    {
        $api = new MockManagementApi( [ new Response( 200 ), new Response( 200 ), new Response( 200 ) ] );

        $page     = 1;
        $per_page = 5;
        $api->call()->grants()->getAll($page, $per_page);
        $query = $api->getHistoryQuery();

        $this->assertEquals( 'GET', $api->getHistoryMethod() );
        $this->assertStringStartsWith( 'https://api.test.local/api/v2/grants', $api->getHistoryUrl() );

        $this->assertStringContainsString( 'page=1', $query );
        $this->assertStringContainsString( 'per_page=5', $query );

        $page     = 2;
        $per_page = null;
        $api->call()->grants()->getAll($page, $per_page);
        $query = $api->getHistoryQuery();

        $this->assertStringContainsString( 'page=2', $query );
        $this->assertStringNotContainsString( 'per_page=', $query );

        $page     = false;
        $per_page = false;
        $api->call()->grants()->getAll($page, $per_page);

        $this->assertNull( $api->getHistoryQuery() );
    }

    /**
     * Test that getAll sends additional parameters with the request.
     *
     * @return void
     *
     * @throws \Exception Unsuccessful HTTP call.
     */
    public function testThatGrantsGetAllAddsExtraParams()
    {
        $api = new MockManagementApi( [ new Response( 200 ) ] );

        $add_params = ['param1' => 'value1', 'param2' => 'value2'];
        $api->call()->grants()->getAll(null, null, $add_params);

        $this->assertEquals( 'GET', $api->getHistoryMethod() );
        $this->assertStringStartsWith( 'https://api.test.local/api/v2/grants', $api->getHistoryUrl() );

        $query = $api->getHistoryQuery();
        $this->assertStringContainsString( 'param1=value1', $query );
        $this->assertStringContainsString( 'param2=value2', $query );
    }

    /**
     * Test that getByClientId adds a client_id to the request.
     *
     * @return void
     *
     * @throws \Exception Unsuccessful HTTP call.
     */
    public function testThatGrantsGetByClientId()
    {
        $api = new MockManagementApi( [ new Response( 200 ) ] );

        $api->call()->grants()->getByClientId('__test_client_id__');

        $this->assertStringStartsWith( 'https://api.test.local/api/v2/grants', $api->getHistoryUrl() );
        $this->assertEquals( 'client_id=__test_client_id__', $api->getHistoryQuery() );
    }

    /**
     * Test that getByClientId adds pagination parameters to the request.
     *
     * @return void
     *
     * @throws \Exception Unsuccessful HTTP call.
     */
    public function testThatGrantsGetByClientIdPaginates()
    {
        $api = new MockManagementApi( [ new Response( 200 ) ] );

        $api->call()->grants()->getByClientId('__test_client_id__', 1, 2);

        $query = $api->getHistoryQuery();
        $this->assertStringContainsString( 'page=1', $query );
        $this->assertStringContainsString( 'per_page=2', $query );
    }

    /**
     * Test that getByClientId throws an exception with an empty parameter.
     *
     * @return void
     *
     * @throws \Exception Unsuccessful HTTP call.
     */
    public function testThatGrantsGetByClientIdThrowsException()
    {
        $api = new Management( '__test_api_token__', '__test_domain__' );

        try {
            $caught_exception = false;
            $api->grants()->getByClientId( '' );
        } catch (CoreException $e) {
            $caught_exception = $this->errorHasString( $e, 'Empty or invalid "client_id" parameter' );
        }

        $this->assertTrue( $caught_exception );

        try {
            $caught_exception = false;
            $api->grants()->getByClientId( [ '__not_empty__' ] );
        } catch (CoreException $e) {
            $caught_exception = $this->errorHasString( $e, 'Empty or invalid "client_id" parameter' );
        }

        $this->assertTrue( $caught_exception );
    }

    /**
     * Test that getByAudience adds an audience to the request.
     *
     * @return void
     *
     * @throws \Exception Unsuccessful HTTP call.
     */
    public function testThatGrantsGetByAudience()
    {
        $api = new MockManagementApi( [ new Response( 200 ) ] );

        $api->call()->grants()->getByAudience('__test_audience__');

        $this->assertStringStartsWith( 'https://api.test.local/api/v2/grants', $api->getHistoryUrl() );
        $this->assertEquals( 'audience=__test_audience__', $api->getHistoryQuery() );
    }

    /**
     * Test that getByAudience adds pagination parameters to the request.
     *
     * @return void
     *
     * @throws \Exception Unsuccessful HTTP call.
     */
    public function testThatGrantsGetByAudiencePaginates()
    {
        $api = new MockManagementApi( [ new Response( 200 ) ] );

        $api->call()->grants()->getByAudience('__test_audience__', 1, 2);

        $query = $api->getHistoryQuery();
        $this->assertStringContainsString( 'page=1', $query );
        $this->assertStringContainsString( 'per_page=2', $query );
    }

    /**
     * Test that getByAudience throws an exception with an empty parameter.
     *
     * @return void
     *
     * @throws \Exception Unsuccessful HTTP call.
     */
    public function testThatGrantsGetByAudienceThrowsException()
    {
        $api = new Management( '__test_api_token__', '__test_domain__' );

        try {
            $caught_exception = false;
            $api->grants()->getByAudience( '' );
        } catch (CoreException $e) {
            $caught_exception = $this->errorHasString( $e, 'Empty or invalid "audience" parameter' );
        }

        $this->assertTrue( $caught_exception );

        try {
            $caught_exception = false;
            $api->grants()->getByAudience( [ '__not_empty__' ] );
        } catch (CoreException $e) {
            $caught_exception = $this->errorHasString( $e, 'Empty or invalid "audience" parameter' );
        }

        $this->assertTrue( $caught_exception );
    }

    /**
     * Test that getByUserId adds an audience to the request.
     *
     * @return void
     *
     * @throws \Exception Unsuccessful HTTP call.
     */
    public function testThatGrantsGetByUserId()
    {
        $api = new MockManagementApi( [ new Response( 200 ) ] );

        $api->call()->grants()->getByUserId('__test_user_id__');

        $this->assertStringStartsWith( 'https://api.test.local/api/v2/grants', $api->getHistoryUrl() );
        $this->assertEquals( 'user_id=__test_user_id__', $api->getHistoryQuery() );
    }

    /**
     * Test that getByUserId adds pagination parameters to the request.
     *
     * @return void
     *
     * @throws \Exception Unsuccessful HTTP call.
     */
    public function testThatGrantsGetByUserIdPaginates()
    {
        $api = new MockManagementApi( [ new Response( 200 ) ] );

        $api->call()->grants()->getByUserId('__test_user_id__', 1, 2);

        $query = $api->getHistoryQuery();
        $this->assertStringContainsString( 'page=1', $query );
        $this->assertStringContainsString( 'per_page=2', $query );
    }

    /**
     * Test that getByUserId throws an exception with an empty parameter.
     *
     * @return void
     *
     * @throws \Exception Unsuccessful HTTP call.
     */
    public function testThatGrantsGetByUserIdThrowsException()
    {
        $api = new Management( '__test_api_token__', '__test_domain__' );

        try {
            $caught_exception = false;
            $api->grants()->getByUserId( '' );
        } catch (CoreException $e) {
            $caught_exception = $this->errorHasString( $e, 'Empty or invalid "user_id" parameter' );
        }

        $this->assertTrue( $caught_exception );

        try {
            $caught_exception = false;
            $api->grants()->getByUserId( [ '__not_empty__' ] );
        } catch (CoreException $e) {
            $caught_exception = $this->errorHasString( $e, 'Empty or invalid "user_id" parameter' );
        }

        $this->assertTrue( $caught_exception );
    }

    /**
     * Test that delete requests properly.
     *
     * @return void
     *
     * @throws \Exception Unsuccessful HTTP call.
     */
    public function testGrantsDelete()
    {
        $api = new MockManagementApi( [ new Response( 204 ) ] );

        $id = uniqid();
        $api->call()->grants()->delete($id);

        $this->assertEquals( 'DELETE', $api->getHistoryMethod() );
        $this->assertEquals( 'https://api.test.local/api/v2/grants/'.$id, $api->getHistoryUrl() );
        $this->assertEmpty( $api->getHistoryQuery() );

        $headers = $api->getHistoryHeaders();
        $this->assertEquals( 'Bearer __api_token__', $headers['Authorization'][0] );
        $this->assertEquals( self::$telemetry, $headers['ICANID-Client'][0] );
    }

    /**
     * Test that a delete request throws an exception with an empty parameter.
     *
     * @return void
     *
     * @throws \Exception Unsuccessful HTTP call.
     */
    public function testGrantsDeleteThrowsException()
    {
        $api = new Management( '__test_api_token__', '__test_domain__' );

        try {
            $caught_exception = false;
            $api->grants()->delete( '' );
        } catch (CoreException $e) {
            $caught_exception = $this->errorHasString( $e, 'Empty or invalid "id" parameter' );
        }

        $this->assertTrue( $caught_exception );

        try {
            $caught_exception = false;
            $api->grants()->delete( [ '__not_empty__' ] );
        } catch (CoreException $e) {
            $caught_exception = $this->errorHasString( $e, 'Empty or invalid "id" parameter' );
        }

        $this->assertTrue( $caught_exception );
    }
}
