<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

test('composer.json is valid json', function () {
    $composerPath = conjurewp_test_get_plugin_path('composer.json');
    $composerContent = file_get_contents($composerPath);
    $composerData = json_decode($composerContent, true);
    
    expect($composerData)->not->toBeNull();
    expect(json_last_error())->toBe(JSON_ERROR_NONE);
});

test('composer.json has required fields', function () {
    $composerPath = conjurewp_test_get_plugin_path('composer.json');
    $composerData = json_decode(file_get_contents($composerPath), true);
    
    expect($composerData)->toHaveKeys(['name', 'description', 'license', 'require']);
    expect($composerData['name'])->toBe('conjurewp/conjurewp');
    expect($composerData['license'])->toBe('GPL-3.0+');
});

test('composer.json requires correct php version', function () {
    $composerPath = conjurewp_test_get_plugin_path('composer.json');
    $composerData = json_decode(file_get_contents($composerPath), true);
    
    expect($composerData['require'])->toHaveKey('php');
    expect($composerData['require']['php'])->toContain('7.4');
});

test('package.json is valid json', function () {
    $packagePath = conjurewp_test_get_plugin_path('package.json');
    $packageContent = file_get_contents($packagePath);
    $packageData = json_decode($packageContent, true);
    
    expect($packageData)->not->toBeNull();
    expect(json_last_error())->toBe(JSON_ERROR_NONE);
});

test('phpcs.xml exists and is valid xml', function () {
    $phpcsPath = conjurewp_test_get_plugin_path('phpcs.xml');
    expect(file_exists($phpcsPath))->toBeTrue();
    
    $xml = simplexml_load_file($phpcsPath);
    expect($xml)->not->toBeFalse();
});

test('plugin has language template file', function () {
    $potFile = conjurewp_test_get_plugin_path('languages/conjurewp.pot');
    expect(file_exists($potFile))->toBeTrue();
});




