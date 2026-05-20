<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

test('conjurewp_safe_error_message hides details in production mode', function () {
	require_once conjurewp_test_get_plugin_path('includes/conjurewp-runtime.php');

	$error = new Exception('internal database path leak');

	expect(conjurewp_safe_error_message($error))->not->toContain('database');
});

test('conjurewp_safe_import_message hides wp error details in production mode', function () {
	require_once conjurewp_test_get_plugin_path('includes/conjurewp-runtime.php');

	$error = new WP_Error( 'test', '/var/www/secret/path failed' );

	expect( conjurewp_safe_import_message( $error, 'Import failed.' ) )->toBe( 'Import failed.' );
});
