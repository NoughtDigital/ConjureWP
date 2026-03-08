<?php

test('freemius class exists and can be loaded', function () {
    $freemiusFile = conjurewp_test_get_plugin_path('includes/class-conjure-freemius.php');
    expect(file_exists($freemiusFile))->toBeTrue();
    
    require_once $freemiusFile;
    expect(class_exists('Conjure_Freemius'))->toBeTrue();
});

test('freemius has is_open_source_theme method', function () {
    require_once conjurewp_test_get_plugin_path('includes/class-conjure-freemius.php');
    
    expect(method_exists('Conjure_Freemius', 'is_open_source_theme'))->toBeTrue();
});

test('freemius is_open_source_theme returns boolean', function () {
    require_once conjurewp_test_get_plugin_path('includes/class-conjure-freemius.php');
    
    $result = Conjure_Freemius::is_open_source_theme();
    expect($result)->toBeBool();
});

test('freemius has has_free_access method', function () {
    require_once conjurewp_test_get_plugin_path('includes/class-conjure-freemius.php');
    
    expect(method_exists('Conjure_Freemius', 'has_free_access'))->toBeTrue();
});

test('freemius free version grants access to all users', function () {
    require_once conjurewp_test_get_plugin_path('includes/class-conjure-freemius.php');
    if ( function_exists( 'con_fs' ) && ! Conjure_Freemius::has_free_access() ) {
        throw new \PHPUnit\Framework\SkippedWithMessageException( 'Premium build: access depends on license' );
    }
    $hasAccess = Conjure_Freemius::has_free_access();
    expect($hasAccess)->toBeTrue();
});

test('freemius has can_auto_install_plugins method', function () {
    require_once conjurewp_test_get_plugin_path('includes/class-conjure-freemius.php');
    
    expect(method_exists('Conjure_Freemius', 'can_auto_install_plugins'))->toBeTrue();
});

test('freemius free version denies auto plugin installation', function () {
    require_once conjurewp_test_get_plugin_path('includes/class-conjure-freemius.php');
    
    $canAutoInstall = Conjure_Freemius::can_auto_install_plugins();
    expect($canAutoInstall)->toBeFalse();
});

test('freemius has can_use_advanced_imports method', function () {
    require_once conjurewp_test_get_plugin_path('includes/class-conjure-freemius.php');
    
    expect(method_exists('Conjure_Freemius', 'can_use_advanced_imports'))->toBeTrue();
});

test('freemius allows advanced imports in free version', function () {
    require_once conjurewp_test_get_plugin_path('includes/class-conjure-freemius.php');
    if ( function_exists( 'con_fs' ) && ! Conjure_Freemius::can_use_advanced_imports() ) {
        throw new \PHPUnit\Framework\SkippedWithMessageException( 'Premium build: advanced imports depend on license' );
    }
    $canUseAdvanced = Conjure_Freemius::can_use_advanced_imports();
    expect($canUseAdvanced)->toBeTrue();
});

test('freemius has has_lifetime_integration method', function () {
    require_once conjurewp_test_get_plugin_path('includes/class-conjure-freemius.php');
    
    expect(method_exists('Conjure_Freemius', 'has_lifetime_integration'))->toBeTrue();
});

test('freemius lifetime integration returns boolean', function () {
    require_once conjurewp_test_get_plugin_path('includes/class-conjure-freemius.php');
    
    $result = Conjure_Freemius::has_lifetime_integration();
    expect($result)->toBeBool();
});




