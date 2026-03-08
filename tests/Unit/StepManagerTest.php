<?php

test('conjure step manager class file exists', function () {
    $classFile = conjurewp_test_get_plugin_path('includes/class-conjure-step-manager.php');
    expect(file_exists($classFile))->toBeTrue();
});

test('conjure step manager class can be loaded', function () {
    require_once conjurewp_test_get_plugin_path('includes/class-conjure-step-manager.php');
    expect(class_exists('Conjure_Step_Manager'))->toBeTrue();
});

test('conjure step manager class has required methods', function () {
    require_once conjurewp_test_get_plugin_path('includes/class-conjure-step-manager.php');
    
    $requiredMethods = [
        '__construct',
        'get_step_completion_state',
        'mark_step_completed',
        'reset_step',
        'reset_all_steps',
        'add_admin_bar_rerun_menu',
        'handle_step_reset',
        'display_admin_notices',
    ];
    
    foreach ($requiredMethods as $method) {
        expect(method_exists('Conjure_Step_Manager', $method))->toBeTrue();
    }
});

