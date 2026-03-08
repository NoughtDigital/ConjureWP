<?php
/**
 * ConjureWP Redirect Control Examples
 *
 * This file demonstrates how to control the automatic redirect behavior
 * when switching themes using filter hooks at the theme level.
 *
 * IMPORTANT: Add these examples to your THEME files (functions.php or includes),
 * NOT to the plugin directory, as plugin files are overwritten on updates.
 *
 * @package   ConjureWP
 * @version   1.0.0
 * @link      https://ConjureWP.com/
 * @author    Jake Henshall, from nought.digital
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ========================================================================
 * EXAMPLE 1: Disable Auto-Redirect (Simplest)
 * ========================================================================
 *
 * Add this to your theme's functions.php to completely disable the
 * automatic redirect when users activate your theme.
 */

add_filter( 'conjure_redirect_on_theme_switch_enabled', '__return_false' );

/**
 * ========================================================================
 * EXAMPLE 2: Redirect to Custom Welcome Page
 * ========================================================================
 *
 * Redirect users to your own custom welcome/onboarding page instead
 * of the ConjureWP wizard. Perfect for showing theme-specific instructions.
 */

function conjurewp_redirect_to_welcome_page( $url, $conjure_url ) {
	// Redirect to your custom welcome page.
	return admin_url( 'admin.php?page=ConjureWP-welcome' );
}
add_filter( 'conjure_redirect_on_theme_switch_url', 'conjurewp_redirect_to_welcome_page', 10, 2 );

/**
 * ========================================================================
 * EXAMPLE 3: Redirect to Pre-Import Instructions Page
 * ========================================================================
 *
 * Show users important information BEFORE they start importing demo content.
 * This is exactly what the user requested - redirect to custom page before import.
 */

function conjurewp_redirect_to_pre_import_instructions( $url, $conjure_url ) {
	// Set a transient to track that user came from theme switch.
	set_transient( 'conjurewp_from_theme_switch', 1, HOUR_IN_SECONDS );
	
	// Redirect to custom pre-import instructions page.
	return admin_url( 'admin.php?page=ConjureWP-pre-import' );
}
add_filter( 'conjure_redirect_on_theme_switch_url', 'conjurewp_redirect_to_pre_import_instructions', 10, 2 );

/**
 * Create the pre-import instructions admin page.
 */
function conjurewp_add_pre_import_page() {
	add_submenu_page(
		null, // Hidden from menu.
		__( 'Before Importing Demo Content', 'ConjureWP' ),
		__( 'Pre-Import', 'ConjureWP' ),
		'manage_options',
		'ConjureWP-pre-import',
		'conjurewp_render_pre_import_page'
	);
}
add_action( 'admin_menu', 'conjurewp_add_pre_import_page' );

/**
 * Render the pre-import instructions page.
 */
function conjurewp_render_pre_import_page() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Welcome to Your New Theme!', 'ConjureWP' ); ?></h1>
		
		<div class="notice notice-info">
			<h2><?php esc_html_e( 'Before You Import Demo Content', 'ConjureWP' ); ?></h2>
			<p><strong><?php esc_html_e( 'Please read these important notes:', 'ConjureWP' ); ?></strong></p>
			<ul>
				<li><?php esc_html_e( '✓ The demo import will add sample posts, pages, and media to your site', 'ConjureWP' ); ?></li>
				<li><?php esc_html_e( '✓ Make sure you have a backup before proceeding', 'ConjureWP' ); ?></li>
				<li><?php esc_html_e( '✓ The import process may take 5-10 minutes depending on your server', 'ConjureWP' ); ?></li>
				<li><?php esc_html_e( '✓ Do not close your browser during the import', 'ConjureWP' ); ?></li>
			</ul>
		</div>
		
		<div class="card">
			<h2><?php esc_html_e( 'System Requirements Check', 'ConjureWP' ); ?></h2>
			<table class="widefat">
				<tbody>
					<tr>
						<td><strong><?php esc_html_e( 'WordPress Memory Limit:', 'ConjureWP' ); ?></strong></td>
						<td><?php echo esc_html( WP_MEMORY_LIMIT ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'PHP Version:', 'ConjureWP' ); ?></strong></td>
						<td><?php echo esc_html( phpversion() ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Max Execution Time:', 'ConjureWP' ); ?></strong></td>
						<td><?php echo esc_html( ini_get( 'max_execution_time' ) ); ?>s</td>
					</tr>
				</tbody>
			</table>
		</div>
		
		<p class="submit" style="margin-top: 20px;">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ConjureWP-setup' ) ); ?>" class="button button-primary button-hero">
				<?php esc_html_e( 'Continue to Demo Import Wizard', 'ConjureWP' ); ?>
			</a>
			
			<a href="<?php echo esc_url( admin_url( 'themes.php' ) ); ?>" class="button button-secondary">
				<?php esc_html_e( 'Skip Demo Import', 'ConjureWP' ); ?>
			</a>
		</p>
	</div>
	<?php
}

/**
 * ========================================================================
 * EXAMPLE 4: Conditional Redirect Based on User Role
 * ========================================================================
 *
 * Send different user roles to different pages.
 */

function conjurewp_redirect_by_user_role( $url, $conjure_url ) {
	if ( current_user_can( 'administrator' ) ) {
		// Admins go to the wizard.
		return $url;
	} elseif ( current_user_can( 'editor' ) ) {
		// Editors go to a limited setup page.
		return admin_url( 'admin.php?page=ConjureWP-editor-setup' );
	}
	
	// Others go to dashboard.
	return admin_url();
}
add_filter( 'conjure_redirect_on_theme_switch_url', 'conjurewp_redirect_by_user_role', 10, 2 );

