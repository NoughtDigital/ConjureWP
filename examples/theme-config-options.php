<?php
/**
 * Theme-Level Configuration Options for ConjureWP
 *
 * This file shows ALL available filter hooks for controlling ConjureWP behavior
 * at the theme level. Add these to your theme's functions.php or an includes file.
 *
 * IMPORTANT: These are THEME-LEVEL controls that won't be overwritten by plugin updates.
 * DO NOT edit files in the plugin directory!
 *
 * @package   YourTheme
 * @version   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ========================================================================
 * 1. REDIRECT CONTROL (When Theme is Activated)
 * ========================================================================
 */

/**
 * Disable auto-redirect to wizard when theme is activated.
 *
 * @param bool $enabled Whether redirect is enabled.
 * @return bool Modified enabled state.
 */
function mytheme_disable_wizard_redirect( $enabled ) {
	return false; // Disable redirect completely.
}
add_filter( 'conjure_redirect_on_theme_switch_enabled', 'mytheme_disable_wizard_redirect' );

/**
 * OR redirect to your custom welcome page instead.
 *
 * @param string $url         The redirect URL.
 * @param string $conjure_url The wizard page slug.
 * @return string Modified redirect URL.
 */
function mytheme_custom_welcome_redirect( $url, $conjure_url ) {
	return admin_url( 'admin.php?page=mytheme-welcome' );
}
add_filter( 'conjure_redirect_on_theme_switch_url', 'mytheme_custom_welcome_redirect', 10, 2 );

/**
 * ========================================================================
 * 2. DEMO CONTENT PATH (Where Demo Files Are Located)
 * ========================================================================
 */

/**
 * Set custom demo content path.
 *
 * Priority: This filter has HIGHEST priority over other methods.
 *
 * @param string $path       Current custom path (empty by default).
 * @param string $theme_slug Optional theme slug.
 * @return string Path to demo content directory.
 */
function mytheme_custom_demo_path( $path, $theme_slug ) {
	// Option 1: Use theme directory.
	return get_template_directory() . '/demo-content';
	
	// Option 2: Use uploads directory.
	// $upload_dir = wp_upload_dir();
	// return $upload_dir['basedir'] . '/mytheme-demos';
	
	// Option 3: Use external storage.
	// return '/var/www/shared-content/demos';
}
add_filter( 'conjurewp_custom_demo_path', 'mytheme_custom_demo_path', 10, 2 );

/**
 * ========================================================================
 * 3. AUTO-REGISTER DEMOS (Automatically Discover & Register)
 * ========================================================================
 */

/**
 * Enable auto-registration of demo content.
 *
 * When enabled, ConjureWP will automatically scan demo directories
 * and register all found demo content.
 *
 * Priority: This filter has HIGHEST priority over wp-config.php constant.
 *
 * @param bool|null $enabled Current enabled state (null = not set).
 * @return bool Whether auto-registration is enabled.
 */
function mytheme_enable_auto_register_demos( $enabled ) {
	return true; // Enable auto-discovery.
}
add_filter( 'conjurewp_auto_register_demos', 'mytheme_enable_auto_register_demos' );

/**
 * ========================================================================
 * 4. LOGGER CONFIGURATION (Control Logging Behavior)
 * ========================================================================
 */

/**
 * Configure logger settings.
 *
 * Priority: This filter has HIGHEST priority over wp-config.php constant.
 *
 * @param array $config Current logger configuration.
 * @return array Modified logger configuration.
 */
function mytheme_configure_logger( $config ) {
	return array(
		'enable_rotation'  => true,    // Enable log file rotation.
		'max_files'        => 10,      // Keep up to 10 rotated log files.
		'max_file_size_mb' => 5,       // Rotate when file reaches 5MB.
		'min_log_level'    => 'DEBUG', // Log everything (DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY).
	);
}
add_filter( 'conjurewp_logger_config', 'mytheme_configure_logger' );

/**
 * ========================================================================
 * 5. DEMO IMPORT FILES (Register Your Demo Content)
 * ========================================================================
 */

/**
 * Register demo import files.
 *
 * This is the main way to tell ConjureWP about your demo content.
 *
 * @param array $import_files Existing import files.
 * @return array Modified import files array.
 */
function mytheme_register_demo_imports( $import_files ) {
	$demo_path = get_template_directory() . '/demo-content/';
	
	$import_files[] = array(
		'import_file_name'             => 'Main Demo',
		'local_import_file'            => $demo_path . 'content.xml',
		'local_import_widget_file'     => $demo_path . 'widgets.json',
		'local_import_customizer_file' => $demo_path . 'customizer.dat',
		'local_import_redux'           => array(
			array(
				'file_path'   => $demo_path . 'redux-options.json',
				'option_name' => 'mytheme_options',
			),
		),
		'import_preview_image_url'     => get_template_directory_uri() . '/assets/demo-preview.jpg',
		'preview_url'                  => 'https://demo.yourtheme.com/main',
		'import_notice'                => __( 'This will import the main demo content with all features.', 'mytheme' ),
	);
	
	// Add more demos as needed...
	$import_files[] = array(
		'import_file_name'             => 'Minimal Demo',
		'local_import_file'            => $demo_path . 'minimal/content.xml',
		'local_import_widget_file'     => $demo_path . 'minimal/widgets.json',
		'local_import_customizer_file' => $demo_path . 'minimal/customizer.dat',
		'import_preview_image_url'     => get_template_directory_uri() . '/assets/demo-minimal.jpg',
		'preview_url'                  => 'https://demo.yourtheme.com/minimal',
		'import_notice'                => __( 'A minimal demo with basic content.', 'mytheme' ),
	);
	
	return $import_files;
}
add_filter( 'conjure_import_files', 'mytheme_register_demo_imports' );

