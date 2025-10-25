<?php
/**
 * ConjureWP configuration file.
 *
 * @package   ConjureWP
 * @version   1.0.0
 * @link      https://conjurewp.com/
 * @author    Jake Henshall, from nought.digital
 * @copyright Copyright (c) 2018, Conjure WP of Inventionn LLC
 * @licence   Licenced GPLv3 for Open Source Use
 */

if ( ! class_exists( 'Conjure' ) ) {
	return;
}

/**
 * Set directory locations, text strings, and settings.
 */
$config = array(
	'base_path'            => CONJUREWP_PLUGIN_DIR, // Base path of the plugin.
	'base_url'             => CONJUREWP_PLUGIN_URL, // Base URL of the plugin.
	'directory'            => '', // Location / directory where Conjure WP is placed (empty since files are in root).
	'conjure_url'          => 'conjurewp-setup', // The wp-admin page slug where Conjure WP loads.
	'parent_slug'          => 'admin.php', // The wp-admin parent page slug for the admin menu item.
	'capability'           => 'manage_options', // The capability required for this menu to be displayed to the user.
	'child_action_btn_url' => 'https://developer.wordpress.org/themes/advanced-topics/child-themes/', // URL for the 'child-action-link'.
	'dev_mode'             => true, // Enable development mode for testing.
	'license_step'         => true, // EDD license activation step.
	'license_required'     => false, // Require the license activation step.
	'license_help_url'     => 'https://yourstore.com/my-account/', // URL for the 'license-tooltip'.
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
	'admin-menu'               => esc_html__( 'Theme Setup Wizard', 'conjurewp' ),

	/* translators: 1: Title Tag 2: Theme Name 3: Closing Title Tag */
	'title%s%s%s%s'            => esc_html__( '%1$s%2$s Themes &lsaquo; Theme Setup: %3$s%4$s', 'conjurewp' ),
	'return-to-dashboard'      => esc_html__( 'Return to the dashboard', 'conjurewp' ),
	'ignore'                   => esc_html__( 'Disable this wizard', 'conjurewp' ),

	'btn-skip'                 => esc_html__( 'Skip', 'conjurewp' ),
	'btn-next'                 => esc_html__( 'Next', 'conjurewp' ),
	'btn-start'                => esc_html__( 'Start', 'conjurewp' ),
	'btn-no'                   => esc_html__( 'Cancel', 'conjurewp' ),
	'btn-plugins-install'      => esc_html__( 'Install', 'conjurewp' ),
	'btn-child-install'        => esc_html__( 'Install', 'conjurewp' ),
	'btn-content-install'      => esc_html__( 'Install', 'conjurewp' ),
	'btn-import'               => esc_html__( 'Import', 'conjurewp' ),
	'btn-license-activate'     => esc_html__( 'Activate', 'conjurewp' ),
	'btn-license-skip'         => esc_html__( 'Later', 'conjurewp' ),

	/* translators: Theme Name */
	'license-header%s'         => esc_html__( 'Activate %s', 'conjurewp' ),
	/* translators: Theme Name */
	'license-header-success%s' => esc_html__( '%s is Activated', 'conjurewp' ),
	/* translators: Theme Name */
	'license%s'                => esc_html__( 'Enter your license key to enable remote updates and theme support.', 'conjurewp' ),
	'license-label'            => esc_html__( 'License key', 'conjurewp' ),
	'license-success%s'        => esc_html__( 'The theme is already registered, so you can go to the next step!', 'conjurewp' ),
	'license-json-success%s'   => esc_html__( 'Your theme is activated! Remote updates and theme support are enabled.', 'conjurewp' ),
	'license-tooltip'          => esc_html__( 'Need help?', 'conjurewp' ),

	/* translators: Theme Name */
	'welcome-header%s'         => esc_html__( 'Welcome to %s', 'conjurewp' ),
	'welcome-header-success%s' => esc_html__( 'Hi. Welcome back', 'conjurewp' ),
	'welcome%s'                => esc_html__( 'This wizard will set up your theme, install plugins, and import content. It is optional & should take only a few minutes.', 'conjurewp' ),
	'welcome-success%s'        => esc_html__( 'You may have already run this theme setup wizard. If you would like to proceed anyway, click on the "Start" button below.', 'conjurewp' ),

	'child-header'             => esc_html__( 'Install Child Theme', 'conjurewp' ),
	'child-header-success'     => esc_html__( 'You\'re good to go!', 'conjurewp' ),
	'child'                    => esc_html__( 'Let\'s build & activate a child theme so you may easily make theme changes.', 'conjurewp' ),
	'child-success%s'          => esc_html__( 'Your child theme has already been installed and is now activated, if it wasn\'t already.', 'conjurewp' ),
	'child-action-link'        => esc_html__( 'Learn about child themes', 'conjurewp' ),
	'child-json-success%s'     => esc_html__( 'Awesome. Your child theme has already been installed and is now activated.', 'conjurewp' ),
	'child-json-already%s'     => esc_html__( 'Awesome. Your child theme has been created and is now activated.', 'conjurewp' ),

	'plugins-header'           => esc_html__( 'Install Plugins', 'conjurewp' ),
	'plugins-header-success'   => esc_html__( 'You\'re up to speed!', 'conjurewp' ),
	'plugins'                  => esc_html__( 'Let\'s install some essential WordPress plugins to get your site up to speed.', 'conjurewp' ),
	'plugins-success%s'        => esc_html__( 'The required WordPress plugins are all installed and up to date. Press "Next" to continue the setup wizard.', 'conjurewp' ),
	'plugins-action-link'      => esc_html__( 'Advanced', 'conjurewp' ),

	'import-header'            => esc_html__( 'Import Content', 'conjurewp' ),
	'import'                   => esc_html__( 'Let\'s import content to your website, to help you get familiar with the theme.', 'conjurewp' ),
	'import-action-link'       => esc_html__( 'Advanced', 'conjurewp' ),

	'ready-header'             => esc_html__( 'All done. Have fun!', 'conjurewp' ),

	/* translators: Theme Author */
	'ready%s'                  => esc_html__( 'Your theme has been all set up. Enjoy your new theme by %s.', 'conjurewp' ),
	'ready-action-link'        => esc_html__( 'Extras', 'conjurewp' ),
	'ready-big-button'         => esc_html__( 'View your website', 'conjurewp' ),
	'ready-link-1'             => sprintf( '<a href="%1$s" target="_blank">%2$s</a>', 'https://wordpress.org/support/', esc_html__( 'Explore WordPress', 'conjurewp' ) ),
	'ready-link-2'             => sprintf( '<a href="%1$s" target="_blank">%2$s</a>', 'https://github.com/NoughtDigital/ConjureWP', esc_html__( 'Get Help', 'conjurewp' ) ),
	'ready-link-3'             => sprintf( '<a href="%1$s">%2$s</a>', admin_url( 'customize.php' ), esc_html__( 'Start Customizing', 'conjurewp' ) ),
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
 *   /themes/your-theme/conjurewp-demos/
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
 * EXAMPLE: Basic theme integration using demo helper
 *
 * Uncomment this to use the plugin's included demo files for testing:
 */
/*
function conjurewp_demo_import_files( $files ) {
	$demo_path = CONJUREWP_PLUGIN_DIR . 'demo/';

	return array(
		array(
			'import_file_name'             => 'Demo Content',
			'local_import_file'            => $demo_path . 'content.xml',
			'local_import_widget_file'     => $demo_path . 'widgets.json',
			'local_import_customizer_file' => $demo_path . 'customizer.dat',
			'import_notice'                => __( 'This is a demo import for testing. It will import sample content, widgets, and customiser settings.', 'conjurewp' ),
			'preview_url'                  => '',
		),
	);
}
add_filter( 'conjure_import_files', 'conjurewp_demo_import_files' );
*/

$wizard = new Conjure( $config, $strings );
