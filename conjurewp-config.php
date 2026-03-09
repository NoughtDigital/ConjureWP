<?php
/**
 * ConjureWP configuration file.
 *
 * @package   ConjureWP
 * @version   1.0.0
 * @link      https://ConjureWP.com/
 * @author    Jake Henshall, from nought.digital
 * @copyright Copyright (c) 2018, Conjure WP of Inventionn LLC
 * @licence   Licenced GPLv3 for Open Source Use
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Conjure' ) ) {
	return;
}

/**
 * Set directory locations, text strings, and settings.
 */
$runtime_mode      = function_exists( 'conjurewp_get_runtime_mode' ) ? conjurewp_get_runtime_mode() : 'plugin';
$is_plugin_runtime = 'plugin' === $runtime_mode;

$config = array(
	'base_path'            => function_exists( 'conjurewp_get_runtime_path' ) ? conjurewp_get_runtime_path() : CONJUREWP_PLUGIN_DIR, // Base path of the active runtime.
	'base_url'             => function_exists( 'conjurewp_get_runtime_url' ) ? conjurewp_get_runtime_url() : CONJUREWP_PLUGIN_URL, // Base URL of the active runtime.
	'directory'            => '', // Location / directory where Conjure WP is placed (empty since files are in root).
	'conjure_url'          => $is_plugin_runtime ? 'ConjureWP-setup' : 'conjure', // The wp-admin page slug where Conjure WP loads.
	'parent_slug'          => $is_plugin_runtime ? 'admin.php' : 'themes.php', // The wp-admin parent page slug for the admin menu item.
	'capability'           => 'manage_options', // The capability required for this menu to be displayed to the user.
	'child_action_btn_url' => 'https://developer.wordpress.org/themes/advanced-topics/child-themes/', // URL for the 'child-action-link'.
	'dev_mode'             => false, // Enable development mode for testing (disabled by default for production builds).
	'license_step'         => $is_plugin_runtime, // Plugin mode shows ConjureWP licence flow by default, theme embeds default to hidden.
	'license_required'     => false, // Require the licence activation step (set false to allow users to skip).
	'license_help_url'     => '', // URL for the 'license-tooltip'. Will be set to Freemius account URL if available.

	/**
	 * PREMIUM FEATURES (ConjureWP Licence Required):
	 * - Automatic plugin installation
	 * - Advanced demo importing capabilities
	 * - Priority support
	 * - Remote plugin updates
	 *
	 * FREE FEATURES (No Licence Required):
	 * - Basic theme setup wizard
	 * - Child theme creation
	 * - Manual content import (upload .xml files)
	 * - Customiser settings import
	 * - Widget import
	 *
	 * LIFETIME INTEGRATION (For Theme Developers):
	 * Theme developers who purchased lifetime ConjureWP integration can bypass
	 * the licence requirement for their users. Add to your theme's functions.php:
	 *
	 *   add_filter( 'conjurewp_has_lifetime_integration', '__return_true' );
	 *
	 * Or whitelist specific themes in wp-config.php:
	 *
	 *   define( 'CONJUREWP_LIFETIME_THEMES', array( 'your-theme-slug' ) );
	 *
	 * Note: This is separate from theme licences (EDD) configured below.
	 */
	'edd_remote_api_url'   => 'https://yourstore.com', // EDD_Theme_Updater_Admin remote_api_url.
	'edd_item_name'        => 'Your Theme Name', // EDD_Theme_Updater_Admin item_name.
	'edd_theme_slug'       => 'your-theme-slug', // EDD_Theme_Updater_Admin item_slug.
	'ready_big_button_url' => home_url( '/' ), // Link for the big button on the ready step.

	// Logging configuration.
	'logging'              => array(
		'enable_rotation'  => true, // Enable log file rotation.
		'max_files'        => 5, // Maximum number of rotated log files to keep.
		'max_file_size_mb' => 10, // Maximum log file size in MB before rotation.
		'min_log_level'    => 'INFO', // Minimum log level: DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY.
	),
);

