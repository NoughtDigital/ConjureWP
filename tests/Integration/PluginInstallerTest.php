<?php

test('plugin installer class file exists', function () {
    $installerFile = getPluginPath('includes/class-conjure-plugin-installer.php');
    expect(file_exists($installerFile))->toBeTrue();
});

test('plugin installer class file has required structure', function () {
    $installerFile = getPluginPath('includes/class-conjure-plugin-installer.php');
    $content = file_get_contents($installerFile);
    
    expect($content)->toContain('class Conjure_Plugin_Installer');
    expect($content)->toContain('function install');
    expect($content)->toContain('function activate');
});

test('demo plugin manager class exists', function () {
    require_once getPluginPath('includes/class-conjure-demo-plugin-manager.php');
    expect(class_exists('Conjure_Demo_Plugin_Manager'))->toBeTrue();
});

test('demo plugin manager has required plugins method', function () {
    require_once getPluginPath('includes/class-conjure-demo-plugin-manager.php');
    
    $methods = get_class_methods('Conjure_Demo_Plugin_Manager');
    $hasPluginMethods = false;
    
    foreach ($methods as $method) {
        if (stripos($method, 'plugin') !== false) {
            $hasPluginMethods = true;
            break;
        }
    }
    
    expect($hasPluginMethods)->toBeTrue();
});

test('theme plugins class exists', function () {
    require_once getPluginPath('includes/class-conjure-theme-plugins.php');
    expect(class_exists('Conjure_Theme_Plugins'))->toBeTrue();
});

test('theme plugins can load plugins json', function () {
    require_once getPluginPath('includes/class-conjure-theme-plugins.php');
    
    $methods = get_class_methods('Conjure_Theme_Plugins');
    $hasLoadMethod = false;
    
    foreach ($methods as $method) {
        if (stripos($method, 'load') !== false || stripos($method, 'get') !== false) {
            $hasLoadMethod = true;
            break;
        }
    }
    
    expect($hasLoadMethod)->toBeTrue();
});

test('downloader class exists for remote content', function () {
    require_once getPluginPath('includes/class-conjure-downloader.php');
    expect(class_exists('Conjure_Downloader'))->toBeTrue();
});

test('downloader has download method in file', function () {
    $downloaderFile = getPluginPath('includes/class-conjure-downloader.php');
    $content = file_get_contents($downloaderFile);
    
    expect($content)->toContain('function download');
});

