<?php
/**
 * ConjureWP Plugin Installer
 *
 * A lightweight plugin installer that replaces TGMPA dependency.
 * Handles installation and activation of plugins from WordPress.org or custom sources.
 *
 * @package   ConjureWP
 * @version   2.0.0
 * @link      https://conjurewp.com/
 * @author    Jake Henshall
 * @copyright Copyright (c) 2024, ConjureWP
 * @license   GPL-3.0-or-later
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load WordPress upgrader classes if not already loaded.
if ( ! class_exists( 'Plugin_Upgrader' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
}
if ( ! class_exists( 'Plugin_Installer_Skin' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-plugin-installer-skin.php';
}

/**
 * Conjure_Plugin_Installer class.
 *
 * Handles plugin installation, activation, and status checking.
 */
class Conjure_Plugin_Installer {

	/**
	 * Logger instance.
	 *
	 * @var Conjure_Logger
	 */
	private $logger;

	/**
	 * Registered plugins.
	 *
	 * @var array
	 */
	private $plugins = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->logger = Conjure_Logger::get_instance();

		// Load required WordPress files.
		$this->load_dependencies();
	}

	/**
	 * Load required WordPress dependencies.
	 */
	private function load_dependencies() {
		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}

		if ( ! class_exists( 'Plugin_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		if ( ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}

	/**
	 * Register plugins that can be installed.
	 *
	 * @param array $plugins Array of plugin configurations.
	 */
	public function register_plugins( $plugins ) {
		foreach ( $plugins as $slug => $plugin ) {
			// Normalize plugin data.
			if ( is_string( $plugin ) ) {
				// Simple format: array( 'plugin-slug' ).
				$slug   = $plugin;
				$plugin = array( 'slug' => $slug );
			} elseif ( is_array( $plugin ) && ! isset( $plugin['slug'] ) ) {
				// Format with slug as key: array( 'slug' => array() ).
				$plugin['slug'] = $slug;
			}

			// Set defaults.
			$plugin = wp_parse_args(
				$plugin,
				array(
					'name'     => ucwords( str_replace( array( '-', '_' ), ' ', $slug ) ),
					'slug'     => $slug,
					'source'   => '', // URL or file path for custom plugins.
					'required' => false,
					'version'  => '',
				)
			);

			$this->plugins[ $slug ] = $plugin;
		}

		$this->logger->info( 'Registered ' . count( $plugins ) . ' plugins' );
	}

	/**
	 * Get all registered plugins.
	 *
	 * @return array
	 */
	public function get_plugins() {
		return $this->plugins;
	}

	/**
	 * Get a specific plugin configuration.
	 *
	 * @param string $slug Plugin slug.
	 * @return array|false Plugin data or false if not found.
	 */
	public function get_plugin( $slug ) {
		return isset( $this->plugins[ $slug ] ) ? $this->plugins[ $slug ] : false;
	}

	/**
	 * Check if a plugin is installed.
	 *
	 * @param string $slug Plugin slug.
	 * @return bool
	 */
	public function is_plugin_installed( $slug ) {
		$plugin_file = $this->get_plugin_file( $slug );
		return ! empty( $plugin_file );
	}

	/**
	 * Check if a plugin is active.
	 *
	 * @param string $slug Plugin slug.
	 * @return bool
	 */
	public function is_plugin_active( $slug ) {
		$plugin_file = $this->get_plugin_file( $slug );

		if ( empty( $plugin_file ) ) {
			$this->logger->info( "is_plugin_active('{$slug}'): plugin file not found" );
			return false;
		}

		$is_active = is_plugin_active( $plugin_file );
		$this->logger->info( "is_plugin_active('{$slug}'): file='{$plugin_file}', active=" . ( $is_active ? 'yes' : 'no' ) );

		return $is_active;
	}

	/**
	 * Get the plugin file path (relative to plugins directory).
	 *
	 * @param string $slug Plugin slug.
	 * @return string|false Plugin file or false if not found.
	 */
	public function get_plugin_file( $slug ) {
		$all_plugins = get_plugins();

		// Check common plugin file patterns.
		$possible_files = array(
			$slug . '/' . $slug . '.php',
			$slug . '/index.php',
			$slug . '.php',
		);

		foreach ( $possible_files as $file ) {
			if ( isset( $all_plugins[ $file ] ) ) {
				return $file;
			}
		}

		// Search all plugins for matching slug.
		foreach ( $all_plugins as $file => $plugin_data ) {
			if ( strpos( $file, $slug . '/' ) === 0 ) {
				return $file;
			}
		}

		return false;
	}

	/**
	 * Install a plugin from WordPress.org repository.
	 *
	 * @param string $slug Plugin slug.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function install_plugin( $slug ) {
		$this->logger->info( "Installing plugin: {$slug}" );

		// Check if already installed.
		if ( $this->is_plugin_installed( $slug ) ) {
			$this->logger->info( "Plugin '{$slug}' is already installed" );
			return true;
		}

		// Try to get registered plugin data (if pre-registered).
		$plugin = $this->get_plugin( $slug );

		// If plugin has custom source, use it.
		if ( ! empty( $plugin['source'] ) ) {
			return $this->install_from_source( $slug, $plugin['source'] );
		}

		// Otherwise, install from WordPress.org (works with or without registration).
		return $this->install_from_repo( $slug );
	}

	/**
	 * Install plugin from WordPress.org repository.
	 *
	 * @param string $slug Plugin slug.
	 * @return bool|WP_Error
	 */
	private function install_from_repo( $slug ) {
		// Get plugin info from WordPress.org API.
		$api = plugins_api(
			'plugin_information',
			array(
				'slug'   => $slug,
				'fields' => array(
					'sections' => false,
					'tags'     => false,
				),
			)
		);

		if ( is_wp_error( $api ) ) {
			$this->logger->error( "Failed to get plugin info for '{$slug}': " . $api->get_error_message() );
			return $api;
		}

		// Use Plugin_Upgrader to install.
		$upgrader = new Plugin_Upgrader( new Conjure_Plugin_Installer_Skin() );
		$result   = $upgrader->install( $api->download_link );

		if ( is_wp_error( $result ) ) {
			$this->logger->error( "Failed to install plugin '{$slug}': " . $result->get_error_message() );
			return $result;
		}

		if ( $result === false ) {
			$error = new WP_Error( 'installation_failed', "Plugin installation failed for '{$slug}'" );
			$this->logger->error( $error->get_error_message() );
			return $error;
		}

		$this->logger->info( "Successfully installed plugin: {$slug}" );

		// Refresh plugin cache.
		wp_clean_plugins_cache();

		return true;
	}

	/**
	 * Install plugin from custom source (ZIP file or URL).
	 *
	 * @param string $slug   Plugin slug.
	 * @param string $source Path or URL to plugin ZIP.
	 * @return bool|WP_Error
	 */
	private function install_from_source( $slug, $source ) {
		$this->logger->info( "Installing plugin '{$slug}' from custom source: {$source}" );

		// Check if source is a URL or local file.
		$is_url = filter_var( $source, FILTER_VALIDATE_URL );

		if ( ! $is_url && ! file_exists( $source ) ) {
			$error = new WP_Error( 'source_not_found', "Plugin source not found: {$source}" );
			$this->logger->error( $error->get_error_message() );
			return $error;
		}

		// Use Plugin_Upgrader to install from ZIP.
		$upgrader = new Plugin_Upgrader( new Conjure_Plugin_Installer_Skin() );
		$result   = $upgrader->install( $source );

		if ( is_wp_error( $result ) ) {
			$this->logger->error( "Failed to install plugin '{$slug}' from source: " . $result->get_error_message() );
			return $result;
		}

		if ( $result === false ) {
			$error = new WP_Error( 'installation_failed', "Plugin installation from source failed for '{$slug}'" );
			$this->logger->error( $error->get_error_message() );
			return $error;
		}

		$this->logger->info( "Successfully installed plugin '{$slug}' from custom source" );

		// Refresh plugin cache.
		wp_clean_plugins_cache();

		return true;
	}

	/**
	 * Activate a plugin.
	 *
	 * @param string $slug Plugin slug.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function activate_plugin( $slug ) {
		$this->logger->info( "Activating plugin: {$slug}" );

		$plugin_file = $this->get_plugin_file( $slug );

		if ( empty( $plugin_file ) ) {
			$error = new WP_Error( 'plugin_not_found', "Plugin '{$slug}' is not installed." );
			$this->logger->error( $error->get_error_message() );
			return $error;
		}

		// Check if already active.
		if ( $this->is_plugin_active( $slug ) ) {
			$this->logger->info( "Plugin '{$slug}' is already active" );
			return true;
		}

		// Wrap activation in try-catch to prevent fatal errors from breaking the import process.
		try {
			// Activate the plugin.
			// Use output buffering to catch any errors or output during activation.
			ob_start();
			$result = activate_plugin( $plugin_file, '', false, true );
			$activation_output = ob_get_clean();

			if ( ! empty( $activation_output ) ) {
				$this->logger->warning( "Plugin '{$slug}' produced output during activation: " . $activation_output );
			}

			if ( is_wp_error( $result ) ) {
				$this->logger->error( "Failed to activate plugin '{$slug}': " . $result->get_error_message() );
				return $result;
			}

			// Verify the plugin is actually active after activation attempt.
			if ( ! $this->is_plugin_active( $slug ) ) {
				$error = new WP_Error( 'activation_verification_failed', "Plugin '{$slug}' activation completed but plugin is not active." );
				$this->logger->error( $error->get_error_message() );
				return $error;
			}

			$this->logger->info( "Successfully activated plugin: {$slug}" );

			return true;
		} catch ( \Exception $e ) {
			$error_message = "Exception during plugin activation for '{$slug}': " . $e->getMessage();
			$this->logger->error( $error_message );
			return new WP_Error( 'activation_exception', $error_message );
		} catch ( \Error $e ) {
			$error_message = "Fatal error during plugin activation for '{$slug}': " . $e->getMessage();
			$this->logger->error( $error_message );
			return new WP_Error( 'activation_fatal_error', $error_message );
		}
	}

	/**
	 * Install and activate a plugin.
	 *
	 * @param string $slug Plugin slug.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function install_and_activate( $slug ) {
		// Install if not installed.
		if ( ! $this->is_plugin_installed( $slug ) ) {
			$install_result = $this->install_plugin( $slug );

			if ( is_wp_error( $install_result ) ) {
				return $install_result;
			}
		}

		// Activate the plugin.
		return $this->activate_plugin( $slug );
	}

	/**
	 * Batch install and activate multiple plugins with progress tracking.
	 *
	 * @param array $slugs Array of plugin slugs.
	 * @return array Results array with success/error for each plugin.
	 */
	public function batch_install( $slugs ) {
		$results = array();
		$total = count( $slugs );
		$current = 0;

		foreach ( $slugs as $slug ) {
			$current++;
			
			// Get plugin name.
			$plugin = $this->get_plugin( $slug );
			$plugin_name = $plugin ? $plugin['name'] : $slug;
			
			// Log progress.
			$this->logger->info(
				sprintf(
					/* translators: 1: current number, 2: total number, 3: plugin name */
					__( 'Installing plugin %1$d of %2$d: %3$s', 'conjurewp' ),
					$current,
					$total,
					$plugin_name
				)
			);

			// Allow external progress tracking.
			do_action( 'conjurewp_plugin_install_progress', $slug, $current, $total );

			$result = $this->install_and_activate( $slug );

			$results[ $slug ] = array(
				'success' => ! is_wp_error( $result ),
				'message' => is_wp_error( $result ) ? $result->get_error_message() : __( 'Successfully installed and activated', 'conjurewp' ),
				'name'    => $plugin_name,
				'progress' => round( ( $current / $total ) * 100, 0 ),
			);
		}

		return $results;
	}

	/**
	 * Get plugin status with detailed information.
	 *
	 * @param string $slug Plugin slug.
	 * @return array Status information.
	 */
	public function get_plugin_status( $slug ) {
		$plugin = $this->get_plugin( $slug );

		if ( ! $plugin ) {
			return array(
				'exists'    => false,
				'installed' => false,
				'active'    => false,
				'required'  => false,
			);
		}

		return array(
			'exists'    => true,
			'installed' => $this->is_plugin_installed( $slug ),
			'active'    => $this->is_plugin_active( $slug ),
			'required'  => ! empty( $plugin['required'] ),
			'name'      => $plugin['name'],
			'slug'      => $slug,
		);
	}

	/**
	 * Get status of multiple plugins.
	 *
	 * @param array $slugs Array of plugin slugs.
	 * @return array Array of plugin statuses.
	 */
	public function get_plugins_status( $slugs ) {
		$statuses = array();

		foreach ( $slugs as $slug ) {
			$statuses[ $slug ] = $this->get_plugin_status( $slug );
		}

		return $statuses;
	}
}

/**
 * Custom upgrader skin to suppress output during plugin installation.
 */
class Conjure_Plugin_Installer_Skin extends WP_Upgrader_Skin {

	/**
	 * Suppress header output.
	 */
	public function header() {
		// Silent installation.
	}

	/**
	 * Suppress footer output.
	 */
	public function footer() {
		// Silent installation.
	}

	/**
	 * Suppress feedback messages.
	 *
	 * @param string $string Message to display.
	 */
	public function feedback( $string, ...$args ) {
		// Silent installation.
	}

	/**
	 * Suppress error output.
	 *
	 * @param string|WP_Error $errors Errors to display.
	 */
	public function error( $errors ) {
		// Errors are handled by the installer class.
	}
}

