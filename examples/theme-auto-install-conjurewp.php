<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Modern Auto-Install & Activate ConjureWP Plugin
 *
 * No TGMPA needed! Just add this code to your theme's functions.php
 * and ConjureWP will auto-install and activate when the theme is activated.
 *
 * @package ConjureWP
 * @subpackage Examples
 */

/**
 * SIMPLE MODERN SOLUTION
 * ======================
 * 
 * Copy this entire code block to your theme's functions.php
 * That's it! ConjureWP will auto-install when theme is activated.
 */

/**
 * Auto-install and activate ConjureWP on theme activation.
 */
function conjurewp_auto_install_conjurewp() {
	// Check if ConjureWP is already active.
	if ( class_exists( 'Conjure' ) ) {
		return; // Already active, nothing to do.
	}

	// Check if we have permission to install plugins.
	if ( ! current_user_can( 'install_plugins' ) ) {
		return;
	}

	// Load required WordPress files.
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/misc.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

	// Check if plugin is installed but not activated.
	$plugin_file = 'ConjureWP/ConjureWP.php';
	if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
		// Plugin exists, just activate it.
		activate_plugin( $plugin_file );
		return;
	}

	// Plugin not installed, let's install it.
	include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

	// Get plugin info from WordPress.org.
	$api = plugins_api(
		'plugin_information',
		array(
			'slug'   => 'ConjureWP',
			'fields' => array( 'sections' => false ),
		)
	);

	if ( is_wp_error( $api ) ) {
		return; // Failed to get plugin info.
	}

	// Install the plugin silently.
	$upgrader = new Plugin_Upgrader( new WP_Ajax_Upgrader_Skin() );
	$install  = $upgrader->install( $api->download_link );

	if ( is_wp_error( $install ) ) {
		return; // Installation failed.
	}

	// Activate the plugin.
	activate_plugin( $plugin_file );
}
add_action( 'after_switch_theme', 'conjurewp_auto_install_conjurewp' );

/**
 * Show admin notice if ConjureWP is not active (backup).
 * This appears if auto-install fails for any reason.
 */
function conjurewp_conjurewp_admin_notice() {
	// Only show on admin pages.
	if ( ! is_admin() ) {
		return;
	}

	// Don't show if already active.
	if ( class_exists( 'Conjure' ) ) {
		return;
	}

	// Build install URL.
	$install_url = wp_nonce_url(
		self_admin_url( 'update.php?action=install-plugin&plugin=ConjureWP' ),
		'install-plugin_ConjureWP'
	);

	// Build activate URL (if already installed).
	$activate_url = wp_nonce_url(
		self_admin_url( 'plugins.php?action=activate&plugin=ConjureWP/ConjureWP.php' ),
		'activate-plugin_ConjureWP/ConjureWP.php'
	);

	// Check if plugin exists.
	$plugin_exists = file_exists( WP_PLUGIN_DIR . '/ConjureWP/ConjureWP.php' );
	?>
	<div class="notice notice-warning is-dismissible">
		<p>
			<strong><?php echo esc_html( wp_get_theme()->get( 'Name' ) ); ?></strong> requires the 
			<strong>ConjureWP</strong> plugin to import demo content.
		</p>
		<p>
			<?php if ( $plugin_exists ) : ?>
				<a href="<?php echo esc_url( $activate_url ); ?>" class="button button-primary">
					Activate ConjureWP Now
				</a>
			<?php else : ?>
				<a href="<?php echo esc_url( $install_url ); ?>" class="button button-primary">
					Install ConjureWP Now
				</a>
			<?php endif; ?>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'conjurewp_conjurewp_admin_notice' );

/**
 * ALTERNATIVE: Redirect to Setup Wizard After Auto-Install
 * =========================================================
 * 
 * Automatically redirect users to ConjureWP setup wizard
 * after theme activation (if ConjureWP was just installed).
 */
function conjurewp_redirect_to_conjure_wizard() {
	// Check if this is theme activation.
	if ( get_transient( 'conjurewp_activated' ) ) {
		delete_transient( 'conjurewp_activated' );
		
		// Check if ConjureWP is active.
		if ( class_exists( 'Conjure' ) ) {
			// Redirect to setup wizard.
			wp_safe_redirect( admin_url( 'admin.php?page=ConjureWP-setup' ) );
			exit;
		}
	}
}
add_action( 'admin_init', 'conjurewp_redirect_to_conjure_wizard' );

function conjurewp_set_activation_flag() {
	set_transient( 'conjurewp_activated', true, 60 );
}
add_action( 'after_switch_theme', 'conjurewp_set_activation_flag' );

/**
 * ADVANCED: Install Multiple Plugins
 * ===================================
 * 
 * Auto-install multiple plugins at once.
 */
