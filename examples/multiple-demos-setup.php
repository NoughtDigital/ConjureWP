<?php
/**
 * Example: Multiple Demo Imports with Visual Selector
 *
 * This shows how to configure multiple demos with preview images
 * that appear as a beautiful card grid in the wizard.
 *
 * @package ConjureWP
 * @subpackage Examples
 */

/**
 * VISUAL DEMO SELECTOR
 * ====================
 * 
 * When you have multiple demos, users see a visual card grid:
 * 
 * ┌─────────────────────────────────────────────┐
 * │  Select your demo:                          │
 * │  Choose which demo content to import.       │
 * │                                             │
 * │  ┌─────────┐  ┌─────────┐  ┌─────────┐    │
 * │  │[Image]  │  │[Image]  │  │[Image]  │    │
 * │  │         │  │         │  │         │    │
 * │  │ Main    │  │ Shop    │  │ Minimal │    │
 * │  │ Demo    │  │ Demo    │  │ Demo    │    │
 * │  │         │  │         │  │         │    │
 * │  │Full site│  │E-comm   │  │Simple   │    │
 * │  └─────────┘  └─────────┘  └─────────┘    │
 * └─────────────────────────────────────────────┘
 * 
 * Features:
 * - Visual preview images
 * - Click to select
 * - Selected card highlights
 * - Shows different plugins per demo
 * - Shows different content per demo
 */

/**
 * METHOD 1: Multiple Demos with Auto-Discovery
 * ============================================
 * 
 * Folder structure:
 * 
 * /wp-content/themes/your-theme/conjurewp-demos/
 * ├── main-demo/
 * │   ├── content.xml
 * │   ├── widgets.json
 * │   ├── customizer.dat
 * │   ├── redux-options.json
 * │   ├── slider.zip
 * │   └── preview.jpg              ← Preview image
 * ├── shop-demo/
 * │   ├── content.xml
 * │   ├── widgets.json
 * │   ├── customizer.dat
 * │   └── preview.jpg              ← Preview image
 * └── minimal-demo/
 *     ├── content.xml
 *     ├── widgets.json
 *     └── preview.jpg              ← Preview image
 * 
 * That's it! ConjureWP auto-discovers all folders and creates the card grid.
 * Each folder name becomes the demo name (formatted nicely).
 * 
 * Preview images are auto-detected:
 * - preview.jpg, preview.png, preview.jpeg, preview.gif, preview.webp
 */

/**
 * METHOD 2: Manual Configuration with More Control
 * ===============================================
 * 
 * Add this to your theme's functions.php for full control:
 */
function mytheme_demo_imports() {
	return array(
		// Demo 1: Main/Full Demo
		array(
			'import_file_name'             => 'Main Demo',
			'categories'                   => array( 'Complete', 'Business' ),
			'local_import_file'            => get_template_directory() . '/conjurewp-demos/main/content.xml',
			'local_import_widget_file'     => get_template_directory() . '/conjurewp-demos/main/widgets.json',
			'local_import_customizer_file' => get_template_directory() . '/conjurewp-demos/main/customizer.dat',
			'local_import_redux'           => array(
				array(
					'file_path'   => get_template_directory() . '/conjurewp-demos/main/redux-options.json',
					'option_name' => 'mytheme_options',
				),
			),
			'local_import_rev_slider_file' => get_template_directory() . '/conjurewp-demos/main/slider.zip',
			'import_preview_image_url'     => get_template_directory_uri() . '/conjurewp-demos/main/preview.jpg',
			'preview_url'                  => 'https://demo.yourtheme.com/main',
			'import_notice'                => 'Complete demo with all features including Revolution Slider and WooCommerce.',
		),

		// Demo 2: Shop/E-commerce Demo
		array(
			'import_file_name'             => 'Shop Demo',
			'categories'                   => array( 'E-commerce', 'WooCommerce' ),
			'local_import_file'            => get_template_directory() . '/conjurewp-demos/shop/content.xml',
			'local_import_widget_file'     => get_template_directory() . '/conjurewp-demos/shop/widgets.json',
			'local_import_customizer_file' => get_template_directory() . '/conjurewp-demos/shop/customizer.dat',
			'import_preview_image_url'     => get_template_directory_uri() . '/conjurewp-demos/shop/preview.jpg',
			'preview_url'                  => 'https://demo.yourtheme.com/shop',
			'import_notice'                => 'E-commerce focused demo with WooCommerce products and shop pages.',
		),

		// Demo 3: Minimal/Simple Demo
		array(
			'import_file_name'             => 'Minimal Demo',
			'categories'                   => array( 'Simple', 'Portfolio' ),
			'local_import_file'            => get_template_directory() . '/conjurewp-demos/minimal/content.xml',
			'local_import_widget_file'     => get_template_directory() . '/conjurewp-demos/minimal/widgets.json',
			'local_import_customizer_file' => get_template_directory() . '/conjurewp-demos/minimal/customizer.dat',
			'import_preview_image_url'     => get_template_directory_uri() . '/conjurewp-demos/minimal/preview.jpg',
			'preview_url'                  => 'https://demo.yourtheme.com/minimal',
			'import_notice'                => 'Clean and minimal demo perfect for portfolios and personal sites.',
		),

		// Demo 4: Blog Demo
		array(
			'import_file_name'             => 'Blog Demo',
			'categories'                   => array( 'Blog', 'Magazine' ),
			'local_import_file'            => get_template_directory() . '/conjurewp-demos/blog/content.xml',
			'local_import_widget_file'     => get_template_directory() . '/conjurewp-demos/blog/widgets.json',
			'local_import_customizer_file' => get_template_directory() . '/conjurewp-demos/blog/customizer.dat',
			'import_preview_image_url'     => get_template_directory_uri() . '/conjurewp-demos/blog/preview.jpg',
			'preview_url'                  => 'https://demo.yourtheme.com/blog',
			'import_notice'                => 'Magazine-style blog with multiple post formats and layouts.',
		),
	);
}
add_filter( 'conjure_import_files', 'mytheme_demo_imports' );

