<?php

test('plugin file exists', function () {
    $pluginFile = getPluginPath('conjurewp.php');
    expect(file_exists($pluginFile))->toBeTrue();
});

test('plugin has correct headers', function () {
    $pluginFile = getPluginPath('conjurewp.php');
    $pluginData = get_file_data($pluginFile, [
        'Name' => 'Plugin Name',
        'PluginURI' => 'Plugin URI',
        'Version' => 'Version',
        'Description' => 'Description',
        'Author' => 'Author',
        'AuthorURI' => 'Author URI',
        'TextDomain' => 'Text Domain',
        'DomainPath' => 'Domain Path',
        'RequiresWP' => 'Requires at least',
        'RequiresPHP' => 'Requires PHP',
    ]);

    expect($pluginData['Name'])->toBe('ConjureWP - WordPress Setup Wizard');
    expect($pluginData['Version'])->toBe('1.0.0');
    expect($pluginData['TextDomain'])->toBe('conjurewp');
    expect($pluginData['RequiresPHP'])->toBe('7.4');
});

test('composer autoload file exists', function () {
    $autoloadFile = getPluginPath('vendor/autoload.php');
    expect(file_exists($autoloadFile))->toBeTrue();
});

test('main class file exists', function () {
    $classFile = getPluginPath('class-conjure.php');
    expect(file_exists($classFile))->toBeTrue();
});

test('includes directory exists with core classes', function () {
    $includesDir = getPluginPath('includes');
    expect(is_dir($includesDir))->toBeTrue();
    
    $coreClasses = [
        'class-conjure-logger.php',
        'class-conjure-importer.php',
        'class-conjure-hooks.php',
        'class-conjure-admin-tools.php',
        'class-conjure-cli.php',
    ];
    
    foreach ($coreClasses as $class) {
        expect(file_exists($includesDir . '/' . $class))->toBeTrue();
    }
});

function get_file_data($file, $headers) {
    $file_data = file_get_contents($file);
    $file_data = str_replace("\r", "\n", $file_data);
    $all_headers = [];
    
    foreach ($headers as $field => $regex) {
        if (preg_match('/^[ \t\/*#@]*' . preg_quote($regex, '/') . ':(.*)$/mi', $file_data, $match) && $match[1]) {
            $all_headers[$field] = trim(preg_replace('/\s*(?:\*\/|\?>).*/', '', $match[1]));
        } else {
            $all_headers[$field] = '';
        }
    }
    
    return $all_headers;
}


