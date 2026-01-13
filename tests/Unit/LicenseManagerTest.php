<?php

test('conjure license manager class file exists', function () {
    $classFile = getPluginPath('includes/class-conjure-license-manager.php');
    expect(file_exists($classFile))->toBeTrue();
});

test('conjure license manager class can be loaded', function () {
    require_once getPluginPath('includes/class-conjure-license-manager.php');
    expect(class_exists('Conjure_License_Manager'))->toBeTrue();
});

test('conjure license manager class has required methods', function () {
    require_once getPluginPath('includes/class-conjure-license-manager.php');
    
    $requiredMethods = [
        '__construct',
        'ajax_activate_license',
        'freemius_activate_license',
        'edd_activate_license',
        'grant_access_for_valid_edd_license',
        'is_theme_registered',
    ];
    
    foreach ($requiredMethods as $method) {
        expect(method_exists('Conjure_License_Manager', $method))->toBeTrue();
    }
});

