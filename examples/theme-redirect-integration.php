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
function conjurewp_redirect_to_welcome_on_activation( $url, $conjure_url ) {
	// Redirect to your custom welcome page instead of ConjureWP wizard.
	return admin_url( 'admin.php?page=ConjureWP-welcome' );
}
add_filter( 'conjure_redirect_on_theme_switch_url', 'conjurewp_redirect_to_welcome_on_activation', 10, 2 );

/**
 * Register custom welcome page in WordPress admin.
 *
 * Add this to your theme's functions.php file.
 */
function conjurewp_add_welcome_page() {
	add_theme_page(
		__( 'Welcome to Your Theme', 'ConjureWP' ), // Page title
		__( 'Welcome', 'ConjureWP' ),                // Menu title
		'manage_options',                             // Capability
		'ConjureWP-welcome',                          // Menu slug
		'conjurewp_render_welcome_page'               // Callback function
	);
}
add_action( 'admin_menu', 'conjurewp_add_welcome_page' );

/**
 * Render the welcome page with link to ConjureWP wizard.
 *
 * Add this to your theme's functions.php file.
 */
function conjurewp_render_welcome_page() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Welcome to Your Theme!', 'ConjureWP' ); ?></h1>
		
		<div class="notice notice-success" style="border-left-color: #46b450;">
			<p style="font-size: 16px; line-height: 1.6;">
				<strong><?php esc_html_e( 'Thank you for choosing our theme!', 'ConjureWP' ); ?></strong><br>
				<?php esc_html_e( 'Let\'s get your site set up in just a few minutes.', 'ConjureWP' ); ?>
			</p>
		</div>
		
		<div class="card" style="max-width: 800px; margin: 20px 0;">
			<h2><?php esc_html_e( 'What happens next?', 'ConjureWP' ); ?></h2>
			<ol style="font-size: 15px; line-height: 1.8;">
				<li><?php esc_html_e( 'Click "Start Setup Wizard" below', 'ConjureWP' ); ?></li>
				<li><?php esc_html_e( 'Install recommended plugins (optional)', 'ConjureWP' ); ?></li>
				<li><?php esc_html_e( 'Import demo content to see how your site will look', 'ConjureWP' ); ?></li>
				<li><?php esc_html_e( 'Customize your site and replace demo content with your own', 'ConjureWP' ); ?></li>
			</ol>
			
			<hr>
			
			<h3><?php esc_html_e( 'Important Notes:', 'ConjureWP' ); ?></h3>
			<ul style="font-size: 14px; line-height: 1.6; color: #666;">
				<li><?php esc_html_e( '✓ Demo import is completely optional', 'ConjureWP' ); ?></li>
				<li><?php esc_html_e( '✓ The process takes 5-10 minutes depending on your server', 'ConjureWP' ); ?></li>
				<li><?php esc_html_e( '✓ Demo content can be removed later if needed', 'ConjureWP' ); ?></li>
				<li><?php esc_html_e( '✓ Recommended: Create a backup before importing', 'ConjureWP' ); ?></li>
			</ul>
		</div>
		
		<p class="submit">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ConjureWP-setup' ) ); ?>" class="button button-primary button-hero">
				<?php esc_html_e( 'Start Setup Wizard', 'ConjureWP' ); ?>
			</a>
			
			<a href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>" class="button button-secondary button-hero">
				<?php esc_html_e( 'Skip & Start Customizing', 'ConjureWP' ); ?>
			</a>
		</p>
		
		<p>
			<a href="<?php echo esc_url( 'https://docs.ConjureWP.com' ); ?>" target="_blank">
				<?php esc_html_e( 'View Documentation', 'ConjureWP' ); ?>
			</a>
			 | 
			<a href="<?php echo esc_url( 'https://support.ConjureWP.com' ); ?>" target="_blank">
				<?php esc_html_e( 'Get Support', 'ConjureWP' ); ?>
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
function conjurewp_conditional_redirect( $enabled ) {
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
// add_filter( 'conjure_redirect_on_theme_switch_enabled', 'conjurewp_conditional_redirect' );

/**
 * ========================================================================
 * INTEGRATION CHECKLIST
 * ========================================================================
 *
 * ✓ Copy the functions you need to your theme's functions.php
 * ✓ Replace 'ConjureWP' with your actual theme text domain
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

