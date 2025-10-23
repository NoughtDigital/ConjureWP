<?php
/**
 * Demo Theme Integration for ConjureWP Testing
 *
 * This file provides a complete working example for testing ConjureWP
 * with the included demo files. Copy this to your theme's functions.php
 * or use it as a mu-plugin to test the import functionality.
 *
 * @package ConjureWP
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Basic Demo Import
 *
 * This demonstrates a basic import with content, widgets, and customizer files.
 */
function conjurewp_demo_basic_import() {
	return array(
		array(
			'import_file_name'             => 'Basic Demo Import',
			'categories'                   => array( 'Demo' ),
			'local_import_file'            => WP_PLUGIN_DIR . '/ConjureWP/demo/content.xml',
			'local_import_widget_file'     => WP_PLUGIN_DIR . '/ConjureWP/demo/widgets.json',
			'local_import_customizer_file' => WP_PLUGIN_DIR . '/ConjureWP/demo/customizer.dat',
			'import_notice'                => __( 'This is a basic demo import with content, widgets, and customizer settings.', 'conjurewp' ),
			'preview_url'                  => home_url( '/' ),
		),
	);
}
// Uncomment the line below to test basic import.
// add_filter( 'conjure_import_files', 'conjurewp_demo_basic_import' );


/**
 * Advanced Demo Import with Redux
 *
 * This demonstrates an import with Redux Framework options.
 */
function conjurewp_demo_redux_import() {
	return array(
		array(
			'import_file_name'             => 'Demo with Redux Options',
			'categories'                   => array( 'Demo', 'Advanced' ),
			'local_import_file'            => WP_PLUGIN_DIR . '/ConjureWP/demo/content.xml',
			'local_import_widget_file'     => WP_PLUGIN_DIR . '/ConjureWP/demo/widgets.json',
			'local_import_customizer_file' => WP_PLUGIN_DIR . '/ConjureWP/demo/customizer.dat',
			'local_import_redux'           => array(
				array(
					'file_path'   => WP_PLUGIN_DIR . '/ConjureWP/demo/redux-options.json',
					'option_name' => 'mytheme_redux_options', // Change this to your Redux option name.
				),
			),
			'import_notice'                => __( 'This demo includes Redux Framework theme options.', 'conjurewp' ),
			'preview_url'                  => home_url( '/' ),
		),
	);
}
// Uncomment the line below to test Redux import.
// add_filter( 'conjure_import_files', 'conjurewp_demo_redux_import' );


/**
 * Multiple Demo Imports
 *
 * This demonstrates offering multiple demo variations.
 */
function conjurewp_demo_multiple_imports() {
	return array(
		array(
			'import_file_name'             => 'Business Demo',
			'categories'                   => array( 'Business', 'Corporate' ),
			'local_import_file'            => WP_PLUGIN_DIR . '/ConjureWP/demo/content.xml',
			'local_import_widget_file'     => WP_PLUGIN_DIR . '/ConjureWP/demo/widgets.json',
			'local_import_customizer_file' => WP_PLUGIN_DIR . '/ConjureWP/demo/customizer.dat',
			'import_notice'                => __( 'Professional business demo with corporate content.', 'conjurewp' ),
			'preview_url'                  => home_url( '/' ),
		),
		array(
			'import_file_name'             => 'Creative Demo',
			'categories'                   => array( 'Creative', 'Portfolio' ),
			'local_import_file'            => WP_PLUGIN_DIR . '/ConjureWP/demo/content.xml',
			'local_import_widget_file'     => WP_PLUGIN_DIR . '/ConjureWP/demo/widgets.json',
			'local_import_customizer_file' => WP_PLUGIN_DIR . '/ConjureWP/demo/customizer.dat',
			'local_import_redux'           => array(
				array(
					'file_path'   => WP_PLUGIN_DIR . '/ConjureWP/demo/redux-options.json',
					'option_name' => 'creative_theme_options',
				),
			),
			'import_notice'                => __( 'Creative portfolio demo with advanced theme options.', 'conjurewp' ),
			'preview_url'                  => home_url( '/' ),
		),
	);
}
// Uncomment the line below to test multiple imports.
// add_filter( 'conjure_import_files', 'conjurewp_demo_multiple_imports' );


/**
 * After Import Setup
 *
 * This function runs after the import completes.
 * It sets up menus, homepage, blog page, and other settings.
 *
 * @param int $selected_import The index of the selected import.
 */
function conjurewp_demo_after_import_setup( $selected_import ) {
	error_log( 'ConjureWP Demo: After import hook fired. Selected import index: ' . $selected_import );

	// Set up navigation menus.
	$main_menu = get_term_by( 'name', 'Main Menu', 'nav_menu' );

	if ( $main_menu ) {
		error_log( 'ConjureWP Demo: Found Main Menu (ID: ' . $main_menu->term_id . ')' );

		set_theme_mod(
			'nav_menu_locations',
			array(
				'primary' => $main_menu->term_id,
				'menu-1'  => $main_menu->term_id, // Some themes use menu-1.
				'main'    => $main_menu->term_id, // Some themes use main.
			)
		);
	} else {
		error_log( 'ConjureWP Demo: Main Menu not found!' );
	}

	// Set homepage and blog page.
	$front_page = get_page_by_title( 'Home' );
	$blog_page  = get_page_by_title( 'Blog' );

	if ( $front_page ) {
		error_log( 'ConjureWP Demo: Setting homepage (ID: ' . $front_page->ID . ')' );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front_page->ID );
	} else {
		error_log( 'ConjureWP Demo: Home page not found!' );
	}

	if ( $blog_page ) {
		error_log( 'ConjureWP Demo: Setting blog page (ID: ' . $blog_page->ID . ')' );
		update_option( 'page_for_posts', $blog_page->ID );
	} else {
		error_log( 'ConjureWP Demo: Blog page not found!' );
	}

	// Different setup based on selected demo.
	switch ( $selected_import ) {
		case 0:
			error_log( 'ConjureWP Demo: First demo selected - running specific setup' );
			// Add specific setup for first demo here.
			break;
		case 1:
			error_log( 'ConjureWP Demo: Second demo selected - running specific setup' );
			// Add specific setup for second demo here.
			break;
	}

	error_log( 'ConjureWP Demo: After import setup completed!' );
}
// Uncomment the line below to test after import hook.
// add_action( 'conjure_after_all_import', 'conjurewp_demo_after_import_setup' );


