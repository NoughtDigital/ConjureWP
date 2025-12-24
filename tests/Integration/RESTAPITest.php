<?php

test('rest api class exists and can be loaded', function () {
    $apiFile = getPluginPath('includes/class-conjure-rest-api.php');
    expect(file_exists($apiFile))->toBeTrue();
    
    require_once $apiFile;
    expect(class_exists('Conjure_REST_API'))->toBeTrue();
});

test('rest api has correct namespace', function () {
    require_once getPluginPath('includes/class-conjure-rest-api.php');
    
    $reflection = new ReflectionClass('Conjure_REST_API');
    $namespaceProperty = $reflection->getProperty('namespace');
    $namespaceProperty->setAccessible(true);
    
    // Create a mock Conjure instance
    $mockConjure = new stdClass();
    $mockConjure->logger = new stdClass();
    
    $api = new Conjure_REST_API($mockConjure);
    $namespace = $namespaceProperty->getValue($api);
    
    expect($namespace)->toBe('conjurewp/v1');
});

test('rest api has register_routes method', function () {
    require_once getPluginPath('includes/class-conjure-rest-api.php');
    
    expect(method_exists('Conjure_REST_API', 'register_routes'))->toBeTrue();
});

test('rest api has permission callback method', function () {
    require_once getPluginPath('includes/class-conjure-rest-api.php');
    
    expect(method_exists('Conjure_REST_API', 'check_permission'))->toBeTrue();
});

test('rest api has list_demos endpoint callback', function () {
    require_once getPluginPath('includes/class-conjure-rest-api.php');
    
    expect(method_exists('Conjure_REST_API', 'list_demos'))->toBeTrue();
});

test('rest api has import_demo endpoint callback', function () {
    require_once getPluginPath('includes/class-conjure-rest-api.php');
    
    expect(method_exists('Conjure_REST_API', 'import_demo'))->toBeTrue();
});

test('rest api class requires conjure instance', function () {
    require_once getPluginPath('includes/class-conjure-rest-api.php');
    
    $reflection = new ReflectionClass('Conjure_REST_API');
    $constructor = $reflection->getConstructor();
    $parameters = $constructor->getParameters();
    
    expect(count($parameters))->toBeGreaterThan(0);
    expect($parameters[0]->getName())->toBe('conjure');
});



