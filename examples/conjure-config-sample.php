<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Conjure WP configuration file.
 *
 * @package   Conjure WP
 * @version   1.0.0
 * @link      https://ConjureWP.com/
 * @author    Jake Henshall, from Nought.digital
 * @copyright Copyright (c) 2018, Conjure WP of Inventionn LLC
 * @license   Licensed GPLv3 for Open Source Use
 */

if ( ! class_exists( 'Conjure' ) ) {
	return;
}

/**
 * Set directory locations, text strings, and settings.
 */
$config = array(
	'directory'            => 'conjure', // Location / directory where Conjure WP is placed in your theme.
	'conjure_url'          => 'conjure', // The wp-admin page slug where Conjure WP loads.
	'parent_slug'          => 'themes.php', // The wp-admin parent page slug for the admin menu item.
	'capability'           => 'manage_options', // The capability required for this menu to be displayed to the user.
	'child_action_btn_url' => 'https://developer.wordpress.org/themes/advanced-topics/child-themes/', // URL for the 'child-action-link'.
	'dev_mode'             => false, // Enable development mode for testing (disabled by default for production builds).
	'license_step'         => false, // EDD license activation step.
	'license_required'     => false, // Require the license activation step.
	'license_help_url'     => '', // URL for the 'license-tooltip'.
	'edd_remote_api_url'   => '', // EDD_Theme_Updater_Admin remote_api_url.
	'edd_item_name'        => '', // EDD_Theme_Updater_Admin item_name.
	'edd_theme_slug'       => '', // EDD_Theme_Updater_Admin item_slug.
	'ready_big_button_url' => '', // Link for the big button on the ready step.
);

$strings = array(
	'admin-menu'               => esc_html__( 'Theme Setup', 'ConjureWP' ),

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
	'btn-license-skip'         => esc_html__( 'Later', 'ConjureWP' ),

	/* translators: Theme Name */
	'license-header%s'         => esc_html__( 'Activate %s', 'ConjureWP' ),
	/* translators: Theme Name */
	'license-header-success%s' => esc_html__( '%s is Activated', 'ConjureWP' ),
	/* translators: Theme Name */
	'license%s'                => esc_html__( 'Enter your license key to enable remote updates and theme support.', 'ConjureWP' ),
	'license-label'            => esc_html__( 'License key', 'ConjureWP' ),
	'license-success%s'        => esc_html__( 'The theme is already registered, so you can go to the next step!', 'ConjureWP' ),
	'license-json-success%s'   => esc_html__( 'Your theme is activated! Remote updates and theme support are enabled.', 'ConjureWP' ),
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
	'plugins-action-link'      => esc_html__( 'Advanced', 'ConjureWP' ),

	'import-header'            => esc_html__( 'Import Content', 'ConjureWP' ),
	'import'                   => esc_html__( 'Let\'s import content to your website, to help you get familiar with the theme.', 'ConjureWP' ),
	'import-action-link'       => esc_html__( 'Advanced', 'ConjureWP' ),

	'ready-header'             => esc_html__( 'All done. Have fun!', 'ConjureWP' ),

	/* translators: Theme Author */
	'ready%s'                  => esc_html__( 'Your theme has been all set up. Enjoy your new theme by %s.', 'ConjureWP' ),
	'ready-action-link'        => esc_html__( 'Extras', 'ConjureWP' ),
	'ready-big-button'         => esc_html__( 'View your website', 'ConjureWP' ),
	'ready-link-1'             => sprintf( '<a href="%1$s" target="_blank">%2$s</a>', 'https://wordpress.org/support/', esc_html__( 'Explore WordPress', 'ConjureWP' ) ),
	'ready-link-2'             => sprintf( '<a href="%1$s" target="_blank">%2$s</a>', 'https://nought.digital/contact/', esc_html__( 'Get Theme Support', 'ConjureWP' ) ),
	'ready-link-3'             => sprintf( '<a href="%1$s">%2$s</a>', admin_url( 'customize.php' ), esc_html__( 'Start Customizing', 'ConjureWP' ) ),
);

$wizard = new Conjure( $config, $strings );
