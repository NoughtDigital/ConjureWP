<?php

test('importer class file exists', function () {
    $importerFile = conjurewp_test_get_plugin_path('includes/class-conjure-importer.php');
    expect(file_exists($importerFile))->toBeTrue();
});

test('wxr importer class file exists', function () {
    $wxrFile = conjurewp_test_get_plugin_path('includes/class-conjure-wxr-importer.php');
    expect(file_exists($wxrFile))->toBeTrue();
});

test('widget importer class file exists', function () {
    $widgetFile = conjurewp_test_get_plugin_path('includes/class-conjure-widget-importer.php');
    expect(file_exists($widgetFile))->toBeTrue();
});

test('customizer importer class file exists', function () {
    $customizerFile = conjurewp_test_get_plugin_path('includes/class-conjure-customizer-importer.php');
    expect(file_exists($customizerFile))->toBeTrue();
});

test('redux importer class file exists', function () {
    $reduxFile = conjurewp_test_get_plugin_path('includes/class-conjure-redux-importer.php');
    expect(file_exists($reduxFile))->toBeTrue();
});




