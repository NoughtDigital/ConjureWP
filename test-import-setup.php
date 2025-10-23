<?php
/**
 * Test Import Setup for ConjureWP
 *
 * This file provides generic test examples for theme-based demo imports.
 * Copy these functions to your theme's functions.php to test with your own demo files.
 *
 * For immediate testing with included demo files, use:
 * - demo-theme-integration.php (ready-to-use with plugin's demo/ files)
 *
 * This file shows examples for:
 * - Local theme-based imports
 * - Remote URL imports
 * - Multiple demo variations
 * - Redux Framework integration
 * - Revolution Slider integration
 *
 * @package ConjureWP
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Example 1: Use Plugin's Demo Files
 *
 * This example uses the demo files included in the ConjureWP plugin.
 * This is the quickest way to test - no setup required!
 *
 * For more control, see demo-theme-integration.php instead.
 */
function conjurewp_use_plugin_demos() {
	return array(
		array(
			'import_file_name'             => 'Quick Test Demo',
			'local_import_file'            => WP_PLUGIN_DIR . '/ConjureWP/demo/content.xml',
			'local_import_widget_file'     => WP_PLUGIN_DIR . '/ConjureWP/demo/widgets.json',
			'local_import_customizer_file' => WP_PLUGIN_DIR . '/ConjureWP/demo/customizer.dat',
			'import_notice'                => __( 'Quick test using ConjureWP demo files.', 'conjurewp' ),
		),
	);
}
// Uncomment to test with plugin demo files (quickest method).
// add_filter( 'conjure_import_files', 'conjurewp_use_plugin_demos' );


/**
 * Example 2: Local Theme Files
 *
 * This example shows how to use demo files stored in your theme directory.
 * Create a demo/ folder in your theme and add your demo files there.
 */
function conjurewp_test_local_import() {
	return array(
		array(
			'import_file_name'             => 'My Theme Demo',
			'local_import_file'            => trailingslashit( get_template_directory() ) . 'demo/content.xml',
			'local_import_widget_file'     => trailingslashit( get_template_directory() ) . 'demo/widgets.json',
			'local_import_customizer_file' => trailingslashit( get_template_directory() ) . 'demo/customizer.dat',
			'import_preview_image_url'     => trailingslashit( get_template_directory_uri() ) . 'demo/preview.jpg',
			'import_notice'                => __( 'Demo files from theme directory.', 'conjurewp' ),
			'preview_url'                  => home_url( '/' ),
		),
	);
}
// Uncomment to test with theme-based local files.
// add_filter( 'conjure_import_files', 'conjurewp_test_local_import' ).


/**
 * Example 3: Remote Import Files
 *
 * This example shows how to import demo files from a remote URL.
 * Useful for hosting demo files on your server for multiple sites.
 */
function conjurewp_test_remote_import() {
	return array(
		array(
			'import_file_name'           => 'Remote Demo Import',
			'import_file_url'            => 'https://yoursite.com/demos/content.xml',
			'import_widget_file_url'     => 'https://yoursite.com/demos/widgets.json',
			'import_customizer_file_url' => 'https://yoursite.com/demos/customizer.dat',
			'import_preview_image_url'   => 'https://yoursite.com/demos/preview.jpg',
			'import_notice'              => __( 'Demo files from remote server.', 'conjurewp' ),
			'preview_url'                => 'https://yoursite.com/demo-preview',
		),
	);
}
// Uncomment to test remote imports.
// add_filter( 'conjure_import_files', 'conjurewp_test_remote_import' ).


/**
 * Example 4: Multiple Demo Variations
 *
 * This example shows how to offer multiple demo imports with categories.
 * Each demo can have different content and settings.
 */