/**
 * ========================================================================
 * EXAMPLE 5: Only Redirect on Fresh Installations
 * ========================================================================
 *
 * Disable redirect if site already has content (not a fresh install).
 */

function conjurewp_redirect_only_on_fresh_install( $enabled ) {
	// Check if site has published posts (allow default "Hello World").
	$post_count = wp_count_posts( 'post' );
	
	if ( $post_count->publish > 1 ) {
		// Site has content, don't redirect.
		return false;
	}
	
	return $enabled;
}
add_filter( 'conjure_redirect_on_theme_switch_enabled', 'conjurewp_redirect_only_on_fresh_install' );

/**
 * ========================================================================
 * EXAMPLE 6: Disable Redirect in Multisite for Super Admins
 * ========================================================================
 *
 * Let super admins switch themes without being redirected.
 */

function conjurewp_disable_redirect_for_super_admins( $enabled ) {
	if ( is_multisite() && is_super_admin() ) {
		return false;
	}
	
	return $enabled;
}
add_filter( 'conjure_redirect_on_theme_switch_enabled', 'conjurewp_disable_redirect_for_super_admins' );

/**
 * ========================================================================
 * EXAMPLE 7: Track Theme Switches and Pass Data
 * ========================================================================
 *
 * Store information about theme switches and pass to redirect page.
 */

function conjurewp_track_theme_switch( $url, $conjure_url ) {
	$previous_theme = get_option( 'conjurewp_previous_theme' );
	$current_theme  = wp_get_theme();
	
	// Store current theme for next switch.
	update_option( 'conjurewp_previous_theme', $current_theme->get_stylesheet() );
	
	// Store switch information in transient.
	set_transient(
		'conjurewp_switch_info',
		array(
			'from'      => $previous_theme,
			'to'        => $current_theme->get_stylesheet(),
			'timestamp' => time(),
			'user_id'   => get_current_user_id(),
		),
		HOUR_IN_SECONDS
	);
	
	// Add parameter to URL so redirect page knows user came from theme switch.
	return add_query_arg( 'theme_switched', '1', $url );
}
add_filter( 'conjure_redirect_on_theme_switch_url', 'conjurewp_track_theme_switch', 10, 2 );

/**
 * ========================================================================
 * EXAMPLE 8: Disable Redirect During Development
 * ========================================================================
 *
 * Automatically disable redirect when WP_DEBUG is enabled.
 */

function conjurewp_disable_redirect_in_debug_mode( $enabled ) {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		return false;
	}
	
	return $enabled;
}
add_filter( 'conjure_redirect_on_theme_switch_enabled', 'conjurewp_disable_redirect_in_debug_mode' );

/**
 * ========================================================================
 * COMPLETE THEME INTEGRATION EXAMPLE
 * ========================================================================
 *
 * Here's a complete example showing how to integrate all redirect controls
 * into your theme. Add this to your theme's functions.php or an includes file.
 */

/**
 * Configure ConjureWP redirect behavior for this theme.
 */
function conjurewp_configure_conjurewp_redirect() {
	// Only redirect on fresh installs.
	add_filter( 'conjure_redirect_on_theme_switch_enabled', function( $enabled ) {
		$post_count = wp_count_posts( 'post' );
		return ( $post_count->publish <= 1 ); // Allow only "Hello World" post.
	});
	
	// Redirect to custom pre-import page.
	add_filter( 'conjure_redirect_on_theme_switch_url', function( $url, $conjure_url ) {
		// Mark that user came from theme switch.
		set_transient( 'conjurewp_show_welcome', 1, HOUR_IN_SECONDS );
		
		// Redirect to our custom page.
		return admin_url( 'admin.php?page=ConjureWP-welcome' );
	}, 10, 2 );
}
add_action( 'after_setup_theme', 'conjurewp_configure_conjurewp_redirect' );

/**
 * ========================================================================
 * AVAILABLE FILTER HOOKS
 * ========================================================================
 *
 * Two filter hooks are available for theme-level control:
 *
 * 1. conjure_redirect_on_theme_switch_enabled
 *    Controls whether redirect happens at all.
 *    
 *    @param bool $enabled Whether redirect is enabled (default: true)
 *    @return bool Modified enabled state
 *    
 *    Example:
 *    add_filter( 'conjure_redirect_on_theme_switch_enabled', '__return_false' );
 *
 * 2. conjure_redirect_on_theme_switch_url
 *    Controls the redirect destination URL.
 *    
 *    @param string $url The redirect URL (default: wizard URL)
 *    @param string $conjure_url The wizard page slug ('ConjureWP-setup')
 *    @return string Modified redirect URL
 *    
 *    Example:
 *    add_filter( 'conjure_redirect_on_theme_switch_url', function( $url ) {
 *        return admin_url( 'admin.php?page=my-custom-page' );
 *    });
 *
 * ========================================================================
 * WHERE TO ADD THESE FILTERS
 * ========================================================================
 *
 * Add these filters to your theme files:
 * - functions.php (most common)
 * - inc/ConjureWP-integration.php (for cleaner organization)
 * - Any file that's included by your theme
 *
 * NEVER edit files in the plugin directory - they'll be overwritten on updates!
 */
