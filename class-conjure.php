<?php
/**
 * Conjure WP
 * Better WordPress Theme Onboarding
 *
 * The following code is a derivative work from the
 * Envato WordPress Theme Setup Wizard by David Baker.
 *
 * @package   Conjure WP
 * @version   1.0.0
 * @link      https://ConjureWP.com/
 * @author    Jake Henshall, from Nought.digital
 * @copyright Copyright (c) 2018, Conjure WP of Nought Digital
 * @license   Licensed GPLv3 for Open Source Use
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conjure.
 */
class Conjure {
	/**
	 * Current theme.
	 *
	 * @var object WP_Theme
	 */
	public $theme;

	/**
	 * Current step.
	 *
	 * @var string
	 */
	public $step = '';

	/**
	 * Steps.
	 *
	 * @var    array
	 */
	public $steps = array();

	/**
	 * Demo Plugin Manager instance.
	 *
	 * @var Conjure_Demo_Plugin_Manager
	 */
	public $demo_plugin_manager;

	/**
	 * Importer.
	 *
	 * @var    ConjureWP\Importer\Importer
	 */
	public $importer;

	/**
	 * WP Hook class.
	 *
	 * @var Conjure_Hooks
	 */
	public $hooks;

	/**
	 * Holds the verified import files.
	 *
	 * @var array
	 */
	public $import_files = array();

	/**
	 * Import path resolution service.
	 *
	 * @var Conjure_Import_Service|null
	 */
	public $import_service;

	/**
	 * The base import file name.
	 *
	 * @var string
	 */
	public $import_file_base_name;

	/**
	 * Helper.
	 *
	 * @var    object
	 */
	protected $helper;

	/**
	 * Updater.
	 *
	 * @var    object
	 */
	protected $updater;

	/**
	 * The text string array.
	 *
	 * @var array $strings
	 */
	public $strings = null;

	/**
	 * The base path where Conjure is located.
	 *
	 * @var string
	 */
	public $base_path = null;

	/**
	 * The base URL where Conjure is located.
	 *
	 * @var string
	 */
	public $base_url = null;

	/**
	 * The location where Conjure is located within the theme or plugin.
	 *
	 * @var string $directory
	 */
	public $directory = null;

	/**
	 * Default ACF local JSON path relative to the active theme (from conjurewp-config.php).
	 *
	 * @var string
	 */
	public $acf_json_save_path = 'acf-json';

	/**
	 * Top level admin page.
	 *
	 * @var string $conjure_url
	 */
	public $conjure_url = null;

	/**
	 * The wp-admin parent page slug for the admin menu item.
	 *
	 * @var string $parent_slug
	 */
	protected $parent_slug = null;

	/**
	 * The capability required for this menu to be displayed to the user.
	 *
	 * @var string $capability
	 */
	protected $capability = null;

	/**
	 * The hook suffix returned by add_submenu_page.
	 *
	 * @var string $hook_suffix
	 */
	public $hook_suffix = null;

	/**
	 * The URL for the "Learn more about child themes" link.
	 *
	 * @var string $child_action_btn_url
	 */
	public $child_action_btn_url = null;

	/**
	 * The flag, to mark, if the theme license step should be enabled.
	 *
	 * @var boolean $license_step_enabled
	 */
	public $license_step_enabled = false;

	/**
	 * The URL for the "Where can I find the license key?" link.
	 *
	 * @var string $theme_license_help_url
	 */
	public $theme_license_help_url = null;

	/**
	 * Remove the "Skip" button, if required.
	 *
	 * @var bool
	 */
	public $license_required = null;

	/**
	 * The item name of the EDD product (this theme).
	 *
	 * @var string $edd_item_name
	 */
	public $edd_item_name = null;

	/**
	 * The theme slug of the EDD product (this theme).
	 *
	 * @var string $edd_theme_slug
	 */
	public $edd_theme_slug = null;

	/**
	 * The remote_api_url of the EDD shop.
	 *
	 * @var string $edd_remote_api_url
	 */
	public $edd_remote_api_url = null;

	/**
	 * Turn on dev mode if you're developing.
	 *
	 * @var bool
	 */
	protected $dev_mode = false;

	/**
	 * The option key used to ignore/dismiss the wizard.
	 *
	 * @var string
	 */
	public $ignore = null;

	/**
	 * The object with logging functionality.
	 *
	 * @var Logger $logger
	 */
	public $logger;

	/**
	 * The URL for the big button on the ready step.
	 *
	 * @var string $ready_big_button_url
	 */
	public $ready_big_button_url = null;

	/**
	 * The theme slug.
	 *
	 * @var string $slug
	 */
	public $slug = '';

	/**
	 * Flag to keep wizard locked on the license step until activation.
	 *
	 * @var bool
	 */
	public $license_gate_active = false;

	/**
	 * Wizard UI instance.
	 *
	 * @var Conjure_Wizard_UI
	 */
	protected $wizard_ui;

	/**
	 * Child theme generator instance.
	 *
	 * @var Conjure_Child_Theme_Generator
	 */
	protected $child_theme_generator;

	/**
	 * License manager instance.
	 *
	 * @var Conjure_License_Manager
	 */
	protected $license_manager;

	/**
	 * Step manager instance.
	 *
	 * @var Conjure_Step_Manager
	 */
	protected $step_manager;

	/**
	 * File upload handler instance.
	 *
	 * @var Conjure_File_Upload_Handler
	 */
	protected $file_upload_handler;

	/**
	 * AJAX handler instance.
	 *
	 * @var Conjure_Ajax_Handler
	 */
	protected $ajax_handler;

	/**
	 * Step connector manager instance.
	 *
	 * @var Conjure_Step_Connector_Manager
	 */
	protected $step_connector_manager;

	/**
	 * Step connectors admin instance.
	 *
	 * @var Conjure_Step_Connectors_Admin
	 */
	protected $step_connectors_admin;

	/**
	 * Setup plugin version.
	 *
	 * @access private
	 * @since 1.0
	 * @return void
	 */

	/**
	 * @var Conjure_Wizard_Controller|null
	 */
	public $wizard_controller;

	/**
	 * @var Conjure_Import_Ajax_Handler|null
	 */
	public $import_ajax_handler;

	/**
	 * @var Conjure_Server_Health|null
	 */
	public $server_health;

	public function admin_page() {
		return $this->wizard_controller->admin_page();
	}

	public function steps() {
		return $this->wizard_controller->steps();
	}

	public function welcome() {
		return $this->wizard_controller->welcome();
	}

	public function welcome_handler() {
		return $this->wizard_controller->welcome_handler();
	}

	public function license() {
		return $this->license_manager->render_license_step();
	}

	public function child() {
		return $this->wizard_controller->child();
	}

	public function plugins() {
		return $this->wizard_controller->plugins();
	}

	public function content() {
		return $this->wizard_controller->content();
	}

	public function ready() {
		return $this->wizard_controller->ready();
	}

