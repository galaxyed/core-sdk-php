<?php
namespace ICANID\Tests\unit;

use ICANID\SDK\ICANID;
use ICANID\SDK\Exception\ApiException;
use ICANID\SDK\Exception\CoreException;
use ICANID\SDK\Exception\InvalidTokenException;
use ICANID\SDK\Store\CookieStore;
use ICANID\SDK\Store\EmptyStore;
use ICANID\SDK\Store\SessionStore;
use ICANID\SDK\Store\StoreInterface;
use ICANID\Tests\Traits\ErrorHelpers;
use PHPUnit\Framework\TestCase;

/**
 * Class ICANIDStoreTest
 *
 * @package ICANID\Tests\unit
 */
class ICANIDStoreTest extends TestCase
{

    use ErrorHelpers;

    /**
     * Basic ICANID class config options.
     *
     * @var array
     */
    public static $baseConfig;

    /**
     * Runs after each test completes.
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$baseConfig = [
            'domain'        => '__test_domain__',
            'client_id'     => '__test_client_id__',
            'redirect_uri'  => '__test_redirect_uri__',
            'scope' => 'openid',
        ];
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
     * @throws ApiException Should not be thrown in this test.
     * @throws CoreException Should not be thrown in this test.
     */
    public function testThatPassedInStoreInterfaceIsUsed()
    {
        $storeMock = new class extends EmptyStore {
            public function get(string $key, $default = null)
            {
                return '__test_custom_store__' . $key . '__';
            }
        };

        $icanid = new ICANID(self::$baseConfig + ['store' => $storeMock]);
        $icanid->setUser(['sub' => '__test_user__']);

        $icanid = new ICANID(self::$baseConfig + ['store' => $storeMock]);
        $this->assertEquals('__test_custom_store__user__', $icanid->getUser());
    }

    /**
     * @throws ApiException Should not be thrown in this test.
     * @throws CoreException Should not be thrown in this test.
     */
    public function testThatSessionStoreIsUsedAsDefault()
    {
        $icanid = new ICANID(self::$baseConfig);
        $icanid->setUser(['sub' => '__test_user__']);

        $this->assertEquals($_SESSION['icanid__user'], $icanid->getUser());
    }

    /**
     * @throws ApiException Should not be thrown in this test.
     * @throws CoreException Should not be thrown in this test.
     */
    public function testThatSessionStoreIsUsedIfPassedIsInvalid()
    {
        $icanid = new ICANID(self::$baseConfig + ['store' => new \stdClass()]);
        $icanid->setUser(['sub' => '__test_user__']);

        $this->assertEquals($_SESSION['icanid__user'], $icanid->getUser());
    }

    public function testThatCookieStoreIsUsedAsDefaultTransient()
    {
        $icanid = new ICANID(self::$baseConfig);
        @$icanid->getLoginUrl(['nonce' => '__test_cookie_nonce__']);

        $this->assertEquals('__test_cookie_nonce__', $_COOKIE['icanid__nonce']);
    }

    public function testThatTransientCanBeSetToAnotherStoreInterface()
    {
        $icanid = new ICANID(self::$baseConfig + ['transient_store' => new SessionStore()]);
        @$icanid->getLoginUrl(['nonce' => '__test_session_nonce__']);

        $this->assertEquals('__test_session_nonce__', $_SESSION['icanid__nonce']);
    }

    /**
     * @throws ApiException Should not be thrown in this test.
     * @throws CoreException Should not be thrown in this test.
     */
    public function testThatEmptyStoreInterfaceStoresNothing()
    {
        $icanid = new ICANID(self::$baseConfig + ['store' => new EmptyStore()]);
        $icanid->setUser(['sub' => '__test_user__']);

        $icanid = new ICANID(self::$baseConfig);
        $this->assertNull($icanid->getUser());
    }

    /**
     * @throws ApiException Should not be thrown in this test.
     * @throws CoreException Should not be thrown in this test.
     */
    public function testThatNoUserPersistenceUsesEmptyStore()
    {
        $icanid = new ICANID(self::$baseConfig + ['persist_user' => false]);
        $icanid->setUser(['sub' => '__test_user__']);

        $icanid = new ICANID(self::$baseConfig + ['persist_user' => false]);
        $this->assertNull($icanid->getUser());
    }
}
