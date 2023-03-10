<?php
namespace ICANID\Tests\unit\API\Management;

use ICANID\SDK\API\Helpers\InformationHeaders;
use ICANID\Tests\Traits\ErrorHelpers;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Class ConnectionsTestMocked.
 *
 * @package ICANID\Tests\unit\API\Management
 */
class ConnectionsTestMocked extends TestCase
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
     * Test a basic getAll connection call.
     *
     * @return void
     *
     * @throws \Exception Unsuccessful HTTP call.
     */
    public function testConnectionsGetAll()
    {
        $api = new MockManagementApi( [ new Response( 200, self::$headers ) ] );

        $api->call()->connections()->getAll();

        $this->assertEquals( 'GET', $api->getHistoryMethod() );
        $this->assertEquals( 'https://api.test.local/api/v2/connections', $api->getHistoryUrl() );
        $this->assertEmpty( $api->getHistoryQuery() );

        $headers = $api->getHistoryHeaders();
        $this->assertEquals( 'Bearer __api_token__', $headers['Authorization'][0] );
        $this->assertEquals( self::$expectedTelemetry, $headers['ICANID-Client'][0] );
    }


    /**
     * Test a getAll request filtered by strategy.
     *
     * @return void
     *
     * @throws \Exception Unsuccessful HTTP call.
     */
    public function testThatConnectionsGetAllAddsFilters()
    {
        $api = new MockManagementApi( [ new Response( 200, self::$headers ) ] );

        $strategy = 'test-strategy-01';
        $api->call()->connections()->getAll($strategy);

        $this->assertEquals( 'GET', $api->getHistoryMethod() );
        $this->assertStringStartsWith( 'https://api.test.local/api/v2/connections', $api->getHistoryUrl() );
        $this->assertStringContainsString( 'strategy='.$strategy, $api->getHistoryQuery() );
    }

    /**
     * Test a getAll request with included fields.
     *
     * @return void
     *
     * @throws \Exception Unsuccessful HTTP call.
     */
    public function testThatConnectionsGetAllIncludesFields()
    {
        $api = new MockManagementApi( [ new Response( 200, self::$headers ), new Response( 200, self::$headers ) ] );

        $strategy = null;
        $fields   = ['id', 'name'];
        $api->call()->connections()->getAll($strategy, $fields);
        $query = $api->getHistoryQuery();

        $this->assertEquals( 'GET', $api->getHistoryMethod() );
        $this->assertStringStartsWith( 'https://api.test.local/api/v2/connections', $api->getHistoryUrl() );
        $this->assertStringContainsString( 'fields='.implode(',', $fields), $query );
        $this->assertStringNotContainsString( 'include_fields=true', $query );

        // Test an explicit true for includeFields.
        $include_fields = true;
        $api->call()->connections()->getAll($strategy, $fields, $include_fields);
        $query = $api->getHistoryQuery();

        $this->assertStringContainsString( 'include_fields=true', $query );
    }

    /**
     * Test a getAll request with excluded fields.
     *
     * @return void
     *
     * @throws \Exception Unsuccessful HTTP call.
     */
    public function testThatConnectionsGetAllExcludesFields()
    {
        $api = new MockManagementApi( [ new Response( 200, self::$headers ) ] );

        $strategy       = null;
        $fields         = ['id', 'name'];
        $include_fields = false;
        $api->call()->connections()->getAll($strategy, $fields, $include_fields);

        $this->assertEquals( 'GET', $api->getHistoryMethod() );
        $this->assertStringStartsWith( 'https://api.test.local/api/v2/connections', $api->getHistoryUrl() );

        $query = $api->getHistoryQuery();
        $this->assertStringContainsString( 'fields='.implode(',', $fields), $query );
        $this->assertStringContainsString( 'include_fields=false', $query );
    }

    /**
     * Test a paginated getAll request.
     *
     * @return void
     *
     * @throws \Exception Unsuccessful HTTP call.
     */
    public function testThatConnectionsGetAllPaginates()
    {
        $api = new MockManagementApi( [ new Response( 200, self::$headers ) ] );

        $strategy       = null;
        $fields         = null;
        $include_fields = null;
        $page           = 0;
        $per_page       = 5;
        $api->call()->connections()->getAll($strategy, $fields, $include_fields, $page, $per_page);

        $this->assertEquals( 'GET', $api->getHistoryMethod() );
        $this->assertStringStartsWith( 'https://api.test.local/api/v2/connections', $api->getHistoryUrl() );

        $query = $api->getHistoryQuery();
        $this->assertStringContainsString( 'page=0', $query );
        $this->assertStringContainsString( 'per_page=5', $query );
    }

    /**
     * Test a getAll request with additional parameters added.
     *
     * @return void
     *
     * @throws \Exception Unsuccessful HTTP call.
     */
    public function testThatConnectionsGetAllAddsExtraParams()
    {
        $api = new MockManagementApi( [ new Response( 200, self::$headers ) ] );

        $strategy       = null;
        $fields         = null;
        $include_fields = null;
        $page           = null;
        $per_page       = null;
        $add_params     = ['param1' => 'value1', 'param2' => 'value2'];
        $api->call()->connections()->getAll($strategy, $fields, $include_fields, $page, $per_page, $add_params);

        $this->assertEquals( 'GET', $api->getHistoryMethod() );
        $this->assertStringStartsWith( 'https://api.test.local/api/v2/connections', $api->getHistoryUrl() );

        $query = $api->getHistoryQuery();
        $this->assertStringContainsString( 'param1=value1', $query );
        $this->assertStringContainsString( 'param2=value2', $query );
    }

    /**
     * Test a basic get request.
     *
     * @return void
     *
     * @throws \Exception Unsuccessful HTTP call.
     */
    public function testConnectionsGet()
    {
        $api = new MockManagementApi( [ new Response( 200, self::$headers ) ] );

        $id = 'con_testConnection10';
        $api->call()->connections()->get($id);

        $this->assertEquals( 'GET', $api->getHistoryMethod() );
        $this->assertEquals( 'https://api.test.local/api/v2/connections/'.$id, $api->getHistoryUrl() );
        $this->assertEmpty( $api->getHistoryQuery() );
    }

    /**
     * Test a get call with included fields.
     *
     * @return void
     *
     * @throws \Exception Unsuccessful HTTP call.
     */
    public function testThatConnectionsGetIncludesFields()
    {
        $api = new MockManagementApi( [ new Response( 200, self::$headers ), new Response( 200, self::$headers ) ] );

        $id     = 'con_testConnection10';
        $fields = ['name', 'strategy'];
        $api->call()->connections()->get($id, $fields);
        $query = $api->getHistoryQuery();

        $this->assertEquals( 'GET', $api->getHistoryMethod() );
        $this->assertStringStartsWith( 'https://api.test.local/api/v2/connections/'.$id, $api->getHistoryUrl() );
        $this->assertStringContainsString( 'fields='.implode(',', $fields), $query );
        $this->assertStringNotContainsString( 'include_fields=', $query );

        // Test an explicit true for includeFields.
        $include_fields = true;
        $api->call()->connections()->get($id, $fields, $include_fields);
        $query = $api->getHistoryQuery();

        $this->assertStringContainsString( 'include_fields=true', $query );
    }

    /**
     * Test a get call with excluded fields.
     *
     * @return void
     *
     * @throws \Exception Unsuccessful HTTP call.
     */
    public function testThatConnectionsGetExcludesFields()
    {
        $api = new MockManagementApi( [ new Response( 200, self::$headers ) ] );

        $id             = 'con_testConnection10';
        $fields         = ['name', 'strategy'];
        $include_fields = false;
        $api->call()->connections()->get($id, $fields, $include_fields);

        $this->assertEquals( 'GET', $api->getHistoryMethod() );
        $this->assertStringStartsWith( 'https://api.test.local/api/v2/connections/'.$id, $api->getHistoryUrl() );

        $query = $api->getHistoryQuery();
        $this->assertStringContainsString( 'fields='.implode(',', $fields), $query );
        $this->assertStringContainsString( 'include_fields=false', $query );
    }

    /**
     * Test a basic delete connection request.
     *
     * @return void
     *
     * @throws \Exception Unsuccessful HTTP call.
     */
    public function testConnectionsDelete()
    {
        $api = new MockManagementApi( [ new Response( 204 ) ] );

        $id = 'con_testConnection10';
        $api->call()->connections()->delete($id);

        $this->assertEquals( 'DELETE', $api->getHistoryMethod() );
        $this->assertEquals( 'https://api.test.local/api/v2/connections/'.$id, $api->getHistoryUrl() );
        $this->assertEmpty( $api->getHistoryQuery() );

        $headers = $api->getHistoryHeaders();
        $this->assertEquals( 'Bearer __api_token__', $headers['Authorization'][0] );
        $this->assertEquals( self::$expectedTelemetry, $headers['ICANID-Client'][0] );
    }

    /**
     * Test a delete user for connection request.
     *
     * @return void
     *
     * @throws \Exception Unsuccessful HTTP call.
     */
    public function testConnectionsDeleteUser()
    {
        $api = new MockManagementApi( [ new Response( 204 ) ] );

        $id    = 'con_testConnection10';
        $email = 'con_testConnection10@auth0.com';
        $api->call()->connections()->deleteUser($id, $email);

        $this->assertEquals( 'DELETE', $api->getHistoryMethod() );
        $this->assertStringContainsString( 'https://api.test.local/api/v2/connections/'.$id.'/users', $api->getHistoryUrl() );
        $this->assertEquals( 'email='.$email, $api->getHistoryQuery() );

        $headers = $api->getHistoryHeaders();
        $this->assertEquals( 'Bearer __api_token__', $headers['Authorization'][0] );
        $this->assertEquals( self::$expectedTelemetry, $headers['ICANID-Client'][0] );
    }

    /**
     * Test a basic connection create call.
     *
     * @return void
     *
     * @throws \Exception Unsuccessful HTTP call.
     */
    public function testConnectionsCreate()
    {
        $api = new MockManagementApi( [ new Response( 200, self::$headers ) ] );

        $name     = 'TestConnection01';
        $strategy = 'test-strategy-01';
        $api->call()->connections()->create( [ 'name' => $name, 'strategy' => $strategy ] );
        $request_body = $api->getHistoryBody();

        $this->assertEquals( $name, $request_body['name'] );
        $this->assertEquals( $strategy, $request_body['strategy'] );
        $this->assertEquals( 'POST', $api->getHistoryMethod() );
        $this->assertEquals( 'https://api.test.local/api/v2/connections', $api->getHistoryUrl() );
        $this->assertEmpty( $api->getHistoryQuery() );

        $headers = $api->getHistoryHeaders();
        $this->assertEquals( 'Bearer __api_token__', $headers['Authorization'][0] );
        $this->assertEquals( self::$expectedTelemetry, $headers['ICANID-Client'][0] );
        $this->assertEquals( 'application/json', $headers['Content-Type'][0] );
    }

    /**
     * Test a connection create call exceptions.
     *
     * @return void
     *
     * @throws \Exception Unsuccessful HTTP call.
     */
    public function testThatConnectionsCreateThrowsExceptions()
    {
        $api = new MockManagementApi();

        $caught_no_name_exception = false;
        try {
            $api->call()->connections()->create(['strategy' => uniqid()]);
        } catch (\Exception $e) {
            $caught_no_name_exception = $this->errorHasString($e, 'Missing required "name" field');
        }

        $this->assertTrue($caught_no_name_exception);

        $caught_no_strategy_exception = false;
        try {
            $api->call()->connections()->create(['name' => uniqid()]);
        } catch (\Exception $e) {
            $caught_no_strategy_exception = $this->errorHasString($e, 'Missing required "strategy" field');
        }

        $this->assertTrue($caught_no_strategy_exception);
    }

    /**
     * Test a basic update request.
     *
     * @return void
     *
     * @throws \Exception Unsuccessful HTTP call.
     */
    public function testConnectionsUpdate()
    {
        $api = new MockManagementApi( [ new Response( 200, self::$headers ) ] );

        $id          = 'con_testConnection10';
        $update_data = [ 'metadata' => [ 'meta1' => 'value1' ] ];
        $api->call()->connections()->update($id, $update_data);
        $request_body = $api->getHistoryBody();

        $this->assertEquals( $update_data, $request_body );
        $this->assertEquals( 'PATCH', $api->getHistoryMethod() );
        $this->assertEquals( 'https://api.test.local/api/v2/connections/'.$id, $api->getHistoryUrl() );
        $this->assertEmpty( $api->getHistoryQuery() );

        $headers = $api->getHistoryHeaders();
        $this->assertEquals( 'Bearer __api_token__', $headers['Authorization'][0] );
        $this->assertEquals( self::$expectedTelemetry, $headers['ICANID-Client'][0] );
        $this->assertEquals( 'application/json', $headers['Content-Type'][0] );
    }
}
