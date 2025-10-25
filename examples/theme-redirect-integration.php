<?php
/**
 * Theme Integration: Custom Redirect on Theme Switch
 *
 * This example shows how to integrate custom redirect behavior into your theme.
 * Copy this code to your theme's functions.php or an includes file.
 *
 * USE CASE: You want users to see your custom welcome page with instructions
 * BEFORE they start importing demo content via ConjureWP.
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
 * QUICK START: Redirect to Custom Page Before Demo Import
 * ========================================================================
 */

/**
 * Redirect to custom welcome page when theme is activated.
 *
 * Add this to your theme's functions.php file.
 */
function yourtheme_redirect_to_welcome_on_activation( $url, $conjure_url ) {
	// Redirect to your custom welcome page instead of ConjureWP wizard.
	return admin_url( 'admin.php?page=yourtheme-welcome' );
}
add_filter( 'conjure_redirect_on_theme_switch_url', 'yourtheme_redirect_to_welcome_on_activation', 10, 2 );

/**
 * Register custom welcome page in WordPress admin.
 *
 * Add this to your theme's functions.php file.
 */
function yourtheme_add_welcome_page() {
	add_theme_page(
		__( 'Welcome to Your Theme', 'yourtheme' ), // Page title
		__( 'Welcome', 'yourtheme' ),                // Menu title
		'manage_options',                             // Capability
		'yourtheme-welcome',                          // Menu slug
		'yourtheme_render_welcome_page'               // Callback function
	);
}
add_action( 'admin_menu', 'yourtheme_add_welcome_page' );

/**
 * Render the welcome page with link to ConjureWP wizard.
 *
 * Add this to your theme's functions.php file.
 */
function yourtheme_render_welcome_page() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Welcome to Your Theme!', 'yourtheme' ); ?></h1>
		
		<div class="notice notice-success" style="border-left-color: #46b450;">
			<p style="font-size: 16px; line-height: 1.6;">
				<strong><?php esc_html_e( 'Thank you for choosing our theme!', 'yourtheme' ); ?></strong><br>
				<?php esc_html_e( 'Let\'s get your site set up in just a few minutes.', 'yourtheme' ); ?>
			</p>
		</div>
		
		<div class="card" style="max-width: 800px; margin: 20px 0;">
			<h2><?php esc_html_e( 'What happens next?', 'yourtheme' ); ?></h2>
			<ol style="font-size: 15px; line-height: 1.8;">
				<li><?php esc_html_e( 'Click "Start Setup Wizard" below', 'yourtheme' ); ?></li>
				<li><?php esc_html_e( 'Install recommended plugins (optional)', 'yourtheme' ); ?></li>
				<li><?php esc_html_e( 'Import demo content to see how your site will look', 'yourtheme' ); ?></li>
				<li><?php esc_html_e( 'Customize your site and replace demo content with your own', 'yourtheme' ); ?></li>
			</ol>
			
			<hr>
			
			<h3><?php esc_html_e( 'Important Notes:', 'yourtheme' ); ?></h3>
			<ul style="font-size: 14px; line-height: 1.6; color: #666;">
				<li><?php esc_html_e( '✓ Demo import is completely optional', 'yourtheme' ); ?></li>
				<li><?php esc_html_e( '✓ The process takes 5-10 minutes depending on your server', 'yourtheme' ); ?></li>
				<li><?php esc_html_e( '✓ Demo content can be removed later if needed', 'yourtheme' ); ?></li>
				<li><?php esc_html_e( '✓ Recommended: Create a backup before importing', 'yourtheme' ); ?></li>
			</ul>
		</div>
		
		<p class="submit">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=conjurewp-setup' ) ); ?>" class="button button-primary button-hero">
				<?php esc_html_e( 'Start Setup Wizard', 'yourtheme' ); ?>
			</a>
			
			<a href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>" class="button button-secondary button-hero">
				<?php esc_html_e( 'Skip & Start Customizing', 'yourtheme' ); ?>
			</a>
		</p>
		
		<p>
			<a href="<?php echo esc_url( 'https://docs.yourtheme.com' ); ?>" target="_blank">
				<?php esc_html_e( 'View Documentation', 'yourtheme' ); ?>
			</a>
			 | 
			<a href="<?php echo esc_url( 'https://support.yourtheme.com' ); ?>" target="_blank">
				<?php esc_html_e( 'Get Support', 'yourtheme' ); ?>
			</a>
		</p>
	</div>
	<?php
}

/**
 * ========================================================================
 * ALTERNATIVE: Disable Redirect Completely
 * ========================================================================
 *
 * If you don't want any redirect when your theme is activated,
 * use this instead of the above code:
 */

// Uncomment this line to disable redirect:
// add_filter( 'conjure_redirect_on_theme_switch_enabled', '__return_false' );

/**
 * ========================================================================
 * ADVANCED: Conditional Redirect Logic
 * ========================================================================
 */

/**
 * Example: Only redirect on fresh WordPress installations.
 *
 * This checks if the site has content and skips redirect if it does.
 */
function yourtheme_conditional_redirect( $enabled ) {
	// Count published posts.
	$post_count = wp_count_posts( 'post' );
	
	// If site has more than the default "Hello World" post, don't redirect.
	if ( $post_count->publish > 1 ) {
		return false;
	}
	
	// Otherwise, allow redirect.
	return $enabled;
}
// Uncomment to use:
// add_filter( 'conjure_redirect_on_theme_switch_enabled', 'yourtheme_conditional_redirect' );

/**
 * ========================================================================
 * INTEGRATION CHECKLIST
 * ========================================================================
 *
 * ✓ Copy the functions you need to your theme's functions.php
 * ✓ Replace 'yourtheme' with your actual theme text domain
 * ✓ Customize the welcome page content to match your theme
 * ✓ Update documentation URLs to your actual docs
 * ✓ Test by switching themes in Appearance > Themes
 *
 * ========================================================================
 * FILTER REFERENCE
 * ========================================================================
 *
 * conjure_redirect_on_theme_switch_enabled
 * - Controls if redirect happens (true/false)
 * - Default: true
 *
 * conjure_redirect_on_theme_switch_url
 * - Controls where to redirect
 * - Default: ConjureWP wizard page
 * - Receives: ($url, $conjure_url)
 *
 * ========================================================================
 */

