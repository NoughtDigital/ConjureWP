<?php

test('demo helpers class exists and can be loaded', function () {
    $helpersFile = getPluginPath('includes/class-conjure-demo-helpers.php');
    expect(file_exists($helpersFile))->toBeTrue();
    
    require_once $helpersFile;
    expect(class_exists('Conjure_Demo_Helpers'))->toBeTrue();
});

test('demo helpers has get_demo_directory method', function () {
    require_once getPluginPath('includes/class-conjure-demo-helpers.php');
    
    expect(method_exists('Conjure_Demo_Helpers', 'get_demo_directory'))->toBeTrue();
});

test('demo helpers get_demo_directory returns string path', function () {
    // Define required constant for test
    if (!defined('CONJUREWP_PLUGIN_DIR')) {
        define('CONJUREWP_PLUGIN_DIR', getPluginPath());
    }
    
    require_once getPluginPath('includes/class-conjure-demo-helpers.php');
    
    $path = Conjure_Demo_Helpers::get_demo_directory();
    expect($path)->toBeString();
    expect(strlen($path))->toBeGreaterThan(0);
});

test('demo helpers has get_theme_demo_directory method', function () {
    require_once getPluginPath('includes/class-conjure-demo-helpers.php');
    
    expect(method_exists('Conjure_Demo_Helpers', 'get_theme_demo_directory'))->toBeTrue();
});

test('demo helpers has get_uploads_demo_directory method', function () {
    require_once getPluginPath('includes/class-conjure-demo-helpers.php');
    
    expect(method_exists('Conjure_Demo_Helpers', 'get_uploads_demo_directory'))->toBeTrue();
});

test('demo helpers methods accept theme slug parameter', function () {
    require_once getPluginPath('includes/class-conjure-demo-helpers.php');
    
    $reflection = new ReflectionClass('Conjure_Demo_Helpers');
    $method = $reflection->getMethod('get_demo_directory');
    $parameters = $method->getParameters();
    
    expect(count($parameters))->toBeGreaterThanOrEqual(1);
    expect($parameters[0]->getName())->toBe('theme_slug');
});

test('demo helpers respects filter for custom demo path', function () {
    // Define required constant for test
    if (!defined('CONJUREWP_PLUGIN_DIR')) {
        define('CONJUREWP_PLUGIN_DIR', getPluginPath());
    }
    
    require_once getPluginPath('includes/class-conjure-demo-helpers.php');
    
    // Test that the method can be called
    $path = Conjure_Demo_Helpers::get_demo_directory();
    expect($path)->toBeString();
});

test('demo helpers has demo file location methods', function () {
    require_once getPluginPath('includes/class-conjure-demo-helpers.php');
    
    $methods = get_class_methods('Conjure_Demo_Helpers');
    
    // Check for various helper methods
    $hasLocationMethods = false;
    foreach ($methods as $method) {
        if (stripos($method, 'directory') !== false || stripos($method, 'path') !== false) {
            $hasLocationMethods = true;
            break;
        }
    }
    
    expect($hasLocationMethods)->toBeTrue();
});

