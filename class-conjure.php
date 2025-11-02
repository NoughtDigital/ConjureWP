<?php
/**
 * Conjure WP
 * Better WordPress Theme Onboarding
 *
 * The following code is a derivative work from the
 * Envato WordPress Theme Setup Wizard by David Baker.
 *
 * @package   Conjure WP
 * @version   @@pkg.version
 * @link      https://conjurewp.com/
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
	protected $theme;

	/**
	 * Current step.
	 *
	 * @var string
	 */
	protected $step = '';

	/**
	 * Steps.
	 *
	 * @var    array
	 */
	protected $steps = array();

	/**
	 * Demo Plugin Manager instance.
	 *
	 * @var Conjure_Demo_Plugin_Manager
	 */
	protected $demo_plugin_manager;

	/**
	 * Importer.
	 *
	 * @var    array
	 */
	protected $importer;

	/**
	 * WP Hook class.
	 *
	 * @var Conjure_Hooks
	 */
	protected $hooks;

	/**
	 * Holds the verified import files.
	 *
	 * @var array
	 */
	public $import_files;

	/**
	 * The base import file name.
	 *
	 * @var string
	 */
	public $import_file_base_name;

	/**
	 * Helper.
	 *
	 * @var    array
	 */
	protected $helper;

	/**
	 * Updater.
	 *
	 * @var    array
	 */
	protected $updater;

	/**
	 * The text string array.
	 *
	 * @var array $strings
	 */
	protected $strings = null;

	/**
	 * The base path where Conjure is located.
	 *
	 * @var array $strings
	 */
	protected $base_path = null;

	/**
	 * The base url where Conjure is located.
	 *
	 * @var array $strings
	 */
	protected $base_url = null;

	/**
	 * The location where Conjure is located within the theme or plugin.
	 *
	 * @var string $directory
	 */
	protected $directory = null;

	/**
	 * Top level admin page.
	 *
	 * @var string $conjure_url
	 */
	protected $conjure_url = null;

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
	protected $hook_suffix = null;

	/**
	 * The URL for the "Learn more about child themes" link.
	 *
	 * @var string $child_action_btn_url
	 */
	protected $child_action_btn_url = null;

	/**
	 * The flag, to mark, if the theme license step should be enabled.
	 *
	 * @var boolean $license_step_enabled
	 */
	protected $license_step_enabled = false;

	/**
	 * The URL for the "Where can I find the license key?" link.
	 *
	 * @var string $theme_license_help_url
	 */
	protected $theme_license_help_url = null;

	/**
	 * Remove the "Skip" button, if required.
	 *
	 * @var string $license_required
	 */
	protected $license_required = null;

	/**
	 * The item name of the EDD product (this theme).
	 *
	 * @var string $edd_item_name
	 */
	protected $edd_item_name = null;

	/**
	 * The theme slug of the EDD product (this theme).
	 *
	 * @var string $edd_theme_slug
	 */
	protected $edd_theme_slug = null;

	/**
	 * The remote_api_url of the EDD shop.
	 *
	 * @var string $edd_remote_api_url
	 */
	protected $edd_remote_api_url = null;

	/**
	 * Turn on dev mode if you're developing.
	 *
	 * @var string $dev_mode
	 */
	protected $dev_mode = false;

	/**
	 * Ignore.
	 *
	 * @var string $ignore
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
	protected $ready_big_button_url = null;

	/**
	 * The theme slug.
	 *
	 * @var string $slug
	 */
	protected $slug = '';

	/**
	 * Flag to keep wizard locked on the license step until activation.
	 *
	 * @var bool
	 */
	protected $license_gate_active = false;

	/**
	 * Setup plugin version.
	 *
	 * @access private
	 * @since 1.0
	 * @return void
	 */
	private function version() {

		if ( ! defined( 'CONJURE_VERSION' ) ) {
			define( 'CONJURE_VERSION', '@@pkg.version' );
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
				'ready_big_button_url' => home_url( '/' ),
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

		if ( class_exists( 'Conjure_Freemius' ) ) {
			add_filter( 'conjurewp_has_free_access', array( $this, 'grant_access_for_valid_edd_license' ), 10, 2 );
		}

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
	$this->logger = Conjure_Logger::get_instance( $logger_config );

	// Is Dev Mode turned on?
	$already_setup = get_option( 'conjure_' . $this->slug . '_completed' );

	// Load admin bar functionality for power users to rerun steps (only if CONJURE_TOOLS_ENABLED constant is defined).
	if ( $already_setup && defined( 'CONJURE_TOOLS_ENABLED' ) && CONJURE_TOOLS_ENABLED ) {
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_rerun_menu' ), 100 );
		add_action( 'admin_init', array( $this, 'handle_step_reset' ), 5 );
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
	}

	// Register REST API endpoints (always available for hosting dashboards).
	add_action( 'rest_api_init', array( $this, 'register_rest_api' ) );

	if ( true !== $this->dev_mode && $already_setup ) {
		// Return if Conjure has already completed it's setup (but admin bar hooks are already registered above if enabled).
		return;
	}

	// Load custom plugin installer.
	require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-plugin-installer.php';
	
	// Load and initialize Demo Plugin Manager.
	require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-demo-plugin-manager.php';
	$this->demo_plugin_manager = new Conjure_Demo_Plugin_Manager();

	add_action( 'admin_init', array( $this, 'required_classes' ) );
		add_action( 'admin_init', array( $this, 'redirect' ), 30 );
		add_action( 'after_switch_theme', array( $this, 'switch_theme' ) );
		add_action( 'admin_init', array( $this, 'steps' ), 30, 0 );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_page' ), 30, 0 );
		add_action( 'admin_init', array( $this, 'ignore' ), 5 );
	add_action( 'admin_footer', array( $this, 'svg_sprite' ) );
	add_action( 'wp_ajax_conjure_content', array( $this, '_ajax_content' ), 10, 0 );
	add_action( 'wp_ajax_conjure_get_total_content_import_items', array( $this, '_ajax_get_total_content_import_items' ), 10, 0 );
	add_action( 'wp_ajax_conjure_install_plugin', array( $this, '_ajax_install_plugin' ), 10, 0 );
		add_action( 'wp_ajax_conjure_child_theme', array( $this, 'generate_child' ), 10, 0 );
		add_action( 'wp_ajax_conjure_activate_license', array( $this, '_ajax_activate_license' ), 10, 0 );
		add_action( 'wp_ajax_conjure_update_selected_import_data_info', array( $this, 'update_selected_import_data_info' ), 10, 0 );
		add_action( 'wp_ajax_conjure_import_finished', array( $this, 'import_finished' ), 10, 0 );
		add_action( 'wp_ajax_conjure_upload_file', array( $this, '_ajax_upload_file' ), 10, 0 );
		add_action( 'wp_ajax_conjure_upload_from_media', array( $this, '_ajax_upload_from_media' ), 10, 0 );
		add_action( 'wp_ajax_conjure_delete_uploaded_file', array( $this, '_ajax_delete_uploaded_file' ), 10, 0 );
		add_action( 'wp_ajax_conjure_get_health_metrics', array( $this, '_ajax_get_health_metrics' ), 10, 0 );
		add_filter( 'pt-importer/new_ajax_request_response_data', array( $this, 'pt_importer_new_ajax_request_response_data' ) );
		add_action( 'import_end', array( $this, 'after_content_import_setup' ) );
		add_action( 'import_start', array( $this, 'before_content_import_setup' ) );
		add_action( 'admin_init', array( $this, 'register_import_files' ) );
		add_filter( 'upload_mimes', array( $this, 'allow_import_file_types' ) );

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
		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-wxr-importer.php';
		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-importer.php';

		$this->importer = new ConjureWP\Importer\Importer( array( 'fetch_attachments' => true ), $this->logger );

		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-widget-importer.php';

		if ( ! class_exists( 'WP_Customize_Setting' ) ) {
			require_once ABSPATH . 'wp-includes/class-wp-customize-setting.php';
		}

		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-customizer-option.php';
		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-customizer-importer.php';
		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-redux-importer.php';
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
		$redirect_url = menu_page_url( $this->conjure_url, false );

		// Allow themes to customize redirect URL via filter.
		$redirect_url = apply_filters( 'conjure_redirect_on_theme_switch_url', $redirect_url, $this->conjure_url );

		wp_safe_redirect( $redirect_url );

		exit;
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
		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'conjurewp-ignore-nounce' ) ) {
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

		$this->hook_suffix = add_submenu_page(
			esc_html( $this->parent_slug ),
			esc_html( $strings['admin-menu'] ),
			esc_html( $strings['admin-menu'] ),
			sanitize_key( $this->capability ),
			sanitize_key( $this->conjure_url ),
			array( $this, 'admin_page' )
		);
	}

	/**
	 * Add the admin page.
	 */
	public function admin_page() {
		// Log that admin_page was called.
		$this->logger->debug( 'admin_page() method called', array( 'page' => isset( $_GET['page'] ) ? $_GET['page'] : 'none', 'step' => isset( $_GET['step'] ) ? $_GET['step'] : 'none' ) );

		// Strings passed in from the config file.
		$strings = $this->strings;

		// Do not proceed, if we're not on the right page.
		if ( empty( $_GET['page'] ) || $this->conjure_url !== $_GET['page'] ) {
			$this->logger->debug( 'Not on ConjureWP page, returning early' );
			return;
		}

		// Ensure steps are initialized first (needed for proper step handling).
		if ( empty( $this->steps ) ) {
			$this->steps();
		}

		// Check access: free for open-source themes, paid for commercial themes.
		// Only check if Freemius class exists, otherwise allow access (graceful degradation).
		if ( class_exists( 'Conjure_Freemius' ) ) {
			$freemius_access = Conjure_Freemius::has_free_access();

			if ( ! $freemius_access ) {
				if ( $this->license_step_enabled && isset( $this->steps['license'] ) ) {
					if ( $this->license_required ) {
						// License is required, so keep the wizard accessible but force the license step.
						$this->license_gate_active = true;
						$this->logger->debug( 'Freemius access requires activation - enforcing license step' );
					} else {
						// License step is optional, so allow the wizard to continue but keep the step visible.
						$this->logger->debug( 'Freemius access missing, but license step is optional - allowing wizard to continue' );
					}
				} else {
					// No license step available, so show the upgrade notice.
					$this->logger->debug( 'Access denied - showing upgrade notice' );
					$this->show_upgrade_notice();
					return;
				}
			} else {
				$this->logger->debug( 'Access granted - proceeding with wizard' );
			}
		} else {
			$this->logger->debug( 'Freemius not detected - proceeding with wizard' );
		}

		if ( ob_get_length() ) {
			ob_end_clean();
		}

		// Get the current step, with fallback to first step if empty or invalid.
		$step_keys = array_keys( $this->steps );
		$default_step = ! empty( $step_keys ) ? $step_keys[0] : '';
		$this->step = isset( $_GET['step'] ) && ! empty( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : $default_step;
		
		// Validate step exists in steps array.
		if ( empty( $this->step ) || ! isset( $this->steps[ $this->step ] ) ) {
			$this->logger->warning( sprintf( __( 'Invalid step "%s" requested, falling back to default: %s', 'conjurewp' ), $this->step, $default_step ) );
			$this->logger->warning( 'Available steps: ' . implode( ', ', array_keys( $this->steps ) ) );
			$this->step = $default_step;
		}

		if ( $this->license_gate_active && 'license' !== $this->step ) {
			$this->logger->debug(
				'Freemius license gate active - forcing license step',
				array(
					'requested_step' => $this->step,
				)
			);
			$this->step          = 'license';
			$_GET['step']        = 'license';
		}

		// Debug log current step.
		error_log( 'CONJUREWP DEBUG: Loading step: ' . $this->step );
		error_log( 'CONJUREWP DEBUG: Steps available: ' . implode( ', ', array_keys( $this->steps ) ) );
		error_log( 'CONJUREWP DEBUG: License step exists? ' . ( isset( $this->steps['license'] ) ? 'YES' : 'NO' ) );
		$this->logger->debug( sprintf( __( 'Loading step: %s', 'conjurewp' ), $this->step ) );
		$this->logger->debug( 'Steps available: ' . print_r( array_keys( $this->steps ), true ) );

		// Prevent deprecated function calls by removing hooks that use them.
		// This prevents warnings from print_emoji_styles and wp_admin_bar_header.
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_head', 'wp_admin_bar_header' );

		// Always use minified files built with Vite.
		$suffix = '.min';

		// Enqueue WordPress media uploader.
		wp_enqueue_media();

		// Enqueue emoji styles using the modern API (replaces deprecated print_emoji_styles).
		if ( function_exists( 'wp_enqueue_emoji_styles' ) ) {
			wp_enqueue_emoji_styles();
		}

		// Enqueue admin bar header styles using the modern API (replaces deprecated wp_admin_bar_header).
		if ( function_exists( 'wp_enqueue_admin_bar_header_styles' ) ) {
			wp_enqueue_admin_bar_header_styles();
		}

		// Enqueue styles.
		$css_file = trailingslashit( $this->base_path ) . $this->directory . '/assets/css/conjure' . $suffix . '.css';
		$version = file_exists( $css_file ) ? filemtime( $css_file ) : CONJURE_VERSION;
		wp_enqueue_style( 'conjure', trailingslashit( $this->base_url ) . $this->directory . '/assets/css/conjure' . $suffix . '.css', array( 'wp-admin' ), $version );

		// Enqueue javascript.
		$js_file = trailingslashit( $this->base_path ) . $this->directory . '/assets/js/conjure' . $suffix . '.js';
		$js_version = file_exists( $js_file ) ? filemtime( $js_file ) : CONJURE_VERSION;
		wp_enqueue_script( 'conjure', trailingslashit( $this->base_url ) . $this->directory . '/assets/js/conjure' . $suffix . '.js', array( 'jquery-core', 'jquery-ui-core' ), $js_version, true );

		$texts = array(
			'something_went_wrong' => esc_html__( 'Something went wrong. Please refresh the page and try again!', 'conjurewp' ),
		);

	// Localize the javascript.
	wp_localize_script(
		'conjure',
		'conjure_params',
		array(
			'ajaxurl'              => admin_url( 'admin-ajax.php' ),
			'wpnonce'              => wp_create_nonce( 'conjure_nonce' ),
			'texts'                => $texts,
			'use_custom_installer' => true, // Always using custom installer.
		)
	);

		/**
		 * Start the actual page content.
		 * Note: We don't use output buffering here as the header and body output directly.
		 */
		$this->header(); ?>

		<div class="conjure__wrapper">

			<div class="conjure__content conjure__content--<?php echo esc_attr( ! empty( $this->step ) && isset( $this->steps[ $this->step ]['name'] ) ? strtolower( $this->steps[ $this->step ]['name'] ) : '' ); ?>">

			<?php
			// Content Handlers.
			$show_content = true;

			if ( ! empty( $_REQUEST['save_step'] ) && isset( $this->steps[ $this->step ]['handler'] ) ) {
				check_admin_referer( 'conjure' );
				$show_content = call_user_func( $this->steps[ $this->step ]['handler'] );
			}

			// Debug: Log what step we're about to render.
			$this->logger->debug( sprintf( __( 'About to render step: %s, show_content: %s', 'conjurewp' ), $this->step, $show_content ? 'yes' : 'no' ) );

			if ( $show_content ) {
				$this->logger->debug( __( 'Calling body() method', 'conjurewp' ) );
				$this->body();
				$this->logger->debug( __( 'Body() method completed', 'conjurewp' ) );
			} else {
				$this->logger->debug( __( 'Content suppressed by handler', 'conjurewp' ) );
			}
			?>

			<?php $this->step_output(); ?>

			<?php
			// Debug output to verify page is rendering.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				echo '<!-- DEBUG: Step = ' . esc_html( $this->step ) . ', Steps available = ' . esc_html( implode( ', ', array_keys( $this->steps ) ) ) . ' -->';
			}
			?>

			</div>

			<?php echo sprintf( '<a class="return-to-dashboard" href="%s">%s</a>', esc_url( admin_url( '/' ) ), esc_html( $strings['return-to-dashboard'] ) ); ?>

			<?php $ignore_url = wp_nonce_url( admin_url( '?' . $this->ignore . '=true' ), 'conjurewp-ignore-nounce' ); ?>

			<?php echo sprintf( '<a class="return-to-dashboard ignore" href="%s">%s</a>', esc_url( $ignore_url ), esc_html( $strings['ignore'] ) ); ?>

		</div>

		<?php $this->footer(); ?>

		<?php
		exit;
	}

	/**
	 * Show upgrade notice for paid themes without license.
	 */
	protected function show_upgrade_notice() {
		// Strings passed in from the config file.
		$strings = $this->strings;

		// Get theme information.
		$theme_name = class_exists( 'Conjure_Freemius' ) ? Conjure_Freemius::get_current_theme_name() : $this->theme->name;
		
		// Always use minified files built with Vite.
		$suffix = '.min';

		// Enqueue styles.
		$css_file = trailingslashit( $this->base_path ) . $this->directory . '/assets/css/conjure' . $suffix . '.css';
		$version = file_exists( $css_file ) ? filemtime( $css_file ) : CONJURE_VERSION;
		wp_enqueue_style( 'conjure', trailingslashit( $this->base_url ) . $this->directory . '/assets/css/conjure' . $suffix . '.css', array( 'wp-admin' ), $version );

		ob_start();
		$this->header();
		?>

		<div class="conjure__wrapper">
			<div class="conjure__content conjure__content--upgrade">
				<div class="conjure__content--transition">
					<?php echo wp_kses( $this->svg( array( 'icon' => 'license' ) ), $this->svg_allowed_html() ); ?>
					
					<h1><?php esc_html_e( 'ConjureWP License Required', 'conjurewp' ); ?></h1>
					
					<p>
						<?php
						printf(
							/* translators: %s: Theme name */
							esc_html__( 'ConjureWP is free for open-source themes, but requires a license for commercial/premium themes like %s.', 'conjurewp' ),
							esc_html( $theme_name )
						);
						?>
					</p>
					
					<p>
						<?php esc_html_e( 'To use ConjureWP with your premium theme, please purchase a license or switch to an open-source theme.', 'conjurewp' ); ?>
					</p>

					<?php if ( function_exists( 'conjure_wp' ) ) : ?>
						<?php $fs = conjure_wp(); ?>
						<?php if ( $fs ) : ?>
							<div class="conjure__upgrade-actions" style="margin-top: 30px;">
								<?php if ( ! $fs->is_registered() ) : ?>
									<a href="<?php echo esc_url( $fs->get_activation_url() ); ?>" class="conjure__button conjure__button--next">
										<?php esc_html_e( 'Get Started', 'conjurewp' ); ?>
									</a>
								<?php elseif ( ! $fs->has_active_valid_license() ) : ?>
									<a href="<?php echo esc_url( $fs->get_upgrade_url() ); ?>" class="conjure__button conjure__button--next">
										<?php esc_html_e( 'Upgrade Now', 'conjurewp' ); ?>
									</a>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					<?php else : ?>
						<p style="margin-top: 20px; color: #d63638;">
							<strong><?php esc_html_e( 'Note:', 'conjurewp' ); ?></strong>
							<?php esc_html_e( 'Freemius SDK is not properly configured. Please contact the plugin developer.', 'conjurewp' ); ?>
						</p>
					<?php endif; ?>
				</div>
			</div>

			<?php echo sprintf( '<a class="return-to-dashboard" href="%s">%s</a>', esc_url( admin_url( '/' ) ), esc_html( $strings['return-to-dashboard'] ) ); ?>
		</div>

		<?php
		$this->footer();
		exit;
	}

	/**
	 * Output the header.
	 */
	protected function header() {

		// Strings passed in from the config file.
		$strings = $this->strings;

		// Get the current step.
		$current_step = '';
		if ( ! empty( $this->step ) && isset( $this->steps[ $this->step ] ) && isset( $this->steps[ $this->step ]['name'] ) ) {
			$current_step = strtolower( $this->steps[ $this->step ]['name'] );
		}

		// Set the current screen to prevent "get_current_screen called incorrectly" notices.
		// This ensures compatibility with plugins that use get_current_screen() in admin_head hooks.
		if ( ! empty( $this->hook_suffix ) ) {
			set_current_screen( $this->hook_suffix );
		}
		?>

		<!DOCTYPE html>
		<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
		<head>
			<meta name="viewport" content="width=device-width"/>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
			<?php printf( esc_html( $strings['title%s%s%s%s'] ), '<ti', 'tle>', esc_html( $this->theme->name ), '</title>' ); ?>
			<?php do_action( 'admin_print_styles' ); ?>
			<?php do_action( 'admin_print_scripts' ); ?>
			<?php do_action( 'admin_head' ); ?>
		</head>
		<body class="conjure__body conjure__body--<?php echo esc_attr( $current_step ); ?>">
		<?php
	}

	/**
	 * Output the content for the current step.
	 */
	protected function body() {
		if ( empty( $this->step ) || ! isset( $this->steps[ $this->step ] ) ) {
			$this->logger->error( sprintf( __( 'Invalid step requested: %s', 'conjurewp' ), $this->step ) );
			return;
		}

		if ( ! isset( $this->steps[ $this->step ]['view'] ) || ! is_callable( $this->steps[ $this->step ]['view'] ) ) {
			$this->logger->error( sprintf( __( 'Step view is not callable: %s', 'conjurewp' ), $this->step ) );
			return;
		}

		try {
			call_user_func( $this->steps[ $this->step ]['view'] );
		} catch ( Exception $e ) {
			$this->logger->error( sprintf( __( 'Error rendering step %s: %s', 'conjurewp' ), $this->step, $e->getMessage() ) );
			echo '<div class="error"><p>' . esc_html__( 'An error occurred while loading this step. Please check the error logs.', 'conjurewp' ) . '</p></div>';
		}
	}

	/**
	 * Output the footer.
	 */
	protected function footer() {
		?>
		</body>
		<?php do_action( 'admin_footer' ); ?>
		<?php do_action( 'admin_print_footer_scripts' ); ?>
		</html>
		<?php
	}

	/**
	 * SVG
	 */
	public function svg_sprite() {

		// Define SVG sprite file.
		$svg = trailingslashit( $this->base_path ) . $this->directory . '/assets/images/sprite.svg';

		// If it exists, include it.
		if ( file_exists( $svg ) ) {
			require_once apply_filters( 'conjure_svg_sprite', $svg );
		}
	}

	/**
	 * Return SVG markup.
	 *
	 * @param array $args {
	 *     Parameters needed to display an SVG.
	 *
	 *     @type string $icon  Required SVG icon filename.
	 *     @type string $title Optional SVG title.
	 *     @type string $desc  Optional SVG description.
	 * }
	 * @return string SVG markup.
	 */
	public function svg( $args = array() ) {

		// Make sure $args are an array.
		if ( empty( $args ) ) {
			return __( 'Please define default parameters in the form of an array.', 'conjurewp' );
		}

		// Define an icon.
		if ( false === array_key_exists( 'icon', $args ) ) {
			return __( 'Please define an SVG icon filename.', 'conjurewp' );
		}

		// Set defaults.
		$defaults = array(
			'icon'        => '',
			'title'       => '',
			'desc'        => '',
			'aria_hidden' => true, // Hide from screen readers.
			'fallback'    => false,
		);

		// Parse args.
		$args = wp_parse_args( $args, $defaults );

		// Set aria hidden.
		$aria_hidden = '';

		if ( true === $args['aria_hidden'] ) {
			$aria_hidden = ' aria-hidden="true"';
		}

		// Set ARIA.
		$aria_labelledby = '';

		if ( $args['title'] && $args['desc'] ) {
			$aria_labelledby = ' aria-labelledby="title desc"';
		}

		// Begin SVG markup.
		$svg = '<svg class="icon icon--' . esc_attr( $args['icon'] ) . '"' . $aria_hidden . $aria_labelledby . ' role="img">';

		// If there is a title, display it.
		if ( $args['title'] ) {
			$svg .= '<title>' . esc_html( $args['title'] ) . '</title>';
		}

		// If there is a description, display it.
		if ( $args['desc'] ) {
			$svg .= '<desc>' . esc_html( $args['desc'] ) . '</desc>';
		}

		$svg .= '<use xlink:href="#icon-' . esc_html( $args['icon'] ) . '"></use>';

		// Add some markup to use as a fallback for browsers that do not support SVGs.
		if ( $args['fallback'] ) {
			$svg .= '<span class="svg-fallback icon--' . esc_attr( $args['icon'] ) . '"></span>';
		}

		$svg .= '</svg>';

		return $svg;
	}

	/**
	 * Allowed HTML for sprites.
	 */
	public function svg_allowed_html() {

		$array = array(
			'svg' => array(
				'class'       => array(),
				'aria-hidden' => array(),
				'role'        => array(),
			),
			'use' => array(
				'xlink:href' => array(),
			),
		);

		return apply_filters( 'conjure_svg_allowed_html', $array );
	}

	/**
	 * Loading conjure-spinner.
	 */
	public function loading_spinner() {

		// Define the spinner file.
		$spinner = trailingslashit( $this->base_path ) . $this->directory . '/assets/images/spinner.php';

		// Retrieve the spinner.
		$spinner = apply_filters( 'conjure_loading_spinner', $spinner );

		if ( file_exists( $spinner ) ) {
			include $spinner;
		}
	}

	/**
	 * Allowed HTML for the loading spinner.
	 */
	public function loading_spinner_allowed_html() {

		$array = array(
			'span' => array(
				'class' => array(),
			),
			'cite' => array(
				'class' => array(),
			),
		);

		return apply_filters( 'conjure_loading_spinner_allowed_html', $array );
	}

	/**
	 * Setup steps.
	 */
	public function steps() {

		$this->steps = array(
			'welcome' => array(
				'name'    => esc_html__( 'Welcome', 'conjurewp' ),
				'view'    => array( $this, 'welcome' ),
				'handler' => array( $this, 'welcome_handler' ),
			),
		);

		$this->steps['child'] = array(
			'name' => esc_html__( 'Child', 'conjurewp' ),
			'view' => array( $this, 'child' ),
		);

	if ( $this->license_step_enabled ) {
		$this->steps['license'] = array(
			'name' => esc_html__( 'License', 'conjurewp' ),
			'view' => array( $this, 'license' ),
		);
		$this->logger->debug( 'License step added to steps array', array( 'license_step_enabled' => $this->license_step_enabled ) );
	} else {
		$this->logger->debug( 'License step NOT added - license_step_enabled is false' );
	}

	// Show the plugin importer (custom built-in installer).
	// Demo selection happens within this step for demo-specific plugins.
	$this->steps['plugins'] = array(
		'name' => esc_html__( 'Plugins', 'conjurewp' ),
		'view' => array( $this, 'plugins' ),
	);

	// Show the content importer - either with pre-configured files or manual upload.
	$this->steps['content'] = array(
		'name' => esc_html__( 'Content', 'conjurewp' ),
		'view' => array( $this, 'content' ),
	);

		$this->steps['ready'] = array(
			'name' => esc_html__( 'Ready', 'conjurewp' ),
			'view' => array( $this, 'ready' ),
		);

		$this->steps = apply_filters( $this->theme->template . '_conjure_steps', $this->steps );
	}

	/**
	 * Output the steps
	 */
	protected function step_output() {
		$ouput_steps  = $this->steps;
		$array_keys   = array_keys( $this->steps );
		$current_step = array_search( $this->step, $array_keys, true );

		array_shift( $ouput_steps );
		?>

		<ol class="dots">

			<?php
			foreach ( $ouput_steps as $step_key => $step ) :

				$class_attr = '';
				$show_link  = false;

				if ( $step_key === $this->step ) {
					$class_attr = 'active';
				} elseif ( $current_step > array_search( $step_key, $array_keys, true ) ) {
					$class_attr = 'done';
					$show_link  = true;
				}
				?>

				<li class="<?php echo esc_attr( $class_attr ); ?>">
					<a href="<?php echo esc_url( $this->step_link( $step_key ) ); ?>" title="<?php echo esc_attr( $step['name'] ); ?>"></a>
				</li>

			<?php endforeach; ?>

		</ol>

		<?php
	}

	/**
	 * Get the step URL.
	 *
	 * @param string $step Name of the step, appended to the URL.
	 */
	protected function step_link( $step ) {
		return add_query_arg( 'step', $step );
	}

	/**
	 * Get the next step link.
	 */
	protected function step_next_link() {
		$keys = array_keys( $this->steps );
		$step = array_search( $this->step, $keys, true ) + 1;

		return add_query_arg( 'step', $keys[ $step ] );
	}

	/**
	 * Introduction step
	 */
	protected function welcome() {

		// Has this theme been setup yet? Compare this to the option set when you get to the last panel.
		$already_setup = get_option( 'conjure_' . $this->slug . '_completed' );

		// Theme Name.
		$theme = ucfirst( $this->theme );

		// Remove "Child" from the current theme name, if it's installed.
		$theme = str_replace( ' Child', '', $theme );

		// Strings passed in from the config file.
		$strings = $this->strings;

		// Text strings.
		$header    = ! $already_setup ? $strings['welcome-header%s'] : $strings['welcome-header-success%s'];
		$paragraph = ! $already_setup ? $strings['welcome%s'] : $strings['welcome-success%s'];
		$start     = $strings['btn-start'];
		$no        = $strings['btn-no'];
		?>

		<div class="conjure__content--transition">

			<?php echo wp_kses( $this->svg( array( 'icon' => 'welcome' ) ), $this->svg_allowed_html() ); ?>

			<h1><?php echo esc_html( sprintf( $header, $theme ) ); ?></h1>

			<p><?php echo esc_html( sprintf( $paragraph, $theme ) ); ?></p>

		</div>

		<footer class="conjure__content__footer">
			<a href="<?php echo esc_url( wp_get_referer() && ! strpos( wp_get_referer(), 'update.php' ) ? wp_get_referer() : admin_url( '/' ) ); ?>" class="conjure__button conjure__button--skip"><?php echo esc_html( $no ); ?></a>
			<a href="<?php echo esc_url( $this->step_next_link() ); ?>" class="conjure__button conjure__button--next conjure__button--proceed conjure__button--colorchange"><?php echo esc_html( $start ); ?></a>
			<?php wp_nonce_field( 'conjure' ); ?>
		</footer>

		<?php
		$this->logger->debug( __( 'The welcome step has been displayed', 'conjurewp' ) );
	}

	/**
	 * Handles save button from welcome page.
	 * This is to perform tasks when the setup wizard has already been run.
	 */
	protected function welcome_handler() {

		check_admin_referer( 'conjure' );

		return false;
	}

	/**
	 * Theme EDD license step.
	 */
	protected function license() {
		// Debug: Check if method is being called at all.
		error_log( 'CONJUREWP: License step method called' );
		$this->logger->debug( __( 'License step view method called', 'conjurewp' ) );

		// Debug: Log license step configuration.
		$this->logger->debug( 
			'License step configuration', 
			array(
				'license_step_enabled' => $this->license_step_enabled,
				'theme_license_help_url' => $this->theme_license_help_url,
				'license_required' => $this->license_required,
				'edd_item_name' => $this->edd_item_name,
				'edd_theme_slug' => $this->edd_theme_slug,
			)
		);

		$is_theme_registered = $this->is_theme_registered();
		$action_url          = $this->theme_license_help_url;
		$required            = $this->license_required;

		$is_theme_registered_class = ( $is_theme_registered ) ? ' is-registered' : null;

		// Theme Name.
		$theme = ucfirst( $this->theme->name );

		// Remove "Child" from the current theme name, if it's installed.
		$theme = str_replace( ' Child', '', $theme );

		// Strings passed in from the config file.
		$strings = $this->strings;

		// Text strings.
		$header    = ! $is_theme_registered ? $strings['license-header%s'] : $strings['license-header-success%s'];
		$action    = $strings['license-tooltip'];
		$label     = $strings['license-label'];
		$skip      = $strings['btn-license-skip'];
		$next      = $strings['btn-next'];
		$paragraph = ! $is_theme_registered ? $strings['license%s'] : $strings['license-success%s'];
		$install   = $strings['btn-license-activate'];
		?>

		<div class="conjure__content--transition">

			<?php echo wp_kses( $this->svg( array( 'icon' => 'license' ) ), $this->svg_allowed_html() ); ?>

			<svg class="icon icon--checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
				<circle class="icon--checkmark__circle" cx="26" cy="26" r="25" fill="none"/><path class="icon--checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
			</svg>

			<h1><?php echo esc_html( sprintf( $header, $theme ) ); ?></h1>

			<p id="license-text"><?php echo esc_html( sprintf( $paragraph, $theme ) ); ?></p>

			<?php if ( ! $is_theme_registered ) : ?>
				<div class="conjure__content--license-key">
					<label for="license-key"><?php echo esc_html( $label ); ?></label>

					<div class="conjure__content--license-key-wrapper">
						<input type="text" id="license-key" class="js-license-key" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
						<?php if ( ! empty( $action_url ) ) : ?>
							<a href="<?php echo esc_url( $action_url ); ?>" alt="<?php echo esc_attr( $action ); ?>" target="_blank">
								<span class="hint--top" aria-label="<?php echo esc_attr( $action ); ?>">
									<?php echo wp_kses( $this->svg( array( 'icon' => 'help' ) ), $this->svg_allowed_html() ); ?>
								</span>
							</a>
						<?php endif ?>
					</div>

				</div>
			<?php endif; ?>

		</div>

		<footer class="conjure__content__footer <?php echo esc_attr( $is_theme_registered_class ); ?>">

			<?php if ( ! $is_theme_registered ) : ?>

				<?php if ( ! $required && ! $this->license_gate_active ) : ?>
					<a href="<?php echo esc_url( $this->step_next_link() ); ?>" class="conjure__button conjure__button--skip conjure__button--proceed"><?php echo esc_html( $skip ); ?></a>
				<?php endif ?>

				<a href="<?php echo esc_url( $this->step_next_link() ); ?>" class="conjure__button conjure__button--next button-next js-conjure-license-activate-button" data-callback="activate_license">
					<span class="conjure__button--loading__text"><?php echo esc_html( $install ); ?></span>
					<?php echo wp_kses( $this->loading_spinner(), $this->loading_spinner_allowed_html() ); ?>
				</a>

			<?php else : ?>
				<a href="<?php echo esc_url( $this->step_next_link() ); ?>" class="conjure__button conjure__button--next conjure__button--proceed conjure__button--colorchange"><?php echo esc_html( $next ); ?></a>
			<?php endif; ?>
			<?php wp_nonce_field( 'conjure' ); ?>
		</footer>
		<?php
		$this->logger->debug( __( 'The license activation step has been displayed', 'conjurewp' ) );
	}


	/**
	 * Allow Freemius gating to accept a valid EDD license.
	 *
	 * @param bool   $has_access Current access flag from Freemius.
	 * @param string $theme_name Theme name passed through the filter (informational).
	 * @return bool
	 */
	public function grant_access_for_valid_edd_license( $has_access, $theme_name = '' ) {
		if ( $has_access ) {
			return true;
		}

		if ( empty( $this->edd_theme_slug ) ) {
			return $has_access;
		}

		return $this->is_theme_registered() ? true : $has_access;
	}

	/**
	 * Check, if the theme is currently registered.
	 *
	 * @return boolean
	 */
	private function is_theme_registered() {
		$is_registered = get_option( $this->edd_theme_slug . '_license_key_status', false ) === 'valid';
		return apply_filters( 'conjure_is_theme_registered', $is_registered );
	}

	/**
	 * Child theme generator.
	 */
	protected function child() {

	// Variables.
	$child_theme_option = get_option( 'conjure_' . $this->slug . '_child' );
	$conjure_created_child = ! empty( $child_theme_option );
	$theme              = $child_theme_option ? wp_get_theme( $child_theme_option )->name : $this->theme . ' Child';
	$action_url         = $this->child_action_btn_url;

	// Strings passed in from the config file.
	$strings = $this->strings;

	// Text strings.
	$header    = ! $conjure_created_child ? $strings['child-header'] : $strings['child-header-success'];
	$action    = $strings['child-action-link'];
	$skip      = $strings['btn-skip'];
	$next      = $strings['btn-next'];
	$paragraph = ! $conjure_created_child ? $strings['child'] : $strings['child-success%s'];
		$install   = $strings['btn-child-install'];
		?>

		<div class="conjure__content--transition">

			<?php echo wp_kses( $this->svg( array( 'icon' => 'child' ) ), $this->svg_allowed_html() ); ?>

			<svg class="icon icon--checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
				<circle class="icon--checkmark__circle" cx="26" cy="26" r="25" fill="none"/><path class="icon--checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
			</svg>

			<h1><?php echo esc_html( $header ); ?></h1>

			<p id="child-theme-text"><?php echo esc_html( sprintf( $paragraph, $theme ) ); ?></p>

			<a class="conjure__button conjure__button--knockout conjure__button--no-chevron conjure__button--external" href="<?php echo esc_url( $action_url ); ?>" target="_blank"><?php echo esc_html( $action ); ?></a>

		</div>

	<footer class="conjure__content__footer">

		<?php if ( ! $conjure_created_child ) : ?>

			<a href="<?php echo esc_url( $this->step_next_link() ); ?>" class="conjure__button conjure__button--skip conjure__button--proceed"><?php echo esc_html( $skip ); ?></a>

			<a href="<?php echo esc_url( $this->step_next_link() ); ?>" class="conjure__button conjure__button--next button-next" data-callback="install_child">
				<span class="conjure__button--loading__text"><?php echo esc_html( $install ); ?></span>
				<?php echo wp_kses( $this->loading_spinner(), $this->loading_spinner_allowed_html() ); ?>
			</a>

		<?php else : ?>
			<a href="<?php echo esc_url( $this->step_next_link() ); ?>" class="conjure__button conjure__button--next conjure__button--proceed conjure__button--colorchange"><?php echo esc_html( $next ); ?></a>
		<?php endif; ?>
		<?php wp_nonce_field( 'conjure' ); ?>
		</footer>
		<?php
		$this->logger->debug( __( 'The child theme installation step has been displayed', 'conjurewp' ) );
	}

	/**
	 * Theme plugins
	 */
	protected function plugins() {

		// Variables.
	$url    = wp_nonce_url( add_query_arg( array( 'plugins' => 'go' ) ), 'conjure' );
	$method = '';
	$fields = array_keys( $_POST );
	$creds  = request_filesystem_credentials( esc_url_raw( $url ), $method, false, false, $fields );

	if ( false === $creds ) {
			return true;
		}

		if ( ! WP_Filesystem( $creds ) ) {
			request_filesystem_credentials( esc_url_raw( $url ), $method, true, false, $fields );
			return true;
		}

	// Check if we have a selected demo for demo-specific plugins.
	$selected_demo_index = get_transient( 'conjure_selected_demo_index' );

	// Validate the selected demo index exists.
	if ( false !== $selected_demo_index && ! isset( $this->import_files[ $selected_demo_index ] ) ) {
		// Invalid demo index, clear it.
		delete_transient( 'conjure_selected_demo_index' );
		$selected_demo_index = false;
	}

	// If no demo selected yet and we have demos, auto-select the first one.
	if ( false === $selected_demo_index && ! empty( $this->import_files ) ) {
		$selected_demo_index = 0;
	}

	// Are there plugins that need installing/activating?
	$plugins             = $this->get_plugins( $selected_demo_index );
	$required_plugins    = array();
	$recommended_plugins = array();
	$count               = count( $plugins['all'] );
	$class               = $count ? null : 'no-plugins';

	// Split the plugins into required and recommended.
	foreach ( $plugins['all'] as $slug => $plugin ) {
		if ( ! empty( $plugin['required'] ) ) {
			$required_plugins[ $slug ] = $plugin;
		} else {
			$recommended_plugins[ $slug ] = $plugin;
		}
	}

	// Debug logging.
	$this->logger->info( 'Plugins page - Total: ' . count( $plugins['all'] ) . ', Required: ' . count( $required_plugins ) . ', Recommended: ' . count( $recommended_plugins ) . ', To Install: ' . count( $plugins['install'] ) . ', To Activate: ' . count( $plugins['activate'] ) );
	foreach ( $plugins['all'] as $slug => $plugin ) {
		$this->logger->info( "  Plugin '{$slug}': active=" . ( ! empty( $plugin['is_active'] ) ? 'yes' : 'no' ) . ", installed=" . ( ! empty( $plugin['is_installed'] ) ? 'yes' : 'no' ) . ", required=" . ( ! empty( $plugin['required'] ) ? 'yes' : 'no' ) );
	}

	// Strings passed in from the config file.
	$strings = $this->strings;

		// Text strings.
		$header    = $count ? $strings['plugins-header'] : $strings['plugins-header-success'];
		$paragraph = $count ? $strings['plugins'] : $strings['plugins-success%s'];
		$action    = $strings['plugins-action-link'];
		$skip      = $strings['btn-skip'];
		$next      = $strings['btn-next'];
		$install   = $strings['btn-plugins-install'];
		?>

		<div class="conjure__content--transition">

			<?php echo wp_kses( $this->svg( array( 'icon' => 'plugins' ) ), $this->svg_allowed_html() ); ?>

			<svg class="icon icon--checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
				<circle class="icon--checkmark__circle" cx="26" cy="26" r="25" fill="none"/><path class="icon--checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
			</svg>

		<h1><?php echo esc_html( $header ); ?></h1>

		<p><?php echo esc_html( $paragraph ); ?></p>

		<?php
		// Show demo selector if demos are available and demo-specific plugins are enabled.
		$show_demo_selector = false;
		if ( $this->import_files && count( $this->import_files ) > 0 && $this->demo_plugin_manager ) {
			$show_demo_selector = $this->demo_plugin_manager->is_demo_specific_plugins_enabled();
		}

		if ( $show_demo_selector && count( $this->import_files ) > 1 ) :
		?>
			<div class="conjure__demo-selector">
				<h3 style="margin-bottom: 0.5em; font-weight: 600; font-size: 1.1em;">
					<?php echo esc_html__( 'Select your demo:', 'conjurewp' ); ?>
				</h3>
				<p class="conjure__demo-grid-description">
					<?php echo esc_html__( 'Each demo has different plugin requirements. Choose your demo to see required and recommended plugins.', 'conjurewp' ); ?>
				</p>
				<div class="conjure__demo-grid">
					<?php foreach ( $this->import_files as $index => $import_file ) : ?>
						<div class="conjure__demo-card js-conjure-demo-card-plugins <?php echo ( $selected_demo_index !== false && $selected_demo_index === $index ) ? 'is-selected' : ''; ?>" data-demo-index="<?php echo esc_attr( $index ); ?>">
							<div class="conjure__demo-card-image">
								<?php if ( ! empty( $import_file['import_preview_image_url'] ) ) : ?>
									<img src="<?php echo esc_url( $import_file['import_preview_image_url'] ); ?>" alt="<?php echo esc_attr( $import_file['import_file_name'] ); ?>" loading="lazy">
								<?php else : ?>
									<div class="conjure__demo-card-placeholder"></div>
								<?php endif; ?>
							</div>
							<div class="conjure__demo-card-content">
								<h4 class="conjure__demo-card-title"><?php echo esc_html( $import_file['import_file_name'] ); ?></h4>
								<?php if ( ! empty( $import_file['import_notice'] ) ) : ?>
									<p class="conjure__demo-card-description"><?php echo esc_html( $import_file['import_notice'] ); ?></p>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( $count ) { ?>
			<a id="conjure__drawer-trigger" class="conjure__button conjure__button--knockout"><span><?php echo esc_html( $action ); ?></span><span class="chevron"></span></a>
		<?php } ?>

	</div>

	<form action="" method="post">

			<?php if ( $count ) : ?>

				<ul class="conjure__drawer conjure__drawer--install-plugins">

			<?php if ( ! empty( $required_plugins ) ) : ?>
		<li class="plugin-section-header plugin-section-header--required">
			<?php echo esc_html__( 'Required Plugins', 'conjurewp' ); ?>
			<span class="plugin-section-header__subtitle">
				<?php echo esc_html__( 'These plugins are essential for the demo to work correctly', 'conjurewp' ); ?>
			</span>
		</li>
		<?php foreach ( $required_plugins as $slug => $plugin ) :
			$is_active = ! empty( $plugin['is_active'] );
		?>
			<li data-slug="<?php echo esc_attr( $slug ); ?>" class="conjure-plugin-row conjure-plugin-row--required<?php echo $is_active ? ' plugin-active' : ''; ?>">
				<!-- Hidden input ensures it's always submitted (no checkbox for required) -->
				<input type="hidden" name="default_plugins[<?php echo esc_attr( $slug ); ?>]" value="1">

				<span class="conjure-plugin-row__indicator" aria-hidden="true">
					<?php echo $is_active ? '✓' : '▸'; ?>
				</span>

				<span class="conjure-plugin-row__title"><?php echo esc_html( $plugin['name'] ); ?></span>

				<?php if ( $is_active ) : ?>
					<span class="badge badge--success">
						<?php esc_html_e( 'Installed', 'conjurewp' ); ?>
					</span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
		<?php endif; ?>

	<?php if ( ! empty( $recommended_plugins ) ) : ?>
		<li class="plugin-section-header plugin-section-header--recommended">
			<?php echo esc_html__( 'Recommended Plugins', 'conjurewp' ); ?>
			<span class="plugin-section-header__subtitle">
				<?php echo esc_html__( 'Optional plugins that enhance the demo (can be unchecked)', 'conjurewp' ); ?>
			</span>
		</li>
	<?php foreach ( $recommended_plugins as $slug => $plugin ) :
		$is_active = ! empty( $plugin['is_active'] );
	?>
		<li data-slug="<?php echo esc_attr( $slug ); ?>" class="conjure-plugin-row<?php echo $is_active ? ' plugin-active conjure-plugin-row--installed' : ''; ?>">
			<?php if ( $is_active ) : ?>
				<input type="hidden" name="default_plugins[<?php echo esc_attr( $slug ); ?>]" value="1">
			<?php endif; ?>
			<input type="checkbox" name="default_plugins[<?php echo esc_attr( $slug ); ?>]" class="checkbox" id="default_plugins_<?php echo esc_attr( $slug ); ?>" value="1" <?php echo $is_active ? 'checked disabled="disabled"' : 'checked'; ?>>

			<label for="default_plugins_<?php echo esc_attr( $slug ); ?>" class="conjure-plugin-option<?php echo $is_active ? ' conjure-plugin-option--installed' : ''; ?>" <?php echo $is_active ? 'aria-disabled="true"' : ''; ?>>
				<i></i>
				<span class="conjure-plugin-row__title"><?php echo esc_html( $plugin['name'] ); ?></span>
				
				<?php if ( $is_active ) : ?>
					<span class="badge badge--success">
						<?php esc_html_e( 'Installed', 'conjurewp' ); ?>
					</span>
				<?php endif; ?>
			</label>
		</li>
			<?php endforeach; ?>
				<?php endif; ?>

				</ul>

			<?php endif; ?>

			<footer class="conjure__content__footer <?php echo esc_attr( $class ); ?>">
				<?php if ( $count ) : ?>
					<a id="close" href="<?php echo esc_url( $this->step_next_link() ); ?>" class="conjure__button conjure__button--skip conjure__button--closer conjure__button--proceed"><?php echo esc_html( $skip ); ?></a>
					<a id="skip" href="<?php echo esc_url( $this->step_next_link() ); ?>" class="conjure__button conjure__button--skip conjure__button--proceed"><?php echo esc_html( $skip ); ?></a>
					<a href="<?php echo esc_url( $this->step_next_link() ); ?>" class="conjure__button conjure__button--next button-next" data-callback="install_plugins">
						<span class="conjure__button--loading__text"><?php echo esc_html( $install ); ?></span>
						<?php echo wp_kses( $this->loading_spinner(), $this->loading_spinner_allowed_html() ); ?>
					</a>
				<?php else : ?>
					<a href="<?php echo esc_url( $this->step_next_link() ); ?>" class="conjure__button conjure__button--next conjure__button--proceed conjure__button--colorchange"><?php echo esc_html( $next ); ?></a>
				<?php endif; ?>
				<?php wp_nonce_field( 'conjure' ); ?>
			</footer>
		</form>

		<?php
		$this->logger->debug( __( 'The plugin installation step has been displayed', 'conjurewp' ) );
	}

	/**
	 * Page setup
	 */
	protected function content() {
		// Check if any demo files are registered.
		if ( empty( $this->import_files ) ) {
			$this->logger->error( 
				'No demo import files are registered! The conjure_import_files filter returned empty.',
				array( 
					'is_manual_upload_mode' => $this->is_manual_upload_mode(),
				)
			);
		}

		// Get the selected demo index from transient or default to 0.
		$selected_demo_index = get_transient( 'conjure_selected_demo_index' );
		
		// If no demo is selected or invalid, use the first demo.
		if ( false === $selected_demo_index || ! isset( $this->import_files[ $selected_demo_index ] ) ) {
			$selected_demo_index = 0;
			// Store it for consistency.
			if ( ! empty( $this->import_files ) ) {
				set_transient( 'conjure_selected_demo_index', $selected_demo_index, HOUR_IN_SECONDS );
			}
		}

		$this->logger->debug( 
			'Content step loading',
			array(
				'selected_demo_index' => $selected_demo_index,
				'total_import_files' => count( $this->import_files ),
				'import_files_keys' => ! empty( $this->import_files ) ? array_keys( $this->import_files[0] ) : array(),
			)
		);

		$import_info = $this->get_import_data_info( $selected_demo_index );

		// If import info is false or empty, log error and provide fallback.
		if ( false === $import_info || empty( $import_info ) ) {
			$this->logger->error( 
				'Failed to load import data info for content step',
				array( 
					'selected_index' => $selected_demo_index,
					'import_files_count' => count( $this->import_files ),
					'import_files_empty' => empty( $this->import_files ),
					'is_manual_upload_mode' => $this->is_manual_upload_mode(),
				)
			);
			
			// Check if we're in manual upload mode.
			if ( ! $this->is_manual_upload_mode() ) {
				// Provide a minimal fallback to prevent blank screen.
				$import_info = array(
					'content' => true,
				);
			} else {
				$import_info = array();
			}
		}

		// Strings passed in from the config file.
		$strings = $this->strings;

		// Text strings.
		$header    = $strings['import-header'];
		$paragraph = $strings['import'];
		$action    = $strings['import-action-link'];
		$skip      = $strings['btn-skip'];
		$next      = $strings['btn-next'];
		$import    = $strings['btn-import'];

		$multi_import = ( 1 < count( $this->import_files ) ) ? 'is-multi-import' : null;
		
		// Initialize server health checker.
		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-server-health.php';
		$server_health = new Conjure_Server_Health();
		?>

		<div class="conjure__content--transition">

			<?php echo wp_kses( $this->svg( array( 'icon' => 'content' ) ), $this->svg_allowed_html() ); ?>

			<svg class="icon icon--checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
				<circle class="icon--checkmark__circle" cx="26" cy="26" r="25" fill="none"/><path class="icon--checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
			</svg>

			<h1><?php echo esc_html( $header ); ?></h1>

			<p><?php echo esc_html( $paragraph ); ?></p>
			
			<?php
			// Display server health check.
			echo $server_health->get_health_check_styles(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$server_health->render_complete(
				array(
					'show_title'       => true,
					'title'            => __( 'Server Health Check', 'conjurewp' ),
					'requirements_url' => '',
					'theme_name'       => $this->theme->name,
				)
			);
			?>

		<?php if ( 1 < count( $this->import_files ) ) : ?>

			<div class="conjure__demo-selector">
				<h3 style="margin-bottom: 0.5em; font-weight: 600; font-size: 1.1em;">
					<?php echo esc_html__( 'Select your demo:', 'conjurewp' ); ?>
				</h3>
				<p class="conjure__demo-grid-description">
					<?php echo esc_html__( 'Choose which demo content to import.', 'conjurewp' ); ?>
				</p>
				<div class="conjure__demo-grid">
					<?php foreach ( $this->import_files as $index => $import_file ) : ?>
						<div class="conjure__demo-card js-conjure-demo-card-import <?php echo ( $selected_demo_index !== false && $selected_demo_index === $index ) ? 'is-selected' : ''; ?>" data-demo-index="<?php echo esc_attr( $index ); ?>">
							<div class="conjure__demo-card-image">
								<?php if ( ! empty( $import_file['import_preview_image_url'] ) ) : ?>
									<img src="<?php echo esc_url( $import_file['import_preview_image_url'] ); ?>" alt="<?php echo esc_attr( $import_file['import_file_name'] ); ?>" loading="lazy">
								<?php else : ?>
									<div class="conjure__demo-card-placeholder"></div>
								<?php endif; ?>
							</div>
							<div class="conjure__demo-card-content">
								<h4 class="conjure__demo-card-title"><?php echo esc_html( $import_file['import_file_name'] ); ?></h4>
								<?php if ( ! empty( $import_file['import_notice'] ) ) : ?>
									<p class="conjure__demo-card-description"><?php echo esc_html( $import_file['import_notice'] ); ?></p>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<span class="js-conjure-select-spinner" style="display:none;">Loading...</span>
			</div>
		<?php endif; ?>

			<a id="conjure__drawer-trigger" class="conjure__button conjure__button--knockout"><span><?php echo esc_html( $action ); ?></span><span class="chevron"></span></a>

		</div>

		<form action="" method="post" class="<?php echo esc_attr( $multi_import ); ?> <?php echo $this->is_manual_upload_mode() ? 'conjure-manual-upload-mode' : ''; ?>">

			<?php if ( $this->is_manual_upload_mode() ) : ?>

				<ul class="conjure__drawer conjure__drawer--import-content conjure__drawer--upload js-conjure-drawer-import-content">
					<?php echo $this->get_manual_upload_html(); ?>
				</ul>

			<?php else : ?>

			<ul class="conjure__drawer conjure__drawer--import-content conjure__drawer--upload js-conjure-drawer-import-content">
				<?php 
				// Add health telemetry at the top of the drawer.
				// Hook at line 2574 area for extensibility via conjure_health_telemetry_in_drawer filter.
				$health_telemetry_html = '';
				if ( $server_health && $server_health->is_enabled() ) {
					$health_telemetry_html = $server_health->render_drawer_telemetry();
				}
				$health_telemetry_html = apply_filters( 'conjure_health_telemetry_in_drawer', $health_telemetry_html, $server_health, $this );
				if ( ! empty( $health_telemetry_html ) ) {
					echo wp_kses_post( $health_telemetry_html );
				}

				// Check if no demos are registered.
				if ( empty( $this->import_files ) && ! $this->is_manual_upload_mode() ) {
					?>
					<li class="conjure__drawer--import-content__list-item" style="color: #d63638; padding: 20px;">
						<strong>⚠️ No Demo Files Registered</strong><br><br>
						No demo import files have been registered. To use ConjureWP, you need to:<br><br>
						<ol style="margin-left: 20px;">
							<li>Add demo files to your theme (content.xml, widgets.json, etc.)</li>
							<li>Register them using the <code>conjure_import_files</code> filter</li>
						</ol>
						<br>
						See: <code>conjurewp-config.php</code> or <code>examples/</code> directory for examples.
					</li>
					<?php
				} else {
					$import_steps_html = $this->get_import_steps_html( $import_info );
					
					// Debug: Log if HTML is empty.
					if ( empty( trim( $import_steps_html ) ) ) {
						$this->logger->error( 
							'Import steps HTML is empty!',
							array(
								'import_info' => $import_info,
								'selected_demo_index' => $selected_demo_index,
								'import_files_count' => count( $this->import_files ),
								'import_files_data' => ! empty( $this->import_files[$selected_demo_index] ) ? array_keys( $this->import_files[$selected_demo_index] ) : 'index not set',
							)
						);
						?>
						<li class="conjure__drawer--import-content__list-item" style="color: #d63638; padding: 20px;">
							<strong>⚠️ No Import Options Found</strong><br><br>
							The selected demo has no import data configured.<br><br>
							<strong>Check:</strong>
							<ul style="margin-left: 20px;">
								<li>Demo files exist (content.xml, widgets.json, etc.)</li>
								<li>File paths are correct in your config</li>
								<li>Check logs at: <code>wp-content/uploads/conjure-logs/</code></li>
							</ul>
						</li>
						<?php
					} else {
						echo $import_steps_html;
					}
				}
				?>
			</ul>

			<?php endif; ?>

			<footer class="conjure__content__footer">

				<a id="close" href="<?php echo esc_url( $this->step_next_link() ); ?>" class="conjure__button conjure__button--skip conjure__button--closer conjure__button--proceed"><?php echo esc_html( $skip ); ?></a>

				<a id="skip" href="<?php echo esc_url( $this->step_next_link() ); ?>" class="conjure__button conjure__button--skip conjure__button--proceed"><?php echo esc_html( $skip ); ?></a>

				<a href="<?php echo esc_url( $this->step_next_link() ); ?>" class="conjure__button conjure__button--next button-next" data-callback="install_content">
					<span class="conjure__button--loading__text"><?php echo esc_html( $import ); ?></span>

					<div class="conjure__progress-bar">
						<span class="js-conjure-progress-bar"></span>
					</div>

					<span class="js-conjure-progress-bar-percentage">0%</span>
				</a>

				<?php wp_nonce_field( 'conjure' ); ?>
			</footer>
		</form>

		<?php
		$this->logger->debug( __( 'The content import step has been displayed', 'conjurewp' ) );
	}


	/**
	 * Final step
	 */
	protected function ready() {

		// Author name.
		$author = $this->theme->author;

		// Theme Name.
		$theme = ucfirst( $this->theme );

		// Remove "Child" from the current theme name, if it's installed.
		$theme = str_replace( ' Child', '', $theme );

		// Strings passed in from the config file.
		$strings = $this->strings;

		// Text strings.
		$header    = $strings['ready-header'];
		$paragraph = $strings['ready%s'];
		$action    = $strings['ready-action-link'];
		$skip      = $strings['btn-skip'];
		$next      = $strings['btn-next'];
		$big_btn   = $strings['ready-big-button'];

		// Links.
		$links = array();

		for ( $i = 1; $i < 4; $i++ ) {
			if ( ! empty( $strings[ "ready-link-$i" ] ) ) {
				$links[] = $strings[ "ready-link-$i" ];
			}
		}

		$links_class = empty( $links ) ? 'conjure__content__footer--nolinks' : null;

		$allowed_html_array = array(
			'a' => array(
				'href'   => array(),
				'title'  => array(),
				'target' => array(),
			),
		);

		update_option( 'conjure_' . $this->slug . '_completed', time() );
		?>

		<div class="conjure__content--transition">

			<?php echo wp_kses( $this->svg( array( 'icon' => 'done' ) ), $this->svg_allowed_html() ); ?>

			<h1><?php echo esc_html( sprintf( $header, $theme ) ); ?></h1>

			<p><?php wp_kses( printf( $paragraph, $author ), $allowed_html_array ); ?></p>

		</div>

		<footer class="conjure__content__footer conjure__content__footer--fullwidth <?php echo esc_attr( $links_class ); ?>">

			<a href="<?php echo esc_url( $this->ready_big_button_url ); ?>" class="conjure__button conjure__button--blue conjure__button--fullwidth conjure__button--popin"><?php echo esc_html( $big_btn ); ?></a>

			<?php if ( ! empty( $links ) ) : ?>
				<a id="conjure__drawer-trigger" class="conjure__button conjure__button--knockout"><span><?php echo esc_html( $action ); ?></span><span class="chevron"></span></a>

				<ul class="conjure__drawer conjure__drawer--extras">

					<?php foreach ( $links as $link ) : ?>
						<li><?php echo wp_kses( $link, $allowed_html_array ); ?></li>
					<?php endforeach; ?>

				</ul>
			<?php endif; ?>

		</footer>

		<?php
		$this->logger->debug( __( 'The final step has been displayed', 'conjurewp' ) );
	}

	/**
	 * Get plugins for installation
	 *
	 * Uses the custom plugin manager to get demo-specific plugins.
	 *
	 * @param int|string|null $demo_index Optional. Demo index or slug for demo-specific plugins.
	 * @return    array Array of plugins organized by status.
	 */
	protected function get_plugins( $demo_index = null ) {
		// Default empty plugin array.
		$plugins = array(
			'all'      => array(), // All plugins which need action.
			'install'  => array(),
			'update'   => array(),
			'activate' => array(),
		);

		if ( ! $this->demo_plugin_manager ) {
			$this->logger->debug( 'Demo plugin manager not available' );
			return $plugins;
		}

		// Get demo-specific plugins if demo index provided.
		if ( null !== $demo_index ) {
			$demo_plugins = $this->demo_plugin_manager->get_demo_plugins_with_status( $demo_index, $this->import_files );
			
			if ( ! empty( $demo_plugins['all'] ) ) {
				$this->logger->info( 'Using demo-specific plugins for demo index: ' . $demo_index );
				return $demo_plugins;
			}
			
			$this->logger->debug( 'No demo-specific plugins found for demo index: ' . $demo_index );
		}

		return $plugins;
	}

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
					'message' => esc_html__( 'You do not have permission to perform this action.', 'conjurewp' ),
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
			$error_message = __( 'Unable to initialize the WordPress filesystem. Cannot create child theme.', 'conjurewp' );
			$this->logger->error( $error_message );

			wp_send_json_error(
				array(
					'message' => esc_html( $error_message ),
				)
			);
		}

		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			$error_message = __( 'WordPress filesystem is not available. Cannot create child theme.', 'conjurewp' );
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
				__( 'Unable to create child theme directory: %s', 'conjurewp' ),
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
			$error_message = __( 'Unable to create child theme style.css file.', 'conjurewp' );
			$this->logger->error( $error_message );

			wp_send_json_error(
				array(
					'message' => esc_html( $error_message ),
				)
			);
		}

		$functions_result = $wp_filesystem->put_contents( $path . '/functions.php', $this->generate_child_functions_php( $parent_slug ) );
		if ( ! $functions_result ) {
			$error_message = __( 'Unable to create child theme functions.php file.', 'conjurewp' );
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

			$this->logger->debug( __( 'The existing child theme was activated', 'conjurewp' ) );

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

	$this->logger->debug( __( 'The newly generated child theme was activated', 'conjurewp' ) );

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
	 * Activate the theme (license key) via AJAX.
	 */
	public function _ajax_activate_license() {

		if ( ! check_ajax_referer( 'conjure_nonce', 'wpnonce' ) ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => esc_html__( 'Yikes! The theme activation failed. Please try again or contact support.', 'conjurewp' ),
				)
			);
		}

		if ( empty( $_POST['license_key'] ) ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => esc_html__( 'Please add your license key before attempting to activate one.', 'conjurewp' ),
				)
			);
		}

		$license_key = sanitize_key( $_POST['license_key'] );

		if ( ! has_filter( 'conjure_ajax_activate_license' ) ) {
			$result = $this->edd_activate_license( $license_key );
		} else {
			$result = apply_filters( 'conjure_ajax_activate_license', $license_key );
		}

		$this->logger->debug( __( 'The license activation was performed with the following results', 'conjurewp' ), $result );

		wp_send_json( array_merge( array( 'done' => 1 ), $result ) );
	}

	/**
	 * Activate the EDD license.
	 *
	 * This code was taken from the EDD licensing addon theme example code
	 * (`activate_license` method of the `EDD_Theme_Updater_Admin` class).
	 *
	 * @param string $license The license key.
	 *
	 * @return array
	 */
	protected function edd_activate_license( $license ) {
		$success = false;

		// Strings passed in from the config file.
		$strings = $this->strings;

		// Theme Name.
		$theme = ucfirst( $this->theme );

		// Remove "Child" from the current theme name, if it's installed.
		$theme = str_replace( ' Child', '', $theme );

		// Text strings.
		$success_message = $strings['license-json-success%s'];

		// Data to send in our API request.
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => rawurlencode( $license ),
			'item_name'  => rawurlencode( $this->edd_item_name ),
			'url'        => esc_url( home_url( '/' ) ),
		);

		$response = $this->edd_get_api_response( $api_params );

		// Make sure the response came back okay.
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = esc_html__( 'An error occurred, please try again.', 'conjurewp' );
			}
		} else {

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( false === $license_data->success ) {

				switch ( $license_data->error ) {

					case 'expired':
						$message = sprintf(
						/* translators: Expiration date */
							esc_html__( 'Your license key expired on %s.', 'conjurewp' ),
							date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, time() ) )
						);
						break;

					case 'revoked':
						$message = esc_html__( 'Your license key has been disabled.', 'conjurewp' );
						break;

					case 'missing':
						$message = esc_html__( 'This appears to be an invalid license key. Please try again or contact support.', 'conjurewp' );
						break;

					case 'invalid':
					case 'site_inactive':
						$message = esc_html__( 'Your license is not active for this URL.', 'conjurewp' );
						break;

					case 'item_name_mismatch':
						/* translators: EDD Item Name */
						$message = sprintf( esc_html__( 'This appears to be an invalid license key for %s.', 'conjurewp' ), $this->edd_item_name );
						break;

					case 'no_activations_left':
						$message = esc_html__( 'Your license key has reached its activation limit.', 'conjurewp' );
						break;

					default:
						$message = esc_html__( 'An error occurred, please try again.', 'conjurewp' );
						break;
				}
			} else {
				if ( 'valid' === $license_data->license ) {
					$message = sprintf( esc_html( $success_message ), $theme );
					$success = true;

					// Removes the default EDD hook for this option, which breaks the AJAX call.
					remove_all_actions( 'update_option_' . $this->edd_theme_slug . '_license_key', 10 );

					update_option( $this->edd_theme_slug . '_license_key_status', $license_data->license );
					update_option( $this->edd_theme_slug . '_license_key', $license );

					// Mark license step as completed.
					$this->mark_step_completed( 'license' );
				}
			}
		}

		return compact( 'success', 'message' );
	}

	/**
	 * Makes a call to the API.
	 *
	 * This code was taken from the EDD licensing addon theme example code
	 * (`get_api_response` method of the `EDD_Theme_Updater_Admin` class).
	 *
	 * @param array $api_params to be used for wp_remote_get.
	 * @return array $response JSON response.
	 */
	private function edd_get_api_response( $api_params ) {

		$verify_ssl = (bool) apply_filters( 'edd_sl_api_request_verify_ssl', true );

		$response = wp_remote_post(
			$this->edd_remote_api_url,
			array(
				'timeout'   => 15,
				'sslverify' => $verify_ssl,
				'body'      => $api_params,
			)
		);

		return $response;
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

		$this->logger->debug( __( 'The child theme functions.php content was generated', 'conjurewp' ) );

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

		$this->logger->debug( __( 'The child theme style.css content was generated', 'conjurewp' ) );

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

			$this->logger->debug( __( 'The child theme screenshot was copied to the child theme, with the following result', 'conjurewp' ), array( 'copied' => $copied ) );
		} else {
			$this->logger->debug( __( 'The child theme screenshot was not generated, because of these results', 'conjurewp' ), array( 'screenshot' => $screenshot ) );
		}
	}

	/**
	 * AJAX handler for custom plugin installation.
	 *
	 * Handles plugin installation using the custom installer (non-TGMPA).
	 */
	public function _ajax_install_plugin() {
		// Verify nonce and check permissions.
		if ( ! check_ajax_referer( 'conjure_nonce', 'wpnonce', false ) ) {
			wp_send_json_error( array(
				'message' => esc_html__( 'Security check failed.', 'conjurewp' ),
			) );
		}

		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( array(
				'message' => esc_html__( 'You do not have permission to install plugins.', 'conjurewp' ),
			) );
		}

		$slug = isset( $_POST['slug'] ) ? sanitize_key( $_POST['slug'] ) : '';

		if ( empty( $slug ) ) {
			wp_send_json_error( array(
				'message' => esc_html__( 'Plugin slug not provided.', 'conjurewp' ),
			) );
		}

		$installer = $this->demo_plugin_manager->get_installer();

		$this->logger->info( "Installing plugin via AJAX: {$slug}" );

		// Install and activate the plugin.
		$result = $installer->install_and_activate( $slug );

		if ( is_wp_error( $result ) ) {
			$this->logger->error( "Plugin installation failed: " . $result->get_error_message() );

			wp_send_json_error( array(
				'message' => $result->get_error_message(),
			) );
		}

		$this->logger->info( "Successfully installed and activated plugin: {$slug}" );

	// Check if all plugins are installed.
	$selected_demo_index = get_transient( 'conjure_selected_demo_index' );
	$plugins             = $this->get_plugins( $selected_demo_index );

	// Check if there's any remaining work to do (install or activate).
	$has_work_remaining = ! empty( $plugins['install'] ) || ! empty( $plugins['activate'] );

	$this->logger->debug( 'After installing ' . $slug . ': install=' . count( $plugins['install'] ) . ', activate=' . count( $plugins['activate'] ) . ', has_work_remaining=' . ( $has_work_remaining ? 'yes' : 'no' ) );

		if ( ! $has_work_remaining ) {
			// All plugins complete, mark step as done.
			$this->mark_step_completed( 'plugins' );

			$this->logger->info( 'All plugins installed and activated, step marked as complete' );

			wp_send_json_success( array(
				'message'   => esc_html__( 'All plugins installed successfully!', 'conjurewp' ),
				'done'      => true,
				'completed' => true,
			) );
		}

		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: %s: plugin slug */
				esc_html__( 'Successfully installed %s', 'conjurewp' ),
				$slug
			),
			'done' => false,
		) );
	}

	/**
	 * Do content's AJAX
	 *
	 * @internal    Used as a callback.
	 */
	public function _ajax_content() {
		// Wrap the entire AJAX handler in try-catch to prevent unhandled errors.
		try {
			static $content = null;

			// Validate POST data exists.
			if ( ! isset( $_POST['selected_index'] ) ) {
				$this->logger->error( __( 'Content import AJAX missing selected_index', 'conjurewp' ) );
				wp_send_json_error(
					array(
						'error'   => 1,
						'message' => esc_html__( 'Missing import index!', 'conjurewp' ),
					)
				);
			}

			$selected_import = intval( $_POST['selected_index'] );

			if ( null === $content ) {
				$content = $this->get_import_data( $selected_import );
				
				// Check if we got valid import data.
				if ( empty( $content ) || ! is_array( $content ) ) {
					$this->logger->error( 
						__( 'Failed to get import data for selected index', 'conjurewp' ),
						array( 'selected_import' => $selected_import )
					);
					wp_send_json_error(
						array(
							'error'   => 1,
							'message' => esc_html__( 'Failed to load import configuration!', 'conjurewp' ),
						)
					);
				}
			}

			if ( ! check_ajax_referer( 'conjure_nonce', 'wpnonce' ) || empty( $_POST['content'] ) || ! isset( $content[ $_POST['content'] ] ) ) {
				$this->logger->error( 
					__( 'The content importer AJAX call failed to start, because of incorrect data', 'conjurewp' ),
					array(
						'content_key' => isset( $_POST['content'] ) ? $_POST['content'] : 'not set',
						'available_keys' => array_keys( $content ),
					)
				);

				wp_send_json_error(
					array(
						'error'   => 1,
						'message' => esc_html__( 'Invalid content!', 'conjurewp' ),
					)
				);
			}

			$json         = false;
			$this_content = $content[ $_POST['content'] ];

			if ( isset( $_POST['proceed'] ) ) {
				if ( is_callable( $this_content['install_callback'] ) ) {
					$this->logger->info(
						__( 'The content import AJAX call will be executed with this import data', 'conjurewp' ),
						array(
							'title' => $this_content['title'],
							'data'  => $this_content['data'],
						)
					);

					// Wrap the callback execution in try-catch.
					try {
						// Use output buffering to catch any unexpected output.
						ob_start();
						$logs = call_user_func( $this_content['install_callback'], $this_content['data'] );
						$callback_output = ob_get_clean();
						
						if ( ! empty( $callback_output ) ) {
							$this->logger->warning( 
								__( 'Import callback produced output', 'conjurewp' ),
								array( 'output' => $callback_output )
							);
						}

						if ( $logs ) {
							$json = array(
								'done'    => 1,
								'message' => $this_content['success'],
								'debug'   => '',
								'logs'    => $logs,
								'errors'  => '',
							);

							// The content import ended, so we should mark that all posts were imported.
							if ( 'content' === $_POST['content'] ) {
								$json['num_of_imported_posts'] = 'all';
							}
						} else {
							$this->logger->warning( 
								__( 'Import callback returned empty/false result', 'conjurewp' ),
								array( 'content_type' => $_POST['content'] )
							);
						}
					} catch ( \Exception $e ) {
						$error_message = sprintf(
							__( 'Exception during content import: %s', 'conjurewp' ),
							$e->getMessage()
						);
						$this->logger->error( $error_message, array( 'trace' => $e->getTraceAsString() ) );
						
						wp_send_json_error(
							array(
								'error'   => 1,
								'message' => $error_message,
								'logs'    => '',
								'errors'  => $e->getMessage(),
							)
						);
					} catch ( \Error $e ) {
						$error_message = sprintf(
							__( 'Fatal error during content import: %s', 'conjurewp' ),
							$e->getMessage()
						);
						$this->logger->error( $error_message, array( 'trace' => $e->getTraceAsString() ) );
						
						wp_send_json_error(
							array(
								'error'   => 1,
								'message' => $error_message,
								'logs'    => '',
								'errors'  => $e->getMessage(),
							)
						);
					}
				} else {
					$this->logger->error(
						__( 'Import callback is not callable', 'conjurewp' ),
						array(
							'callback' => $this_content['install_callback'],
							'content_type' => $_POST['content'],
						)
					);
				}
			} else {
				$json = array(
					'url'            => admin_url( 'admin-ajax.php' ),
					'action'         => 'conjure_content',
					'proceed'        => 'true',
					'content'        => $_POST['content'],
					'_wpnonce'       => wp_create_nonce( 'conjure_nonce' ),
					'selected_index' => $selected_import,
					'message'        => $this_content['installing'],
					'logs'           => '',
					'errors'         => '',
				);
			}

			if ( $json ) {
				$json['hash'] = md5( serialize( $json ) );
				wp_send_json( $json );
			} else {
				$this->logger->error(
					__( 'The content import AJAX call failed with this passed data', 'conjurewp' ),
					array(
						'selected_content_index' => $selected_import,
						'importing_content'      => $_POST['content'],
						'importing_data'         => $this_content['data'],
					)
				);

				wp_send_json(
					array(
						'error'   => 1,
						'message' => esc_html__( 'Error', 'conjurewp' ),
						'logs'    => '',
						'errors'  => '',
					)
				);
			}
		} catch ( \Exception $e ) {
			$this->logger->error(
				__( 'Uncaught exception in content import AJAX handler', 'conjurewp' ),
				array(
					'message' => $e->getMessage(),
					'trace' => $e->getTraceAsString(),
				)
			);

			wp_send_json_error(
				array(
					'error'   => 1,
					'message' => sprintf(
						/* translators: %s: error message */
						esc_html__( 'Import error: %s', 'conjurewp' ),
						$e->getMessage()
					),
					'logs'    => '',
					'errors'  => $e->getMessage(),
				)
			);
		} catch ( \Error $e ) {
			$this->logger->error(
				__( 'Fatal error in content import AJAX handler', 'conjurewp' ),
				array(
					'message' => $e->getMessage(),
					'trace' => $e->getTraceAsString(),
				)
			);

			wp_send_json_error(
				array(
					'error'   => 1,
					'message' => sprintf(
						/* translators: %s: error message */
						esc_html__( 'Fatal import error: %s', 'conjurewp' ),
						$e->getMessage()
					),
					'logs'    => '',
					'errors'  => $e->getMessage(),
				)
			);
		}
	}


	/**
	 * AJAX call to retrieve total items (posts, pages, CPT, attachments) for the content import.
	 */
	public function _ajax_get_total_content_import_items() {
		// Wrap in try-catch to prevent errors from breaking the AJAX response.
		try {
			// Catch any output from plugins that might interfere with JSON response.
			ob_start();

			if ( ! check_ajax_referer( 'conjure_nonce', 'wpnonce' ) || empty( $_POST['selected_index'] ) ) {
				ob_end_clean();
				$this->logger->error( __( 'The content importer AJAX call for retrieving total content import items failed to start, because of incorrect data.', 'conjurewp' ) );

				wp_send_json_error(
					array(
						'error'   => 1,
						'message' => esc_html__( 'Invalid data!', 'conjurewp' ),
					)
				);
			}

			$selected_import = intval( $_POST['selected_index'] );
			$import_files    = $this->get_import_files_paths( $selected_import );

			// Check if we have valid content file.
			if ( empty( $import_files['content'] ) || ! file_exists( $import_files['content'] ) ) {
				ob_end_clean();
				$this->logger->warning( 'Content file not found for counting import items' );
				wp_send_json_success( 0 );
			}

			$total_items = $this->importer->get_number_of_posts_to_import( $import_files['content'] );

			// Clean any buffered output before sending JSON.
			$buffered_output = ob_get_clean();
			if ( ! empty( $buffered_output ) ) {
				$this->logger->warning( 
					'Unexpected output during content item counting',
					array( 'output' => $buffered_output )
				);
			}

			wp_send_json_success( $total_items );

		} catch ( \Exception $e ) {
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}
			
			$this->logger->error(
				'Exception in _ajax_get_total_content_import_items',
				array(
					'message' => $e->getMessage(),
					'trace' => $e->getTraceAsString(),
				)
			);

			// Return 0 instead of error to allow import to proceed without progress bar.
			wp_send_json_success( 0 );

		} catch ( \Error $e ) {
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}
			
			$this->logger->error(
				'Fatal error in _ajax_get_total_content_import_items',
				array(
					'message' => $e->getMessage(),
					'trace' => $e->getTraceAsString(),
				)
			);

			// Return 0 instead of error to allow import to proceed without progress bar.
			wp_send_json_success( 0 );
		}
	}

	/**
	 * AJAX handler for live health metrics checks.
	 */
	public function _ajax_get_health_metrics() {
		// Wrap in try-catch to prevent errors from breaking the AJAX response.
		try {
			// Catch any output from plugins that might interfere with JSON response.
			ob_start();

			if ( ! check_ajax_referer( 'conjure_nonce', 'wpnonce', false ) ) {
				ob_end_clean();
				wp_send_json_error(
					array(
						'message' => esc_html__( 'Security check failed.', 'conjurewp' ),
					)
				);
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				ob_end_clean();
				wp_send_json_error(
					array(
						'message' => esc_html__( 'You do not have permission to perform this action.', 'conjurewp' ),
					)
				);
			}

			require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-server-health.php';
			$server_health = new Conjure_Server_Health();

			$metrics = $server_health->get_telemetry_metrics();

			// Clean any buffered output before sending JSON.
			$buffered_output = ob_get_clean();
			if ( ! empty( $buffered_output ) ) {
				$this->logger->warning( 
					'Unexpected output during health metrics check',
					array( 'output' => $buffered_output )
				);
			}

			wp_send_json_success( $metrics );

		} catch ( \Exception $e ) {
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}

			$this->logger->error(
				'Exception in _ajax_get_health_metrics',
				array(
					'message' => $e->getMessage(),
					'trace' => $e->getTraceAsString(),
				)
			);

			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %s: error message */
						esc_html__( 'Health check error: %s', 'conjurewp' ),
						$e->getMessage()
					),
				)
			);

		} catch ( \Error $e ) {
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}
			
			$this->logger->error(
				'Fatal error in _ajax_get_health_metrics',
				array(
					'message' => $e->getMessage(),
					'trace' => $e->getTraceAsString(),
				)
			);

			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %s: error message */
						esc_html__( 'Fatal health check error: %s', 'conjurewp' ),
						$e->getMessage()
					),
				)
			);
		}
	}


	/**
	 * Get import data from the selected import.
	 * Which data does the selected import have for the import.
	 *
	 * @param int $selected_import_index The index of the predefined demo import.
	 *
	 * @return bool|array
	 */
	public function get_import_data_info( $selected_import_index = 0 ) {
		$import_data = array(
			'content'      => false,
			'widgets'      => false,
			'options'      => false,
			'sliders'      => false,
			'redux'        => false,
			'after_import' => false,
		);

		// If in manual upload mode (no registered files), return empty structure
		if ( empty( $this->import_files[ $selected_import_index ] ) ) {
			// Check if we're in manual upload mode
			if ( $this->is_manual_upload_mode() ) {
				// Return all false - manual upload will be handled by the UI
				return $import_data;
			}
			return false;
		}

		if (
			! empty( $this->import_files[ $selected_import_index ]['import_file_url'] ) ||
			! empty( $this->import_files[ $selected_import_index ]['local_import_file'] )
		) {
			$import_data['content'] = true;
		}

		if (
			! empty( $this->import_files[ $selected_import_index ]['import_widget_file_url'] ) ||
			! empty( $this->import_files[ $selected_import_index ]['local_import_widget_file'] )
		) {
			$import_data['widgets'] = true;
		}

		if (
			! empty( $this->import_files[ $selected_import_index ]['import_customizer_file_url'] ) ||
			! empty( $this->import_files[ $selected_import_index ]['local_import_customizer_file'] )
		) {
			$import_data['options'] = true;
		}

		if (
			! empty( $this->import_files[ $selected_import_index ]['import_rev_slider_file_url'] ) ||
			! empty( $this->import_files[ $selected_import_index ]['local_import_rev_slider_file'] )
		) {
			$import_data['sliders'] = true;
		}

		if (
			! empty( $this->import_files[ $selected_import_index ]['import_redux'] ) ||
			! empty( $this->import_files[ $selected_import_index ]['local_import_redux'] )
		) {
			$import_data['redux'] = true;
		}

		if ( false !== has_action( 'conjure_after_all_import' ) ) {
			$import_data['after_import'] = true;
		}

		return $import_data;
	}


	/**
	 * Get the import files/data.
	 *
	 * @param int $selected_import_index The index of the predefined demo import.
	 *
	 * @return    array
	 */
	protected function get_import_data( $selected_import_index = 0 ) {
		$content = array();

		$import_files = $this->get_import_files_paths( $selected_import_index );

		if ( ! empty( $import_files['content'] ) ) {
			$content['content'] = array(
				'title'            => esc_html__( 'Content', 'conjurewp' ),
				'description'      => esc_html__( 'Demo content data.', 'conjurewp' ),
				'pending'          => esc_html__( 'Pending', 'conjurewp' ),
				'installing'       => esc_html__( 'Installing', 'conjurewp' ),
				'success'          => esc_html__( 'Success', 'conjurewp' ),
				'checked'          => $this->is_possible_upgrade() ? 0 : 1,
				'install_callback' => array( $this->importer, 'import' ),
				'data'             => $import_files['content'],
			);
		}

		if ( ! empty( $import_files['widgets'] ) ) {
			$content['widgets'] = array(
				'title'            => esc_html__( 'Widgets', 'conjurewp' ),
				'description'      => esc_html__( 'Sample widgets data.', 'conjurewp' ),
				'pending'          => esc_html__( 'Pending', 'conjurewp' ),
				'installing'       => esc_html__( 'Installing', 'conjurewp' ),
				'success'          => esc_html__( 'Success', 'conjurewp' ),
				'install_callback' => array( 'Conjure_Widget_Importer', 'import' ),
				'checked'          => $this->is_possible_upgrade() ? 0 : 1,
				'data'             => $import_files['widgets'],
			);
		}

		if ( ! empty( $import_files['sliders'] ) ) {
			$content['sliders'] = array(
				'title'            => esc_html__( 'Revolution Slider', 'conjurewp' ),
				'description'      => esc_html__( 'Sample Revolution sliders data.', 'conjurewp' ),
				'pending'          => esc_html__( 'Pending', 'conjurewp' ),
				'installing'       => esc_html__( 'Installing', 'conjurewp' ),
				'success'          => esc_html__( 'Success', 'conjurewp' ),
				'install_callback' => array( $this, 'import_revolution_sliders' ),
				'checked'          => $this->is_possible_upgrade() ? 0 : 1,
				'data'             => $import_files['sliders'],
			);
		}

		if ( ! empty( $import_files['options'] ) ) {
			$content['options'] = array(
				'title'            => esc_html__( 'Options', 'conjurewp' ),
				'description'      => esc_html__( 'Sample theme options data.', 'conjurewp' ),
				'pending'          => esc_html__( 'Pending', 'conjurewp' ),
				'installing'       => esc_html__( 'Installing', 'conjurewp' ),
				'success'          => esc_html__( 'Success', 'conjurewp' ),
				'install_callback' => array( 'Conjure_Customizer_Importer', 'import' ),
				'checked'          => $this->is_possible_upgrade() ? 0 : 1,
				'data'             => $import_files['options'],
			);
		}

		if ( ! empty( $import_files['redux'] ) ) {
			$content['redux'] = array(
				'title'            => esc_html__( 'Redux Options', 'conjurewp' ),
				'description'      => esc_html__( 'Redux framework options.', 'conjurewp' ),
				'pending'          => esc_html__( 'Pending', 'conjurewp' ),
				'installing'       => esc_html__( 'Installing', 'conjurewp' ),
				'success'          => esc_html__( 'Success', 'conjurewp' ),
				'install_callback' => array( 'Conjure_Redux_Importer', 'import' ),
				'checked'          => $this->is_possible_upgrade() ? 0 : 1,
				'data'             => $import_files['redux'],
			);
		}

		if ( false !== has_action( 'conjure_after_all_import' ) ) {
			$content['after_import'] = array(
				'title'            => esc_html__( 'After import setup', 'conjurewp' ),
				'description'      => esc_html__( 'After import setup.', 'conjurewp' ),
				'pending'          => esc_html__( 'Pending', 'conjurewp' ),
				'installing'       => esc_html__( 'Installing', 'conjurewp' ),
				'success'          => esc_html__( 'Success', 'conjurewp' ),
				'install_callback' => array( $this->hooks, 'after_all_import_action' ),
				'checked'          => $this->is_possible_upgrade() ? 0 : 1,
				'data'             => $selected_import_index,
			);
		}

		// Hook at line 2574: Allow filtering of base content before returning.
		// Health telemetry is handled separately in drawer rendering, but this hook
		// can be used to add health check items to import content if needed.
		$content = apply_filters( 'conjure_get_base_content', $content, $this );

		return $content;
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

		$this->logger->info( __( 'The revolution slider import was executed', 'conjurewp' ) );

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return 'true';
		}
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
		$data['message']  = esc_html__( 'Installing', 'conjurewp' );
		$data['proceed']  = 'true';
		$data['action']   = 'conjure_content';
		$data['content']  = 'content';
		$data['_wpnonce'] = wp_create_nonce( 'conjure_nonce' );
		$data['hash']     = md5( rand() ); // Has to be unique (check JS code catching this AJAX response).

		return $data;
	}

	/**
	 * After content import setup code.
	 */
	public function after_content_import_setup() {
		// Set static homepage.
		$homepage = get_page_by_title( apply_filters( 'conjure_content_home_page_title', 'Home' ) );

		if ( $homepage ) {
			update_option( 'page_on_front', $homepage->ID );
			update_option( 'show_on_front', 'page' );

			$this->logger->debug( __( 'The home page was set', 'conjurewp' ), array( 'homepage_id' => $homepage ) );
		}

		// Set static blog page.
		$blogpage = get_page_by_title( apply_filters( 'conjure_content_blog_page_title', 'Blog' ) );

		if ( $blogpage ) {
			update_option( 'page_for_posts', $blogpage->ID );
			update_option( 'show_on_front', 'page' );

			$this->logger->debug( __( 'The blog page was set', 'conjurewp' ), array( 'blog_page_id' => $blogpage ) );
		}
	}

	/**
	 * Before content import setup code.
	 */
	public function before_content_import_setup() {
		// Update the Hello World! post by making it a draft.
		$hello_world = get_page_by_title( 'Hello World!', OBJECT, 'post' );

		if ( ! empty( $hello_world ) && apply_filters( 'conjure_draft_hello_world', true ) ) {
			$hello_world->post_status = 'draft';
			wp_update_post( $hello_world );

			$this->logger->debug( __( 'The Hello world post status was set to draft', 'conjurewp' ) );
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

		// Load the CLI class.
		require_once trailingslashit( $this->base_path ) . $this->directory . '/includes/class-conjure-cli.php';

		// Register the CLI commands.
		$cli = new Conjure_CLI( $this );
		WP_CLI::add_command( 'conjure list', array( $cli, 'list_demos' ) );
		WP_CLI::add_command( 'conjure import', array( $cli, 'import' ) );
	}

	/**
	 * Register REST API endpoints.
	 */
	public function register_rest_api() {
		// Load the REST API class.
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
				$this->logger->warning( __( 'This predefined demo import does not have the name parameter: import_file_name', 'conjurewp' ), $import_file );
			}
		}

		return $filtered_import_file_info;
	}

	/**
	 * Set the import file base name.
	 * Check if an existing base name is available (saved in a transient).
	 */
	public function set_import_file_base_name() {
		$existing_name = get_transient( 'conjure_import_file_base_name' );

		if ( ! empty( $existing_name ) ) {
			$this->import_file_base_name = $existing_name;
		} else {
			$this->import_file_base_name = gmdate( 'Y-m-d__H-i-s' );
		}

		set_transient( 'conjure_import_file_base_name', $this->import_file_base_name, MINUTE_IN_SECONDS );
	}

	/**
	 * Get the import file paths.
	 * Grab the defined local paths, download the files or reuse existing files.
	 *
	 * @param int $selected_import_index The index of the selected import.
	 *
	 * @return array
	 */
	public function get_import_files_paths( $selected_import_index ) {
		$selected_import_data = empty( $this->import_files[ $selected_import_index ] ) ? false : $this->import_files[ $selected_import_index ];

		// Check for manually uploaded files if no registered import files.
		if ( empty( $selected_import_data ) ) {
			$uploaded_files = get_transient( 'conjure_uploaded_files' );

			if ( ! empty( $uploaded_files ) && is_array( $uploaded_files ) ) {
				$import_files = array(
					'content' => '',
					'widgets' => '',
					'options' => '',
					'redux'   => array(),
					'sliders' => '',
					'images'  => '',
					'menus'   => '',
				);

				// Map uploaded files to expected format.
				foreach ( $uploaded_files as $type => $file_data ) {
					if ( isset( $import_files[ $type ] ) && file_exists( $file_data['path'] ) ) {
						if ( 'redux' === $type ) {
							$import_files['redux'][] = array(
								'option_name' => 'redux_option_name',
								'file_path'   => $file_data['path'],
							);
						} else {
							$import_files[ $type ] = $file_data['path'];
						}
					}
				}

				return $import_files;
			}

			return array();
		}

		// Set the base name for the import files.
		$this->set_import_file_base_name();

		$base_file_name = $this->import_file_base_name;
		$import_files   = array(
			'content' => '',
			'widgets' => '',
			'options' => '',
			'redux'   => array(),
			'sliders' => '',
		);

		$downloader = new Conjure_Downloader();

		// Check if 'import_file_url' is not defined. That would mean a local file.
		if ( empty( $selected_import_data['import_file_url'] ) ) {
			if ( ! empty( $selected_import_data['local_import_file'] ) && file_exists( $selected_import_data['local_import_file'] ) ) {
				$import_files['content'] = $selected_import_data['local_import_file'];
			}
		} else {
			// Set the filename string for content import file.
			$content_filename = 'content-' . $base_file_name . '.xml';

			// Retrieve the content import file.
			$import_files['content'] = $downloader->fetch_existing_file( $content_filename );

			// Download the file, if it's missing.
			if ( empty( $import_files['content'] ) ) {
				$import_files['content'] = $downloader->download_file( $selected_import_data['import_file_url'], $content_filename );
			}

			// Reset the variable, if there was an error.
			if ( is_wp_error( $import_files['content'] ) ) {
				$import_files['content'] = '';
			}
		}

		// Get widgets file as well. If defined!
		if ( ! empty( $selected_import_data['import_widget_file_url'] ) ) {
			// Set the filename string for widgets import file.
			$widget_filename = 'widgets-' . $base_file_name . '.json';

			// Retrieve the content import file.
			$import_files['widgets'] = $downloader->fetch_existing_file( $widget_filename );

			// Download the file, if it's missing.
			if ( empty( $import_files['widgets'] ) ) {
				$import_files['widgets'] = $downloader->download_file( $selected_import_data['import_widget_file_url'], $widget_filename );
			}

			// Reset the variable, if there was an error.
			if ( is_wp_error( $import_files['widgets'] ) ) {
				$import_files['widgets'] = '';
			}
		} elseif ( ! empty( $selected_import_data['local_import_widget_file'] ) ) {
			if ( file_exists( $selected_import_data['local_import_widget_file'] ) ) {
				$import_files['widgets'] = $selected_import_data['local_import_widget_file'];
			}
		}

		// Get customizer import file as well. If defined!
		if ( ! empty( $selected_import_data['import_customizer_file_url'] ) ) {
			// Setup filename path to save the customizer content.
			$customizer_filename = 'options-' . $base_file_name . '.dat';

			// Retrieve the content import file.
			$import_files['options'] = $downloader->fetch_existing_file( $customizer_filename );

			// Download the file, if it's missing.
			if ( empty( $import_files['options'] ) ) {
				$import_files['options'] = $downloader->download_file( $selected_import_data['import_customizer_file_url'], $customizer_filename );
			}

			// Reset the variable, if there was an error.
			if ( is_wp_error( $import_files['options'] ) ) {
				$import_files['options'] = '';
			}
		} elseif ( ! empty( $selected_import_data['local_import_customizer_file'] ) ) {
			if ( file_exists( $selected_import_data['local_import_customizer_file'] ) ) {
				$import_files['options'] = $selected_import_data['local_import_customizer_file'];
			}
		}

		// Get revolution slider import file as well. If defined!
		if ( ! empty( $selected_import_data['import_rev_slider_file_url'] ) ) {
			// Setup filename path to save the customizer content.
			$rev_slider_filename = 'slider-' . $base_file_name . '.zip';

			// Retrieve the content import file.
			$import_files['sliders'] = $downloader->fetch_existing_file( $rev_slider_filename );

			// Download the file, if it's missing.
			if ( empty( $import_files['sliders'] ) ) {
				$import_files['sliders'] = $downloader->download_file( $selected_import_data['import_rev_slider_file_url'], $rev_slider_filename );
			}

			// Reset the variable, if there was an error.
			if ( is_wp_error( $import_files['sliders'] ) ) {
				$import_files['sliders'] = '';
			}
		} elseif ( ! empty( $selected_import_data['local_import_rev_slider_file'] ) ) {
			if ( file_exists( $selected_import_data['local_import_rev_slider_file'] ) ) {
				$import_files['sliders'] = $selected_import_data['local_import_rev_slider_file'];
			}
		}

		// Get redux import file as well. If defined!
		if ( ! empty( $selected_import_data['import_redux'] ) ) {
			$redux_items = array();

			// Setup filename paths to save the Redux content.
			foreach ( $selected_import_data['import_redux'] as $index => $redux_item ) {
				$redux_filename = 'redux-' . $index . '-' . $base_file_name . '.json';

				// Retrieve the content import file.
				$file_path = $downloader->fetch_existing_file( $redux_filename );

				// Download the file, if it's missing.
				if ( empty( $file_path ) ) {
					$file_path = $downloader->download_file( $redux_item['file_url'], $redux_filename );
				}

				// Reset the variable, if there was an error.
				if ( is_wp_error( $file_path ) ) {
					$file_path = '';
				}

				$redux_items[] = array(
					'option_name' => $redux_item['option_name'],
					'file_path'   => $file_path,
				);
			}

			// Download the Redux import file.
			$import_files['redux'] = $redux_items;
		} elseif ( ! empty( $selected_import_data['local_import_redux'] ) ) {
			$redux_items = array();

			// Setup filename paths to save the Redux content.
			foreach ( $selected_import_data['local_import_redux'] as $redux_item ) {
				if ( file_exists( $redux_item['file_path'] ) ) {
					$redux_items[] = $redux_item;
				}
			}

			// Download the Redux import file.
			$import_files['redux'] = $redux_items;
		}

		return $import_files;
	}

	/**
	 * AJAX callback for the 'conjure_update_selected_import_data_info' action.
	 */
	public function update_selected_import_data_info() {
		// Wrap in try-catch to prevent errors from breaking the AJAX response.
		try {
			// Catch any output from plugins that might interfere with JSON response.
			ob_start();

			if ( ! check_ajax_referer( 'conjure_nonce', 'wpnonce', false ) ) {
				ob_end_clean();
				wp_send_json_error( array( 'message' => esc_html__( 'Security check failed.', 'conjurewp' ) ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				ob_end_clean();
				wp_send_json_error( array( 'message' => esc_html__( 'You do not have permission to perform this action.', 'conjurewp' ) ) );
			}

			$selected_index = ! isset( $_POST['selected_index'] ) ? false : intval( $_POST['selected_index'] );

			if ( false === $selected_index ) {
				ob_end_clean();
				wp_send_json_error( array( 'message' => esc_html__( 'Invalid demo selection.', 'conjurewp' ) ) );
			}

			// Store the selected demo index for demo-specific plugin installation.
			set_transient( 'conjure_selected_demo_index', $selected_index, HOUR_IN_SECONDS );

			$this->logger->info( 'Demo selected: ' . $selected_index );

			$import_info = $this->get_import_data_info( $selected_index );
			
			// Check if we got valid import info.
			if ( false === $import_info ) {
				ob_end_clean();
				$this->logger->error( 'Failed to get import data info for demo: ' . $selected_index );
				wp_send_json_error( array( 'message' => esc_html__( 'Failed to load demo configuration.', 'conjurewp' ) ) );
			}

			$import_info_html = $this->get_import_steps_html( $import_info );

			// Get demo-specific plugins if available.
			$demo_plugins = array();
			if ( $this->demo_plugin_manager ) {
				$demo_plugins = $this->demo_plugin_manager->get_demo_plugins_with_status( $selected_index, $this->import_files );
			}

			// Clean any buffered output before sending JSON.
			$buffered_output = ob_get_clean();
			if ( ! empty( $buffered_output ) ) {
				$this->logger->warning( 
					'Unexpected output during demo selection update',
					array( 'output' => $buffered_output )
				);
			}

			wp_send_json_success( array(
				'import_info_html' => $import_info_html,
				'demo_plugins'     => $demo_plugins,
				'has_plugins'      => ! empty( $demo_plugins['all'] ),
			) );

		} catch ( \Exception $e ) {
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}
			
			$this->logger->error(
				'Exception in update_selected_import_data_info',
				array(
					'message' => $e->getMessage(),
					'trace' => $e->getTraceAsString(),
				)
			);

			wp_send_json_error( array(
				'message' => sprintf(
					/* translators: %s: error message */
					esc_html__( 'Error loading demo: %s', 'conjurewp' ),
					$e->getMessage()
				),
			) );

		} catch ( \Error $e ) {
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}
			
			$this->logger->error(
				'Fatal error in update_selected_import_data_info',
				array(
					'message' => $e->getMessage(),
					'trace' => $e->getTraceAsString(),
				)
			);

			wp_send_json_error( array(
				'message' => sprintf(
					/* translators: %s: error message */
					esc_html__( 'Fatal error loading demo: %s', 'conjurewp' ),
					$e->getMessage()
				),
			) );
		}
	}

	/**
	 * Get the import steps HTML output.
	 *
	 * @param array $import_info The import info to prepare the HTML for.
	 *
	 * @return string
	 */
	public function get_import_steps_html( $import_info ) {
		// Validate input.
		if ( ! is_array( $import_info ) || empty( $import_info ) ) {
			$this->logger->warning( 'get_import_steps_html called with invalid import_info' );
			return '<li class="conjure__drawer--import-content__list-item">No import options available.</li>';
		}

		$uploaded_files  = get_transient( 'conjure_uploaded_files' );
		$upload_options  = $this->get_upload_options( is_array( $uploaded_files ) ? $uploaded_files : array() );

		ob_start();
		?>
			<?php foreach ( $import_info as $slug => $available ) : ?>
				<?php
				if ( ! $available ) {
					continue;
				}

				$has_upload_section = isset( $upload_options[ $slug ] );
				$has_file           = $has_upload_section && ! empty( $uploaded_files[ $slug ] );
				$file_info          = $has_file ? $uploaded_files[ $slug ] : null;
				$list_item_classes  = array(
					'conjure__drawer--import-content__list-item',
					'status',
					'status--Pending',
				);

				if ( $has_upload_section ) {
					$list_item_classes[] = 'conjure__drawer--upload__item';
					$list_item_classes[] = 'has-inline-upload';
				}
				?>

				<li class="<?php echo esc_attr( implode( ' ', $list_item_classes ) ); ?>" data-content="<?php echo esc_attr( $slug ); ?>" data-upload-type="<?php echo esc_attr( $slug ); ?>">
					<div class="conjure__upload-zone-wrapper">
						<input type="checkbox" name="default_content[<?php echo esc_attr( $slug ); ?>]" class="checkbox checkbox-<?php echo esc_attr( $slug ); ?> js-conjure-upload-checkbox" id="default_content_<?php echo esc_attr( $slug ); ?>" value="1">
						<label for="default_content_<?php echo esc_attr( $slug ); ?>">
							<i></i><span><?php echo esc_html( ucfirst( str_replace( '_', ' ', $slug ) ) ); ?></span>
						</label>

						<?php if ( $has_upload_section ) : ?>
							<?php echo $this->render_upload_zone_markup( $slug, $upload_options[ $slug ], $has_file, $file_info ); ?>
						<?php endif; ?>
					</div>
				</li>

			<?php endforeach; ?>
		<?php

		return ob_get_clean();
	}


	/**
	 * AJAX call for cleanup after the importing steps are done -> import finished.
	 */
	public function import_finished() {
		// Wrap in try-catch to prevent errors from breaking the AJAX response.
		try {
			// Catch any output from plugins that might interfere with JSON response.
			ob_start();

			if ( ! check_ajax_referer( 'conjure_nonce', 'wpnonce', false ) ) {
				ob_end_clean();
				wp_send_json_error( array( 'message' => esc_html__( 'Security check failed.', 'conjurewp' ) ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				ob_end_clean();
				wp_send_json_error( array( 'message' => esc_html__( 'You do not have permission to perform this action.', 'conjurewp' ) ) );
			}

			delete_transient( 'conjure_import_file_base_name' );
			$this->cleanup_uploaded_files();

			// Mark content import step as completed.
			$this->mark_step_completed( 'content' );

			// Clean any buffered output before sending JSON.
			$buffered_output = ob_get_clean();
			if ( ! empty( $buffered_output ) ) {
				$this->logger->warning( 
					'Unexpected output during import finish cleanup',
					array( 'output' => $buffered_output )
				);
			}

			wp_send_json_success();

		} catch ( \Exception $e ) {
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}
			
			$this->logger->error(
				'Exception in import_finished',
				array(
					'message' => $e->getMessage(),
					'trace' => $e->getTraceAsString(),
				)
			);

			wp_send_json_error( array(
				'message' => sprintf(
					/* translators: %s: error message */
					esc_html__( 'Error finishing import: %s', 'conjurewp' ),
					$e->getMessage()
				),
			) );

		} catch ( \Error $e ) {
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}
			
			$this->logger->error(
				'Fatal error in import_finished',
				array(
					'message' => $e->getMessage(),
					'trace' => $e->getTraceAsString(),
				)
			);

			wp_send_json_error( array(
				'message' => sprintf(
					/* translators: %s: error message */
					esc_html__( 'Fatal error finishing import: %s', 'conjurewp' ),
					$e->getMessage()
				),
			) );
		}
	}

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
					__( 'Failed to create upload directory: %s', 'conjurewp' ),
					$conjure_dir
				);
				
				$this->logger->error( $error_message );
				
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					trigger_error( $error_message, E_USER_ERROR );
				}
				
				return false;
			}
			
			// Add .htaccess for security.
			$htaccess_content = 'deny from all';
			$htaccess_file = $conjure_dir . '.htaccess';
			$htaccess_result = file_put_contents( $htaccess_file, $htaccess_content );
			
			if ( false === $htaccess_result ) {
				$error_message = sprintf(
					__( 'Failed to create .htaccess file in upload directory: %s', 'conjurewp' ),
					$conjure_dir
				);
				
				$this->logger->error( $error_message );
			}
			
			// Add index.php to prevent directory listing.
			$index_file = $conjure_dir . 'index.php';
			$index_result = file_put_contents( $index_file, '<?php // Silence is golden.' );
			
			if ( false === $index_result ) {
				$error_message = sprintf(
					__( 'Failed to create index.php file in upload directory: %s', 'conjurewp' ),
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
	public function _ajax_upload_file() {
		if ( ! check_ajax_referer( 'conjure_nonce', 'wpnonce', false ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Security check failed.', 'conjurewp' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'You do not have permission to upload files.', 'conjurewp' ) ) );
		}

		if ( empty( $_FILES['file'] ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'No file uploaded.', 'conjurewp' ) ) );
		}

		$file = $_FILES['file'];
		$file_type = isset( $_POST['file_type'] ) ? sanitize_key( $_POST['file_type'] ) : '';

		// Validate file type.
		$allowed_types = array(
			'content' => array( 'xml' ),
			'widgets' => array( 'json', 'wie' ),
			'options' => array( 'dat', 'json' ),
			'redux' => array( 'json' ),
			'sliders' => array( 'zip' ),
			'images' => array( 'xml' ),
			'menus' => array( 'json' ),
		);

		if ( ! isset( $allowed_types[ $file_type ] ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid file type specified.', 'conjurewp' ) ) );
		}

		$file_extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

		if ( ! in_array( $file_extension, $allowed_types[ $file_type ], true ) ) {
			wp_send_json_error( array(
				'message' => sprintf(
					esc_html__( 'Invalid file extension. Allowed: %s', 'conjurewp' ),
					implode( ', ', $allowed_types[ $file_type ] )
				),
			) );
		}

		// Check for upload errors.
		if ( $file['error'] !== UPLOAD_ERR_OK ) {
			wp_send_json_error( array( 'message' => esc_html__( 'File upload error.', 'conjurewp' ) ) );
		}

		// Validate file size (max 50MB).
		$max_size = 50 * 1024 * 1024;
		if ( $file['size'] > $max_size ) {
			wp_send_json_error( array( 'message' => esc_html__( 'File is too large. Maximum size is 50MB.', 'conjurewp' ) ) );
		}

		// Move file to upload directory.
		$upload_dir = $this->get_upload_dir();
		
		if ( false === $upload_dir ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Failed to create upload directory. Please check file permissions.', 'conjurewp' ) ) );
		}
		
		$filename = $file_type . '-' . time() . '.' . $file_extension;
		$destination = $upload_dir . $filename;

		if ( ! move_uploaded_file( $file['tmp_name'], $destination ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Failed to save uploaded file.', 'conjurewp' ) ) );
		}

		// Store file info in transient.
		$uploaded_files = get_transient( 'conjure_uploaded_files' );
		if ( ! $uploaded_files ) {
			$uploaded_files = array();
		}

		$uploaded_files[ $file_type ] = array(
			'path' => $destination,
			'name' => sanitize_file_name( $file['name'] ),
			'size' => $file['size'],
			'time' => time(),
		);

		set_transient( 'conjure_uploaded_files', $uploaded_files, HOUR_IN_SECONDS );

		$this->logger->info(
			__( 'File uploaded successfully', 'conjurewp' ),
			array(
				'type' => $file_type,
				'name' => $file['name'],
				'size' => size_format( $file['size'] ),
			)
		);

		wp_send_json_success( array(
			'message' => esc_html__( 'File uploaded successfully.', 'conjurewp' ),
			'filename' => $file['name'],
			'size' => size_format( $file['size'] ),
		) );
	}

	/**
	 * AJAX handler for uploading from WordPress media library.
	 */
	public function _ajax_upload_from_media() {
		if ( ! check_ajax_referer( 'conjure_nonce', 'wpnonce', false ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Security check failed.', 'conjurewp' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'You do not have permission to upload files.', 'conjurewp' ) ) );
		}

		if ( empty( $_POST['attachment_id'] ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'No file selected.', 'conjurewp' ) ) );
		}

		$attachment_id = intval( $_POST['attachment_id'] );
		$file_type = isset( $_POST['file_type'] ) ? sanitize_key( $_POST['file_type'] ) : '';

		// Validate file type.
		$allowed_types = array( 'content', 'widgets', 'options', 'redux', 'sliders', 'images', 'menus' );

		if ( ! in_array( $file_type, $allowed_types, true ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid file type specified.', 'conjurewp' ) ) );
		}

		// Get attachment file path.
		$file_path = get_attached_file( $attachment_id );

		if ( ! $file_path || ! file_exists( $file_path ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'File not found in media library.', 'conjurewp' ) ) );
		}

		// Validate file extension.
		$file_extension = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );
		$allowed_extensions = array(
			'content' => array( 'xml' ),
			'widgets' => array( 'json', 'wie' ),
			'options' => array( 'dat', 'json' ),
			'redux' => array( 'json' ),
			'sliders' => array( 'zip' ),
			'images' => array( 'xml' ),
			'menus' => array( 'json' ),
		);

		if ( ! isset( $allowed_extensions[ $file_type ] ) || ! in_array( $file_extension, $allowed_extensions[ $file_type ], true ) ) {
			wp_send_json_error( array(
				'message' => sprintf(
					esc_html__( 'Invalid file extension. Allowed: %s', 'conjurewp' ),
					implode( ', ', $allowed_extensions[ $file_type ] )
				),
			) );
		}

		// Copy file to upload directory.
		$upload_dir = $this->get_upload_dir();
		
		if ( false === $upload_dir ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Failed to create upload directory. Please check file permissions.', 'conjurewp' ) ) );
		}
		
		$filename = $file_type . '-' . time() . '.' . $file_extension;
		$destination = $upload_dir . $filename;

		if ( ! copy( $file_path, $destination ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Failed to copy file.', 'conjurewp' ) ) );
		}

		// Get file info.
		$file_size = filesize( $destination );
		$file_name = basename( get_attached_file( $attachment_id ) );

		// Store file info in transient.
		$uploaded_files = get_transient( 'conjure_uploaded_files' );
		if ( ! $uploaded_files ) {
			$uploaded_files = array();
		}

		$uploaded_files[ $file_type ] = array(
			'path' => $destination,
			'name' => sanitize_file_name( $file_name ),
			'size' => $file_size,
			'time' => time(),
		);

		set_transient( 'conjure_uploaded_files', $uploaded_files, HOUR_IN_SECONDS );

		$this->logger->info(
			__( 'File copied from media library successfully', 'conjurewp' ),
			array(
				'type' => $file_type,
				'name' => $file_name,
				'size' => size_format( $file_size ),
			)
		);

		wp_send_json_success( array(
			'message' => esc_html__( 'File uploaded successfully.', 'conjurewp' ),
			'filename' => $file_name,
			'size' => size_format( $file_size ),
		) );
	}

	/**
	 * AJAX handler for deleting uploaded files.
	 */
	public function _ajax_delete_uploaded_file() {
		if ( ! check_ajax_referer( 'conjure_nonce', 'wpnonce', false ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Security check failed.', 'conjurewp' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'You do not have permission to delete files.', 'conjurewp' ) ) );
		}

		$file_type = isset( $_POST['file_type'] ) ? sanitize_key( $_POST['file_type'] ) : '';

		if ( empty( $file_type ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'No file type specified.', 'conjurewp' ) ) );
		}

		$uploaded_files = get_transient( 'conjure_uploaded_files' );

		if ( ! empty( $uploaded_files[ $file_type ] ) ) {
			$file_path = $uploaded_files[ $file_type ]['path'];

			if ( file_exists( $file_path ) ) {
				wp_delete_file( $file_path );
			}

			unset( $uploaded_files[ $file_type ] );
			set_transient( 'conjure_uploaded_files', $uploaded_files, HOUR_IN_SECONDS );

			$this->logger->info( __( 'Uploaded file deleted', 'conjurewp' ), array( 'type' => $file_type ) );
		}

		wp_send_json_success( array( 'message' => esc_html__( 'File deleted successfully.', 'conjurewp' ) ) );
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

			$this->logger->info( __( 'All uploaded files cleaned up', 'conjurewp' ) );
		}
	}

	/**
	 * Check if manual upload mode is enabled (no pre-registered import files).
	 *
	 * @return bool
	 */
	private function is_manual_upload_mode() {
		return empty( $this->import_files );
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
	 * Get the manual upload zones HTML.
	 *
	 * @return string
	 */
	private function get_manual_upload_html() {
		$uploaded_files = get_transient( 'conjure_uploaded_files' );

		$upload_options = $this->get_upload_options( is_array( $uploaded_files ) ? $uploaded_files : array() );

		ob_start();
		?>

		<?php foreach ( $upload_options as $type => $option ) : ?>
			<?php
			$has_file = ! empty( $uploaded_files[ $type ] );
			$file_info = $has_file ? $uploaded_files[ $type ] : null;
			?>

			<li class="conjure__drawer--upload__item" data-upload-type="<?php echo esc_attr( $type ); ?>">
				<div class="conjure__upload-zone-wrapper">
					
						<input 
							type="checkbox" 
							name="default_content[<?php echo esc_attr( $type ); ?>]" 
							class="checkbox checkbox-<?php echo esc_attr( $type ); ?> js-conjure-upload-checkbox" 
							id="default_content_<?php echo esc_attr( $type ); ?>" 
							value="1"
							data-manual-upload="1"
							<?php checked( $has_file ); ?>
							<?php disabled( ! $has_file ); ?>
						>
					
					<label for="default_content_<?php echo esc_attr( $type ); ?>" class="conjure__upload-label">
						<i></i>
						<span class="conjure__upload-label-content">
							<span class="conjure__upload-title">
								<?php echo esc_html( $option['title'] ); ?>
								<?php if ( ! empty( $option['tooltip'] ) ) : ?>
									<span class="hint--top hint--rounded" aria-label="<?php echo esc_attr( $option['tooltip'] ); ?>">
										<?php echo wp_kses( $this->svg( array( 'icon' => 'help' ) ), $this->svg_allowed_html() ); ?>
									</span>
								<?php endif; ?>
							</span>
							<span class="conjure__upload-description"><?php echo esc_html( $option['description'] ); ?></span>
						</span>
					</label>

					<?php echo $this->render_upload_zone_markup( $type, $option, $has_file, $file_info ); ?>

				</div>
			</li>

		<?php endforeach; ?>

		<?php
		return ob_get_clean();
	}

	/**
	 * Retrieve the upload configuration for each content type.
	 *
	 * @param array $uploaded_files Files stored in transient storage.
	 * @return array
	 */
	private function get_upload_options( $uploaded_files ) {
		$upload_options = array(
			'content' => array(
				'title'       => esc_html__( 'Content', 'conjurewp' ),
				'description' => esc_html__( 'Posts, pages, and site structure', 'conjurewp' ),
				'accept'      => '.xml',
			),
			'images' => array(
				'title'       => esc_html__( 'Images & Media', 'conjurewp' ),
				'description' => esc_html__( 'Import media library attachments', 'conjurewp' ),
				'tooltip'     => esc_html__( 'Uncheck if replacing images or on shared hosting to speed up import', 'conjurewp' ),
				'accept'      => '.xml',
			),
			'widgets' => array(
				'title'       => esc_html__( 'Widgets', 'conjurewp' ),
				'description' => esc_html__( 'Sidebar widgets and widget areas', 'conjurewp' ),
				'accept'      => '.json,.wie',
			),
			'options' => array(
				'title'       => esc_html__( 'Theme Options', 'conjurewp' ),
				'description' => esc_html__( 'Customizer settings and theme options', 'conjurewp' ),
				'accept'      => '.dat,.json',
			),
			'sliders' => array(
				'title'       => esc_html__( 'Sliders', 'conjurewp' ),
				'description' => esc_html__( 'Revolution Slider packages (.zip)', 'conjurewp' ),
				'accept'      => '.zip',
			),
			'redux' => array(
				'title'       => esc_html__( 'Redux Options', 'conjurewp' ),
				'description' => esc_html__( 'Redux framework settings', 'conjurewp' ),
				'accept'      => '.json',
			),
			'menus' => array(
				'title'       => esc_html__( 'Menus', 'conjurewp' ),
				'description' => esc_html__( 'Navigation menu assignments', 'conjurewp' ),
				'accept'      => '.json',
			),
		);

		return apply_filters( 'conjure_manual_upload_sections', $upload_options, $uploaded_files );
	}

	/**
	 * Render the reusable upload zone markup.
	 *
	 * @param string     $type      Upload type key.
	 * @param array      $option    Upload option configuration.
	 * @param bool       $has_file  Whether a file is already stored.
	 * @param array|null $file_info Information about the stored file.
	 * @return string
	 */
	private function render_upload_zone_markup( $type, $option, $has_file, $file_info ) {
		$file_name = $has_file && ! empty( $file_info['name'] ) ? $file_info['name'] : '';
		$file_size = $has_file && ! empty( $file_info['size'] ) ? size_format( $file_info['size'] ) : '';

		ob_start();
		?>
		<div class="conjure__upload-zone <?php echo $has_file ? 'has-file' : ''; ?>"
			data-type="<?php echo esc_attr( $type ); ?>"
			data-accept="<?php echo esc_attr( $option['accept'] ); ?>">

			<div class="conjure__upload-prompt" <?php echo $has_file ? 'style="display:none;"' : ''; ?>>
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
					<polyline points="17 8 12 3 7 8"></polyline>
					<line x1="12" y1="3" x2="12" y2="15"></line>
				</svg>
				<p class="conjure__upload-text">
					<strong><?php esc_html_e( 'Click to select file', 'conjurewp' ); ?></strong>
					<span class="conjure__upload-file-type"><?php echo esc_html( $option['accept'] ); ?></span>
				</p>
			</div>

			<div class="conjure__upload-success" style="display: <?php echo $has_file ? 'flex' : 'none'; ?>;">
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<polyline points="20 6 9 17 4 12"></polyline>
				</svg>
				<div class="conjure__file-info">
					<strong class="conjure__file-name"><?php echo esc_html( $file_name ); ?></strong>
					<span class="conjure__file-size"><?php echo esc_html( $file_size ); ?></span>
				</div>
				<button type="button" class="conjure__remove-file" data-type="<?php echo esc_attr( $type ); ?>" title="<?php esc_attr_e( 'Remove file', 'conjurewp' ); ?>" <?php echo $has_file ? '' : 'style="display:none;"'; ?>>
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<line x1="18" y1="6" x2="6" y2="18"></line>
						<line x1="6" y1="6" x2="18" y2="18"></line>
					</svg>
				</button>
			</div>

			<div class="conjure__upload-progress" style="display:none;">
				<div class="conjure__progress-bar-small">
					<div class="conjure__progress-fill"></div>
				</div>
				<span class="conjure__upload-status"><?php esc_html_e( 'Uploading...', 'conjurewp' ); ?></span>
			</div>
		</div>
		<?php
		return ob_get_clean();
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
			sprintf( __( 'Step "%s" marked as completed', 'conjurewp' ), $step_key ),
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
				sprintf( __( 'Step "%s" reset for rerunning', 'conjurewp' ), $step_key ),
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

		$this->logger->info( __( 'All Conjure steps have been reset', 'conjurewp' ) );
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
				'title' => '<span class="ab-icon dashicons-update"></span><span class="ab-label">' . esc_html__( 'Conjure WP', 'conjurewp' ) . '</span>',
				'href'  => admin_url( 'themes.php?page=' . $this->conjure_url ),
				'meta'  => array(
					'title' => esc_html__( 'Rerun Conjure WP steps', 'conjurewp' ),
				),
			)
		);

		// Get step completion states.
		$step_states = get_option( 'conjure_' . $this->slug . '_step_completion', array() );

		// Define available steps for rerunning.
		$rerun_steps = array(
			'child'   => esc_html__( 'Child Theme', 'conjurewp' ),
			'license' => esc_html__( 'License Activation', 'conjurewp' ),
			'plugins' => esc_html__( 'Plugins', 'conjurewp' ),
			'content' => esc_html__( 'Content Import', 'conjurewp' ),
		);

		$rerun_steps = apply_filters( $this->theme->template . '_conjure_rerun_steps', $rerun_steps, $this->steps );

		// Add individual step reset options.
		foreach ( $rerun_steps as $step_key => $step_label ) {
			// Skip license step if not enabled.
			if ( 'license' === $step_key && ! $this->license_step_enabled ) {
				continue;
			}

		$is_completed = isset( $step_states[ $step_key ] ) && $step_states[ $step_key ];
			$status_icon = $is_completed ? '✓' : '○';

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
							esc_html__( 'Reset and rerun: %s', 'conjurewp' ),
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
				'title'  => '↻ ' . esc_html__( 'Reset All Steps', 'conjurewp' ),
				'href'   => wp_nonce_url(
					admin_url( '?conjure_reset_step=all' ),
					'conjure_reset_step_all',
					'_conjure_nonce'
				),
				'meta'   => array(
					'title' => esc_html__( 'Reset all steps and rerun complete onboarding', 'conjurewp' ),
				),
			)
		);

		// Add "Open Wizard" option.
		$wp_admin_bar->add_node(
			array(
				'parent' => 'conjure-rerun',
				'id'     => 'conjure-open-wizard',
				'title'  => '→ ' . esc_html__( 'Open Wizard', 'conjurewp' ),
				'href'   => admin_url( 'themes.php?page=' . $this->conjure_url ),
				'meta'   => array(
					'title' => esc_html__( 'Open Conjure WP setup wizard', 'conjurewp' ),
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

		$step = sanitize_key( $_GET['conjure_reset_step'] );

		// Verify nonce.
		if ( ! wp_verify_nonce( $_GET['_conjure_nonce'], 'conjure_reset_step_' . $step ) ) {
			wp_die( esc_html__( 'Security check failed.', 'conjurewp' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'conjurewp' ) );
		}

		// Handle reset.
		if ( 'all' === $step ) {
			$this->reset_all_steps();
			$redirect_url = admin_url( 'themes.php?page=' . $this->conjure_url );
			$message = __( 'All steps have been reset. You can now rerun the complete onboarding.', 'conjurewp' );
		} else {
			$this->reset_step( $step );
			$redirect_url = admin_url( 'themes.php?page=' . $this->conjure_url . '&step=' . $step );
			$message = sprintf(
				/* translators: %s: step name */
				__( 'Step "%s" has been reset. You can now rerun this step.', 'conjurewp' ),
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
				<p><strong><?php echo esc_html__( 'Conjure WP:', 'conjurewp' ); ?></strong> <?php echo esc_html( $message ); ?></p>
			</div>
			<?php
		}
	}
}