function conjurewp_test_multiple_imports() {
	return array(
		array(
			'import_file_name'             => 'Business Demo',
			'categories'                   => array( 'Business', 'Corporate' ),
			'local_import_file'            => trailingslashit( get_template_directory() ) . 'demo/business/content.xml',
			'local_import_widget_file'     => trailingslashit( get_template_directory() ) . 'demo/business/widgets.json',
			'local_import_customizer_file' => trailingslashit( get_template_directory() ) . 'demo/business/customizer.dat',
			'import_preview_image_url'     => trailingslashit( get_template_directory_uri() ) . 'demo/business/preview.jpg',
			'import_notice'                => __( 'Professional business demo.', 'conjurewp' ),
			'preview_url'                  => 'https://demo.yoursite.com/business',
		),
		array(
			'import_file_name'             => 'Portfolio Demo',
			'categories'                   => array( 'Portfolio', 'Creative' ),
			'local_import_file'            => trailingslashit( get_template_directory() ) . 'demo/portfolio/content.xml',
			'local_import_widget_file'     => trailingslashit( get_template_directory() ) . 'demo/portfolio/widgets.json',
			'local_import_customizer_file' => trailingslashit( get_template_directory() ) . 'demo/portfolio/customizer.dat',
			'import_preview_image_url'     => trailingslashit( get_template_directory_uri() ) . 'demo/portfolio/preview.jpg',
			'import_notice'                => __( 'Creative portfolio showcase.', 'conjurewp' ),
			'preview_url'                  => 'https://demo.yoursite.com/portfolio',
		),
		array(
			'import_file_name'             => 'Blog Demo',
			'categories'                   => array( 'Blog', 'Magazine' ),
			'local_import_file'            => trailingslashit( get_template_directory() ) . 'demo/blog/content.xml',
			'local_import_widget_file'     => trailingslashit( get_template_directory() ) . 'demo/blog/widgets.json',
			'local_import_customizer_file' => trailingslashit( get_template_directory() ) . 'demo/blog/customizer.dat',
			'import_preview_image_url'     => trailingslashit( get_template_directory_uri() ) . 'demo/blog/preview.jpg',
			'import_notice'                => __( 'Blog with multiple post formats.', 'conjurewp' ),
			'preview_url'                  => 'https://demo.yoursite.com/blog',
		),
	);
}
// Uncomment to test multiple imports.
// add_filter( 'conjure_import_files', 'conjurewp_test_multiple_imports' ).


/**
 * Example 5: Redux Framework Integration
 *
 * This example shows how to import Redux Framework options.
 * Useful for themes that use Redux for theme settings.
 */
function conjurewp_test_redux_import() {
	return array(
		array(
			'import_file_name'             => 'Demo with Redux Options',
			'local_import_file'            => trailingslashit( get_template_directory() ) . 'demo/content.xml',
			'local_import_widget_file'     => trailingslashit( get_template_directory() ) . 'demo/widgets.json',
			'local_import_customizer_file' => trailingslashit( get_template_directory() ) . 'demo/customizer.dat',
			'local_import_redux'           => array(
				array(
					'file_path'   => trailingslashit( get_template_directory() ) . 'demo/redux-options.json',
					'option_name' => 'mytheme_options', // Replace with your Redux option name.
				),
			),
			'import_preview_image_url'     => trailingslashit( get_template_directory_uri() ) . 'demo/preview.jpg',
			'import_notice'                => __( 'Includes Redux Framework theme settings.', 'conjurewp' ),
		),
	);
}
// Uncomment to test Redux imports.
// add_filter( 'conjure_import_files', 'conjurewp_test_redux_import' );


/**
 * Example 6: Revolution Slider Integration
 *
 * This example shows how to import Revolution Slider.
 * Requires Revolution Slider plugin to be installed.
 */
function conjurewp_test_revslider_import() {
	return array(
		array(
			'import_file_name'             => 'Demo with Revolution Slider',
			'local_import_file'            => trailingslashit( get_template_directory() ) . 'demo/content.xml',
			'local_import_rev_slider_file' => trailingslashit( get_template_directory() ) . 'demo/slider.zip',
			'import_preview_image_url'     => trailingslashit( get_template_directory_uri() ) . 'demo/preview.jpg',
			'import_notice'                => __( 'Includes Revolution Slider.', 'conjurewp' ),
		),
	);
}
// Uncomment to test Revolution Slider imports.
// add_filter( 'conjure_import_files', 'conjurewp_test_revslider_import' );


