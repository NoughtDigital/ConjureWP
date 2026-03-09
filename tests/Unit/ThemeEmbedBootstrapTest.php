<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

test('theme embed bootstrap file exists', function () {
    $embedFile = conjurewp_test_get_plugin_path('conjurewp-embed.php');

    expect(file_exists($embedFile))->toBeTrue();
});

test('theme embed bootstrap uses shared loader in theme mode', function () {
    $embedFile = conjurewp_test_get_plugin_path('conjurewp-embed.php');
    $content = file_get_contents($embedFile);

    expect($content)->toContain('includes/conjurewp-loader.php');
    expect($content)->toContain("'mode'      => 'theme'");
    expect($content)->toContain('conjurewp_get_theme_embed_base_url');
});

test('shared runtime helpers exist', function () {
    expect(file_exists(conjurewp_test_get_plugin_path('includes/conjurewp-loader.php')))->toBeTrue();
    expect(file_exists(conjurewp_test_get_plugin_path('includes/conjurewp-runtime.php')))->toBeTrue();
});

test('theme embed base url resolves correctly from the active child theme', function () {
    require_once conjurewp_test_get_plugin_path('includes/conjurewp-runtime.php');

    $GLOBALS['conjurewp_test_stylesheet_directory'] = '/tmp/wordpress/wp-content/themes/test-child';
    $GLOBALS['conjurewp_test_stylesheet_directory_uri'] = 'http://example.com/wp-content/themes/test-child';
    $GLOBALS['conjurewp_test_template_directory'] = '/tmp/wordpress/wp-content/themes/test-parent';
    $GLOBALS['conjurewp_test_template_directory_uri'] = 'http://example.com/wp-content/themes/test-parent';

    $baseUrl = conjurewp_get_theme_embed_base_url('/tmp/wordpress/wp-content/themes/test-child/inc/conjurewp');

    expect($baseUrl)->toBe('http://example.com/wp-content/themes/test-child/inc/conjurewp/');

    unset(
        $GLOBALS['conjurewp_test_stylesheet_directory'],
        $GLOBALS['conjurewp_test_stylesheet_directory_uri'],
        $GLOBALS['conjurewp_test_template_directory'],
        $GLOBALS['conjurewp_test_template_directory_uri']
    );
});

test('theme embed base url falls back to the parent theme when the package lives there', function () {
    require_once conjurewp_test_get_plugin_path('includes/conjurewp-runtime.php');

    $GLOBALS['conjurewp_test_stylesheet_directory'] = '/tmp/wordpress/wp-content/themes/test-child';
    $GLOBALS['conjurewp_test_stylesheet_directory_uri'] = 'http://example.com/wp-content/themes/test-child';
    $GLOBALS['conjurewp_test_template_directory'] = '/tmp/wordpress/wp-content/themes/test-parent';
    $GLOBALS['conjurewp_test_template_directory_uri'] = 'http://example.com/wp-content/themes/test-parent';

    $baseUrl = conjurewp_get_theme_embed_base_url('/tmp/wordpress/wp-content/themes/test-parent/inc/conjurewp');

    expect($baseUrl)->toBe('http://example.com/wp-content/themes/test-parent/inc/conjurewp/');

    unset(
        $GLOBALS['conjurewp_test_stylesheet_directory'],
        $GLOBALS['conjurewp_test_stylesheet_directory_uri'],
        $GLOBALS['conjurewp_test_template_directory'],
        $GLOBALS['conjurewp_test_template_directory_uri']
    );
});
