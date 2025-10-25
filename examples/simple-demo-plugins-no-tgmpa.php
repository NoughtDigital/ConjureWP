<?php
/**
 * Simple Demo Plugins Setup
 *
 * This example shows how to use ConjureWP's built-in custom plugin installer.
 * Zero external dependencies required!
 *
 * BENEFITS:
 * - Zero dependencies - works out of the box
 * - Easier setup - just define plugins in your demos
 * - Better UX - seamless one-wizard experience
 * - Supports WordPress.org plugins and custom ZIPs
 *
 * Add this code to your theme's functions.php file.
 *
 * @package   YourTheme
 * @version   2.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ========================================================================
 * STEP 1: Define Your Demos with Plugin Requirements
 * ========================================================================
 *
 * Just define plugins directly in your demos - no external setup needed!
 */
function mytheme_simple_demos() {
	$demo_path = get_template_directory() . '/demo-content/';
	
	return array(
		// Business Demo
		array(
			'import_file_name'     => 'Business Demo',
			'import_file_slug'     => 'business-demo',
			'local_import_file'    => $demo_path . 'business/content.xml',
			'import_preview_image' => get_template_directory_uri() . '/demos/business-preview.jpg',
			
			// Plugins for this demo (from WordPress.org)
			'required_plugins'     => array(
				array(
					'name'     => 'Contact Form 7',
					'slug'     => 'contact-form-7',
					'required' => true, // REQUIRED - user must install
				),
				array(
					'name'     => 'Yoast SEO',
					'slug'     => 'wordpress-seo',
					'required' => false, // RECOMMENDED - user can skip
				),
			),
		),
		
		// E-commerce Demo
		array(
			'import_file_name'     => 'E-commerce Demo',
			'import_file_slug'     => 'ecommerce-demo',
			'local_import_file'    => $demo_path . 'shop/content.xml',
			'import_preview_image' => get_template_directory_uri() . '/demos/shop-preview.jpg',
			
			// Different plugins for this demo
			'required_plugins'     => array(
				array(
					'name'     => 'WooCommerce',
					'slug'     => 'woocommerce',
					'required' => true, // Must have for shop
				),
				array(
					'name'     => 'Contact Form 7',
					'slug'     => 'contact-form-7',
					'required' => false,
				),
			),
		),
		
		// Portfolio Demo with Premium Plugin
		array(
			'import_file_name'     => 'Portfolio Demo',
			'import_file_slug'     => 'portfolio-demo',
			'local_import_file'    => $demo_path . 'portfolio/content.xml',
			
			// Mix of free and premium plugins
			'required_plugins'     => array(
				// Free plugin from WordPress.org
				array(
					'name'     => 'Elementor',
					'slug'     => 'elementor',
					'required' => true,
				),
				// Premium plugin from ZIP file
				array(
					'name'     => 'Elementor Pro',
					'slug'     => 'elementor-pro',
					'source'   => get_template_directory() . '/plugins/elementor-pro.zip',
					'required' => true,
				),
			),
		),
		
		// Minimal Blog - No plugins!
		array(
			'import_file_name'     => 'Minimal Blog',
			'import_file_slug'     => 'minimal-blog',
			'local_import_file'    => $demo_path . 'minimal/content.xml',
			
			// No plugins needed!
			'required_plugins'     => array(),
		),
	);
}
add_filter( 'conjure_import_files', 'mytheme_simple_demos' );

/**
 * ========================================================================
 * STEP 2: Register Plugins with ConjureWP (Optional but Recommended)
 * ========================================================================
 *
 * Register all possible plugins your theme might use.
 * This gives you one place to manage plugin configurations.
 */
function mytheme_register_plugins() {
	// Get the demo plugin manager
	global $conjure_demo_plugin_manager;
	
	if ( ! $conjure_demo_plugin_manager ) {
		return;
	}
	
	$plugins = array(
		// WordPress.org plugins
		'contact-form-7'  => array(
			'name' => 'Contact Form 7',
			'slug' => 'contact-form-7',
		),
		'woocommerce'     => array(
			'name' => 'WooCommerce',
			'slug' => 'woocommerce',
		),
		'elementor'       => array(
			'name' => 'Elementor',
			'slug' => 'elementor',
		),
		'wordpress-seo'   => array(
			'name' => 'Yoast SEO',
			'slug' => 'wordpress-seo',
		),
		
		// Premium/custom plugins
		'elementor-pro'   => array(
			'name'   => 'Elementor Pro',
			'slug'   => 'elementor-pro',
			'source' => get_template_directory() . '/plugins/elementor-pro.zip',
		),
		'my-custom-plugin' => array(
			'name'   => 'My Custom Plugin',
			'slug'   => 'my-custom-plugin',
			'source' => get_template_directory() . '/plugins/my-custom-plugin.zip',
		),
	);
	
	$conjure_demo_plugin_manager->register_plugins( $plugins );
}
add_action( 'init', 'mytheme_register_plugins' );