/**
 * ========================================================================
 * 6. AFTER IMPORT SETUP (Post-Import Actions)
 * ========================================================================
 */

/**
 * Run custom code after demo import completes.
 *
 * @param array $selected_import Selected import data.
 */
function mytheme_after_import_setup( $selected_import ) {
	// Set front page and blog page.
	$front_page = get_page_by_title( 'Home' );
	$blog_page  = get_page_by_title( 'Blog' );
	
	if ( $front_page ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front_page->ID );
	}
	
	if ( $blog_page ) {
		update_option( 'page_for_posts', $blog_page->ID );
	}
	
	// Assign menus to locations.
	$main_menu = get_term_by( 'name', 'Main Menu', 'nav_menu' );
	if ( $main_menu ) {
		set_theme_mod( 'nav_menu_locations', array(
			'primary' => $main_menu->term_id,
		) );
	}
	
	// Update permalink structure.
	update_option( 'permalink_structure', '/%postname%/' );
	flush_rewrite_rules();
	
	// Set default theme options.
	update_option( 'mytheme_setup_completed', true );
}
add_action( 'conjure_after_all_import', 'mytheme_after_import_setup' );

/**
 * ========================================================================
 * COMPLETE CONFIGURATION EXAMPLE
 * ========================================================================
 *
 * Here's a complete example showing how to configure everything:
 */

/**
 * Complete ConjureWP configuration for your theme.
 */
function mytheme_configure_conjurewp() {
	// 1. Redirect to custom welcome page on theme activation.
	add_filter( 'conjure_redirect_on_theme_switch_url', function( $url ) {
		return admin_url( 'admin.php?page=mytheme-welcome' );
	});
	
	// 2. Set custom demo path (if not using auto-discovery).
	add_filter( 'conjurewp_custom_demo_path', function( $path ) {
		return get_template_directory() . '/conjurewp-demos';
	});
	
	// 3. Enable auto-registration of demos.
	add_filter( 'conjurewp_auto_register_demos', '__return_true' );
	
	// 4. Configure logger for more verbose logging during development.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		add_filter( 'conjurewp_logger_config', function( $config ) {
			return array(
				'enable_rotation'  => true,
				'max_files'        => 5,
				'max_file_size_mb' => 10,
				'min_log_level'    => 'DEBUG',
			);
		});
	}
}
add_action( 'after_setup_theme', 'mytheme_configure_conjurewp' );

/**
 * ========================================================================
 * AVAILABLE FILTER HOOKS REFERENCE
 * ========================================================================
 *
 * REDIRECT CONTROL:
 * - conjure_redirect_on_theme_switch_enabled
 *   Controls if redirect happens on theme switch
 *   @param bool $enabled
 *   @return bool
 *
 * - conjure_redirect_on_theme_switch_url
 *   Controls redirect destination URL
 *   @param string $url
 *   @param string $conjure_url
 *   @return string
 *
 * DEMO CONFIGURATION:
 * - conjurewp_custom_demo_path
 *   Set custom demo content directory path
 *   @param string $path
 *   @param string $theme_slug
 *   @return string
 *
 * - conjurewp_auto_register_demos
 *   Enable/disable auto-discovery of demos
 *   @param bool|null $enabled
 *   @return bool
 *
 * - conjure_import_files
 *   Register demo import files
 *   @param array $import_files
 *   @return array
 *
 * LOGGER CONFIGURATION:
 * - conjurewp_logger_config
 *   Configure logger settings
 *   @param array $config
 *   @return array
 *
 * POST-IMPORT ACTIONS:
 * - conjure_after_all_import
 *   Run code after import completes
 *   @param array $selected_import
 *
 * WIZARD STEPS:
 * - conjure_steps
 *   Add, modify, or reorder wizard steps
 *   @param array $steps Array of step definitions
 *   @return array Modified steps array
 *   See examples/custom-steps-example.php for detailed usage
 *
 * - {theme_template}_conjure_steps
 *   Theme-specific step customisation (backward compatibility)
 *   @param array $steps Array of step definitions
 *   @return array Modified steps array
 *   Note: Use 'conjure_steps' filter instead for better compatibility
 *
 * ========================================================================
 * PRIORITY ORDER
 * ========================================================================
 *
 * All configuration follows this priority order:
 * 1. Filter hooks (HIGHEST - theme-level control)
 * 2. Theme auto-discovery (for demo paths)
 * 3. wp-config.php constants (server-level override)
 * 4. Plugin defaults (LOWEST)
 *
 * This ensures themes have full control over configuration, while
 * allowing server administrators to override when necessary.
 *
 * ========================================================================
 */