	protected function get_plugins( $demo_index = null ) {
		return $this->wizard_controller->get_plugins( $demo_index );
	}

	public function get_import_data_info( $selected_import_index = 0 ) {
		return $this->import_ajax_handler->get_import_data_info( $selected_import_index );
	}

	public function _ajax_install_plugin() {
		return $this->import_ajax_handler->_ajax_install_plugin();
	}

	public function _ajax_content() {
		return $this->import_ajax_handler->_ajax_content();
	}

	public function _ajax_get_total_content_import_items() {
		return $this->import_ajax_handler->_ajax_get_total_content_import_items();
	}

	public function _ajax_get_health_metrics() {
		return $this->import_ajax_handler->_ajax_get_health_metrics();
	}

	public function update_selected_import_data_info() {
		return $this->import_ajax_handler->update_selected_import_data_info();
	}

	public function get_import_steps_html( $import_info ) {
		return $this->import_ajax_handler->get_import_steps_html( $import_info );
	}

	public function import_finished() {
		return $this->import_ajax_handler->import_finished();
	}

	public function get_server_health() {
		if ( ! $this->server_health ) {
			conjurewp_require_runtime_include( 'includes/class-conjure-server-health.php' );
			$this->server_health = new Conjure_Server_Health();
		}
		return $this->server_health;
	}

	private function version() {
		if ( ! defined( 'CONJURE_VERSION' ) ) {
			define( 'CONJURE_VERSION', defined( 'CONJUREWP_VERSION' ) ? CONJUREWP_VERSION : '1.0.0' );
		}
	}

