<?php

test('conjure wizard ui class file exists', function () {
    $classFile = getPluginPath('includes/class-conjure-wizard-ui.php');
    expect(file_exists($classFile))->toBeTrue();
});

test('conjure wizard ui class can be loaded', function () {
    require_once getPluginPath('includes/class-conjure-wizard-ui.php');
    expect(class_exists('Conjure_Wizard_UI'))->toBeTrue();
});

test('conjure wizard ui class has required methods', function () {
    require_once getPluginPath('includes/class-conjure-wizard-ui.php');
    
    $requiredMethods = [
        '__construct',
        'header',
        'body',
        'footer',
        'step_output',
        'step_link',
        'step_next_link',
        'svg_sprite',
        'svg',
        'svg_allowed_html',
        'loading_spinner',
        'loading_spinner_allowed_html',
    ];
    
    foreach ($requiredMethods as $method) {
        expect(method_exists('Conjure_Wizard_UI', $method))->toBeTrue();
    }
});

