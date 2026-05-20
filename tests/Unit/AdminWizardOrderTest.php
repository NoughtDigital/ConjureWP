<?php

test('every enabled connector appears in admin wizard order without pro', function () {
	require_once conjurewp_test_get_plugin_path( 'includes/class-conjure-connector-catalog.php' );
	require_once conjurewp_test_get_plugin_path( 'includes/class-conjure-step-connector-base.php' );
	require_once conjurewp_test_get_plugin_path( 'includes/class-conjure-step-connector-manager.php' );
	require_once conjurewp_test_get_plugin_path( 'steps/acf/class-conjure-step-connector-acf.php' );
	require_once conjurewp_test_get_plugin_path( 'steps/woocommerce/class-conjure-step-connector-woocommerce.php' );
	require_once conjurewp_test_get_plugin_path( 'steps/bricks/class-conjure-step-connector-bricks.php' );

	$deny = static function () {
		return false;
	};

	add_filter( 'conjure_connector_has_pro_license', $deny, 99 );

	$conjure_stub = new class() {
		public $slug = 'test-theme';
		public $base_path = '';
		public $directory = '';
		public $steps = array(
			'welcome' => array( 'name' => 'Welcome' ),
			'license' => array( 'name' => 'License' ),
			'content' => array( 'name' => 'Content' ),
			'ready'   => array( 'name' => 'Ready' ),
		);
	};

	$manager = new Conjure_Step_Connector_Manager( $conjure_stub );

	$manager_reflection = new ReflectionClass( $manager );
	$connectors_property = $manager_reflection->getProperty( 'connectors' );
	$connectors_property->setAccessible( true );
	$connectors_property->setValue(
		$manager,
		array(
			'acf' => new Conjure_Step_Connector_ACF(
				$conjure_stub,
				array(
					'id'        => 'acf',
					'step_key'  => 'acf',
					'step_name' => 'ACF',
				)
			),
			'woocommerce' => new Conjure_Step_Connector_WooCommerce(
				$conjure_stub,
				array(
					'id'        => 'woocommerce',
					'step_key'  => 'woocommerce',
					'step_name' => 'WooCommerce',
				)
			),
			'bricks' => new Conjure_Step_Connector_Bricks(
				$conjure_stub,
				array(
					'id'        => 'bricks',
					'step_key'  => 'bricks',
					'step_name' => 'Bricks',
				)
			),
		)
	);

	$manager->save_settings(
		array(
			'acf'         => array( 'enabled' => true, 'features' => array() ),
			'woocommerce' => array( 'enabled' => true, 'features' => array() ),
			'bricks'      => array( 'enabled' => false, 'features' => array() ),
		)
	);

	$steps = $manager->build_admin_wizard_order_steps( $conjure_stub->steps );

	expect( $steps )->toHaveKeys( array( 'acf', 'woocommerce' ) );
	expect( $steps )->not->toHaveKey( 'bricks' );

	remove_filter( 'conjure_connector_has_pro_license', $deny, 99 );
});

test('enabled connector without features still shows in admin order but not live wizard', function () {
	require_once conjurewp_test_get_plugin_path( 'includes/class-conjure-connector-catalog.php' );
	require_once conjurewp_test_get_plugin_path( 'includes/class-conjure-step-connector-base.php' );
	require_once conjurewp_test_get_plugin_path( 'steps/acf/class-conjure-step-connector-acf.php' );

	$deny = static function () {
		return false;
	};

	add_filter( 'conjure_connector_has_pro_license', $deny, 99 );

	$conjure_stub = new class() {
		public $slug = 'test-theme';
	};

	$connector = new Conjure_Step_Connector_ACF(
		$conjure_stub,
		array(
			'id'        => 'acf',
			'step_key'  => 'acf',
			'step_name' => 'ACF',
		)
	);

	$settings = array(
		'enabled'  => true,
		'features' => array(
			'field_group_config' => false,
			'starter_field_groups' => false,
			'json_sync' => false,
			'options_pages' => false,
			'content_structure' => false,
		),
	);

	expect( $connector->should_show_in_admin_wizard_order( $settings ) )->toBeTrue();
	expect( $connector->has_enabled_features( $settings ) )->toBeFalse();
	expect( $connector->should_show_in_wizard( $settings ) )->toBeFalse();
	expect( $connector->get_admin_order_step_definition( $settings ) )->not->toBeEmpty();

	remove_filter( 'conjure_connector_has_pro_license', $deny, 99 );
});
