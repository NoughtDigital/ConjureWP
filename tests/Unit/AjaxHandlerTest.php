<?php

test('conjure ajax handler class file exists', function () {
    $classFile = getPluginPath('includes/class-conjure-ajax-handler.php');
    expect(file_exists($classFile))->toBeTrue();
});

test('conjure ajax handler class can be loaded', function () {
    require_once getPluginPath('includes/class-conjure-ajax-handler.php');
    expect(class_exists('Conjure_Ajax_Handler'))->toBeTrue();
});

test('conjure ajax handler class has required methods', function () {
    require_once getPluginPath('includes/class-conjure-ajax-handler.php');
    
    $requiredMethods = [
        '__construct',
        'register_ajax_handlers',
    ];
    
    foreach ($requiredMethods as $method) {
        expect(method_exists('Conjure_Ajax_Handler', $method))->toBeTrue();
    }
});

