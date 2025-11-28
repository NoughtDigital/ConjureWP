<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(TestCase::class)->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeWordPressPlugin', function () {
    return $this->toBeArray()
        ->toHaveKeys(['Name', 'PluginURI', 'Version', 'Author']);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function getPluginPath(string $file = ''): string
{
    $base = dirname(__DIR__);
    return $file ? $base . '/' . ltrim($file, '/') : $base;
}

// Mock WordPress functions globally for tests
if (!function_exists('add_action')) {
    function add_action() {}
}
if (!function_exists('add_filter')) {
    function add_filter() {}
}
if (!function_exists('do_action')) {
    function do_action() {}
}
if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value) { return $value; }
}
if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') { return $text; }
}
if (!function_exists('esc_html_e')) {
    function esc_html_e($text, $domain = 'default') { echo $text; }
}
if (!function_exists('__')) {
    function __($text, $domain = 'default') { return $text; }
}
if (!function_exists('_e')) {
    function _e($text, $domain = 'default') { echo $text; }
}
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) { return dirname($file) . '/'; }
}
if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) { return 'http://example.com/wp-content/plugins/' . basename(dirname($file)) . '/'; }
}

// Mock additional WordPress helper functions
if (!function_exists('absint')) {
    function absint($maybeint) { return abs(intval($maybeint)); }
}
if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = array()) {
        if (is_object($args)) {
            $parsed_args = get_object_vars($args);
        } elseif (is_array($args)) {
            $parsed_args =& $args;
        } else {
            parse_str($args, $parsed_args);
        }
        if (is_array($defaults) && $defaults) {
            return array_merge($defaults, $parsed_args);
        }
        return $parsed_args;
    }
}
if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir($time = null, $create_dir = true, $refresh_cache = false) {
        return [
            'path' => '/tmp/wordpress/wp-content/uploads',
            'url' => 'http://example.com/wp-content/uploads',
            'subdir' => '',
            'basedir' => '/tmp/wordpress/wp-content/uploads',
            'baseurl' => 'http://example.com/wp-content/uploads',
            'error' => false,
        ];
    }
}
if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p($target) { return mkdir($target, 0755, true); }
}
if (!function_exists('wp_get_theme')) {
    function wp_get_theme($stylesheet = null, $theme_root = null) {
        return (object) ['Name' => 'Test Theme', 'Version' => '1.0'];
    }
}
if (!function_exists('get_template_directory')) {
    function get_template_directory() { return '/tmp/wordpress/wp-content/themes/test-theme'; }
}
if (!function_exists('trailingslashit')) {
    function trailingslashit($value) { return rtrim($value, '/\\') . '/'; }
}
if (!function_exists('admin_url')) {
    function admin_url($path = '', $scheme = 'admin') { return 'http://example.com/wp-admin/' . ltrim($path, '/'); }
}
if (!function_exists('plugin_basename')) {
    function plugin_basename($file) { return basename(dirname($file)) . '/' . basename($file); }
}
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) { return strip_tags($str); }
}
if (!function_exists('wp_unslash')) {
    function wp_unslash($value) { return stripslashes($value); }
}
if (!function_exists('current_user_can')) {
    function current_user_can($capability, ...$args) { return true; }
}
if (!function_exists('register_rest_route')) {
    function register_rest_route($namespace, $route, $args = array(), $override = false) { return true; }
}

// Define WordPress constants if not already defined
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}
if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', '/tmp/wordpress/wp-content');
}
if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', '/tmp/wordpress/wp-content/plugins');
}
