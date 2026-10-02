<?php

if ( 'cli' !== PHP_SAPI && 'phpdbg' !== PHP_SAPI ) {
	exit;
}

function env_or_default( $name, $default ) {
	$value = getenv( $name );

	return false === $value || '' === $value ? $default : $value;
}

define( 'ABSPATH', rtrim( env_or_default( 'WP_CORE_DIR', dirname( __DIR__ ) . '/vendor/wordpress' ), '/' ) . '/' );

define( 'DB_NAME', env_or_default( 'WP_TESTS_DB_NAME', 'wordpress_test' ) );
define( 'DB_USER', env_or_default( 'WP_TESTS_DB_USER', 'root' ) );
define( 'DB_PASSWORD', env_or_default( 'WP_TESTS_DB_PASSWORD', 'root' ) );
define( 'DB_HOST', env_or_default( 'WP_TESTS_DB_HOST', 'mariadb' ) );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- the WordPress test suite reads this global.
$table_prefix = env_or_default( 'WP_TESTS_TABLE_PREFIX', 'wptests_' );

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'LibreSign Test Site' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WPLANG', '' );

define( 'WP_DEBUG', true );

define( 'AUTH_KEY', 'libresign-theme-tests-auth-key' );
define( 'SECURE_AUTH_KEY', 'libresign-theme-tests-secure-auth-key' );
define( 'LOGGED_IN_KEY', 'libresign-theme-tests-logged-in-key' );
define( 'NONCE_KEY', 'libresign-theme-tests-nonce-key' );
define( 'AUTH_SALT', 'libresign-theme-tests-auth-salt' );
define( 'SECURE_AUTH_SALT', 'libresign-theme-tests-secure-auth-salt' );
define( 'LOGGED_IN_SALT', 'libresign-theme-tests-logged-in-salt' );
define( 'NONCE_SALT', 'libresign-theme-tests-nonce-salt' );
