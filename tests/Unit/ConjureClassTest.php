<?php

test('conjure main class file exists', function () {
    $classFile = getPluginPath('class-conjure.php');
    expect(file_exists($classFile))->toBeTrue();
});

test('conjure class can be loaded', function () {
    require_once getPluginPath('class-conjure.php');
    expect(class_exists('Conjure'))->toBeTrue();
});

test('conjure class has required methods', function () {
    require_once getPluginPath('class-conjure.php');
    
    $requiredMethods = [
        '__construct',
        'get_import_data_info',
        'get_import_files_paths',
        'get_import_steps_html',
        'get_step_completion_state',
    ];
    
    foreach ($requiredMethods as $method) {
        expect(method_exists('Conjure', $method))->toBeTrue();
    }
});

test('conjure class has import_files property', function () {
    require_once getPluginPath('class-conjure.php');
    
    $reflection = new ReflectionClass('Conjure');
    expect($reflection->hasProperty('import_files'))->toBeTrue();
});

