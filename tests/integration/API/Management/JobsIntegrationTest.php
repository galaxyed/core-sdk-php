<?php
namespace ICANID\Tests\integration\API\Management;

use ICANID\SDK\API\Helpers\InformationHeaders;
use ICANID\SDK\API\Management;
use ICANID\Tests\API\ApiTests;

class JobsIntegrationTest extends ApiTests
{

    /**
     * Expected telemetry value.
     *
     * @var string
     */
    protected static $testImportUsersJsonPath;

    /**
     * Runs before test suite starts.
     */
    public static function setUpBeforeClass(): void
    {
        self::$testImportUsersJsonPath = ICANID_PHP_TEST_JSON_DIR.'test-import-users-file.json';
    }

    /**
     * @throws \ICANID\SDK\Exception\ApiException
     * @throws \Exception
     */
    public function testIntegrationImportUsersJob()
    {
        $env = self::getEnv();

        if (! $env['API_TOKEN']) {
            $this->markTestSkipped( 'No client secret; integration test skipped' );
        }

        $api = new Management($env['API_TOKEN'], $env['DOMAIN']);

        // Get a single, active database connection.
        $default_db_name       = 'Username-Password-Authentication';
        $get_connection_result = $api->connections()->getAll( 'icanid', ['id'], true, 0, 1, ['name' => $default_db_name] );
        $this->sleep();

        $conn_id            = $get_connection_result[0]['id'];
        $import_user_params = [
            'upsert' => true,
            'send_completion_email' => false,
            'external_id' => '__test_ext_id__',
        ];

        $import_job_result = $api->jobs()->importUsers(self::$testImportUsersJsonPath, $conn_id, $import_user_params);
        $this->sleep();

        $this->assertEquals( $conn_id, $import_job_result['connection_id'] );
        $this->assertEquals( $default_db_name, $import_job_result['connection'] );
        $this->assertEquals( '__test_ext_id__', $import_job_result['external_id'] );
        $this->assertEquals( 'users_import', $import_job_result['type'] );

        $get_job_result = $api->jobs()->get($import_job_result['id']);
        $this->sleep();

        $this->assertEquals( $conn_id, $get_job_result['connection_id'] );
        $this->assertEquals( $default_db_name, $get_job_result['connection'] );
        $this->assertEquals( '__test_ext_id__', $get_job_result['external_id'] );
        $this->assertEquals( 'users_import', $get_job_result['type'] );
    }

    /**
     * @throws \ICANID\SDK\Exception\ApiException
     * @throws \Exception
     */
    public function testIntegrationSendEmailVerificationJob()
    {
        $env = self::getEnv();

        if (! $env['API_TOKEN']) {
            $this->markTestSkipped( 'No client secret; integration test skipped' );
        }

        $api = new Management($env['API_TOKEN'], $env['DOMAIN']);

        $create_user_data   = [
            'connection' => 'Username-Password-Authentication',
            'email' => 'php-sdk-test-email-verification-job-'.uniqid().'@auth0.com',
            'password' => uniqid().'&*@'.uniqid().uniqid(),
        ];
        $create_user_result = $api->users()->create( $create_user_data );
        $this->sleep();

        $user_id = $create_user_result['user_id'];

        $email_job_result = $api->jobs()->sendVerificationEmail($user_id);
        $this->sleep();

        $this->assertEquals( 'verification_email', $email_job_result['type'] );

        $get_job_result = $api->jobs()->get($email_job_result['id']);
        $this->sleep();

        $this->assertEquals( 'verification_email', $get_job_result['type'] );

        $api->users()->delete( $user_id );
        $this->sleep();
    }
}
