<?php

test('importer class file exists', function () {
    $importerFile = getPluginPath('includes/class-conjure-importer.php');
    expect(file_exists($importerFile))->toBeTrue();
});

test('wxr importer class file exists', function () {
    $wxrFile = getPluginPath('includes/class-conjure-wxr-importer.php');
    expect(file_exists($wxrFile))->toBeTrue();
});

test('widget importer class file exists', function () {
    $widgetFile = getPluginPath('includes/class-conjure-widget-importer.php');
    expect(file_exists($widgetFile))->toBeTrue();
});

test('customizer importer class file exists', function () {
    $customizerFile = getPluginPath('includes/class-conjure-customizer-importer.php');
    expect(file_exists($customizerFile))->toBeTrue();
});

test('redux importer class file exists', function () {
    $reduxFile = getPluginPath('includes/class-conjure-redux-importer.php');
    expect(file_exists($reduxFile))->toBeTrue();
});