/**
 * After Import Setup
 *
 * This function runs after the import completes.
 * Use it to set up menus, homepage, blog page, and other configuration.
 *
 * @param int $selected_import The index of the selected import.
 */
function conjurewp_test_after_import( $selected_import ) {
	// Assign navigation menus to theme locations.
	$main_menu = get_term_by( 'name', 'Main Menu', 'nav_menu' );

	if ( $main_menu ) {
		set_theme_mod(
			'nav_menu_locations',
			array(
				'primary' => $main_menu->term_id,
				'footer'  => $main_menu->term_id, // Add other menu locations as needed.
			)
		);
	}

	// Set front page and posts page.
	$front_page = get_page_by_title( 'Home' );
	$blog_page  = get_page_by_title( 'Blog' );

	if ( $front_page ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front_page->ID );
	}

	if ( $blog_page ) {
		update_option( 'page_for_posts', $blog_page->ID );
	}

	// Different setup based on which demo was selected.
	switch ( $selected_import ) {
		case 0:
			// Setup for first demo.
			break;
		case 1:
			// Setup for second demo.
			break;
		case 2:
			// Setup for third demo.
			break;
	}
}
// Uncomment to enable after import setup.
// add_action( 'conjure_after_all_import', 'conjurewp_test_after_import' );


/**
 * Debug Helper
 *
 * This function helps debug import configuration.
 * Check your error log or debug.log for output.
 */
function conjurewp_debug_import_files() {
	if ( ! is_admin() ) {
		return;
	}

	// Check if filter is registered.
	$has_filter = has_filter( 'conjure_import_files' );
	error_log( 'ConjureWP: Filter registered: ' . ( $has_filter ? 'YES' : 'NO' ) );

	// Get registered imports.
	$import_files = apply_filters( 'conjure_import_files', array() );

	if ( ! empty( $import_files ) ) {
		error_log( 'ConjureWP: Found ' . count( $import_files ) . ' import(s)' );
		foreach ( $import_files as $index => $import ) {
			error_log( "Import #{$index}: " . ( $import['import_file_name'] ?? 'Unnamed' ) );
			
			// Check file existence.
			if ( ! empty( $import['local_import_file'] ) ) {
				$exists = file_exists( $import['local_import_file'] ) ? 'YES' : 'NO';
				error_log( "  Content file exists: {$exists}" );
			}
		}
	} else {
		error_log( 'ConjureWP: No import files registered!' );
	}

	// Check after import action.
	$has_action = has_action( 'conjure_after_all_import' );
	error_log( 'ConjureWP: After import action registered: ' . ( $has_action ? 'YES' : 'NO' ) );
}
// Uncomment to enable debug logging.
// add_action( 'admin_init', 'conjurewp_debug_import_files', 999 );


/**
 * QUICK REFERENCE
 * ===============
 *
 * Import Configuration Keys:
 * - import_file_name             (required) Display name
 * - local_import_file            Path to content.xml
 * - import_file_url              URL to content.xml
 * - local_import_widget_file     Path to widgets.json/.wie
 * - import_widget_file_url       URL to widgets file
 * - local_import_customizer_file Path to customizer.dat
 * - import_customizer_file_url   URL to customizer file
 * - local_import_redux           Redux options array
 * - import_redux                 Remote Redux options
 * - local_import_rev_slider_file Path to slider.zip
 * - import_rev_slider_file_url   URL to slider file
 * - import_preview_image_url     Preview image URL
 * - preview_url                  Live demo URL
 * - import_notice                Description text
 * - categories                   Category tags array
 *
 * Useful Actions & Filters:
 * - conjure_import_files         Define import files
 * - conjure_after_all_import     Post-import setup
 * - conjure_content_home_page_title
 * - conjure_content_blog_page_title
 *
 * See QUICK-REFERENCE.md and IMPORT-SETUP-GUIDE.md for more details.
 */