/**
 * ========================================================================
 * STEP 3: Post-Import Setup (Optional)
 * ========================================================================
 *
 * Configure your site after demo import completes.
 */
function mytheme_after_import( $selected_import ) {
	// Set up menus
	$main_menu = get_term_by( 'name', 'Main Menu', 'nav_menu' );
	if ( $main_menu ) {
		set_theme_mod( 'nav_menu_locations', array(
			'primary' => $main_menu->term_id,
		) );
	}
	
	// Set front page
	$front_page = get_page_by_title( 'Home' );
	if ( $front_page ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front_page->ID );
	}
	
	// Demo-specific setup
	if ( isset( $selected_import['import_file_slug'] ) ) {
		switch ( $selected_import['import_file_slug'] ) {
			case 'ecommerce-demo':
				// WooCommerce setup
				if ( class_exists( 'WooCommerce' ) ) {
					$shop_page = get_page_by_title( 'Shop' );
					if ( $shop_page ) {
						update_option( 'woocommerce_shop_page_id', $shop_page->ID );
					}
				}
				break;
		}
	}
	
	flush_rewrite_rules();
}
add_action( 'conjure_after_all_import', 'mytheme_after_import' );

/**
 * ========================================================================
 * PLUGIN FORMAT REFERENCE
 * ========================================================================
 *
 * WordPress.org Plugin:
 * array(
 *     'name'     => 'Plugin Name',
 *     'slug'     => 'plugin-slug',
 *     'required' => true,  // or false
 * )
 *
 * Custom/Premium Plugin from ZIP:
 * array(
 *     'name'     => 'Premium Plugin',
 *     'slug'     => 'premium-plugin',
 *     'source'   => '/path/to/plugin.zip', // or URL
 *     'required' => true,
 * )
 *
 * Custom/Premium Plugin from URL:
 * array(
 *     'name'     => 'Premium Plugin',
 *     'slug'     => 'premium-plugin',
 *     'source'   => 'https://example.com/premium-plugin.zip',
 *     'required' => true,
 * )
 */

/**
 * ========================================================================
 * HOW IT WORKS
 * ========================================================================
 *
 * 1. User goes to Theme Setup Wizard
 * 2. On Plugins step, plugins are automatically shown
 * 3. Required plugins display with "REQUIRED" badge (will be installed)
 * 4. Recommended plugins show with "optional" badge (can be unchecked)
 * 5. User clicks "Install Plugins"
 * 6. Plugins install automatically from WordPress.org or custom source
 * 7. All plugins activate automatically
 * 8. User continues to Content step
 * 9. Demo imports with all required plugins active
 *
 * ========================================================================
 * FEATURES
 * ========================================================================
 *
 * ✓ Zero external dependencies
 * ✓ Installs from WordPress.org
 * ✓ Installs from ZIP files
 * ✓ Installs from URLs
 * ✓ Automatic activation
 * ✓ Per-demo plugin filtering
 * ✓ Required vs recommended plugins
 * ✓ Visual status indicators
 * ✓ Progress tracking
 * ✓ Error handling
 * ✓ Logging support
 * ✓ AJAX installation
 *
 * ========================================================================
 * TROUBLESHOOTING
 * ========================================================================
 *
 * Plugins not showing?
 * - Check that 'required_plugins' is defined in demo array
 * - Verify plugin slugs are correct
 * - Check logs in wp-content/uploads/conjurewp-logs/
 *
 * Plugin installation failing?
 * - Verify plugin exists on WordPress.org
 * - Check ZIP file path for premium plugins
 * - Ensure write permissions on wp-content/plugins/
 * - Check PHP memory_limit and max_execution_time
 *
 * Demo selector not showing?
 * - This is normal for single-demo setups
 * - Selector only appears when you have 2+ demos defined
 * - If you have multiple demos, ensure at least one has required_plugins
 *
 * ========================================================================
 */