/**
 * HOW IT APPEARS IN THE WIZARD
 * =============================
 * 
 * Step 1: WELCOME
 * - User sees "Get Started" button
 * 
 * Step 2: CONTENT (if multiple demos)
 * - User sees VISUAL CARD GRID
 * - Each demo shows:
 *   ✓ Preview image (if provided)
 *   ✓ Demo name as title
 *   ✓ Description (import_notice)
 *   ✓ Hover effect
 * - User clicks a card to select
 * - Selected card highlights with border
 * - Content checkboxes appear below (Content, Widgets, Options, Redux, Revolution Slider)
 * 
 * Step 3: READY
 * - Shows selected demo name
 * - Confirms what will be imported
 * 
 * Step 4: IMPORT
 * - Imports selected demo
 * - Shows progress
 */

/**
 * DEMO-SPECIFIC PLUGINS
 * ======================
 * 
 * You can also show different plugins for different demos:
 */
function mytheme_demo_with_plugins() {
	return array(
		array(
			'import_file_name'     => 'Main Demo',
			'local_import_file'    => get_template_directory() . '/conjurewp-demos/main/content.xml',
			'import_preview_image_url' => get_template_directory_uri() . '/conjurewp-demos/main/preview.jpg',
			// Plugins for this demo only
			'required_plugins'     => array(
				array(
					'slug'     => 'contact-form-7',
					'required' => true,
				),
				array(
					'slug'     => 'elementor',
					'required' => true,
				),
			),
		),
		array(
			'import_file_name'     => 'Shop Demo',
			'local_import_file'    => get_template_directory() . '/conjurewp-demos/shop/content.xml',
			'import_preview_image_url' => get_template_directory_uri() . '/conjurewp-demos/shop/preview.jpg',
			// Different plugins for this demo
			'required_plugins'     => array(
				array(
					'slug'     => 'woocommerce',
					'required' => true,
				),
				array(
					'slug'     => 'contact-form-7',
					'required' => true,
				),
			),
		),
	);
}
// add_filter( 'conjure_import_files', 'mytheme_demo_with_plugins' );

/**
 * PREVIEW IMAGE SPECIFICATIONS
 * ============================
 * 
 * Recommended size: 800x600px (4:3 ratio)
 * Accepted formats: .jpg, .jpeg, .png, .gif, .webp
 * Max file size: Keep under 200KB for fast loading
 * 
 * Good preview images:
 * ✓ Screenshot of homepage
 * ✓ Clear and recognisable
 * ✓ Shows key design elements
 * ✓ Professional quality
 * 
 * Auto-detection (for auto-discovery):
 * - preview.jpg (checked first)
 * - preview.png
 * - preview.jpeg
 * - preview.gif
 * - preview.webp
 */

/**
 * STYLING
 * =======
 * 
 * The card grid is fully styled and responsive:
 * 
 * Desktop: 3 columns
 * Tablet:  2 columns
 * Mobile:  1 column
 * 
 * Cards include:
 * - Image with aspect ratio preservation
 * - Title with proper typography
 * - Description with ellipsis
 * - Hover effects (scale + shadow)
 * - Selected state (blue border)
 * - Click animation
 * 
 * No custom CSS needed!
 */

/**
 * AFTER IMPORT ACTIONS (Per Demo)
 * ================================
 * 
 * Run different code after each demo imports:
 */
function mytheme_after_import_setup( $selected_import ) {
	// Check which demo was imported
	if ( isset( $selected_import['import_file_name'] ) ) {
		$demo_name = $selected_import['import_file_name'];
		
		switch ( $demo_name ) {
			case 'Main Demo':
				// Set homepage for Main Demo
				$home = get_page_by_title( 'Home' );
				if ( $home ) {
					update_option( 'page_on_front', $home->ID );
					update_option( 'show_on_front', 'page' );
				}
				
				// Assign menus
				$locations = get_theme_mod( 'nav_menu_locations' );
				$menu = get_term_by( 'name', 'Main Menu', 'nav_menu' );
				if ( $menu ) {
					$locations['primary'] = $menu->term_id;
					set_theme_mod( 'nav_menu_locations', $locations );
				}
				break;
				
			case 'Shop Demo':
				// Set shop page as homepage for Shop Demo
				if ( class_exists( 'WooCommerce' ) ) {
					$shop = get_page_by_title( 'Shop' );
					if ( $shop ) {
						update_option( 'page_on_front', $shop->ID );
						update_option( 'show_on_front', 'page' );
					}
				}
				break;
				
			case 'Minimal Demo':
				// Different settings for Minimal Demo
				$about = get_page_by_title( 'About' );
				if ( $about ) {
					update_option( 'page_on_front', $about->ID );
					update_option( 'show_on_front', 'page' );
				}
				break;
		}
	}
}
add_action( 'conjure_after_import', 'mytheme_after_import_setup' );

/**
 * COMPLETE EXAMPLE
 * ================
 * 
 * Here's what users experience:
 * 
 * 1. Activate your theme
 * 2. Get prompted to install ConjureWP (via TGMPA)
 * 3. Click "Run Setup Wizard"
 * 4. See beautiful card grid with 3-4 demo options
 * 5. Click their preferred demo card
 * 6. Selected card highlights
 * 7. Import content checkboxes appear
 * 8. Click "Import" button
 * 9. Watch progress as content imports
 * 10. Done! Redirect to their new site
 * 
 * Professional, beautiful, easy!
 */

