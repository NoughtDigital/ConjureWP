<?php
/**
 * Plugin Name: Conjure Setup Wizard
 * Plugin URI: https://ConjureWP.com/
 * Description: A powerful WordPress onboarding wizard that helps users set up themes, install plugins, import content, and more. Built on Conjure WP.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: ConjureWP
 * Author URI: https://ConjureWP.com/
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

// Freemius auto-deactivation mechanism.
if ( function_exists( 'con_fs' ) ) {
	con_fs()->set_basename( true, __FILE__ );
} else {
	/**
	 * DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE
	 * `function_exists` CALL ABOVE TO PROPERLY WORK.
	 */

	// Define plugin constants.
	define( 'CONJUREWP_VERSION', '1.0.0' );
	define( 'CONJUREWP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
	define( 'CONJUREWP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
	define( 'CONJUREWP_PLUGIN_FILE', __FILE__ );
	require_once CONJUREWP_PLUGIN_DIR . 'includes/conjurewp-loader.php';

	conjurewp_bootstrap(
		array(
			'mode'      => 'plugin',
			'base_path' => CONJUREWP_PLUGIN_DIR,
			'base_url'  => CONJUREWP_PLUGIN_URL,
			'file'      => CONJUREWP_PLUGIN_FILE,
		)
	);

	/**
	 * Add settings link to plugins page.
	 *
	 * @param array $links Plugin action links.
	 * @return array Modified plugin action links.
	 */
	function conjurewp_add_action_links( $links ) {
		$setup_link = array(
			'<a href="' . admin_url( 'admin.php?page=ConjureWP-setup' ) . '">' . __( 'Run Setup Wizard', 'ConjureWP' ) . '</a>',
		);

		// Add license activation link if Freemius is available and theme doesn't have lifetime integration.
		// Theme developers with lifetime integration shouldn't show license link to end users.
		$has_lifetime_integration = class_exists( 'Conjure_Freemius' ) ? Conjure_Freemius::has_lifetime_integration() : false;

		if ( ! $has_lifetime_integration && function_exists( 'con_fs' ) ) {
			$fs = con_fs();
			if ( $fs && is_object( $fs ) ) {
				// Link to Freemius account page for license activation.
				$account_url = $fs->get_account_url();
				if ( $account_url ) {
					$license_link = array(
						'<a href="' . esc_url( $account_url ) . '">' . __( 'Activate License', 'ConjureWP' ) . '</a>',
					);
					$setup_link = array_merge( $setup_link, $license_link );
				}
			}
		}

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

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'toplevel_page_ConjureWP-setup' !== $screen->id ) {
			return;
		}

		$log_path   = conjurewp_get_log_path();
		$upload_dir = wp_upload_dir();
		$log_url    = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $log_path );

		?>
	<div class="notice notice-info is-dismissible" style="display:none;" id="ConjureWP-log-notice">
		<p>
			<strong><?php esc_html_e( 'ConjureWP Debug Information:', 'ConjureWP' ); ?></strong><br>
			<?php
			/* translators: %s: file path to the log file */
			printf( esc_html__( 'Log file location: %s', 'ConjureWP' ), '<code>' . esc_html( $log_path ) . '</code>' );
			?>
			<br>
			<small><?php esc_html_e( 'Check this file if you encounter any issues during the import process.', 'ConjureWP' ); ?></small>
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
		// Only show when WP_DEBUG is enabled.
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

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
				'id'    => 'ConjureWP-reset',
				'title' => __( 'ConjureWP Reset', 'ConjureWP' ),
				'href'  => '#',
			)
		);

		// Add the reset action as a child menu item.
		$wp_admin_bar->add_node(
			array(
				'parent' => 'ConjureWP-reset',
				'id'     => 'ConjureWP-reset-wizard',
				'title'  => __( 'Reset Setup Wizard', 'ConjureWP' ),
				'href'   => $reset_url,
				'meta'   => array(
					'onclick' => 'return confirm("' . esc_js( __( 'Are you sure you want to reset ConjureWP? This will delete the child theme and allow you to run the setup wizard again.', 'ConjureWP' ) ) . '");',
				),
			)
		);

		// Add a link to run the wizard.
		$wp_admin_bar->add_node(
			array(
				'parent' => 'ConjureWP-reset',
				'id'     => 'ConjureWP-run-wizard',
				'title'  => __( 'Run Setup Wizard', 'ConjureWP' ),
				'href'   => admin_url( 'admin.php?page=ConjureWP-setup' ),
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
			wp_die( esc_html__( 'Security check failed. Please try again.', 'ConjureWP' ) );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'ConjureWP' ) );
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
					$logger->info( __( 'Switching from child theme to parent theme before deletion', 'ConjureWP' ) );
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
					/* translators: %s: Path to the deleted child theme directory. */
					$logger->info( sprintf( __( 'Child theme deleted successfully: %s', 'ConjureWP' ), $child_theme_path ) );
				} else {
					/* translators: %s: Path to the child theme directory that failed to delete. */
					$logger->error( sprintf( __( 'Failed to delete child theme directory: %s', 'ConjureWP' ), $child_theme_path ) );
				}
			} else {
				/* translators: %s: Path to the missing child theme directory. */
				$logger->warning( sprintf( __( 'Child theme directory not found: %s', 'ConjureWP' ), $child_theme_path ) );
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
		$logger->info( __( 'ConjureWP was reset via admin bar', 'ConjureWP' ) );

		// Redirect to the setup wizard; use transient so success notice can show without relying on GET.
		set_transient( 'conjurewp_reset_success', 1, 30 );
		wp_safe_redirect( admin_url( 'admin.php?page=ConjureWP-setup' ) );
		exit;
	}
	add_action( 'admin_init', 'conjurewp_handle_reset', 1 );

	/**
	 * Display success notice after reset.
	 */
	function conjurewp_reset_success_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! get_transient( 'conjurewp_reset_success' ) ) {
			return;
		}
		delete_transient( 'conjurewp_reset_success' );

		?>
	<div class="notice notice-success is-dismissible">
		<p>
			<strong><?php esc_html_e( 'ConjureWP Reset Successful!', 'ConjureWP' ); ?></strong><br>
			<?php esc_html_e( 'The child theme has been deleted and the setup wizard has been reset. You can now run through the setup process again.', 'ConjureWP' ); ?>
		</p>
	</div>
		<?php
	}
	add_action( 'admin_notices', 'conjurewp_reset_success_notice' );
}
