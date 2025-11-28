<?php

test('importer class file has namespaced class', function () {
    $importerFile = getPluginPath('includes/class-conjure-importer.php');
    $content = file_get_contents($importerFile);
    
    expect($content)->toContain('namespace ConjureWP\Importer');
    expect($content)->toContain('class Importer');
});

test('importer class extends wxr importer', function () {
    $importerFile = getPluginPath('includes/class-conjure-importer.php');
    $content = file_get_contents($importerFile);
    
    expect($content)->toContain('extends WXRImporter');
});

test('wxr importer class file exists and has required structure', function () {
    $wxrFile = getPluginPath('includes/class-conjure-wxr-importer.php');
    $content = file_get_contents($wxrFile);
    
    expect($content)->toContain('class WXRImporter');
    expect($content)->toContain('extends \WP_Importer');
});

test('widget importer class can be loaded', function () {
    require_once getPluginPath('includes/class-conjure-widget-importer.php');
    expect(class_exists('Conjure_Widget_Importer'))->toBeTrue();
});

test('widget importer has import method', function () {
    require_once getPluginPath('includes/class-conjure-widget-importer.php');
    
    expect(method_exists('Conjure_Widget_Importer', 'import'))->toBeTrue();
});

test('customizer importer class can be loaded', function () {
    require_once getPluginPath('includes/class-conjure-customizer-importer.php');
    expect(class_exists('Conjure_Customizer_Importer'))->toBeTrue();
});

test('customizer importer has import method', function () {
    require_once getPluginPath('includes/class-conjure-customizer-importer.php');
    
    expect(method_exists('Conjure_Customizer_Importer', 'import'))->toBeTrue();
});

test('redux importer class can be loaded', function () {
    require_once getPluginPath('includes/class-conjure-redux-importer.php');
    expect(class_exists('Conjure_Redux_Importer'))->toBeTrue();
});

test('redux importer has import method', function () {
    require_once getPluginPath('includes/class-conjure-redux-importer.php');
    
    expect(method_exists('Conjure_Redux_Importer', 'import'))->toBeTrue();
});

test('importer logger class file contains proper class definition', function () {
    $loggerFile = getPluginPath('includes/class-conjure-wp-importer-logger.php');
    $content = file_get_contents($loggerFile);
    
    expect($content)->toContain('class WPImporterLogger');
});

test('wxr import info class file contains proper class definition', function () {
    $infoFile = getPluginPath('includes/class-conjure-wxr-import-info.php');
    $content = file_get_contents($infoFile);
    
    expect($content)->toContain('class WXRImportInfo');
});

