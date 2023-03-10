<?php
namespace ICANID\Tests\Store;

use ICANID\SDK\Store\CookieStore;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Error\Warning;
use ReflectionClass;

/**
 * Class CookieStoreTest.
 * Tests the CookieStore class.
 */
class CookieStoreTest extends TestCase
{

    private static $mockSpyCookie;

    private static $mockSpyHeader;

    /**
     * Run after each test in this suite.
     */
    public function tearDown(): void
    {
        $_COOKIE             = [];
        self::$mockSpyCookie = null;
        self::$mockSpyHeader = null;
    }

    /**
     * @param array $args
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|CookieStore
     */
    public function getMock(array $args = [])
    {
        $mockStore = $this->getMockBuilder(CookieStore::class)
            ->setConstructorArgs([$args])
            ->onlyMethods(['setCookie','setCookieHeader'])
            ->getMock();

        $mockStore->expects(self::$mockSpyCookie = $this->any())
            ->method('setCookie')
            ->willReturn(true);

        $mockStore->expects(self::$mockSpyHeader = $this->any())
            ->method('setCookieHeader');

        return $mockStore;
    }

    /**
     * Gain access to PHPUnit's mock invocation stack for analyzing calls.
     * PHPUnit 8.4 removed the native getInvocations property, requiring this workaround.
     *
     * @param object $mock
     *
     * @return array
     */
    public function getMockInvocations(object $mock)
    {
        $reflector = new ReflectionClass(get_class($mock));
        $invocations = $reflector->getParentClass()->getProperty('invocations');
        $invocations->setAccessible(true);

        return $invocations->getValue($mock);
    }

    public function testGetCookieName()
    {
        $store = new CookieStore();
        $this->assertEquals('icanid__test_key', $store->getCookieName('test_key'));
    }

    public function testCustomBaseName()
    {
        $store = new CookieStore(['base_name' => 'custom_base']);
        $this->assertEquals('custom_base_test_key', $store->getCookieName('test_key'));

        $store = new CookieStore(['base_name' => 'custom_base_']);
        $this->assertEquals('custom_base__test_key', $store->getCookieName('test_key'));
    }

    public function testSetNoSameSite()
    {
        $mockStore = $this->getMock(['now' => 1, 'expiration' => 1]);
        $mockStore->set('test_set_key', '__test_set_value__');

        $this->assertEquals('__test_set_value__', $_COOKIE['icanid__test_set_key']);
        $this->assertArrayNotHasKey('_icanid__test_set_key', $_COOKIE);

        $this->assertCount(0, (array) $this->getMockInvocations(self::$mockSpyHeader));
        $this->assertCount(1, (array) $this->getMockInvocations(self::$mockSpyCookie));

        $setCookieParams = $this->getMockInvocations(self::$mockSpyCookie)[0]->getParameters();

        $this->assertEquals('icanid__test_set_key', $setCookieParams[0]);
        $this->assertEquals('__test_set_value__', $setCookieParams[1]);
        $this->assertEquals(2, $setCookieParams[2]);
    }

    public function testSetSameSiteNone()
    {
        $mockStore = $this->getMock(['now' => 10, 'expiration' => 10, 'samesite' => 'None']);
        $mockStore->set('test_set_key', '__test_set_value__');

        $this->assertEquals('__test_set_value__', $_COOKIE['icanid__test_set_key']);
        $this->assertEquals('__test_set_value__', $_COOKIE['_icanid__test_set_key']);

        $this->assertCount(1, (array) $this->getMockInvocations(self::$mockSpyHeader));

        $setHeaderParams = $this->getMockInvocations(self::$mockSpyHeader)[0]->getParameters();

        $this->assertEquals('icanid__test_set_key', $setHeaderParams[0]);
        $this->assertEquals('__test_set_value__', $setHeaderParams[1]);
        $this->assertEquals(20, $setHeaderParams[2]);

        $this->assertCount(1, (array) $this->getMockInvocations(self::$mockSpyCookie));

        $setCookieParams = $this->getMockInvocations(self::$mockSpyCookie)[0]->getParameters();

        $this->assertEquals('_icanid__test_set_key', $setCookieParams[0]);
        $this->assertEquals('__test_set_value__', $setCookieParams[1]);
        $this->assertEquals(20, $setCookieParams[2]);
    }

