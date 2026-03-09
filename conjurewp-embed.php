<?php
/**
 * ConjureWP theme-embedded bootstrap.
 *
 * Include this file from a theme to run ConjureWP without requiring the plugin
 * to be installed separately.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'CONJUREWP_BOOTSTRAPPED' ) && CONJUREWP_BOOTSTRAPPED ) {
	return;
}

require_once __DIR__ . '/includes/conjurewp-loader.php';

conjurewp_bootstrap(
	array(
		'mode'      => 'theme',
		'base_path' => __DIR__,
		'base_url'  => conjurewp_get_theme_embed_base_url( __DIR__ ),
		'file'      => __FILE__,
	)
);
