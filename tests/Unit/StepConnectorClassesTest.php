<?php

test('step connector base class file exists', function () {
    $classFile = conjurewp_test_get_plugin_path('includes/class-conjure-step-connector-base.php');
    expect(file_exists($classFile))->toBeTrue();
});

test('step connector manager class file exists', function () {
    $classFile = conjurewp_test_get_plugin_path('includes/class-conjure-step-connector-manager.php');
    expect(file_exists($classFile))->toBeTrue();
});

test('step connectors admin class file exists', function () {
    $classFile = conjurewp_test_get_plugin_path('includes/class-conjure-step-connectors-admin.php');
    expect(file_exists($classFile))->toBeTrue();
});

test('step connector classes can be loaded', function () {
    require_once conjurewp_test_get_plugin_path('includes/class-conjure-step-connector-base.php');
    require_once conjurewp_test_get_plugin_path('includes/class-conjure-step-connector-manager.php');
    require_once conjurewp_test_get_plugin_path('includes/class-conjure-step-connectors-admin.php');

    expect(class_exists('Conjure_Step_Connector_Base'))->toBeTrue();
    expect(class_exists('Conjure_Step_Connector_Manager'))->toBeTrue();
    expect(class_exists('Conjure_Step_Connectors_Admin'))->toBeTrue();
});

test('WooCommerce connector class can be loaded', function () {
    require_once conjurewp_test_get_plugin_path('includes/class-conjure-step-connector-base.php');
    require_once conjurewp_test_get_plugin_path('steps/woocommerce/class-conjure-step-connector-woocommerce.php');

    expect(class_exists('Conjure_Step_Connector_WooCommerce'))->toBeTrue();
});

test('WooCommerce connector definition file returns valid array', function () {
    $definition = include conjurewp_test_get_plugin_path('steps/woocommerce/connector.php');

    expect($definition)->toBeArray();
    expect($definition)->toHaveKeys(['id', 'name', 'description', 'step_key', 'class_file', 'class_name', 'plugin']);
    expect($definition['id'])->toBe('woocommerce');
    expect($definition['class_name'])->toBe('Conjure_Step_Connector_WooCommerce');
});

test('WooCommerce connector registers all expected features', function () {
    require_once conjurewp_test_get_plugin_path('includes/class-conjure-step-connector-base.php');
    require_once conjurewp_test_get_plugin_path('steps/woocommerce/class-conjure-step-connector-woocommerce.php');

    $class = new ReflectionClass('Conjure_Step_Connector_WooCommerce');
    $method = $class->getMethod('get_features');

    $connector = $class->newInstanceWithoutConstructor();
    $features = $method->invoke($connector);

    $expected_features = [
        'set_currency',
        'set_store_location',
        'set_measurement_units',
        'assign_shop_page',
        'assign_cart_page',
        'assign_checkout_page',
        'assign_myaccount_page',
        'configure_tax',
        'enable_cod_payment',
        'configure_checkout_accounts',
        'set_catalog_defaults',
        'flush_rewrite_rules',
    ];

    expect(array_keys($features))->toBe($expected_features);

    foreach ($features as $feature_id => $feature) {
        expect($feature)->toHaveKeys(['label', 'description', 'default_enabled']);
        expect($feature['label'])->toBeString()->not->toBeEmpty();
        expect($feature['description'])->toBeString()->not->toBeEmpty();
        expect($feature['default_enabled'])->toBeBool();
    }
});

test('WooCommerce connector has feature groups covering all features', function () {
    require_once conjurewp_test_get_plugin_path('includes/class-conjure-step-connector-base.php');
    require_once conjurewp_test_get_plugin_path('steps/woocommerce/class-conjure-step-connector-woocommerce.php');

    $class = new ReflectionClass('Conjure_Step_Connector_WooCommerce');
    $groups_property = $class->getProperty('feature_groups');
    $groups_property->setAccessible(true);
    $groups = $groups_property->getValue();

    $features_method = $class->getMethod('get_features');
    $connector = $class->newInstanceWithoutConstructor();
    $features = $features_method->invoke($connector);

    $grouped_feature_ids = [];
    foreach ($groups as $group) {
        $grouped_feature_ids = array_merge($grouped_feature_ids, $group['features']);
    }

    foreach (array_keys($features) as $feature_id) {
        expect($grouped_feature_ids)->toContain($feature_id);
    }
});
