<?php

test('server health class exists and can be loaded', function () {
    $healthFile = getPluginPath('includes/class-conjure-server-health.php');
    expect(file_exists($healthFile))->toBeTrue();
    
    require_once $healthFile;
    expect(class_exists('Conjure_Server_Health'))->toBeTrue();
});

test('server health can be instantiated', function () {
    require_once getPluginPath('includes/class-conjure-server-health.php');
    
    $health = new Conjure_Server_Health();
    expect($health)->toBeInstanceOf('Conjure_Server_Health');
});

test('server health accepts custom memory limit', function () {
    require_once getPluginPath('includes/class-conjure-server-health.php');
    
    $health = new Conjure_Server_Health(512, 600);
    expect($health)->toBeInstanceOf('Conjure_Server_Health');
});

test('server health has is_enabled method', function () {
    require_once getPluginPath('includes/class-conjure-server-health.php');
    
    expect(method_exists('Conjure_Server_Health', 'is_enabled'))->toBeTrue();
});

test('server health is_enabled returns boolean', function () {
    require_once getPluginPath('includes/class-conjure-server-health.php');
    
    $health = new Conjure_Server_Health();
    $enabled = $health->is_enabled();
    
    expect($enabled)->toBeBool();
});

test('server health has get_min_memory_limit method', function () {
    require_once getPluginPath('includes/class-conjure-server-health.php');
    
    expect(method_exists('Conjure_Server_Health', 'get_min_memory_limit'))->toBeTrue();
});

test('server health returns correct minimum memory limit', function () {
    require_once getPluginPath('includes/class-conjure-server-health.php');
    
    $health = new Conjure_Server_Health(512, 600);
    $minMemory = $health->get_min_memory_limit();
    
    expect($minMemory)->toBe(512);
});

test('server health has get_min_execution_time method', function () {
    require_once getPluginPath('includes/class-conjure-server-health.php');
    
    expect(method_exists('Conjure_Server_Health', 'get_min_execution_time'))->toBeTrue();
});

test('server health returns correct minimum execution time', function () {
    require_once getPluginPath('includes/class-conjure-server-health.php');
    
    $health = new Conjure_Server_Health(256, 450);
    $minExecution = $health->get_min_execution_time();
    
    expect($minExecution)->toBe(450);
});

test('server health uses default values when zero provided', function () {
    require_once getPluginPath('includes/class-conjure-server-health.php');
    
    $health = new Conjure_Server_Health(0, 0);
    
    expect($health->get_min_memory_limit())->toBeGreaterThan(0);
    expect($health->get_min_execution_time())->toBeGreaterThan(0);
});

test('server health has requirements check methods', function () {
    require_once getPluginPath('includes/class-conjure-server-health.php');
    
    $methods = get_class_methods('Conjure_Server_Health');
    $hasCheckMethods = false;
    
    foreach ($methods as $method) {
        if (stripos($method, 'check') !== false || stripos($method, 'meets') !== false) {
            $hasCheckMethods = true;
            break;
        }
    }
    
    expect($hasCheckMethods)->toBeTrue();
});




