<?php
/**
 * Example Theme Integration with ConjureWP
 *
 * Add this code to your theme's functions.php file or in a custom plugin
 * to configure ConjureWP for your theme's demo content.
 *
 * @package ConjureWP
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define demo import files.
 *
 * This example shows how to define demo content that can be imported
 * through the ConjureWP setup wizard.
 */
function my_theme_import_files() {
	return array(
		array(
			'import_file_name'             => 'Demo Import 1',
			'categories'                   => array( 'Business', 'Portfolio' ),
			'local_import_file'            => trailingslashit( get_template_directory() ) . 'demo/content.xml',
			'local_import_widget_file'     => trailingslashit( get_template_directory() ) . 'demo/widgets.wie',
			'local_import_customizer_file' => trailingslashit( get_template_directory() ) . 'demo/customizer.dat',
			'import_preview_image_url'     => trailingslashit( get_template_directory_uri() ) . 'demo/preview.jpg',
			'import_notice'                => __( 'This demo includes sample content, widgets, and customizer settings.', 'your-textdomain' ),
			'preview_url'                  => 'https://your-theme-demo.com/',
		),
		// Add more demo imports here if you have multiple demos.
	);
}
add_filter( 'conjure_import_files', 'my_theme_import_files' );

/**
 * Execute custom code after the import.
 *
 * This is where you can set up menus, assign homepage, and do any
 * other post-import configuration.
 *
 * @param int $selected_import The index of the selected import.
 */
function my_theme_after_import_setup( $selected_import ) {
	// Example: Set up navigation menus.
	$main_menu   = get_term_by( 'name', 'Main Menu', 'nav_menu' );
	$footer_menu = get_term_by( 'name', 'Footer Menu', 'nav_menu' );

	if ( $main_menu && $footer_menu ) {
		set_theme_mod(
			'nav_menu_locations',
			array(
				'primary' => $main_menu->term_id,
				'footer'  => $footer_menu->term_id,
			)
		);
	}

	// Example: Assign front page and posts page (blog page).
	$front_page_id = get_page_by_title( 'Home' );
	$blog_page_id  = get_page_by_title( 'Blog' );

	if ( $front_page_id ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front_page_id->ID );
	}

	if ( $blog_page_id ) {
		update_option( 'page_for_posts', $blog_page_id->ID );
	}

	// Example: Set permalink structure.
	// Uncomment if you want to set a specific permalink structure.
	// global $wp_rewrite;
	// $wp_rewrite->set_permalink_structure( '/%postname%/' );
	// flush_rewrite_rules().

	// Example: Set default category.
	$default_category = get_term_by( 'name', 'Uncategorized', 'category' );
	if ( $default_category ) {
		update_option( 'default_category', $default_category->term_id );
	}
}
add_action( 'conjure_after_all_import', 'my_theme_after_import_setup' );

/**
 * Customize the homepage title to look for during import.
 *
 * @param string $title The default homepage title.
 * @return string Modified homepage title.
 */
function my_theme_content_home_page_title( $title ) {
	return 'Home'; // Change this to match your demo's homepage title.
}
add_filter( 'conjure_content_home_page_title', 'my_theme_content_home_page_title' );

/**
 * Customize the blog page title to look for during import.
 *
 * @param string $title The default blog page title.
 * @return string Modified blog page title.
 */
function my_theme_content_blog_page_title( $title ) {
	return 'Blog'; // Change this to match your demo's blog page title.
}
add_filter( 'conjure_content_blog_page_title', 'my_theme_content_blog_page_title' );

/**
 * Example: Redux Framework integration
 *
 * If your theme uses Redux Framework, you can import Redux options like this:
 */
function my_theme_redux_import_files() {
	return array(
		array(
			'import_file_name'  => 'Demo Import with Redux',
			'local_import_file' => trailingslashit( get_template_directory() ) . 'demo/content.xml',
			'import_redux'      => array(
				array(
					'file_url'    => trailingslashit( get_template_directory_uri() ) . 'demo/redux-options.json',
					'option_name' => 'your_redux_option_name', // Replace with your actual Redux option name.
				),
			),
		),
	);
}
// Uncomment the line below if you use Redux.
// add_filter( 'conjure_import_files', 'my_theme_redux_import_files' ).

/**
 * Example: Revolution Slider integration
 *
 * If you want to import Revolution Slider demos:
 */
function my_theme_revslider_import_files() {
	return array(
		array(
			'import_file_name'             => 'Demo with Revolution Slider',
			'local_import_file'            => trailingslashit( get_template_directory() ) . 'demo/content.xml',
			'local_import_rev_slider_file' => trailingslashit( get_template_directory() ) . 'demo/slider.zip',
		),
	);
}
// Uncomment the line below if you use Revolution Slider.
// add_filter( 'conjure_import_files', 'my_theme_revslider_import_files' ).

/**
 * Customize the child theme functions.php content.
 *
 * @param string $output The default functions.php content.
 * @param string $slug   The parent theme slug.
 * @return string Modified functions.php content.
 */
function my_theme_generate_child_functions_php( $output, $slug ) {
	$slug_no_hyphens = strtolower( preg_replace( '#[^a-zA-Z]#', '', $slug ) );

	$output = "
		<?php
		/**
		 * Child Theme Functions
		 * 
		 * @link https://developer.wordpress.org/themes/basics/theme-functions/
		 */
		
		// Enqueue parent and child theme styles
		function {$slug_no_hyphens}_child_enqueue_styles() {
			wp_enqueue_style( '{$slug}-style', get_template_directory_uri() . '/style.css' );
			wp_enqueue_style(
				'{$slug}-child-style',
				get_stylesheet_directory_uri() . '/style.css',
				array( '{$slug}-style' ),
				wp_get_theme()->get('Version')
			);
		}
		add_action( 'wp_enqueue_scripts', '{$slug_no_hyphens}_child_enqueue_styles' );
		
		// Add your custom code below this line
		
	";

	return $output;
}
add_filter( 'conjure_generate_child_functions_php', 'my_theme_generate_child_functions_php', 10, 2 );

/**
 * Additional filter examples:
 *
 * Conjure_generate_child_style_css - Customize child theme style.css
 * conjure_generate_child_screenshot - Customize child theme screenshot
 * conjure_is_theme_registered - Override license registration check
 * conjure_svg_sprite - Change SVG sprite location
 * conjure_loading_spinner - Change loading spinner
 */
