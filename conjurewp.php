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
 * License: GPLv3
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
 */
if ( file_exists( CONJUREWP_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once CONJUREWP_PLUGIN_DIR . 'vendor/autoload.php';
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
	if ( ! isset( $_GET['page'] ) || 'conjurewp-setup' !== $_GET['page'] ) {
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
				'onclick' => 'return confirm("' . esc_js( __( 'Are you sure you want to reset ConjureWP? This will allow you to run the setup wizard again.', 'conjurewp' ) ) . '");',
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
	if ( ! isset( $_GET['conjurewp_reset'] ) || 'true' !== $_GET['conjurewp_reset'] ) {
		return;
	}

	// Verify nonce.
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'conjurewp_reset_nonce' ) ) {
		wp_die( __( 'Security check failed. Please try again.', 'conjurewp' ) );
	}

	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'conjurewp' ) );
	}

	// Get the current theme.
	$theme = wp_get_theme();
	$slug  = strtolower( preg_replace( '#[^a-zA-Z]#', '', $theme->template ) );

	// Delete ConjureWP options.
	delete_option( 'conjure_' . $slug . '_completed' );
	delete_option( 'conjure_' . $slug . '_child' );
	delete_transient( $theme->template . '_conjure_redirect' );
	delete_transient( 'conjure_import_file_base_name' );

	// Log the reset action.
	$logger = conjurewp_get_logger();
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
	if ( ! isset( $_GET['reset'] ) || 'success' !== $_GET['reset'] ) {
		return;
	}

	?>
	<div class="notice notice-success is-dismissible">
		<p>
			<strong><?php _e( 'ConjureWP Reset Successful!', 'conjurewp' ); ?></strong><br>
			<?php _e( 'The setup wizard has been reset. You can now run through the setup process again.', 'conjurewp' ); ?>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'conjurewp_reset_success_notice' );

