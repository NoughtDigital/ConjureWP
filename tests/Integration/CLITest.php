<?php

test('cli class exists and can be loaded', function () {
    $cliFile = getPluginPath('includes/class-conjure-cli.php');
    expect(file_exists($cliFile))->toBeTrue();
    
    require_once $cliFile;
    expect(class_exists('Conjure_CLI'))->toBeTrue();
});

test('cli has list_demos command', function () {
    require_once getPluginPath('includes/class-conjure-cli.php');
    
    expect(method_exists('Conjure_CLI', 'list_demos'))->toBeTrue();
});

test('cli has import command', function () {
    require_once getPluginPath('includes/class-conjure-cli.php');
    
    // The import command is typically named 'import' or similar
    $methods = get_class_methods('Conjure_CLI');
    $hasImportCommand = false;
    
    foreach ($methods as $method) {
        if (stripos($method, 'import') !== false) {
            $hasImportCommand = true;
            break;
        }
    }
    
    expect($hasImportCommand)->toBeTrue();
});

test('cli class requires conjure instance', function () {
    require_once getPluginPath('includes/class-conjure-cli.php');
    
    $reflection = new ReflectionClass('Conjure_CLI');
    $constructor = $reflection->getConstructor();
    $parameters = $constructor->getParameters();
    
    expect(count($parameters))->toBeGreaterThan(0);
    expect($parameters[0]->getName())->toBe('conjure');
});

test('cli has logger property', function () {
    require_once getPluginPath('includes/class-conjure-cli.php');
    
    $reflection = new ReflectionClass('Conjure_CLI');
    expect($reflection->hasProperty('logger'))->toBeTrue();
});

test('cli has conjure property', function () {
    require_once getPluginPath('includes/class-conjure-cli.php');
    
    $reflection = new ReflectionClass('Conjure_CLI');
    expect($reflection->hasProperty('conjure'))->toBeTrue();
});

test('cli list_demos accepts required parameters', function () {
    require_once getPluginPath('includes/class-conjure-cli.php');
    
    $reflection = new ReflectionClass('Conjure_CLI');
    $method = $reflection->getMethod('list_demos');
    $parameters = $method->getParameters();
    
    expect(count($parameters))->toBe(2);
    expect($parameters[0]->getName())->toBe('args');
    expect($parameters[1]->getName())->toBe('assoc_args');
});




