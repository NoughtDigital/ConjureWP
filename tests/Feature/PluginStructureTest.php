<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

test('plugin has required directories', function () {
    $requiredDirs = [
        'includes',
        'assets',
        'assets/css',
        'assets/js',
        'assets/images',
        'languages',
        'examples',
    ];
    
    foreach ($requiredDirs as $dir) {
        $path = conjurewp_test_get_plugin_path($dir);
        expect(is_dir($path))->toBeTrue("Directory {$dir} should exist");
    }
});

test('plugin has required asset files', function () {
    $requiredAssets = [
        'assets/css/conjure.css',
        'assets/js/conjure.js',
        'assets/images/sprite.svg',
    ];
    
    foreach ($requiredAssets as $asset) {
        $path = conjurewp_test_get_plugin_path($asset);
        expect(file_exists($path))->toBeTrue("Asset {$asset} should exist");
    }
});

test('plugin has minified assets', function () {
    $minifiedAssets = [
        'assets/css/conjure.min.css',
        'assets/js/conjure.min.js',
    ];
    
    foreach ($minifiedAssets as $asset) {
        $path = conjurewp_test_get_plugin_path($asset);
        expect(file_exists($path))->toBeTrue("Minified asset {$asset} should exist");
    }
});

test('plugin has documentation', function () {
    expect(file_exists(conjurewp_test_get_plugin_path('README.md')))->toBeTrue();
    expect(file_exists(conjurewp_test_get_plugin_path('readme.txt')))->toBeTrue();
});

test('plugin has composer configuration', function () {
    expect(file_exists(conjurewp_test_get_plugin_path('composer.json')))->toBeTrue();
    expect(file_exists(conjurewp_test_get_plugin_path('composer.lock')))->toBeTrue();
});

test('plugin has package.json for frontend dependencies', function () {
    expect(file_exists(conjurewp_test_get_plugin_path('package.json')))->toBeTrue();
});

test('plugin has phpcs configuration', function () {
    expect(file_exists(conjurewp_test_get_plugin_path('phpcs.xml')))->toBeTrue();
});

test('all include classes exist', function () {
    $includeClasses = [
        'class-conjure-logger.php',
        'class-conjure-importer.php',
        'class-conjure-hooks.php',
        'class-conjure-admin-tools.php',
        'class-conjure-cli.php',
        'class-conjure-customizer-importer.php',
        'class-conjure-demo-helpers.php',
        'class-conjure-demo-plugin-manager.php',
        'class-conjure-downloader.php',
        'class-conjure-freemius.php',
        'class-conjure-plugin-installer.php',
        'class-conjure-premium-features.php',
        'class-conjure-redux-importer.php',
        'class-conjure-rest-api.php',
        'class-conjure-server-health.php',
        'class-conjure-theme-plugins.php',
        'class-conjure-widget-importer.php',
        'class-conjure-wxr-importer.php',
    ];
    
    foreach ($includeClasses as $class) {
        $path = conjurewp_test_get_plugin_path('includes/' . $class);
        expect(file_exists($path))->toBeTrue("Class {$class} should exist");
    }
});

test('demo directory has required files when present', function () {
    $demoDir = conjurewp_test_get_plugin_path('demo');
    if ( ! is_dir( $demoDir ) ) {
        expect( true )->toBeTrue();
        return;
    }
    $demoFiles = [
        'demo/content.xml',
        'demo/widgets.json',
        'demo/redux-options.json',
        'demo/customizer.dat',
    ];
    foreach ($demoFiles as $file) {
        $path = conjurewp_test_get_plugin_path($file);
        expect(file_exists($path))->toBeTrue("Demo file {$file} should exist");
    }
});

test('examples directory has sample files', function () {
    $exampleFiles = [
        'examples/theme-integration.php',
        'examples/conjure-config-sample.php',
        'examples/conjure-filters-sample.php',
    ];
    
    foreach ($exampleFiles as $file) {
        $path = conjurewp_test_get_plugin_path($file);
        expect(file_exists($path))->toBeTrue("Example file {$file} should exist");
    }
});




