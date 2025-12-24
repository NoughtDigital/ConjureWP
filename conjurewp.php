<?php
/**
 * Plugin Name: ConjureWP - WordPress Setup Wizard
 * Plugin URI: https://conjurewp.com/
 * Description: A powerful WordPress onboarding wizard that helps users set up themes, install plugins, import content, and more. Built on Conjure WP.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: ConjureWP
 * Author URI: https://conjurewp.com/
 * License: GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: conjurewp
 * Domain Path: /languages
 *
 * @package ConjureWP
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'CONJUREWP_VERSION', '1.0.0' );
define( 'CONJUREWP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CONJUREWP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CONJUREWP_PLUGIN_FILE', __FILE__ );

/**
 * Load Composer dependencies.
 * 
 * FREEMIUS NOTE: The vendor/freemius/ directory is automatically stripped from the
 * WordPress.org free version by Freemius. All code gracefully handles its absence.
 */
if ( file_exists( CONJUREWP_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once CONJUREWP_PLUGIN_DIR . 'vendor/autoload.php';
}

/**
 * Load Freemius integration.
 * 
 * DEPLOYMENT: This file contains code wrapped in @freemius:premium-start/@freemius:premium-end
 * tags. When deployed to WordPress.org via Freemius, the premium code is stripped out, leaving
 * only free-version stubs that grant full access to all features.
 * 
 * FREE VERSION: Conjure_Freemius methods return values that grant full access (no restrictions).
 * PREMIUM VERSION: Includes Freemius SDK for license management (optional feature gating).
 */
if ( file_exists( CONJUREWP_PLUGIN_DIR . 'includes/class-conjure-freemius.php' ) ) {
	require_once CONJUREWP_PLUGIN_DIR . 'includes/class-conjure-freemius.php';
}

/**
 * Load premium features helper.
 * 
 * Works with or without Freemius SDK. In free version, all methods return
 * non-premium values (is_free() returns true, is_premium() returns false, etc.).
 */
if ( file_exists( CONJUREWP_PLUGIN_DIR . 'includes/class-conjure-premium-features.php' ) ) {
	require_once CONJUREWP_PLUGIN_DIR . 'includes/class-conjure-premium-features.php';
}

/**
 * Load Conjure WP class.
 */
require_once CONJUREWP_PLUGIN_DIR . 'class-conjure.php';

/**
 * Load the logger class.
 */
require_once CONJUREWP_PLUGIN_DIR . 'includes/class-conjure-logger.php';

/**
 * Load demo helpers class.
 */
require_once CONJUREWP_PLUGIN_DIR . 'includes/class-conjure-demo-helpers.php';

/**
 * Load theme plugin bundling class.
 */
require_once CONJUREWP_PLUGIN_DIR . 'includes/class-conjure-theme-plugins.php';

/**
 * Auto-merge theme-bundled plugins with demo-specific plugins.
 *
 * Allows theme developers to bundle plugins in /conjurewp-plugins/ folder
 * with a plugins.json configuration file.
 *
 * @param array $demo_plugins  Demo-specific plugins.
 * @param int   $demo_index    Demo index.
 * @param array $selected_demo Demo configuration.
 * @return array Merged plugin list.
 */
function conjurewp_merge_theme_bundled_plugins( $demo_plugins, $demo_index, $selected_demo ) {
	return Conjure_Theme_Plugins::merge_with_demo_plugins( $demo_plugins );
}
add_filter( 'conjure_demo_required_plugins', 'conjurewp_merge_theme_bundled_plugins', 5, 3 );

/**
 * Load the configuration.
 */
require_once CONJUREWP_PLUGIN_DIR . 'conjurewp-config.php';

/**
 * Load admin tools for viewing logs.
 */
if ( is_admin() ) {
	require_once CONJUREWP_PLUGIN_DIR . 'includes/class-conjure-admin-tools.php';
}

/**
 * Load plugin textdomain.
 */
function conjurewp_load_textdomain() {
	load_plugin_textdomain( 'conjurewp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'conjurewp_load_textdomain' );

/**
 * Auto-register demo imports from custom directory.
 *
 * This function automatically discovers and registers demo content
 * if CONJUREWP_AUTO_REGISTER_DEMOS is enabled in wp-config.php
 *
 * @param array $import_files Existing import files.
 * @return array Modified import files with auto-discovered demos.
 */
function conjurewp_auto_register_demos( $import_files ) {
	// Check if auto-registration is enabled.
	if ( ! Conjure_Demo_Helpers::is_auto_register_enabled() ) {
		return $import_files;
	}

	// Auto-discover demos from custom directory.
	$auto_demos = Conjure_Demo_Helpers::auto_discover_demos();

	// Merge with existing demos (auto-discovered demos come first).
	if ( ! empty( $auto_demos ) ) {
		$import_files = array_merge( $auto_demos, $import_files );
	}

	return $import_files;
}
add_filter( 'conjure_import_files', 'conjurewp_auto_register_demos', 5 );

/**
 * Add settings link to plugins page.
 *
 * @param array $links Plugin action links.
 * @return array Modified plugin action links.
 */
function conjurewp_add_action_links( $links ) {
	$setup_link = array(
		'<a href="' . admin_url( 'admin.php?page=conjurewp-setup' ) . '">' . __( 'Run Setup Wizard', 'conjurewp' ) . '</a>',
	);
	return array_merge( $setup_link, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'conjurewp_add_action_links' );

/**
 * Get the logger instance.
 *
 * @return Conjure_Logger The logger instance.
 */
function conjurewp_get_logger() {
	return Conjure_Logger::get_instance();
}

/**
 * Get the log file path.
 *
 * @return string The absolute path to the log file.
 */
function conjurewp_get_log_path() {
	$logger = conjurewp_get_logger();
	return $logger->get_log_path();
}

/**
 * Display admin notice with log file location.
 */
function conjurewp_admin_log_notice() {
	// Only show to administrators.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Only show on ConjureWP pages.
	$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
	if ( 'conjurewp-setup' !== $page ) {
		return;
	}

	$log_path   = conjurewp_get_log_path();
	$upload_dir = wp_upload_dir();
	$log_url    = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $log_path );

	?>
	<div class="notice notice-info is-dismissible" style="display:none;" id="conjurewp-log-notice">
		<p>
			<strong><?php _e( 'ConjureWP Debug Information:', 'conjurewp' ); ?></strong><br>
			<?php
			/* translators: %s: file path to the log file */
			printf( __( 'Log file location: %s', 'conjurewp' ), '<code>' . esc_html( $log_path ) . '</code>' );
			?>
			<br>
			<small><?php _e( 'Check this file if you encounter any issues during the import process.', 'conjurewp' ); ?></small>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'conjurewp_admin_log_notice' );

/**
 * Add reset option to the WordPress admin bar.
 *
 * @param WP_Admin_Bar $wp_admin_bar The admin bar object.
 */
function conjurewp_admin_bar_reset( $wp_admin_bar ) {
	// Only show to administrators.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Create the reset URL with nonce.
	$reset_url = wp_nonce_url(
		add_query_arg( 'conjurewp_reset', 'true', admin_url() ),
		'conjurewp_reset_nonce',
		'_wpnonce'
	);

	// Add the parent menu item.
	$wp_admin_bar->add_node(
		array(
			'id'    => 'conjurewp-reset',
			'title' => __( 'ConjureWP Reset', 'conjurewp' ),
			'href'  => '#',
		)
	);

	// Add the reset action as a child menu item.
	$wp_admin_bar->add_node(
		array(
			'parent' => 'conjurewp-reset',
			'id'     => 'conjurewp-reset-wizard',
			'title'  => __( 'Reset Setup Wizard', 'conjurewp' ),
			'href'   => $reset_url,
			'meta'   => array(
				'onclick' => 'return confirm("' . esc_js( __( 'Are you sure you want to reset ConjureWP? This will delete the child theme and allow you to run the setup wizard again.', 'conjurewp' ) ) . '");',
			),
		)
	);

	// Add a link to run the wizard.
	$wp_admin_bar->add_node(
		array(
			'parent' => 'conjurewp-reset',
			'id'     => 'conjurewp-run-wizard',
			'title'  => __( 'Run Setup Wizard', 'conjurewp' ),
			'href'   => admin_url( 'admin.php?page=conjurewp-setup' ),
		)
	);
}
add_action( 'admin_bar_menu', 'conjurewp_admin_bar_reset', 999 );

/**
 * Handle the ConjureWP reset action.
 */
function conjurewp_handle_reset() {
	// Check if the reset parameter is set.
	$reset_action = isset( $_GET['conjurewp_reset'] ) ? sanitize_text_field( wp_unslash( $_GET['conjurewp_reset'] ) ) : '';
	if ( 'true' !== $reset_action ) {
		return;
	}

	// Verify nonce.
	$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
	if ( ! $nonce || ! wp_verify_nonce( $nonce, 'conjurewp_reset_nonce' ) ) {
		wp_die( __( 'Security check failed. Please try again.', 'conjurewp' ) );
	}

	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'conjurewp' ) );
	}

	// Get the logger.
	$logger = conjurewp_get_logger();

	// Get the current theme.
	$theme    = wp_get_theme();
	$is_child = is_child_theme();
	
	// Determine the parent theme slug (used for option names).
	// If we're on a child theme, $theme->template gives us the parent.
	// If we're on a parent theme, $theme->template gives us the current theme.
	$parent_template = $theme->template;
	$slug            = strtolower( preg_replace( '#[^a-zA-Z]#', '', $parent_template ) );
	
	$logger->info( sprintf( 'Reset initiated. Current theme: %s, Parent template: %s, Slug: %s, Is child: %s', $theme->get_stylesheet(), $parent_template, $slug, $is_child ? 'yes' : 'no' ) );

	// Check if a child theme was created by ConjureWP.
	$child_theme_option = get_option( 'conjure_' . $slug . '_child' );
	
	$logger->info( sprintf( 'Child theme option value: %s', $child_theme_option ? $child_theme_option : 'not set' ) );
	
	if ( $child_theme_option ) {
		// Build child theme slug and path.
		$child_theme_name = $child_theme_option;
		$child_theme_slug = sanitize_title( $child_theme_name );
		$child_theme_path = get_theme_root() . '/' . $child_theme_slug;

		$logger->info( sprintf( 'Attempting to delete child theme: %s at path: %s', $child_theme_slug, $child_theme_path ) );

		// If the child theme directory exists, delete it.
		if ( file_exists( $child_theme_path ) ) {
			// If we're currently using the child theme, switch to parent first.
			if ( $is_child && $theme->get_stylesheet() === $child_theme_slug ) {
				$logger->info( __( 'Switching from child theme to parent theme before deletion', 'conjurewp' ) );
				switch_theme( $parent_template );
				$logger->info( sprintf( 'Switched to parent theme: %s', $parent_template ) );
			}

			// Initialize WP_Filesystem.
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
			global $wp_filesystem;

			// Delete the child theme directory.
			$deleted = $wp_filesystem->delete( $child_theme_path, true );

			if ( $deleted ) {
				$logger->info( sprintf( __( 'Child theme deleted successfully: %s', 'conjurewp' ), $child_theme_path ) );
			} else {
				$logger->error( sprintf( __( 'Failed to delete child theme directory: %s', 'conjurewp' ), $child_theme_path ) );
			}
		} else {
			$logger->warning( sprintf( __( 'Child theme directory not found: %s', 'conjurewp' ), $child_theme_path ) );
		}
	} else {
		$logger->info( 'No child theme option found, skipping child theme deletion.' );
	}

	// Delete ConjureWP options.
	delete_option( 'conjure_' . $slug . '_completed' );
	delete_option( 'conjure_' . $slug . '_child' );
	delete_transient( $parent_template . '_conjure_redirect' );
	delete_transient( 'conjure_import_file_base_name' );

	// Log the reset action.
	$logger->info( __( 'ConjureWP was reset via admin bar', 'conjurewp' ) );

	// Redirect to the setup wizard.
	wp_safe_redirect( admin_url( 'admin.php?page=conjurewp-setup&reset=success' ) );
	exit;
}
add_action( 'admin_init', 'conjurewp_handle_reset', 1 );

/**
 * Display success notice after reset.
 */
function conjurewp_reset_success_notice() {
	// Only show to administrators.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Check if reset was successful.
	$reset_status = isset( $_GET['reset'] ) ? sanitize_text_field( wp_unslash( $_GET['reset'] ) ) : '';
	if ( 'success' !== $reset_status ) {
		return;
	}

	?>
	<div class="notice notice-success is-dismissible">
		<p>
			<strong><?php _e( 'ConjureWP Reset Successful!', 'conjurewp' ); ?></strong><br>
			<?php _e( 'The child theme has been deleted and the setup wizard has been reset. You can now run through the setup process again.', 'conjurewp' ); ?>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'conjurewp_reset_success_notice' );
