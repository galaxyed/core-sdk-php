<?php
$tests_dir = dirname(__FILE__).'/';

require_once $tests_dir.'../vendor/autoload.php';

ini_set('session.use_cookies', false);
ini_set('session.cache_limiter', false);

if (! defined( 'ICANID_PHP_TEST_JSON_DIR' )) {
    define( 'ICANID_PHP_TEST_JSON_DIR', $tests_dir.'json/' );
}

require_once $tests_dir.'traits/ErrorHelpers.php';
