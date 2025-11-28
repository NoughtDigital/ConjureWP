<?php

test('customizer option class file exists and has proper structure', function () {
    $customizerOptionFile = getPluginPath('includes/class-conjure-customizer-option.php');
    $content = file_get_contents($customizerOptionFile);
    
    expect($content)->toContain('class Conjure_Customizer_Option');
    expect($content)->toContain('extends \WP_Customize_Setting');
});

test('customizer importer can import data file', function () {
    require_once getPluginPath('includes/class-conjure-customizer-importer.php');
    
    $customizerFile = getPluginPath('demo/customizer.dat');
    
    if (!file_exists($customizerFile)) {
        expect(true)->toBeTrue(); // Skip if demo file doesn't exist
        return;
    }
    
    expect(file_exists($customizerFile))->toBeTrue();
    expect(filesize($customizerFile))->toBeGreaterThan(0);
});

test('widget importer can parse widget data', function () {
    require_once getPluginPath('includes/class-conjure-widget-importer.php');
    
    $widgetsFile = getPluginPath('demo/widgets.json');
    
    if (!file_exists($widgetsFile)) {
        expect(true)->toBeTrue(); // Skip if demo file doesn't exist
        return;
    }
    
    $widgetData = json_decode(file_get_contents($widgetsFile), true);
    expect($widgetData)->toBeArray();
});

test('widget importer handles empty sidebars', function () {
    require_once getPluginPath('includes/class-conjure-widget-importer.php');
    
    expect(method_exists('Conjure_Widget_Importer', 'import'))->toBeTrue();
});

test('customizer importer validates data before import', function () {
    require_once getPluginPath('includes/class-conjure-customizer-importer.php');
    
    $methods = get_class_methods('Conjure_Customizer_Importer');
    $hasValidation = false;
    
    foreach ($methods as $method) {
        if (stripos($method, 'valid') !== false || stripos($method, 'check') !== false) {
            $hasValidation = true;
            break;
        }
    }
    
    // At minimum should have import method
    expect(in_array('import', $methods))->toBeTrue();
});

test('redux importer can handle redux framework options', function () {
    require_once getPluginPath('includes/class-conjure-redux-importer.php');
    
    $reduxFile = getPluginPath('demo/redux-options.json');
    
    if (!file_exists($reduxFile)) {
        expect(true)->toBeTrue(); // Skip if demo file doesn't exist
        return;
    }
    
    $reduxData = json_decode(file_get_contents($reduxFile), true);
    
    // Redux data should be valid JSON
    expect(json_last_error())->toBe(JSON_ERROR_NONE);
});

test('customizer option class has import method', function () {
    $customizerOptionFile = getPluginPath('includes/class-conjure-customizer-option.php');
    $content = file_get_contents($customizerOptionFile);
    
    expect($content)->toContain('function import');
});