$strings = array(
	'admin-menu'               => $is_plugin_runtime ? esc_html__( 'Theme Setup Wizard', 'ConjureWP' ) : esc_html__( 'Theme Setup', 'ConjureWP' ),

	/* translators: 1: Title Tag 2: Theme Name 3: Closing Title Tag */
	'title%s%s%s%s'            => esc_html__( '%1$s%2$s Themes &lsaquo; Theme Setup: %3$s%4$s', 'ConjureWP' ),
	'return-to-dashboard'      => esc_html__( 'Return to the dashboard', 'ConjureWP' ),
	'ignore'                   => esc_html__( 'Disable this wizard', 'ConjureWP' ),

	'btn-skip'                 => esc_html__( 'Skip', 'ConjureWP' ),
	'btn-next'                 => esc_html__( 'Next', 'ConjureWP' ),
	'btn-start'                => esc_html__( 'Start', 'ConjureWP' ),
	'btn-no'                   => esc_html__( 'Cancel', 'ConjureWP' ),
	'btn-plugins-install'      => esc_html__( 'Install', 'ConjureWP' ),
	'btn-child-install'        => esc_html__( 'Install', 'ConjureWP' ),
	'btn-content-install'      => esc_html__( 'Install', 'ConjureWP' ),
	'btn-import'               => esc_html__( 'Import', 'ConjureWP' ),
	'btn-license-activate'     => esc_html__( 'Activate', 'ConjureWP' ),
	'btn-license-skip'         => esc_html__( 'Free Version', 'ConjureWP' ),

	/* translators: Theme Name (kept for backwards compatibility, but consider removing %s) */
	'license-header%s'         => esc_html__( 'Enter Your ConjureWP Licence Key', 'ConjureWP' ),
	/* translators: Theme Name (kept for backwards compatibility, but consider removing %s) */
	'license-header-success%s' => esc_html__( 'ConjureWP Premium Activated', 'ConjureWP' ),
	/* translators: Theme Name */
	'license%s'                => esc_html__( 'Enter your ConjureWP licence key to unlock premium features like automatic plugin installation and advanced importing. Theme licences are separate.', 'ConjureWP' ),
	'license-label'            => esc_html__( 'ConjureWP licence key', 'ConjureWP' ),
	'license-success%s'        => esc_html__( 'ConjureWP is already registered, so you can go to the next step!', 'ConjureWP' ),
	'license-json-success%s'   => esc_html__( 'ConjureWP is activated! Premium features are now enabled.', 'ConjureWP' ),
	'license-tooltip'          => esc_html__( 'Need help?', 'ConjureWP' ),

	/* translators: Theme Name */
	'welcome-header%s'         => esc_html__( 'Welcome to %s', 'ConjureWP' ),
	'welcome-header-success%s' => esc_html__( 'Hi. Welcome back', 'ConjureWP' ),
	'welcome%s'                => esc_html__( 'This wizard will set up your theme, install plugins, and import content. It is optional & should take only a few minutes.', 'ConjureWP' ),
	'welcome-success%s'        => esc_html__( 'You may have already run this theme setup wizard. If you would like to proceed anyway, click on the "Start" button below.', 'ConjureWP' ),

	'child-header'             => esc_html__( 'Install Child Theme', 'ConjureWP' ),
	'child-header-success'     => esc_html__( 'You\'re good to go!', 'ConjureWP' ),
	'child'                    => esc_html__( 'Let\'s build & activate a child theme so you may easily make theme changes.', 'ConjureWP' ),
	'child-success%s'          => esc_html__( 'Your child theme has already been installed and is now activated, if it wasn\'t already.', 'ConjureWP' ),
	'child-action-link'        => esc_html__( 'Learn about child themes', 'ConjureWP' ),
	'child-json-success%s'     => esc_html__( 'Awesome. Your child theme has already been installed and is now activated.', 'ConjureWP' ),
	'child-json-already%s'     => esc_html__( 'Awesome. Your child theme has been created and is now activated.', 'ConjureWP' ),

	'plugins-header'           => esc_html__( 'Install Plugins', 'ConjureWP' ),
	'plugins-header-success'   => esc_html__( 'You\'re up to speed!', 'ConjureWP' ),
	'plugins'                  => esc_html__( 'Let\'s install some essential WordPress plugins to get your site up to speed.', 'ConjureWP' ),
	'plugins-success%s'        => esc_html__( 'The required WordPress plugins are all installed and up to date. Press "Next" to continue the setup wizard.', 'ConjureWP' ),
	'plugins-action-link'      => esc_html__( 'Plugins', 'ConjureWP' ),

	'import-header'            => esc_html__( 'Import Content', 'ConjureWP' ),
	'import'                   => esc_html__( 'Let\'s import content to your website, to help you get familiar with the theme.', 'ConjureWP' ),
	'import-action-link'       => esc_html__( 'Advanced', 'ConjureWP' ),

	'ready-header'             => esc_html__( 'All done. Have fun!', 'ConjureWP' ),

	/* translators: Theme Author */
	'ready%s'                  => esc_html__( 'Your theme has been all set up. Enjoy your new theme by %s.', 'ConjureWP' ),
	'ready-action-link'        => esc_html__( 'Extras', 'ConjureWP' ),
	'ready-big-button'         => esc_html__( 'View your website', 'ConjureWP' ),
	'ready-link-1'             => sprintf( '<a href="%1$s" target="_blank">%2$s</a>', 'https://wordpress.org/support/', esc_html__( 'Explore WordPress', 'ConjureWP' ) ),
	'ready-link-2'             => sprintf( '<a href="%1$s" target="_blank">%2$s</a>', 'https://github.com/NoughtDigital/ConjureWP', esc_html__( 'Get Help', 'ConjureWP' ) ),
	'ready-link-3'             => sprintf( '<a href="%1$s">%2$s</a>', admin_url( 'customize.php' ), esc_html__( 'Start Customizing', 'ConjureWP' ) ),
);