    public function testSetSameSiteNoneNoLegacy()
    {
        $mockStore = $this->getMock(['legacy_samesite_none' => false, 'samesite' => 'None']);
        $mockStore->set('test_set_key', '__test_set_value__');

        $this->assertEquals('__test_set_value__', $_COOKIE['icanid__test_set_key']);
        $this->assertArrayNotHasKey('_icanid__test_set_key', $_COOKIE);
        $this->assertCount(0, (array) $this->getMockInvocations(self::$mockSpyCookie));
        $this->assertCount(1, (array) $this->getMockInvocations(self::$mockSpyHeader));

        $setCookieParams = $this->getMockInvocations(self::$mockSpyHeader)[0]->getParameters();

        $this->assertEquals('icanid__test_set_key', $setCookieParams[0]);
        $this->assertEquals('__test_set_value__', $setCookieParams[1]);
        $this->assertGreaterThanOrEqual(time() + 600, $setCookieParams[2]);
    }

    public function testGet()
    {
        $store = new CookieStore();

        $_COOKIE['icanid__test_get_key']  = '__test_get_value__';
        $_COOKIE['_icanid__test_get_key'] = '__test_get_legacy_value__';

        $this->assertEquals('__test_get_value__', $store->get('test_get_key'));
        $this->assertEquals('__test_default_value__', $store->get('test_empty_key', '__test_default_value__'));

        unset($_COOKIE['icanid__test_get_key']);
        $this->assertEquals('__test_get_legacy_value__', $store->get('test_get_key'));
    }

    public function testGetNoLegacy()
    {
        $store = new CookieStore(['legacy_samesite_none' => false]);

        $_COOKIE['icanid__test_get_key']  = '__test_get_value__';
        $_COOKIE['_icanid__test_get_key'] = '__test_get_legacy_value__';

        $this->assertEquals('__test_get_value__', $store->get('test_get_key'));
        $this->assertEquals('__test_default_value__', $store->get('test_empty_key', '__test_default_value__'));

        unset($_COOKIE['icanid__test_get_key']);
        $this->assertNull($store->get('test_get_key'));
    }

    public function testDelete()
    {
        $_COOKIE['icanid__test_delete_key']  = '__test_delete_value__';
        $_COOKIE['_icanid__test_delete_key'] = '__test_delete_value__';

        $mockStore = $this->getMock();
        $mockStore->delete('test_delete_key');

        $this->assertNull($mockStore->get('test_delete_key'));
        $this->assertArrayNotHasKey('icanid__test_delete_key', $_COOKIE);
        $this->assertArrayNotHasKey('_icanid__test_delete_key', $_COOKIE);

        $this->assertCount(0, (array) $this->getMockInvocations(self::$mockSpyHeader));
        $this->assertCount(2, (array) $this->getMockInvocations(self::$mockSpyCookie));

        $setCookieParams = $this->getMockInvocations(self::$mockSpyCookie)[0]->getParameters();

        $this->assertEquals('icanid__test_delete_key', $setCookieParams[0]);
        $this->assertEquals('', $setCookieParams[1]);
        $this->assertEquals(0, $setCookieParams[2]);

        $setCookieParams = $this->getMockInvocations(self::$mockSpyCookie)[1]->getParameters();

        $this->assertEquals('_icanid__test_delete_key', $setCookieParams[0]);
        $this->assertEquals('', $setCookieParams[1]);
        $this->assertEquals(0, $setCookieParams[2]);
    }

