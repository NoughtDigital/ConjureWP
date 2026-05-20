<?php

test('connector catalogue lists fifteen shipped connectors', function () {
	require_once conjurewp_test_get_plugin_path( 'includes/class-conjure-connector-catalog.php' );

	expect( Conjure_Connector_Catalog::CONNECTORS )->toHaveCount( 15 );
});

test('connectors can always be configured in admin', function () {
	require_once conjurewp_test_get_plugin_path( 'includes/class-conjure-connector-catalog.php' );

	$deny = static function () {
		return false;
	};

	add_filter( 'conjure_connector_has_pro_license', $deny, 99 );

	expect( Conjure_Connector_Catalog::can_configure_connector( 'woocommerce' ) )->toBeTrue();
	expect( Conjure_Connector_Catalog::can_configure_connector( 'yoast-seo' ) )->toBeTrue();
});

test('connectors only join the wizard when pro is active', function () {
	require_once conjurewp_test_get_plugin_path( 'includes/class-conjure-connector-catalog.php' );

	$deny = static function () {
		return false;
	};

	add_filter( 'conjure_connector_has_pro_license', $deny, 99 );

	expect( Conjure_Connector_Catalog::can_show_connector_in_wizard( 'woocommerce' ) )->toBeFalse();

	remove_filter( 'conjure_connector_has_pro_license', $deny, 99 );
});

test('native sync supports every on-disk connector id', function () {
	require_once conjurewp_test_get_plugin_path( 'includes/class-conjure-connector-catalog.php' );
	require_once conjurewp_test_get_plugin_path( 'includes/class-conjure-connector-native-sync.php' );

	foreach ( array_keys( Conjure_Connector_Catalog::CONNECTORS ) as $connector_id ) {
		expect( Conjure_Connector_Native_Sync::supports( $connector_id ) )->toBeTrue();
	}
});

test('marketing-only connectors are documented separately from disk catalogue', function () {
	require_once conjurewp_test_get_plugin_path( 'includes/class-conjure-connector-catalog.php' );

	expect( array_keys( Conjure_Connector_Catalog::MARKETING_ONLY ) )->toBe(
		array(
			'pdf',
			'code-snippets',
			'custom-posts',
			'memberpress',
			'shortpixel',
		)
	);

	foreach ( array_keys( Conjure_Connector_Catalog::MARKETING_ONLY ) as $id ) {
		expect( Conjure_Connector_Catalog::CONNECTORS )->not->toHaveKey( $id );
	}
});