function conjurewp_auto_install_plugins() {
	// Define plugins to install.
	$plugins = array(
		array(
			'slug' => 'ConjureWP',
			'file' => 'ConjureWP/ConjureWP.php',
		),
		array(
			'slug' => 'contact-form-7',
			'file' => 'contact-form-7/wp-contact-form-7.php',
		),
		// Add more plugins as needed.
	);

	// Check if user has permission.
	if ( ! current_user_can( 'install_plugins' ) ) {
		return;
	}

	// Load required files.
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/misc.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

	foreach ( $plugins as $plugin ) {
		// Skip if already active.
		if ( is_plugin_active( $plugin['file'] ) ) {
			continue;
		}

		// Check if installed but not activated.
		if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin['file'] ) ) {
			activate_plugin( $plugin['file'] );
			continue;
		}

		// Get plugin info from WordPress.org.
		$api = plugins_api(
			'plugin_information',
			array(
				'slug'   => $plugin['slug'],
				'fields' => array( 'sections' => false ),
			)
		);

		if ( is_wp_error( $api ) ) {
			continue;
		}

		// Install the plugin.
		$upgrader = new Plugin_Upgrader( new WP_Ajax_Upgrader_Skin() );
		$install  = $upgrader->install( $api->download_link );

		if ( ! is_wp_error( $install ) ) {
			activate_plugin( $plugin['file'] );
		}
	}
}
// Uncomment to auto-install multiple plugins:
// add_action( 'after_switch_theme', 'conjurewp_auto_install_plugins' );

/**
 * RECOMMENDED: Add "Setup Demo" Link to Admin Menu
 * =================================================
 * 
 * Show a permanent link in admin menu to setup wizard.
 */
function conjurewp_add_demo_setup_menu() {
	// Only show if ConjureWP is active.
	if ( ! class_exists( 'Conjure' ) ) {
		return;
	}

	add_menu_page(
		__( 'Import Demo', 'ConjureWP' ),
		__( 'Import Demo', 'ConjureWP' ),
		'manage_options',
		'ConjureWP-setup',
		'',
		'dashicons-download',
		3 // Position (appears near top of menu)
	);
}
add_action( 'admin_menu', 'conjurewp_add_demo_setup_menu' );

/**
 * COMPLETE SETUP EXAMPLE
 * =======================
 * 
 * Copy these 3 blocks to your theme's functions.php:
 */

// 1. Auto-install on theme activation
add_action( 'after_switch_theme', function() {
	if ( class_exists( 'Conjure' ) || ! current_user_can( 'install_plugins' ) ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/misc.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

	$plugin_file = 'ConjureWP/ConjureWP.php';
	
	if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
		activate_plugin( $plugin_file );
		return;
	}

	$api = plugins_api( 'plugin_information', array( 'slug' => 'ConjureWP', 'fields' => array( 'sections' => false ) ) );
	if ( ! is_wp_error( $api ) ) {
		$upgrader = new Plugin_Upgrader( new WP_Ajax_Upgrader_Skin() );
		$install  = $upgrader->install( $api->download_link );
		if ( ! is_wp_error( $install ) ) {
			activate_plugin( $plugin_file );
		}
	}
});

// 2. Show notice if auto-install failed
add_action( 'admin_notices', function() {
	if ( is_admin() && ! class_exists( 'Conjure' ) ) {
		$install_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=ConjureWP' ), 'install-plugin_ConjureWP' );
		echo '<div class="notice notice-warning is-dismissible">';
		echo '<p><strong>' . esc_html( wp_get_theme()->get( 'Name' ) ) . '</strong> requires <strong>ConjureWP</strong> to import demo content.</p>';
		echo '<p><a href="' . esc_url( $install_url ) . '" class="button button-primary">Install ConjureWP Now</a></p>';
		echo '</div>';
	}
});

// 3. Redirect to wizard after theme activation
add_action( 'after_switch_theme', function() {
	set_transient( 'conjurewp_activated', true, 60 );
});

add_action( 'admin_init', function() {
	if ( get_transient( 'conjurewp_activated' ) ) {
		delete_transient( 'conjurewp_activated' );
		if ( class_exists( 'Conjure' ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=ConjureWP-setup' ) );
			exit;
		}
	}
});

/**
 * HOW IT WORKS
 * ============
 * 
 * When user activates your theme:
 * 
 * 1. ✓ Checks if ConjureWP is installed
 * 2. ✓ If not, downloads from WordPress.org
 * 3. ✓ Installs it silently
 * 4. ✓ Activates it automatically
 * 5. ✓ Redirects to setup wizard
 * 
 * If auto-install fails:
 * 
 * 1. ✓ Shows admin notice
 * 2. ✓ One-click install button
 * 3. ✓ User clicks, it installs
 * 
 * No old libraries needed!
 * No manual plugin installation!
 * Modern, clean, simple!
 */