    public function testDeleteNoLegacy()
    {
        $_COOKIE['icanid__test_delete_key']  = '__test_delete_value__';
        $_COOKIE['_icanid__test_delete_key'] = '__test_delete_value__';

        $mockStore = $this->getMock(['legacy_samesite_none' => false]);
        $mockStore->delete('test_delete_key');

        $this->assertNull($mockStore->get('test_delete_key'));
        $this->assertArrayNotHasKey('icanid__test_delete_key', $_COOKIE);
        $this->assertArrayHasKey('_icanid__test_delete_key', $_COOKIE);

        $this->assertCount(1, (array) $this->getMockInvocations(self::$mockSpyCookie));

        $setCookieParams = $this->getMockInvocations(self::$mockSpyCookie)[0]->getParameters();

        $this->assertEquals('icanid__test_delete_key', $setCookieParams[0]);
        $this->assertEquals('', $setCookieParams[1]);
        $this->assertEquals(0, $setCookieParams[2]);
    }

    public function testGetSetCookieHeaderStrict()
    {
        $store  = new CookieStore(['now' => 303943620, 'expiration' => 0, 'samesite' => 'lax']);
        $method = new \ReflectionMethod(CookieStore::class, 'getSameSiteCookieHeader');
        $method->setAccessible(true);
        $header = $method->invokeArgs($store, ['__test_name_1__', '__test_value_1__', 303943620]);

        $this->assertEquals(
            'Set-Cookie: __test_name_1__=__test_value_1__; path=/; '.'expires=Sunday, 19-Aug-1979 20:47:00 GMT; HttpOnly; SameSite=Lax',
            $header
        );
    }

    public function testGetSetCookieHeaderNone()
    {
        $store  = new CookieStore(['now' => 303943620, 'expiration' => 0, 'samesite' => 'none']);
        $method = new \ReflectionMethod(CookieStore::class, 'getSameSiteCookieHeader');
        $method->setAccessible(true);
        $header = $method->invokeArgs($store, ['__test_name_2__', '__test_value_2__', 303943620]);

        $this->assertEquals(
            'Set-Cookie: __test_name_2__=__test_value_2__; path=/; '.'expires=Sunday, 19-Aug-1979 20:47:00 GMT; HttpOnly; SameSite=None; Secure',
            $header
        );
    }

    public function testSetCookieHeaderFailsWithInvalidCookieName()
    {
        $store  = new CookieStore(['now' => 303943620, 'expiration' => 0, 'samesite' => 'none']);
        $method = new \ReflectionMethod(CookieStore::class, 'getSameSiteCookieHeader');
        $method->setAccessible(true);
        $methodArgs = ['__test_invalid_name_;__', uniqid(), mt_rand(1000, 9999)];

        try {
            $method->invokeArgs($store, $methodArgs);
            $error_msg = 'No warning caught';
        } catch (Warning $e) {
            $error_msg = $e->getMessage();
        }

        $this->assertEquals("Cookie names cannot contain any of the following ',; \\t\\r\\n\\013\\014'", $error_msg);

        $header = @$method->invokeArgs($store, $methodArgs);
        $this->assertEquals('', $header);
    }

    public function testSetCookieHeaderFailsWithInvalidCookieValue()
    {
        $store  = new CookieStore(['now' => 303943620, 'expiration' => 0, 'samesite' => 'none']);
        $method = new \ReflectionMethod(CookieStore::class, 'getSameSiteCookieHeader');
        $method->setAccessible(true);
        $methodArgs = [uniqid(), '__test_invalid_value_;__', mt_rand(1000, 9999)];

        try {
            $method->invokeArgs($store, $methodArgs);
            $error_msg = 'No warning caught';
        } catch (Warning $e) {
            $error_msg = $e->getMessage();
        }

        $this->assertEquals("Cookie values cannot contain any of the following ',; \\t\\r\\n\\013\\014'", $error_msg);

        $header = @$method->invokeArgs($store, $methodArgs);
        $this->assertEquals('', $header);
    }
}
