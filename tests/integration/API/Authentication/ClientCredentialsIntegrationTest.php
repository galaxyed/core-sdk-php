<?php
namespace ICANID\Tests\integration\API\Authentication;

use ICANID\SDK\API\Authentication;
use ICANID\Tests\API\ApiTests;

class ClientCredentialsIntegrationTest extends ApiTests
{

    public function testOauthToken()
    {
        $env = self::getEnv();

        $api = new Authentication($env['DOMAIN'], $env['APP_CLIENT_ID'], $env['APP_CLIENT_SECRET']);

        $token = $api->client_credentials(
            [
                'audience' => 'https://'.$env['DOMAIN'].'/api/v2/'
            ]
        );

        $this->assertArrayHasKey('access_token', $token);
        $this->assertArrayHasKey('token_type', $token);
        $this->assertEquals('bearer', strtolower($token['token_type']));
    }
}
