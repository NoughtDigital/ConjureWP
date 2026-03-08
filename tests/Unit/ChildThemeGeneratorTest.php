<?php

test('conjure child theme generator class file exists', function () {
    $classFile = conjurewp_test_get_plugin_path('includes/class-conjure-child-theme-generator.php');
    expect(file_exists($classFile))->toBeTrue();
});

test('conjure child theme generator class can be loaded', function () {
    require_once conjurewp_test_get_plugin_path('includes/class-conjure-child-theme-generator.php');
    expect(class_exists('Conjure_Child_Theme_Generator'))->toBeTrue();
});

test('conjure child theme generator class has required methods', function () {
    require_once conjurewp_test_get_plugin_path('includes/class-conjure-child-theme-generator.php');
    
    $requiredMethods = [
        '__construct',
        'generate_child',
        'generate_child_functions_php',
        'generate_child_style_css',
        'generate_child_screenshot',
    ];
    
    foreach ($requiredMethods as $method) {
        expect(method_exists('Conjure_Child_Theme_Generator', $method))->toBeTrue();
    }
});