	/**
	 * Class Constructor.
	 *
	 * @param array $config Package-specific configuration args.
	 * @param array $strings Text for the different elements.
	 */
	public function __construct( $config = array(), $strings = array() ) {

		$this->version();

		$config = wp_parse_args(
			$config,
			array(
				'base_path'            => get_parent_theme_file_path(),
				'base_url'             => get_parent_theme_file_uri(),
				'directory'            => 'conjure',
				'conjure_url'          => 'conjure',
				'parent_slug'          => 'themes.php',
				'capability'           => 'manage_options',
				'child_action_btn_url' => '',
				'dev_mode'             => '',
				'license_step'         => false,
				'license_help_url'     => '',
				'license_required'     => false,
				'edd_item_name'        => '',
				'edd_theme_slug'       => '',
				'edd_remote_api_url'   => '',
				'logging'              => array(),
				'ready_big_button_url' => home_url( '/' ),
				'acf_json_save_path'   => 'acf-json',
			)
		);

		// Set config arguments.
		$this->base_path              = $config['base_path'];
		$this->base_url               = $config['base_url'];
		$this->directory              = $config['directory'];
		$this->conjure_url            = $config['conjure_url'];
		$this->parent_slug            = $config['parent_slug'];
		$this->capability             = $config['capability'];
		$this->child_action_btn_url   = $config['child_action_btn_url'];
		$this->license_step_enabled   = $config['license_step'];
		$this->theme_license_help_url = $config['license_help_url'];
		$this->license_required       = $config['license_required'];
		$this->edd_item_name          = $config['edd_item_name'];
		$this->edd_theme_slug         = $config['edd_theme_slug'];
		$this->edd_remote_api_url     = $config['edd_remote_api_url'];
		$this->dev_mode               = $config['dev_mode'];
		$this->ready_big_button_url   = $config['ready_big_button_url'];
		$this->acf_json_save_path     = isset( $config['acf_json_save_path'] ) ? $config['acf_json_save_path'] : 'acf-json';

		// Strings passed in from the config file.
		$this->strings = $strings;

		// Retrieve a WP_Theme object.
		$this->theme = wp_get_theme();
		$this->slug  = strtolower( preg_replace( '#[^a-zA-Z]#', '', $this->theme->template ) );

		// Set the ignore option.
		$this->ignore = $this->slug . '_ignore';

		// Get the logger object early, so it can be used in the whole class.
		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-logger.php';

		// Pass logging config if available.
		$logger_config = isset( $config['logging'] ) ? $config['logging'] : array();
		$this->logger  = Conjure_Logger::get_instance( $logger_config );

		// Initialize refactored class components.
		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-step-manager.php';
		$this->step_manager = new Conjure_Step_Manager( $this );

		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-wizard-ui.php';
		$this->wizard_ui = new Conjure_Wizard_UI( $this, $this->step_manager );

		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-step-connector-base.php';
		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-step-connector-manager.php';
		$this->step_connector_manager = new Conjure_Step_Connector_Manager( $this );

		if ( true === $this->dev_mode ) {
			add_filter(
				'conjure_connector_has_pro_license',
				static function ( $has_access ) {
					return true;
				},
				5
			);
		}

		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-child-theme-generator.php';
		$this->child_theme_generator = new Conjure_Child_Theme_Generator( $this );

		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-license-manager.php';
		$this->license_manager = new Conjure_License_Manager( $this, $this->step_manager );

		// Register Freemius filter AFTER License Manager is instantiated.
		if ( class_exists( 'Conjure_Freemius' ) ) {
			add_filter( 'conjurewp_has_free_access', array( $this->license_manager, 'grant_access_for_valid_edd_license' ), 10, 2 );
		}

		conjurewp_require_runtime_include( 'includes/class-conjure-wizard-controller.php' );
		$this->wizard_controller = new Conjure_Wizard_Controller( $this );

		conjurewp_require_runtime_include( 'includes/class-conjure-import-ajax-handler.php' );
		$this->import_ajax_handler = new Conjure_Import_Ajax_Handler( $this );

		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-file-upload-handler.php';
		$this->file_upload_handler = new Conjure_File_Upload_Handler( $this, $this->wizard_ui );

		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-ajax-handler.php';
		$this->ajax_handler = new Conjure_Ajax_Handler( $this, $this->child_theme_generator, $this->license_manager, $this->file_upload_handler );

		// Check if wizard has already been completed.
		$already_setup = get_option( 'conjure_' . $this->slug . '_completed' );

		// Load admin bar functionality for power users to rerun steps (only if CONJURE_TOOLS_ENABLED constant is defined).
		if ( $already_setup && defined( 'CONJURE_TOOLS_ENABLED' ) && CONJURE_TOOLS_ENABLED ) {
			add_action( 'admin_bar_menu', array( $this->step_manager, 'add_admin_bar_rerun_menu' ), 100 );
			add_action( 'admin_init', array( $this->step_manager, 'handle_step_reset' ), 5 );
			add_action( 'admin_notices', array( $this->step_manager, 'display_admin_notices' ) );
		}

		// Register REST API endpoints (always available for hosting dashboards).
		add_action( 'rest_api_init', array( $this, 'register_rest_api' ) );

		// Always register the admin menu and steps so users can access it even after setup is complete.
		// Menu is registered but hidden from sidebar - access via plugin action links or direct URL.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 9 );
		add_action( 'admin_menu', array( $this, 'hide_admin_menu' ), 999 );
		add_action( 'admin_init', array( $this, 'steps' ), 30, 0 );
		add_action( 'admin_init', array( $this, 'admin_page' ), 30, 0 );

		if ( function_exists( 'is_admin' ) && is_admin() ) {
			require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-step-connectors-admin.php';
			$this->step_connectors_admin = new Conjure_Step_Connectors_Admin( $this, $this->step_connector_manager );
		}

		if ( true !== $this->dev_mode && $already_setup ) {
			// Return if Conjure has already completed its setup (admin bar hooks are already registered above if enabled).
			// Note: Menu registration and steps happen above so users can still access the wizard.
			return;
		}

		// Load custom plugin installer.
		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-plugin-installer.php';

		// Load and initialise Demo Plugin Manager.
		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-demo-plugin-manager.php';
		$this->demo_plugin_manager = new Conjure_Demo_Plugin_Manager();

		add_action( 'admin_init', array( $this, 'required_classes' ) );
		add_action( 'admin_init', array( $this, 'redirect' ), 30 );
		add_action( 'after_switch_theme', array( $this, 'switch_theme' ) );
		add_action( 'admin_init', array( $this, 'ignore' ), 5 );
		add_action( 'admin_footer', array( $this->wizard_ui, 'svg_sprite' ) );

		// Register AJAX handlers through the AJAX handler class.
		$this->ajax_handler->register_ajax_handlers();

		add_filter( 'pt-importer/new_ajax_request_response_data', array( $this, 'pt_importer_new_ajax_request_response_data' ) );
		add_action( 'import_end', array( $this, 'after_content_import_setup' ) );
		add_action( 'import_start', array( $this, 'before_content_import_setup' ) );
		add_action( 'conjurewp_register_import_files', array( $this, 'register_import_files' ) );
		add_filter( 'upload_mimes', array( $this->file_upload_handler, 'allow_import_file_types' ) );

		// Register WP-CLI commands if WP-CLI is available.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			add_action( 'admin_init', array( $this, 'register_cli_commands' ) );
		}
	}

	/**
	 * Require necessary classes.
	 */
	public function required_classes() {
		if ( ! class_exists( '\WP_Importer' ) ) {
			require ABSPATH . '/wp-admin/includes/class-wp-importer.php';
		}

		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-downloader.php';
		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-wxr-import-info.php';
		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-wp-importer-logger.php';
		conjurewp_require_runtime_include( 'includes/class-conjure-wxr-importer-bootstrap.php' );
		conjurewp_require_runtime_include( 'includes/class-conjure-server-health.php' );
		$this->server_health = new Conjure_Server_Health();
		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-importer.php';

		$this->importer = new ConjureWP\Importer\Importer( array( 'fetch_attachments' => true ), $this->logger );

		conjurewp_require_runtime_include( 'includes/class-conjure-import-service.php' );
		conjurewp_require_runtime_include( 'includes/class-conjure-import-runner.php' );
		$this->import_service = new Conjure_Import_Service( $this );

		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-widget-importer.php';

		if ( ! class_exists( 'WP_Customize_Setting' ) ) {
			require_once ABSPATH . 'wp-includes/class-wp-customize-setting.php';
		}

		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-customizer-option.php';
		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-customizer-importer.php';
		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-redux-importer.php';
		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-acf-json-importer.php';
		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-gravity-forms-importer.php';
		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-import-archive-helper.php';
		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-connector-importers.php';
		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-connector-upload-registry.php';
		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-hooks.php';

		$this->hooks = new Conjure_Hooks();

		if ( class_exists( 'EDD_Theme_Updater_Admin' ) ) {
			$this->updater = new EDD_Theme_Updater_Admin();
		}
	}

	/**
	 * Set redirection transient on theme switch.
	 *
	 * @since 1.0.0
	 */
	public function switch_theme() {
		// Allow themes to disable redirect via filter (default: true).
		$redirect_enabled = apply_filters( 'conjure_redirect_on_theme_switch_enabled', true );

		// Only set redirect transient if enabled and not a child theme.
		if ( $redirect_enabled && ! is_child_theme() ) {
			set_transient( $this->theme->template . '_conjure_redirect', 1 );
		}
	}

	/**
	 * Redirection transient.
	 *
	 * @since 1.0.0
	 */
	public function redirect() {

		if ( ! get_transient( $this->theme->template . '_conjure_redirect' ) ) {
			return;
		}

		delete_transient( $this->theme->template . '_conjure_redirect' );

		// Get default wizard URL.
		$redirect_url = $this->get_wizard_url();

		// Allow themes to customize redirect URL via filter.
		$redirect_url = apply_filters( 'conjure_redirect_on_theme_switch_url', $redirect_url, $this->conjure_url );

		wp_safe_redirect( $redirect_url );

		exit;
	}

	/**
	 * Get the wizard URL for the active runtime.
	 *
	 * @param array $args Optional query arguments to append.
	 * @return string
	 */
	public function get_wizard_url( $args = array() ) {
		$wizard_url = menu_page_url( $this->conjure_url, false );

		if ( empty( $wizard_url ) ) {
			$admin_file = 'admin.php' === $this->parent_slug ? 'admin.php' : $this->parent_slug;
			$wizard_url = add_query_arg( 'page', $this->conjure_url, admin_url( $admin_file ) );
		}

		if (
			$this->step_connector_manager instanceof Conjure_Step_Connector_Manager &&
			$this->step_connector_manager->is_preview_active()
		) {
			$args[ Conjure_Step_Connector_Manager::PREVIEW_QUERY_ARG ] = $this->step_connector_manager->get_active_preview_token();
		}

		if ( empty( $args ) ) {
			return $wizard_url;
		}

		return add_query_arg( $args, $wizard_url );
	}

	/**
	 * Determine whether the wizard is showing a temporary step preview.
	 *
	 * @return bool
	 */
	public function is_step_preview_active() {
		return $this->step_connector_manager instanceof Conjure_Step_Connector_Manager
			&& $this->step_connector_manager->is_preview_active();
	}

	/**
	 * Give the user the ability to ignore Conjure WP.
	 */
	public function ignore() {

		// Bail out if not on correct page.
		if ( ! isset( $_GET['_wpnonce'] ) || ! isset( $_GET[ $this->ignore ] ) ) {
			return;
		}

		// Verify nonce.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'ConjureWP-ignore-nonce' ) ) {
			return;
		}

		// Check permissions.
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Mark wizard as ignored.
		update_option( 'conjure_' . $this->slug . '_completed', 'ignored' );

		// Redirect to dashboard.
		wp_safe_redirect( admin_url() );
		exit;
	}

	/**
	 * Determine if the user already has theme content installed.
	 * This can happen if swapping from a previous theme or updated the current theme.
	 * We change the UI a bit when updating / swapping to a new theme.
	 *
	 * @access public
	 */
	protected function is_possible_upgrade() {
		return false;
	}

	/**
	 * Add the admin menu item, under Appearance.
	 */
	public function add_admin_menu() {

		// Strings passed in from the config file.
		$strings = $this->strings;

		// If parent_slug is 'admin.php', create a top-level menu instead.
		if ( 'admin.php' === $this->parent_slug ) {
			$this->hook_suffix = add_menu_page(
				esc_html( $strings['admin-menu'] ),
				esc_html( $strings['admin-menu'] ),
				$this->capability,
				$this->conjure_url,
				array( $this, 'admin_page' ),
				'dashicons-admin-generic',
				30
			);
		} else {
			$this->hook_suffix = add_submenu_page(
				esc_html( $this->parent_slug ),
				esc_html( $strings['admin-menu'] ),
				esc_html( $strings['admin-menu'] ),
				$this->capability,
				$this->conjure_url,
				array( $this, 'admin_page' )
			);
		}
	}

	/**
	 * Hide the admin menu from sidebar while keeping the page registered for access control.
	 */
	public function hide_admin_menu() {
		// Hide the menu from sidebar but keep the page registered for access control.
		if ( 'admin.php' === $this->parent_slug ) {
			remove_menu_page( $this->conjure_url );
		} else {
			remove_submenu_page( $this->parent_slug, $this->conjure_url );
		}
	}

	public function header() {
		return $this->wizard_ui->header();
	}

	/**
	 * Output the content for the current step (delegates to Wizard UI).
	 */
	public function body() {
		return $this->wizard_ui->body();
	}

	/**
	 * Output the footer (delegates to Wizard UI).
	 */
	public function footer() {
		return $this->wizard_ui->footer();
	}

	/**
	 * SVG sprite (delegates to Wizard UI).
	 */
	public function svg_sprite() {
		return $this->wizard_ui->svg_sprite();
	}

	/**
	 * Return SVG markup (delegates to Wizard UI).
	 *
	 * @param array $args Parameters needed to display an SVG.
	 * @return string SVG markup.
	 */
	public function svg( $args = array() ) {
		return $this->wizard_ui->svg( $args );
	}

	/**
	 * Allowed HTML for sprites (delegates to Wizard UI).
	 *
	 * @return array
	 */
	public function svg_allowed_html() {
		return $this->wizard_ui->svg_allowed_html();
	}

	/**
	 * Loading spinner (delegates to Wizard UI).
	 */
	public function loading_spinner() {
		return $this->wizard_ui->loading_spinner();
	}

	/**
	 * Allowed HTML for the loading spinner (delegates to Wizard UI).
	 *
	 * @return array
	 */
	public function loading_spinner_allowed_html() {
		return $this->wizard_ui->loading_spinner_allowed_html();
	}

	/**
	 * Setup steps.
	 */

	/**
	 * Output the steps navigation (delegates to Wizard UI).
	 */
	public function step_output() {
		return $this->wizard_ui->step_output();
	}

	/**
	 * Get the step URL (delegates to Wizard UI).
	 *
	 * @param string $step Name of the step, appended to the URL.
	 * @return string
	 */
	public function step_link( $step ) {
		return $this->wizard_ui->step_link( $step );
	}

	/**
	 * Get the next step link (delegates to Wizard UI).
	 *
	 * @return string
	 */
	public function step_next_link() {
		return $this->wizard_ui->step_next_link();
	}

	/**
	 * Introduction step
	 */

	/**
	 * Generate the child theme via AJAX.
	 */
	public function generate_child() {

		// Verify nonce for security.
		check_ajax_referer( 'conjure_nonce', 'wpnonce' );

		// Check if user has permission to switch themes.
		if ( ! current_user_can( 'switch_themes' ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'You do not have permission to perform this action.', 'ConjureWP' ),
				)
			);
		}

		// Strings passed in from the config file.
		$strings = $this->strings;

		// Text strings.
		$success = $strings['child-json-success%s'];
		$already = $strings['child-json-already%s'];

		// Get the parent theme (in case we're already on a child theme).
		$parent_theme = wp_get_theme( $this->theme->template );
		$parent_slug  = $parent_theme->get_stylesheet();
		$name         = $parent_theme->name . ' Child';
		$slug         = sanitize_title( $name );

		$path = get_theme_root() . '/' . $slug;

		if ( ! file_exists( $path ) ) {

			if ( ! WP_Filesystem() ) {
				$error_message = __( 'Unable to initialise the WordPress filesystem. Cannot create child theme.', 'ConjureWP' );
				$this->logger->error( $error_message );

				wp_send_json_error(
					array(
						'message' => esc_html( $error_message ),
					)
				);
			}

			global $wp_filesystem;

			if ( ! $wp_filesystem ) {
				$error_message = __( 'WordPress filesystem is not available. Cannot create child theme.', 'ConjureWP' );
				$this->logger->error( $error_message );

				wp_send_json_error(
					array(
						'message' => esc_html( $error_message ),
					)
				);
			}

			$mkdir_result = $wp_filesystem->mkdir( $path );
			if ( ! $mkdir_result ) {
				$error_message = sprintf(
					/* translators: %s: directory path */
					__( 'Unable to create child theme directory: %s', 'ConjureWP' ),
					$path
				);
				$this->logger->error( $error_message );

				wp_send_json_error(
					array(
						'message' => esc_html( $error_message ),
					)
				);
			}

			$style_result = $wp_filesystem->put_contents( $path . '/style.css', $this->generate_child_style_css( $parent_slug, $parent_theme->name, $parent_theme->author, $parent_theme->version ) );
			if ( ! $style_result ) {
				$error_message = __( 'Unable to create child theme style.css file.', 'ConjureWP' );
				$this->logger->error( $error_message );

				wp_send_json_error(
					array(
						'message' => esc_html( $error_message ),
					)
				);
			}

			$functions_result = $wp_filesystem->put_contents( $path . '/functions.php', $this->generate_child_functions_php( $parent_slug ) );
			if ( ! $functions_result ) {
				$error_message = __( 'Unable to create child theme functions.php file.', 'ConjureWP' );
				$this->logger->error( $error_message );

				wp_send_json_error(
					array(
						'message' => esc_html( $error_message ),
					)
				);
			}

			$this->generate_child_screenshot( $path );

			$allowed_themes          = get_option( 'allowedthemes' );
			$allowed_themes[ $slug ] = true;
			update_option( 'allowedthemes', $allowed_themes );

		} else {

			if ( $this->theme->template !== $slug ) :
				update_option( 'conjure_' . $this->slug . '_child', $name );
				switch_theme( $slug );
			endif;

			wp_send_json(
				array(
					'done'    => 1,
					'message' => sprintf(
						esc_html( $success ),
						$slug
					),
				)
			);
		}

		if ( $this->theme->template !== $slug ) :
			update_option( 'conjure_' . $this->slug . '_child', $name );
			switch_theme( $slug );
		endif;

		// Mark child theme step as completed.
		$this->mark_step_completed( 'child' );

		wp_send_json(
			array(
				'done'    => 1,
				'message' => sprintf(
					esc_html( $already ),
					$name
				),
			)
		);
	}

	/**
	 * Content template for the child theme functions.php file.
	 *
	 * @param string $slug Parent theme slug.
	 */
	public function generate_child_functions_php( $slug ) {

		// Strip any existing '-child' suffix to prevent child_child_child issues.
		$clean_slug = preg_replace( '/-child$/', '', $slug );
		$slug_no_hyphens = strtolower( preg_replace( '#[^a-zA-Z]#', '', $clean_slug ) );

		$output = "
			<?php
			/**
			 * Theme functions and definitions.
			 * This child theme was generated by Conjure WP.
			 *
			 * @link https://developer.wordpress.org/themes/basics/theme-functions/
			 */

			/*
			 * If your child theme has more than one .css file (eg. ie.css, style.css, main.css) then
			 * you will have to make sure to maintain all of the parent theme dependencies.
			 *
			 * Make sure you're using the correct handle for loading the parent theme's styles.
			 * Failure to use the proper tag will result in a CSS file needlessly being loaded twice.
			 * This will usually not affect the site appearance, but it's inefficient and extends your page's loading time.
			 *
			 * @link https://developer.wordpress.org/themes/advanced-topics/child-themes/
			 */
			function {$slug_no_hyphens}_child_enqueue_styles() {
			    wp_enqueue_style( '{$clean_slug}-style' , get_template_directory_uri() . '/style.css' );
			    wp_enqueue_style( '{$clean_slug}-child-style',
			        get_stylesheet_directory_uri() . '/style.css',
			        array( '{$clean_slug}-style' ),
			        wp_get_theme()->get('Version')
			    );
			}

			add_action(  'wp_enqueue_scripts', '{$slug_no_hyphens}_child_enqueue_styles' );\n
		";

		// Let's remove the tabs so that it displays nicely.
		$output = trim( preg_replace( '/\t+/', '', $output ) );

		$this->logger->debug( __( 'The child theme functions.php content was generated', 'ConjureWP' ) );

		// Filterable return.
		return apply_filters( 'conjure_generate_child_functions_php', $output, $slug );
	}

	/**
	 * Content template for the child theme functions.php file.
	 *
	 * @param string $slug              Parent theme slug.
	 * @param string $parent_theme_name Parent theme name.
	 * @param string $author            Parent theme author.
	 * @param string $version           Parent theme version.
	 */
	public function generate_child_style_css( $slug, $parent_theme_name, $author, $version ) {

		$output = "
			/**
			* Theme Name: {$parent_theme_name} Child
			* Description: This is a child theme of {$parent_theme_name}, generated by Conjure WP.
			* Author: {$author}
			* Template: {$slug}
			* Version: {$version}
			*/\n
		";

		// Let's remove the tabs so that it displays nicely.
		$output = trim( preg_replace( '/\t+/', '', $output ) );

		$this->logger->debug( __( 'The child theme style.css content was generated', 'ConjureWP' ) );

		return apply_filters( 'conjure_generate_child_style_css', $output, $slug, $parent_theme_name, $version );
	}

	/**
	 * Generate child theme screenshot file.
	 *
	 * @param string $path    Child theme path.
	 */
	public function generate_child_screenshot( $path ) {

		$screenshot = apply_filters( 'conjure_generate_child_screenshot', '' );

		if ( ! empty( $screenshot ) ) {
			// Get custom screenshot file extension.
			if ( '.png' === substr( $screenshot, -4 ) ) {
				$screenshot_ext = 'png';
			} else {
				$screenshot_ext = 'jpg';
			}
		} else {
			if ( file_exists( $this->base_path . '/screenshot.png' ) ) {
				$screenshot     = $this->base_path . '/screenshot.png';
				$screenshot_ext = 'png';
			} elseif ( file_exists( $this->base_path . '/screenshot.jpg' ) ) {
				$screenshot     = $this->base_path . '/screenshot.jpg';
				$screenshot_ext = 'jpg';
			}
		}

		if ( ! empty( $screenshot ) && file_exists( $screenshot ) ) {
			$copied = copy( $screenshot, $path . '/screenshot.' . $screenshot_ext );

			$this->logger->debug( __( 'The child theme screenshot was copied to the child theme, with the following result', 'ConjureWP' ), array( 'copied' => $copied ) );
		} else {
			$this->logger->debug( __( 'The child theme screenshot was not generated, because of these results', 'ConjureWP' ), array( 'screenshot' => $screenshot ) );
		}
	}

	/**
	 * Import revolution slider.
	 *
	 * @param string $file Path to the revolution slider zip file.
	 */
	public function import_revolution_sliders( $file ) {
		if ( ! class_exists( 'RevSlider', false ) ) {
			return 'failed';
		}

		$importer = new RevSlider();

		$response = $importer->importSliderFromPost( true, true, $file );

		$this->logger->info( __( 'The revolution slider import was executed', 'ConjureWP' ) );

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return 'true';
		}
	}

	/**
	 * Import media files from a zip archive into the WordPress media library.
	 *
	 * @param string $file Path to the media zip file.
	 * @return string|bool Import status for AJAX handlers.
	 */
	public function import_media_zip( $file ) {
		if ( empty( $file ) || ! file_exists( $file ) ) {
			$this->logger->error( __( 'Media zip file was not found.', 'ConjureWP' ) );
			return false;
		}

		if ( ! function_exists( 'unzip_file' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$upload_dir = wp_upload_dir();
		$extract_to = trailingslashit( $upload_dir['basedir'] ) . 'conjure-media-' . gmdate( 'Y-m-d-His' );

		wp_mkdir_p( $extract_to );

		$unzipped = unzip_file( $file, $extract_to );

		if ( is_wp_error( $unzipped ) ) {
			$this->logger->error(
				__( 'Failed to extract media zip.', 'ConjureWP' ),
				array( 'error' => $unzipped->get_error_message() )
			);
			return false;
		}

		$image_extensions = array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif' );
		$imported         = 0;
		$iterator         = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $extract_to, FilesystemIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $media_file ) {
			if ( ! $media_file->isFile() ) {
				continue;
			}

			$extension = strtolower( $media_file->getExtension() );

			if ( ! in_array( $extension, $image_extensions, true ) ) {
				continue;
			}

			$file_path = $media_file->getPathname();
			$file_name = $media_file->getFilename();
			$mime_type = wp_check_filetype( $file_name )['type'];

			if ( empty( $mime_type ) ) {
				continue;
			}

			$tmp_name = wp_tempnam( $file_name );

			if ( ! $tmp_name || ! copy( $file_path, $tmp_name ) ) {
				continue;
			}

			$file_array = array(
				'name'     => $file_name,
				'tmp_name' => $tmp_name,
			);

			$attachment_id = media_handle_sideload( $file_array, 0 );

			if ( file_exists( $tmp_name ) ) {
				wp_delete_file( $tmp_name );
			}

			if ( is_wp_error( $attachment_id ) ) {
				$this->logger->warning(
					__( 'Skipped a file from the media zip.', 'ConjureWP' ),
					array(
						'file'  => $file_name,
						'error' => $attachment_id->get_error_message(),
					)
				);
				continue;
			}

			++$imported;
		}

		// Remove extracted files after import.
		if ( function_exists( 'wp_delete_file' ) ) {
			$this->delete_directory( $extract_to );
		}

		$this->logger->info(
			__( 'Media zip import completed.', 'ConjureWP' ),
			array( 'imported' => $imported )
		);

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return $imported > 0 ? 'true' : false;
		}

		return $imported > 0;
	}

	/**
	 * Recursively delete a directory.
	 *
	 * @param string $directory Directory path.
	 * @return void
	 */
	private function delete_directory( $directory ) {
		if ( ! is_dir( $directory ) ) {
			return;
		}

		$items = scandir( $directory );

		if ( ! is_array( $items ) ) {
			return;
		}

		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}

			$path = $directory . DIRECTORY_SEPARATOR . $item;

			if ( is_dir( $path ) ) {
				$this->delete_directory( $path );
			} else {
				wp_delete_file( $path );
			}
		}

		rmdir( $directory );
	}

	/**
	 * Change the new AJAX request response data.
	 *
	 * @param array $data The default data.
	 *
	 * @return array The updated data.
	 */
	public function pt_importer_new_ajax_request_response_data( $data ) {
		$data['url']      = admin_url( 'admin-ajax.php' );
		$data['message']  = esc_html__( 'Installing', 'ConjureWP' );
		$data['proceed']  = 'true';
		$data['action']   = 'conjure_content';
		$data['content']  = 'content';
		$data['_wpnonce'] = wp_create_nonce( 'conjure_nonce' );
		$data['hash']     = md5( (string) wp_rand() ); // Has to be unique (check JS code catching this AJAX response).

		return $data;
	}

	/**
	 * Get the first post matching an exact title.
	 *
	 * @param string       $title     The title to find.
	 * @param string|array $post_type Optional. Post type or types. Defaults to 'page'.
	 *
	 * @return WP_Post|null
	 */
	private function get_post_by_title( $title, $post_type = 'page' ) {
		$query = new WP_Query(
			array(
				'post_type'              => $post_type,
				'title'                  => $title,
				'post_status'            => 'any',
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'ignore_sticky_posts'    => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		if ( empty( $query->posts[0] ) || ! $query->posts[0] instanceof WP_Post ) {
			return null;
		}

		return $query->posts[0];
	}

	/**
	 * After content import setup code.
	 */
	public function after_content_import_setup() {
		// Set static homepage.
		$homepage = $this->get_post_by_title( apply_filters( 'conjure_content_home_page_title', 'Home' ) );

		if ( $homepage ) {
			update_option( 'page_on_front', $homepage->ID );
			update_option( 'show_on_front', 'page' );

			$this->logger->debug( __( 'The home page was set', 'ConjureWP' ), array( 'homepage_id' => $homepage ) );
		}

		// Set static blog page.
		$blogpage = $this->get_post_by_title( apply_filters( 'conjure_content_blog_page_title', 'Blog' ) );

		if ( $blogpage ) {
			update_option( 'page_for_posts', $blogpage->ID );
			update_option( 'show_on_front', 'page' );

			$this->logger->debug( __( 'The blog page was set', 'ConjureWP' ), array( 'blog_page_id' => $blogpage ) );
		}
	}

	/**
	 * Before content import setup code.
	 */
	public function before_content_import_setup() {
		// Update the Hello World! post by making it a draft.
		$hello_world = $this->get_post_by_title( 'Hello World!', 'post' );

		if ( ! empty( $hello_world ) && apply_filters( 'conjure_draft_hello_world', true ) ) {
			$hello_world->post_status = 'draft';
			wp_update_post( $hello_world );

			$this->logger->debug( __( 'The Hello world post status was set to draft', 'ConjureWP' ) );
		}
	}

	/**
	 * Register the import files via the `conjure_import_files` filter.
	 */
	public function register_import_files() {
		$this->import_files = $this->validate_import_file_info( apply_filters( 'conjure_import_files', array() ) );
	}

	/**
	 * Register WP-CLI commands.
	 */
	public function register_cli_commands() {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}

		conjurewp_require_runtime_include( 'includes/class-conjure-import-service.php' );
		conjurewp_require_runtime_include( 'includes/class-conjure-import-runner.php' );
		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-cli.php';

		// Register the CLI commands.
		$cli = new Conjure_CLI( $this );
		WP_CLI::add_command( 'conjure list', array( $cli, 'list_demos' ) );
		WP_CLI::add_command( 'conjure import', array( $cli, 'import' ) );
		WP_CLI::add_command( 'conjure validate-theme-plugins', array( $cli, 'validate_theme_plugins' ) );
		WP_CLI::add_command( 'conjure list-theme-plugins', array( $cli, 'list_theme_plugins' ) );
		WP_CLI::add_command( 'conjure test-plugin-download', array( $cli, 'test_plugin_download' ) );
		WP_CLI::add_command( 'conjure connectors-smoke', array( $cli, 'connectors_smoke' ) );
	}

	/**
	 * Register REST API endpoints.
	 */
	public function register_rest_api() {
		conjurewp_require_runtime_include( 'includes/class-conjure-import-service.php' );
		conjurewp_require_runtime_include( 'includes/class-conjure-import-runner.php' );
		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-rest-api.php';

		// Initialize and register REST API routes.
		$rest_api = new Conjure_REST_API( $this );
		$rest_api->register_routes();
	}

	/**
	 * Filter through the array of import files and get rid of those who do not comply.
	 *
	 * @param  array $import_files list of arrays with import file details.
	 * @return array list of filtered arrays.
	 */
	public function validate_import_file_info( $import_files ) {
		$filtered_import_file_info = array();

		foreach ( $import_files as $import_file ) {
			if ( ! empty( $import_file['import_file_name'] ) ) {
				$filtered_import_file_info[] = $import_file;
			} else {
				$this->logger->warning( __( 'This predefined demo import does not have the name parameter: import_file_name', 'ConjureWP' ), $import_file );
			}
		}

		return $filtered_import_file_info;
	}

	/**
	 * Set the import file base name.
	 * Check if an existing base name is available (saved in a transient).
	 */
	public function set_import_file_base_name() {
		$this->ensure_import_service();
		$this->import_service->set_import_file_base_name();
	}

	/**
	 * Get the import file paths.
	 *
	 * @param int $selected_import_index The index of the selected import.
	 * @return array
	 */
	public function get_import_files_paths( $selected_import_index ) {
		$this->ensure_import_service();
		return $this->import_service->get_import_files_paths( $selected_import_index );
	}

	/**
	 * Lazy-load the import path service.
	 */
	private function ensure_import_service() {
		if ( $this->import_service instanceof Conjure_Import_Service ) {
			return;
		}

		if ( ! class_exists( 'Conjure_Import_Service' ) ) {
			conjurewp_require_runtime_include( 'includes/class-conjure-import-service.php' );
		}

		$this->import_service = new Conjure_Import_Service( $this );
	}

	/**
	 * AJAX callback for the 'conjure_update_selected_import_data_info' action.
	 */

	/**
	 * Get the upload directory for Conjure files.
	 *
	 * @return string
	 */
	private function get_upload_dir() {
		$upload_dir = wp_upload_dir();
		$conjure_dir = trailingslashit( $upload_dir['basedir'] ) . 'conjure-uploads/';

		if ( ! file_exists( $conjure_dir ) ) {
			$mkdir_result = wp_mkdir_p( $conjure_dir );

			if ( ! $mkdir_result || ! file_exists( $conjure_dir ) ) {
				$error_message = sprintf(
					/* translators: %s: Upload directory path. */
					__( 'Failed to create upload directory: %s', 'ConjureWP' ),
					$conjure_dir
				);

				$this->logger->error( $error_message );

				return false;
			}

			// Add .htaccess for security (Apache 2.4+ syntax).
			$htaccess_content = "# Deny access to all files in this directory\n";
			$htaccess_content .= "<IfModule mod_authz_core.c>\n";
			$htaccess_content .= "    Require all denied\n";
			$htaccess_content .= "</IfModule>\n";
			$htaccess_content .= "<IfModule !mod_authz_core.c>\n";
			$htaccess_content .= "    Order deny,allow\n";
			$htaccess_content .= "    Deny from all\n";
			$htaccess_content .= "</IfModule>\n";
			$htaccess_file = $conjure_dir . '.htaccess';
			global $wp_filesystem;
			if ( ! function_exists( 'WP_Filesystem' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}
			WP_Filesystem();

			$htaccess_result = $wp_filesystem->put_contents( $htaccess_file, $htaccess_content, FS_CHMOD_FILE );

			if ( false === $htaccess_result ) {
				$error_message = sprintf(
					/* translators: %s: Upload directory path. */
					__( 'Failed to create .htaccess file in upload directory: %s', 'ConjureWP' ),
					$conjure_dir
				);

				$this->logger->error( $error_message );
			}

			// Add index.php to prevent directory listing.
			$index_file   = $conjure_dir . 'index.php';
			$index_result = $wp_filesystem->put_contents( $index_file, '<?php // Silence is golden.', FS_CHMOD_FILE );

			if ( false === $index_result ) {
				$error_message = sprintf(
					/* translators: %s: Upload directory path. */
					__( 'Failed to create index.php file in upload directory: %s', 'ConjureWP' ),
					$conjure_dir
				);

				$this->logger->error( $error_message );
			}
		}

		return $conjure_dir;
	}

	/**
	 * AJAX handler for file uploads.
	 */
	public function _ajax_delete_uploaded_file() {
		if ( ! check_ajax_referer( 'conjure_nonce', 'wpnonce', false ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Security check failed.', 'ConjureWP' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'You do not have permission to delete files.', 'ConjureWP' ) ) );
		}

		$file_type = isset( $_POST['file_type'] ) ? sanitize_key( $_POST['file_type'] ) : '';

		if ( empty( $file_type ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'No file type specified.', 'ConjureWP' ) ) );
		}

		$uploaded_files = get_transient( 'conjure_uploaded_files' );

		if ( ! empty( $uploaded_files[ $file_type ] ) ) {
			$file_path = $uploaded_files[ $file_type ]['path'];

			if ( file_exists( $file_path ) ) {
				wp_delete_file( $file_path );
			}

			unset( $uploaded_files[ $file_type ] );
			set_transient( 'conjure_uploaded_files', $uploaded_files, HOUR_IN_SECONDS );

			$this->logger->info( __( 'Uploaded file deleted', 'ConjureWP' ), array( 'type' => $file_type ) );
		}

		wp_send_json_success( array( 'message' => esc_html__( 'File deleted successfully.', 'ConjureWP' ) ) );
	}

	/**
	 * Cleanup all uploaded files.
	 */
	private function cleanup_uploaded_files() {
		$uploaded_files = get_transient( 'conjure_uploaded_files' );

		if ( ! empty( $uploaded_files ) && is_array( $uploaded_files ) ) {
			foreach ( $uploaded_files as $file_data ) {
				if ( ! empty( $file_data['path'] ) && file_exists( $file_data['path'] ) ) {
					wp_delete_file( $file_data['path'] );
				}
			}

			delete_transient( 'conjure_uploaded_files' );

			$this->logger->info( __( 'All uploaded files cleaned up', 'ConjureWP' ) );
		}
	}

	/**
	 * Check if manual upload mode is enabled (no pre-registered import files).
	 *
	 * @return bool
	 */
	public function is_manual_upload_mode() {
		return $this->file_upload_handler->is_manual_upload_mode();
	}

	/**
	 * Get the manual upload zones HTML.
	 *
	 * @return string
	 */
	public function get_manual_upload_html() {
		return $this->file_upload_handler->get_manual_upload_html();
	}

	/**
	 * Allow import file types (XML, JSON, DAT, WIE) in WordPress media uploads.
	 *
	 * @param array $mimes Existing allowed MIME types.
	 * @return array Modified MIME types.
	 */
	public function allow_import_file_types( $mimes ) {
		// Add support for import file types.
		$mimes['xml']  = 'application/xml';
		$mimes['json'] = 'application/json';
		$mimes['dat']  = 'application/octet-stream';
		$mimes['wie']  = 'application/json'; // Widget import/export format.

		return apply_filters( 'conjure_allowed_import_mimes', $mimes );
	}

	/**
	 * Get individual step completion state.
	 *
	 * @param string $step_key The step key to check (e.g., 'plugins', 'content', 'child').
	 * @return bool Whether the step is completed.
	 */
	public function get_step_completion_state( $step_key ) {
		$step_states = get_option( 'conjure_' . $this->slug . '_step_completion', array() );
		return isset( $step_states[ $step_key ] ) && $step_states[ $step_key ];
	}

	/**
	 * Mark a step as completed.
	 *
	 * @param string $step_key The step key to mark as completed.
	 */
	public function mark_step_completed( $step_key ) {
		$step_states = get_option( 'conjure_' . $this->slug . '_step_completion', array() );
		$step_states[ $step_key ] = time();
		update_option( 'conjure_' . $this->slug . '_step_completion', $step_states );

		$this->logger->info(
			/* translators: %s: Step key identifier. */
			sprintf( __( 'Step "%s" marked as completed', 'ConjureWP' ), $step_key ),
			array( 'step' => $step_key )
		);
	}

	/**
	 * Reset a specific step's completion state.
	 *
	 * @param string $step_key The step key to reset.
	 */
	public function reset_step( $step_key ) {
		$step_states = get_option( 'conjure_' . $this->slug . '_step_completion', array() );

		if ( isset( $step_states[ $step_key ] ) ) {
			unset( $step_states[ $step_key ] );
			update_option( 'conjure_' . $this->slug . '_step_completion', $step_states );

			$this->logger->info(
				/* translators: %s: Step key identifier. */
				sprintf( __( 'Step "%s" reset for rerunning', 'ConjureWP' ), $step_key ),
				array( 'step' => $step_key )
			);
		}
	}

	/**
	 * Reset all step completion states.
	 */
	public function reset_all_steps() {
		delete_option( 'conjure_' . $this->slug . '_step_completion' );
		delete_option( 'conjure_' . $this->slug . '_completed' );

		$this->logger->info( __( 'All Conjure steps have been reset', 'ConjureWP' ) );
	}

	/**
	 * Add admin bar menu for rerunning steps (power users only).
	 *
	 * Note: This only appears when CONJURE_TOOLS_ENABLED constant is defined.
	 * Add this to wp-config.php to enable:
	 * define( 'CONJURE_TOOLS_ENABLED', true );
	 *
	 * @param WP_Admin_Bar $wp_admin_bar The admin bar object.
	 */
	public function add_admin_bar_rerun_menu( $wp_admin_bar ) {
		// Only show to users with manage_options capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Add parent menu item.
		$wp_admin_bar->add_node(
			array(
				'id'    => 'conjure-rerun',
				'title' => '<span class="ab-icon dashicons-update"></span><span class="ab-label">' . esc_html__( 'Conjure WP', 'ConjureWP' ) . '</span>',
				'href'  => $this->get_wizard_url(),
				'meta'  => array(
					'title' => esc_html__( 'Rerun Conjure WP steps', 'ConjureWP' ),
				),
			)
		);

		// Get step completion states.
		$step_states = get_option( 'conjure_' . $this->slug . '_step_completion', array() );

		// Define available steps for rerunning.
		$rerun_steps = array(
			'child'   => esc_html__( 'Child Theme', 'ConjureWP' ),
			'license' => esc_html__( 'License Activation', 'ConjureWP' ),
			'plugins' => esc_html__( 'Plugins', 'ConjureWP' ),
			'content' => esc_html__( 'Content Import', 'ConjureWP' ),
		);

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Legacy theme-specific dynamic hook for backward compatibility.
		$rerun_steps = apply_filters( $this->theme->template . '_conjure_rerun_steps', $rerun_steps, $this->steps );

		// Add individual step reset options.
		foreach ( $rerun_steps as $step_key => $step_label ) {
			// Skip license step if not enabled.
			if ( 'license' === $step_key && ! $this->license_step_enabled ) {
				continue;
			}

			$is_completed = isset( $step_states[ $step_key ] ) && $step_states[ $step_key ];
			$status_icon  = $is_completed ? '✓' : '○';

			$wp_admin_bar->add_node(
				array(
					'parent' => 'conjure-rerun',
					'id'     => 'conjure-rerun-' . $step_key,
					'title'  => $status_icon . ' ' . $step_label,
					'href'   => wp_nonce_url(
						admin_url( '?conjure_reset_step=' . $step_key ),
						'conjure_reset_step_' . $step_key,
						'_conjure_nonce'
					),
					'meta'   => array(
						'title' => sprintf(
							/* translators: %s: step name */
							esc_html__( 'Reset and rerun: %s', 'ConjureWP' ),
							$step_label
						),
					),
				)
			);
		}

		// Add divider.
		$wp_admin_bar->add_node(
			array(
				'parent' => 'conjure-rerun',
				'id'     => 'conjure-rerun-divider',
				'title'  => '<hr style="margin: 5px 0; border: none; border-top: 1px solid rgba(255,255,255,0.2);">',
				'href'   => false,
				'meta'   => array(
					'html' => '<hr style="margin: 5px 0; border: none; border-top: 1px solid rgba(255,255,255,0.2);">',
				),
			)
		);

		// Add "Reset All" option.
		$wp_admin_bar->add_node(
			array(
				'parent' => 'conjure-rerun',
				'id'     => 'conjure-reset-all',
				'title'  => '↻ ' . esc_html__( 'Reset All Steps', 'ConjureWP' ),
				'href'   => wp_nonce_url(
					admin_url( '?conjure_reset_step=all' ),
					'conjure_reset_step_all',
					'_conjure_nonce'
				),
				'meta'   => array(
					'title' => esc_html__( 'Reset all steps and rerun complete onboarding', 'ConjureWP' ),
				),
			)
		);

		// Add "Open Wizard" option.
		$wp_admin_bar->add_node(
			array(
				'parent' => 'conjure-rerun',
				'id'     => 'conjure-open-wizard',
				'title'  => '→ ' . esc_html__( 'Open Wizard', 'ConjureWP' ),
				'href'   => $this->get_wizard_url(),
				'meta'   => array(
					'title' => esc_html__( 'Open Conjure WP setup wizard', 'ConjureWP' ),
				),
			)
		);
	}

	/**
	 * Handle step reset requests from admin bar.
	 */
	public function handle_step_reset() {
		// Check if this is a reset request.
		if ( ! isset( $_GET['conjure_reset_step'] ) || ! isset( $_GET['_conjure_nonce'] ) ) {
			return;
		}

		$step  = sanitize_key( wp_unslash( $_GET['conjure_reset_step'] ) );
		$nonce = sanitize_text_field( wp_unslash( $_GET['_conjure_nonce'] ) );

		// Verify nonce.
		if ( ! wp_verify_nonce( $nonce, 'conjure_reset_step_' . $step ) ) {
			wp_die( esc_html__( 'Security check failed.', 'ConjureWP' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'ConjureWP' ) );
		}

		// Handle reset.
		if ( 'all' === $step ) {
			$this->reset_all_steps();
			$redirect_url = $this->get_wizard_url();
			$message = __( 'All steps have been reset. You can now rerun the complete onboarding.', 'ConjureWP' );
		} else {
			$this->reset_step( $step );
			$redirect_url = $this->get_wizard_url( array( 'step' => $step ) );
			$message = sprintf(
				/* translators: %s: step name */
				__( 'Step "%s" has been reset. You can now rerun this step.', 'ConjureWP' ),
				ucfirst( str_replace( '_', ' ', $step ) )
			);
		}

		// Set admin notice.
		set_transient( 'conjure_admin_notice', $message, 30 );

		// Redirect to the wizard.
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Display admin notices for step resets.
	 */
	public function display_admin_notices() {
		$message = get_transient( 'conjure_admin_notice' );

		if ( $message ) {
			delete_transient( 'conjure_admin_notice' );
			?>
			<div class="notice notice-success is-dismissible">
				<p><strong><?php echo esc_html__( 'Conjure WP:', 'ConjureWP' ); ?></strong> <?php echo esc_html( $message ); ?></p>
			</div>
			<?php
		}
	}
}
