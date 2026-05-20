<?php

beforeEach(function () {
	require_once conjurewp_test_get_plugin_path( 'includes/class-conjure-connector-catalog.php' );
	require_once conjurewp_test_get_plugin_path( 'includes/class-conjure-connector-native-sync.php' );

	$GLOBALS['conjurewp_test_options'] = array();
});

test('yoast native sync maps conjure preferences into wpseo options', function () {
	$GLOBALS['conjurewp_test_options']['conjure_yoast_title_separator']     = '|';
	$GLOBALS['conjurewp_test_options']['conjure_yoast_post_title_template'] = '%%title%%';
	$GLOBALS['conjurewp_test_options']['conjure_yoast_enable_sitemap']      = true;
	$GLOBALS['conjurewp_test_options']['conjure_yoast_enable_opengraph']    = true;

	if ( ! defined( 'WPSEO_VERSION' ) ) {
		define( 'WPSEO_VERSION', '23.0' );
	}

	Conjure_Connector_Native_Sync::apply( 'yoast-seo', array( 'search_appearance', 'xml_sitemap', 'social_metadata' ) );

	$titles = get_option( 'wpseo_titles', array() );
	$xml    = get_option( 'wpseo_xml', array() );
	$social = get_option( 'wpseo_social', array() );

	expect( $titles['separator'] )->toBe( '|' );
	expect( $titles['title-post'] )->toBe( '%%title%%' );
	expect( $xml['enable_xml_sitemap'] )->toBeTrue();
	expect( $social['opengraph'] )->toBeTrue();
});

test('wp rocket native sync merges into wp_rocket_settings', function () {
	if ( ! function_exists( 'conjurewp_is_wp_rocket_active' ) ) {
		function conjurewp_is_wp_rocket_active() {
			return true;
		}
	}

	$GLOBALS['conjurewp_test_options']['conjure_wprocket_minify_css'] = true;
	$GLOBALS['conjurewp_test_options']['conjure_wprocket_lazy_images'] = true;

	Conjure_Connector_Native_Sync::apply( 'wp-rocket', array( 'file_optimisation', 'lazy_loading' ) );

	$settings = get_option( 'wp_rocket_settings', array() );

	expect( $settings['minify_css'] )->toBe( 1 );
	expect( $settings['lazyload'] )->toBe( 1 );
});
