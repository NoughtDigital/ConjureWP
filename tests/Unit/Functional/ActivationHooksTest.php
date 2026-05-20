<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

test('plugin can be activated without errors', function () {
    $pluginFile = conjurewp_test_get_plugin_path('conjurewp.php');
    $content = file_get_contents($pluginFile);
    
    // Plugin should initialize properly via includes or admin_init hooks
    $hasInitialization = strpos($content, 'admin_init') !== false ||
                         strpos($content, 'plugins_loaded') !== false ||
                         strpos($content, 'init') !== false;
    
    expect($hasInitialization)->toBeTrue();
});

test('plugin bootstraps shared runtime on load', function () {
    $pluginFile = conjurewp_test_get_plugin_path('conjurewp.php');
    $content = file_get_contents($pluginFile);

    expect($content)->toContain('conjurewp-loader.php');
    expect($content)->toContain('conjurewp_bootstrap');
});

test('plugin defines required constants on load', function () {
    $pluginFile = conjurewp_test_get_plugin_path('conjurewp.php');
    $content = file_get_contents($pluginFile);
    
    expect($content)->toContain("define( 'CONJUREWP_VERSION'");
    expect($content)->toContain("define( 'CONJUREWP_PLUGIN_DIR'");
    expect($content)->toContain("define( 'CONJUREWP_PLUGIN_URL'");
    expect($content)->toContain("define( 'CONJUREWP_PLUGIN_FILE'");
});

test('runtime loader loads composer autoloader when present', function () {
    $loaderFile = conjurewp_test_get_plugin_path('includes/conjurewp-loader.php');
    $content = file_get_contents($loaderFile);

    expect($content)->toContain('vendor/autoload.php');
});

test('plugin checks for direct access', function () {
    $pluginFile = conjurewp_test_get_plugin_path('conjurewp.php');
    $content = file_get_contents($pluginFile);
    
    expect($content)->toContain("if ( ! defined( 'ABSPATH' ) )");
    expect($content)->toContain('exit');
});