/**
 * Debug Helper
 *
 * This function helps debug the import setup by logging information
 * about registered imports and available files.
 */
function conjurewp_demo_debug_info() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Check if filter is registered.
	if ( has_filter( 'conjure_import_files' ) ) {
		error_log( 'ConjureWP Demo: conjure_import_files filter is registered' );
	} else {
		error_log( 'ConjureWP Demo: conjure_import_files filter is NOT registered!' );
	}

	// Get registered imports.
	$imports = apply_filters( 'conjure_import_files', array() );
	error_log( 'ConjureWP Demo: Found ' . count( $imports ) . ' registered imports' );

	if ( ! empty( $imports ) ) {
		foreach ( $imports as $index => $import ) {
			error_log( "ConjureWP Demo: Import #{$index}: " . ( $import['import_file_name'] ?? 'Unnamed' ) );

			// Check if content file exists.
			if ( ! empty( $import['local_import_file'] ) ) {
				$exists   = file_exists( $import['local_import_file'] ) ? 'YES' : 'NO';
				$readable = is_readable( $import['local_import_file'] ) ? 'YES' : 'NO';
				error_log( "  - Content file exists: {$exists}, readable: {$readable}" );
				error_log( "  - Path: {$import['local_import_file']}" );
			}

			// Check if widget file exists.
			if ( ! empty( $import['local_import_widget_file'] ) ) {
				$exists   = file_exists( $import['local_import_widget_file'] ) ? 'YES' : 'NO';
				$readable = is_readable( $import['local_import_widget_file'] ) ? 'YES' : 'NO';
				error_log( "  - Widget file exists: {$exists}, readable: {$readable}" );
			}

			// Check if customizer file exists.
			if ( ! empty( $import['local_import_customizer_file'] ) ) {
				$exists   = file_exists( $import['local_import_customizer_file'] ) ? 'YES' : 'NO';
				$readable = is_readable( $import['local_import_customizer_file'] ) ? 'YES' : 'NO';
				error_log( "  - Customizer file exists: {$exists}, readable: {$readable}" );
			}

			// Check if Redux file exists.
			if ( ! empty( $import['local_import_redux'] ) ) {
				foreach ( $import['local_import_redux'] as $redux_index => $redux_item ) {
					$exists   = file_exists( $redux_item['file_path'] ) ? 'YES' : 'NO';
					$readable = is_readable( $redux_item['file_path'] ) ? 'YES' : 'NO';
					error_log( "  - Redux file #{$redux_index} exists: {$exists}, readable: {$readable}" );
				}
			}
		}
	}

	// Check if after import action is registered.
	if ( has_action( 'conjure_after_all_import' ) ) {
		error_log( 'ConjureWP Demo: conjure_after_all_import action is registered' );
	} else {
		error_log( 'ConjureWP Demo: conjure_after_all_import action is NOT registered' );
	}
}
// Uncomment the line below to enable debug logging.
// add_action( 'admin_init', 'conjurewp_demo_debug_info', 999 );


/**
 * Instructions Output
 *
 * Displays admin notice with instructions for testing.
 */
function conjurewp_demo_instructions() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( 'plugins' !== $screen->id && 'themes' !== $screen->id ) {
		return;
	}

	?>
	<div class="notice notice-info">
		<h3>ConjureWP Demo Testing Instructions</h3>
		<p><strong>To test the demo import:</strong></p>
		<ol>
			<li>Open <code>demo-theme-integration.php</code> in the ConjureWP plugin directory</li>
			<li>Uncomment one of the import filter lines (remove the <code>//</code> at the start)</li>
			<li>Uncomment the after import action line</li>
			<li>Optionally uncomment the debug info line to see detailed logs</li>
			<li>Save the file and refresh this page</li>
			<li>Navigate to <strong>Appearance &gt; Theme Setup</strong> (or wherever your wizard is)</li>
			<li>Complete the import wizard</li>
			<li>Check <code>wp-content/uploads/conjure-wp/main.log</code> for import details</li>
		</ol>
		<p><strong>Available test imports:</strong></p>
		<ul>
			<li><code>conjurewp_demo_basic_import</code> - Basic content, widgets, and customizer</li>
			<li><code>conjurewp_demo_redux_import</code> - Includes Redux Framework options</li>
			<li><code>conjurewp_demo_multiple_imports</code> - Multiple demo variations</li>
		</ul>
		<p><em>Demo files are located in: <code>/wp-content/plugins/ConjureWP/demo/</code></em></p>
	</div>
	<?php
}
// Uncomment the line below to show instructions notice.
// add_action( 'admin_notices', 'conjurewp_demo_instructions' );

