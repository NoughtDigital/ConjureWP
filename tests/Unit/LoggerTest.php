<?php

test('logger class file exists', function () {
    $loggerFile = getPluginPath('includes/class-conjure-logger.php');
    expect(file_exists($loggerFile))->toBeTrue();
});

test('logger class can be loaded', function () {
    require_once getPluginPath('includes/class-conjure-logger.php');
    expect(class_exists('Conjure_Logger'))->toBeTrue();
});

test('logger has get_instance method', function () {
    require_once getPluginPath('includes/class-conjure-logger.php');
    expect(method_exists('Conjure_Logger', 'get_instance'))->toBeTrue();
});

test('logger has required logging methods', function () {
    require_once getPluginPath('includes/class-conjure-logger.php');
    
    $methods = ['info', 'error', 'warning', 'debug'];
    
    foreach ($methods as $method) {
        expect(method_exists('Conjure_Logger', $method))->toBeTrue();
    }
});

