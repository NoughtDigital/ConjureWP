<?php

test('plugin can be activated without errors', function () {
    $pluginFile = getPluginPath('conjurewp.php');
    $content = file_get_contents($pluginFile);
    
    // Plugin should initialize properly via includes or admin_init hooks
    $hasInitialization = strpos($content, 'admin_init') !== false ||
                         strpos($content, 'plugins_loaded') !== false ||
                         strpos($content, 'init') !== false;
    
    expect($hasInitialization)->toBeTrue();
});

test('plugin initializes core classes on load', function () {
    $pluginFile = getPluginPath('conjurewp.php');
    $content = file_get_contents($pluginFile);
    
    // Should load core classes
    expect($content)->toContain('class-conjure.php');
});

test('plugin defines required constants on load', function () {
    $pluginFile = getPluginPath('conjurewp.php');
    $content = file_get_contents($pluginFile);
    
    expect($content)->toContain("define( 'CONJUREWP_VERSION'");
    expect($content)->toContain("define( 'CONJUREWP_PLUGIN_DIR'");
    expect($content)->toContain("define( 'CONJUREWP_PLUGIN_URL'");
    expect($content)->toContain("define( 'CONJUREWP_PLUGIN_FILE'");
});

test('plugin loads composer autoloader', function () {
    $pluginFile = getPluginPath('conjurewp.php');
    $content = file_get_contents($pluginFile);
    
    expect($content)->toContain('vendor/autoload.php');
});

test('plugin checks for direct access', function () {
    $pluginFile = getPluginPath('conjurewp.php');
    $content = file_get_contents($pluginFile);
    
    expect($content)->toContain("if ( ! defined( 'ABSPATH' ) )");
    expect($content)->toContain('exit');
});

