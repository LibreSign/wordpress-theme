<?php

if ( 'cli' !== PHP_SAPI && 'phpdbg' !== PHP_SAPI ) {
	exit;
}

$composer_autoload = dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! file_exists( $composer_autoload ) ) {
	echo 'Error: run `composer install` before running the tests.' . PHP_EOL;
	exit( 1 );
}

require_once $composer_autoload;

define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor-bin/phpunit/vendor/yoast/phpunit-polyfills' );

putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php' );

$wp_phpunit_dir = getenv( 'WP_PHPUNIT__DIR' );

if ( false === $wp_phpunit_dir || '' === $wp_phpunit_dir ) {
	$wp_phpunit_dir = dirname( __DIR__ ) . '/vendor/wp-phpunit/wp-phpunit';
}

$test_plugins_dir = dirname( __DIR__ ) . '/vendor/test-plugins';
$theme_slug       = basename( dirname( __DIR__ ) );

require_once $wp_phpunit_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () use ( $test_plugins_dir ) {
		require $test_plugins_dir . '/woocommerce/woocommerce.php';
		require $test_plugins_dir . '/woocommerce-subscriptions/woocommerce-subscriptions.php';
		register_theme_directory( dirname( __DIR__, 2 ) );
	}
);

tests_add_filter(
	'pre_option_active_plugins',
	static function () {
		return array(
			'woocommerce/woocommerce.php',
			'woocommerce-subscriptions/woocommerce-subscriptions.php',
		);
	}
);

tests_add_filter( 'stylesheet', static fn () => $theme_slug );
tests_add_filter( 'template', static fn () => $theme_slug );

tests_add_filter(
	'setup_theme',
	static function () {
		WC_Install::install();
	}
);

tests_add_filter(
	'pre_http_request',
	static function ( $preempt, $args, $url ) {
		if ( false !== $preempt ) {
			return $preempt;
		}

		if ( '127.0.0.1' === wp_parse_url( $url, PHP_URL_HOST ) ) {
			return $preempt;
		}

		return new WP_Error(
			'libresign_theme_tests_http_blocked',
			sprintf( 'Unexpected HTTP request to %s. Stub it with the pre_http_request filter.', $url )
		);
	},
	PHP_INT_MAX,
	3
);

require $wp_phpunit_dir . '/includes/bootstrap.php';
