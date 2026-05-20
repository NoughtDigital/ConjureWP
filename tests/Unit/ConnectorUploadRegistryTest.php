<?php

test('conjure connector upload registry loads and defines expected slugs', function () {
	require_once conjurewp_test_get_plugin_path( 'includes/class-conjure-connector-upload-registry.php' );

	$definitions = Conjure_Connector_Upload_Registry::get_definitions();

	expect( $definitions )->toHaveKeys(
		array(
			'elementor',
			'cf7',
			'wp_rocket',
			'whippet',
			'wpforms',
			'bricks',
			'litespeed',
			'yoast_seo',
			'rank_math',
			'woocommerce',
		)
	);

	expect( $definitions['elementor']['section']['accept'] )->toBe( '.json,.zip' );
	expect( $definitions['cf7']['extensions'] )->toContain( 'txt' );
});

test('conjure connector upload registry discovers demo elementor file', function () {
	require_once conjurewp_test_get_plugin_path( 'includes/class-conjure-connector-upload-registry.php' );

	$config = Conjure_Connector_Upload_Registry::discover_demo_files(
		array(),
		'/demo/path/'
	);

	expect( $config )->toBe( array() );
});
