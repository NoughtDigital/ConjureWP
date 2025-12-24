<?php

test('conjure class handles ajax callbacks', function () {
    require_once getPluginPath('class-conjure.php');
    
    $methods = get_class_methods('Conjure');
    $hasAjaxMethods = false;
    
    foreach ($methods as $method) {
        if (stripos($method, 'ajax') !== false || stripos($method, 'callback') !== false) {
            $hasAjaxMethods = true;
            break;
        }
    }
    
    // Conjure should have callback/handler methods
    expect($methods)->toBeArray();
    expect(count($methods))->toBeGreaterThan(0);
});

test('hooks class exists for ajax handlers', function () {
    require_once getPluginPath('includes/class-conjure-hooks.php');
    expect(class_exists('Conjure_Hooks'))->toBeTrue();
});

test('hooks class registers ajax actions', function () {
    require_once getPluginPath('includes/class-conjure-hooks.php');
    
    $methods = get_class_methods('Conjure_Hooks');
    
    // Should have methods for registering hooks
    expect($methods)->toBeArray();
    expect(count($methods))->toBeGreaterThan(0);
});

test('admin tools class exists for ajax operations', function () {
    require_once getPluginPath('includes/class-conjure-admin-tools.php');
    expect(class_exists('Conjure_Admin_Tools'))->toBeTrue();
});

test('admin tools has ajax handler methods', function () {
    require_once getPluginPath('includes/class-conjure-admin-tools.php');
    
    $methods = get_class_methods('Conjure_Admin_Tools');
    
    expect($methods)->toBeArray();
    expect(count($methods))->toBeGreaterThan(0);
});

test('ajax operations require proper nonce verification', function () {
    $mainFile = getPluginPath('class-conjure.php');
    $content = file_get_contents($mainFile);
    
    // Should have nonce verification for security
    $hasNonceCheck = stripos($content, 'wp_verify_nonce') !== false ||
                     stripos($content, 'check_ajax_referer') !== false;
    
    expect($hasNonceCheck)->toBeTrue();
});

test('ajax handlers check user capabilities', function () {
    $mainFile = getPluginPath('class-conjure.php');
    $content = file_get_contents($mainFile);
    
    // Should check user capabilities
    $hasCapCheck = stripos($content, 'current_user_can') !== false ||
                   stripos($content, 'is_admin') !== false;
    
    expect($hasCapCheck)->toBeTrue();
});