/**
 * Allow theme developers to override configuration settings.
 *
 * @param array $config Configuration settings.
 */
$config = apply_filters( 'conjurewp_config', $config );

/**
 * Allow theme developers to override text strings.
 *
 * @param array $strings Text strings.
 */
$strings = apply_filters( 'conjurewp_strings', $strings );

/**
 * DEMO IMPORT CONFIGURATION
 *
 * The plugin includes sample demo files in the /demo/ folder for testing only.
 * These files will be overwritten during plugin updates.
 *
 * ========================================================================
 * RECOMMENDED APPROACH: Theme-Level Configuration (Update-Safe)
 * ========================================================================
 *
 * For theme developers, use filter hooks in your theme's functions.php:
 *
 * Option 1: Place demo files in theme directory
 *   /themes/your-theme/ConjureWP-demos/
 *   (Automatically detected - no configuration needed!)
 *
 * Option 2: Custom path via filter hook (for external storage)
 *   add_filter( 'conjurewp_custom_demo_path', function() {
 *       return get_template_directory() . '/demo-content';
 *   });
 *
 * Option 3: Auto-register demos via filter hook
 *   add_filter( 'conjurewp_auto_register_demos', '__return_true' );
 *
 * ========================================================================
 * ALTERNATIVE: Server-Level Configuration (Special Cases Only)
 * ========================================================================
 *
 * For server administrators who need to override theme settings,
 * add these to wp-config.php (above "That's all, stop editing!" line):
 *
 *   define( 'CONJUREWP_DEMO_PATH', '/path/to/demo/files' );
 *   define( 'CONJUREWP_AUTO_REGISTER_DEMOS', true );
 *
 * Note: Filter hooks (theme-level) take priority over wp-config constants.
 *
 * ========================================================================
 *
 * See /examples/theme-integration.php for complete examples.
 */

/**
 * DEMO CONFIGURATION
 *
 * By default, ConjureWP auto-discovers demos from your theme directory.
 *
 * HOW IT WORKS:
 *
 * 1. MULTIPLE DEMOS (Dropdown will appear):
 *    Create folders in your theme like:
 *    /themes/your-theme/ConjureWP-demos/xxx-demo/
 *    /themes/your-theme/ConjureWP-demos/abc-demo/
 *    Each folder should contain: content.xml, widgets.json, customizer.dat
 *
 * 2. SINGLE DEMO (No dropdown):
 *    Place demo files directly in:
 *    /themes/your-theme/ConjureWP-demos/
 *    Or in your theme root directory
 *
 * 3. CURRENT THEME AS DEMO (Fallback):
 *    If no demo files found anywhere, the current theme is used as the demo
 *
 * DISABLE AUTO-DISCOVERY:
 * If you want to manually register demos, add this to functions.php:
 * add_filter( 'conjurewp_auto_register_demos', '__return_false' );
 *
 * Then use the conjure_import_files filter to register demos manually.
 * See examples/ directory for manual registration examples.
 */

$wizard = new Conjure( $config, $strings );
