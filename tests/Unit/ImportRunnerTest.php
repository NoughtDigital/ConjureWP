<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

test('conjure import runner resolves demo by slug and index', function () {
	require_once conjurewp_test_get_plugin_path('includes/conjurewp-runtime.php');
	require_once conjurewp_test_get_plugin_path('includes/class-conjure-import-runner.php');

	$conjure = new stdClass();
	$conjure->import_files = array(
		array( 'import_file_name' => 'Agency Demo' ),
		array( 'import_file_name' => 'Shop Demo' ),
	);
	$conjure->logger = new stdClass();

	$runner = new Conjure_Import_Runner( $conjure );

	expect( $runner->find_demo_index( 1, $conjure->import_files ) )->toBe( 1 );
	expect( $runner->find_demo_index( 'agency-demo', $conjure->import_files ) )->toBe( 0 );
	expect( $runner->find_demo_index( 'missing', $conjure->import_files ) )->toBe( false );
});

test('conjurewp_json_decode rejects excessive nesting depth', function () {
	require_once conjurewp_test_get_plugin_path('includes/conjurewp-runtime.php');

	$nested = str_repeat( '{"a":', 40 ) . '1' . str_repeat( '}', 40 );

	expect( conjurewp_json_decode( $nested, true, 8 ) )->toBeNull();
});

test('conjurewp_rest_import_rate_limit blocks rapid repeat imports', function () {
	require_once conjurewp_test_get_plugin_path('includes/conjurewp-runtime.php');

	$user_id = 42;
	delete_transient( 'conjurewp_rest_import_rl_' . $user_id );

	expect( conjurewp_rest_import_rate_limit( $user_id ) )->toBe( true );
	expect( conjurewp_rest_import_rate_limit( $user_id ) )->toBeInstanceOf( WP_Error::class );

	delete_transient( 'conjurewp_rest_import_rl_' . $user_id );
});
