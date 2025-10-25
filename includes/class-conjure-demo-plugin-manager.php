<?php
/**
 * Demo Plugin Manager
 *
 * Manages per-demo plugin dependencies, allowing themes to specify
 * which plugins are required for each demo import.
 *
 * @package   ConjureWP
 * @version   1.1.0
 * @link      https://conjurewp.com/
 * @author    Jake Henshall
 * @copyright Copyright (c) 2024, ConjureWP
 * @license   GPL-3.0-or-later
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conjure_Demo_Plugin_Manager class.
 *
 * Handles the relationship between demos and their required plugins,
 * integrating seamlessly with TGMPA.
 */
class Conjure_Demo_Plugin_Manager {

	/**
	 * Logger instance.
	 *
	 * @var Conjure_Logger
	 */
	private $logger;

	/**
	 * TGMPA instance (for backward compatibility).
	 *
	 * @var TGM_Plugin_Activation
	 */
	private $tgmpa;

	/**
	 * Custom plugin installer instance.
	 *
	 * @var Conjure_Plugin_Installer
	 */
	private $installer;

	/**
	 * Demo plugin dependencies cache.
	 *
	 * @var array
	 */
	private $demo_plugins_cache = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->logger = Conjure_Logger::get_instance();

		// Initialize custom plugin installer.
		$this->installer = new Conjure_Plugin_Installer();

		// Get TGMPA instance if available (backward compatibility).
		if ( class_exists( 'TGM_Plugin_Activation' ) ) {
			$this->tgmpa = isset( $GLOBALS['tgmpa'] ) ? $GLOBALS['tgmpa'] : TGM_Plugin_Activation::get_instance();
		}

		add_action( 'wp_ajax_conjure_get_demo_plugins', array( $this, 'ajax_get_demo_plugins' ) );
	}

	/**
	 * Get plugins required for a specific demo.
	 *
	 * @param int|string $demo_index Demo index or slug.
	 * @param array      $import_files All available import files.
	 * @return array Array of required plugins for this demo.
	 */
	public function get_demo_plugins( $demo_index, $import_files = array() ) {
		// Check cache first.
		$cache_key = is_numeric( $demo_index ) ? "index_{$demo_index}" : "slug_{$demo_index}";
		if ( isset( $this->demo_plugins_cache[ $cache_key ] ) ) {
			return $this->demo_plugins_cache[ $cache_key ];
		}

		if ( empty( $import_files ) ) {
			$import_files = apply_filters( 'conjure_import_files', array() );
		}

		$selected_demo = $this->get_demo_by_index( $demo_index, $import_files );

		if ( ! $selected_demo ) {
			$this->logger->warning( "Demo not found: {$demo_index}" );
			return array();
		}

		$demo_plugins = array();

		// Method 1: Plugins defined directly in the demo configuration.
		if ( ! empty( $selected_demo['required_plugins'] ) ) {
			$demo_plugins = $selected_demo['required_plugins'];
		}

		/**
		 * Filter the required plugins for a specific demo.
		 *
		 * Allows themes to dynamically define which plugins are needed for each demo.
		 *
		 * @since 1.1.0
		 *
		 * @param array $demo_plugins  Array of plugin slugs or plugin config arrays.
		 * @param int   $demo_index    Index of the selected demo.
		 * @param array $selected_demo The full demo configuration array.
		 */
		$demo_plugins = apply_filters( 'conjure_demo_required_plugins', $demo_plugins, $demo_index, $selected_demo );

		// Normalize plugin data to ensure consistency.
		$demo_plugins = $this->normalize_plugin_data( $demo_plugins );

		// Cache the result.
		$this->demo_plugins_cache[ $cache_key ] = $demo_plugins;

		$this->logger->info( "Retrieved " . count( $demo_plugins ) . " plugins for demo: {$selected_demo['import_file_name']}" );

		return $demo_plugins;
	}

	/**
	 * Register plugins with the custom installer.
	 *
	 * @param array $plugins Array of plugin configurations.
	 */
	public function register_plugins( $plugins ) {
		$this->installer->register_plugins( $plugins );
		$this->logger->info( 'Registered plugins with custom installer' );
	}

	/**
	 * Get the plugin installer instance.
	 *
	 * @return Conjure_Plugin_Installer
	 */
	public function get_installer() {
		return $this->installer;
	}

	/**
	 * Get all plugins required across all demos (for backward compatibility).
	 *
	 * This returns all plugins that TGMPA has registered, maintaining
	 * compatibility with themes that don't use per-demo plugin dependencies.
	 *
	 * @return array All plugins with installation/activation status.
	 */
	public function get_all_plugins() {
		// Try TGMPA first (backward compatibility).
		if ( $this->tgmpa && isset( $this->tgmpa->plugins ) ) {
			return $this->get_all_plugins_tgmpa();
		}

		// Use custom installer.
		return $this->get_all_plugins_custom();
	}

	/**
	 * Get all plugins using TGMPA (backward compatibility).
	 *
	 * @return array
	 */
	private function get_all_plugins_tgmpa() {
		$plugins = array(
			'all'      => array(),
			'install'  => array(),
			'update'   => array(),
			'activate' => array(),
		);

		foreach ( $this->tgmpa->plugins as $slug => $plugin ) {
			if ( $this->tgmpa->is_plugin_active( $slug ) && false === $this->tgmpa->does_plugin_have_update( $slug ) ) {
				continue;
			}

			$plugins['all'][ $slug ] = $plugin;

			if ( ! $this->tgmpa->is_plugin_installed( $slug ) ) {
				$plugins['install'][ $slug ] = $plugin;
			} else {
				if ( false !== $this->tgmpa->does_plugin_have_update( $slug ) ) {
					$plugins['update'][ $slug ] = $plugin;
				}
				if ( $this->tgmpa->can_plugin_activate( $slug ) ) {
					$plugins['activate'][ $slug ] = $plugin;
				}
			}
		}

		return $plugins;
	}

	/**
	 * Get all plugins using custom installer.
	 *
	 * @return array
	 */
	private function get_all_plugins_custom() {
		$all_plugins = $this->installer->get_plugins();
		$plugins     = array(
			'all'      => array(),
			'install'  => array(),
			'update'   => array(),
			'activate' => array(),
		);

		foreach ( $all_plugins as $slug => $plugin ) {
			// Skip if already active.
			if ( $this->installer->is_plugin_active( $slug ) ) {
				continue;
			}

			$plugins['all'][ $slug ] = $plugin;

			if ( ! $this->installer->is_plugin_installed( $slug ) ) {
				$plugins['install'][ $slug ] = $plugin;
			} else {
				$plugins['activate'][ $slug ] = $plugin;
			}
		}

		return $plugins;
	}

	/**
	 * Get plugins required for a specific demo with their installation status.
	 *
	 * @param int|string $demo_index Demo index or slug.
	 * @param array      $import_files All available import files.
	 * @return array Array with plugin status (install, update, activate).
	 */
	public function get_demo_plugins_with_status( $demo_index, $import_files = array() ) {
		$required_slugs = $this->get_demo_plugins( $demo_index, $import_files );

		if ( empty( $required_slugs ) ) {
			return array(
				'all'      => array(),
				'install'  => array(),
				'update'   => array(),
				'activate' => array(),
			);
		}

		// Use TGMPA if available (backward compatibility).
		if ( $this->tgmpa && isset( $this->tgmpa->plugins ) ) {
			return $this->get_demo_plugins_status_tgmpa( $required_slugs );
		}

		// Use custom installer.
		return $this->get_demo_plugins_status_custom( $required_slugs );
	}

	/**
	 * Get demo plugin status using TGMPA.
	 *
	 * @param array $required_slugs Required plugin slugs.
	 * @return array
	 */
	private function get_demo_plugins_status_tgmpa( $required_slugs ) {
		$plugins = array(
			'all'      => array(),
			'install'  => array(),
			'update'   => array(),
			'activate' => array(),
		);

		foreach ( $required_slugs as $slug => $plugin_config ) {
			// Get full plugin data from TGMPA.
			if ( ! isset( $this->tgmpa->plugins[ $slug ] ) ) {
				$this->logger->warning( "Plugin '{$slug}' not registered with TGMPA" );
				continue;
			}

			$plugin = $this->tgmpa->plugins[ $slug ];

			// Merge any demo-specific configuration.
			if ( is_array( $plugin_config ) ) {
				$plugin = array_merge( $plugin, $plugin_config );
			}

			// Skip if already active and up-to-date.
			if ( $this->tgmpa->is_plugin_active( $slug ) && false === $this->tgmpa->does_plugin_have_update( $slug ) ) {
				continue;
			}

			$plugins['all'][ $slug ] = $plugin;

			if ( ! $this->tgmpa->is_plugin_installed( $slug ) ) {
				$plugins['install'][ $slug ] = $plugin;
			} else {
				if ( false !== $this->tgmpa->does_plugin_have_update( $slug ) ) {
					$plugins['update'][ $slug ] = $plugin;
				}
				if ( $this->tgmpa->can_plugin_activate( $slug ) ) {
					$plugins['activate'][ $slug ] = $plugin;
				}
			}
		}

		return $plugins;
	}

	/**
	 * Get demo plugin status using custom installer.
	 *
	 * @param array $required_slugs Required plugin slugs.
	 * @return array
	 */
	private function get_demo_plugins_status_custom( $required_slugs ) {
		$plugins = array(
			'all'      => array(),
			'install'  => array(),
			'update'   => array(),
			'activate' => array(),
		);

		foreach ( $required_slugs as $slug => $plugin_config ) {
			// Use the plugin config directly from the demo.
			$plugin = is_array( $plugin_config ) ? $plugin_config : array( 'slug' => $slug );
			
			// Ensure slug is set.
			if ( ! isset( $plugin['slug'] ) ) {
				$plugin['slug'] = $slug;
			}
			
			// Set default name if not provided.
			if ( ! isset( $plugin['name'] ) ) {
				$plugin['name'] = ucwords( str_replace( '-', ' ', $slug ) );
			}

		// Check plugin status and add to plugin data.
		$is_active = $this->installer->is_plugin_active( $slug );
		$is_installed = $this->installer->is_plugin_installed( $slug );
		
		$this->logger->debug( "Plugin '{$slug}': is_active={$is_active}, is_installed={$is_installed}" );
		
		// Add status info to plugin data.
		$plugin['is_active'] = $is_active;
		$plugin['is_installed'] = $is_installed;

			// Always add to 'all' array so it shows in the UI (like content options).
			$plugins['all'][ $slug ] = $plugin;

			// Only add to action arrays if not already active.
			if ( ! $is_active ) {
				if ( ! $is_installed ) {
					$plugins['install'][ $slug ] = $plugin;
				} else {
					$plugins['activate'][ $slug ] = $plugin;
				}
			}
		}

		$this->logger->info( 'Custom installer found ' . count( $plugins['all'] ) . ' plugins to process' );

		return $plugins;
	}

	/**
	 * Check if demo-specific plugin configuration is enabled.
	 *
	 * @return bool True if any demo has specific plugin requirements.
	 */
	public function is_demo_specific_plugins_enabled() {
		$import_files = apply_filters( 'conjure_import_files', array() );

		foreach ( $import_files as $index => $import ) {
			$plugins = $this->get_demo_plugins( $index, $import_files );
			if ( ! empty( $plugins ) ) {
				return true;
			}
		}

		/**
		 * Filter to force enable demo-specific plugin mode.
		 *
		 * @since 1.1.0
		 *
		 * @param bool $enabled Whether demo-specific plugins is enabled.
		 */
		return apply_filters( 'conjure_demo_specific_plugins_enabled', false );
	}

	/**
	 * Normalize plugin data to consistent format.
	 *
	 * Accepts either simple array of slugs or detailed plugin configurations.
	 *
	 * @param array $plugins Plugin data to normalize.
	 * @return array Normalized plugin data.
	 */
	private function normalize_plugin_data( $plugins ) {
		$normalized = array();

		foreach ( $plugins as $key => $value ) {
			// Format 1: Simple slug string: array( 'contact-form-7', 'woocommerce' )
			if ( is_numeric( $key ) && is_string( $value ) ) {
				$normalized[ $value ] = array();
			}
			// Format 2: Full config with 'slug' key: array( array( 'slug' => 'contact-form-7', 'name' => '...', 'required' => true ) )
			elseif ( is_numeric( $key ) && is_array( $value ) && isset( $value['slug'] ) ) {
				$slug = $value['slug'];
				$normalized[ $slug ] = $value;
			}
			// Format 3: Slug as key: array( 'woocommerce' => array( 'required' => true ) )
			elseif ( is_string( $key ) && is_array( $value ) ) {
				$normalized[ $key ] = $value;
			}
			// Format 4: Already normalized slug only
			elseif ( is_string( $key ) ) {
				$normalized[ $key ] = array();
			}
		}

		return $normalized;
	}

	/**
	 * Get demo configuration by index or slug.
	 *
	 * @param int|string $demo_identifier Demo index or slug.
	 * @param array      $import_files All import files.
	 * @return array|false Demo configuration or false if not found.
	 */
	private function get_demo_by_index( $demo_identifier, $import_files ) {
		// Numeric index.
		if ( is_numeric( $demo_identifier ) ) {
			return isset( $import_files[ $demo_identifier ] ) ? $import_files[ $demo_identifier ] : false;
		}

		// Find by slug or name.
		foreach ( $import_files as $index => $import ) {
			// Check slug.
			if ( isset( $import['import_file_slug'] ) && $import['import_file_slug'] === $demo_identifier ) {
				return $import;
			}
			// Check name as fallback.
			if ( isset( $import['import_file_name'] ) ) {
				$generated_slug = sanitize_title( $import['import_file_name'] );
				if ( $generated_slug === $demo_identifier ) {
					return $import;
				}
			}
		}

		return false;
	}

	/**
	 * AJAX handler to get plugins for a specific demo.
	 */
	public function ajax_get_demo_plugins() {
		// Verify nonce.
		check_ajax_referer( 'conjure_nonce', 'wpnonce' );

		// Check capability.
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( array(
				'message' => __( 'You do not have permission to install plugins.', 'conjurewp' ),
			) );
		}

		$demo_index = isset( $_POST['demo_index'] ) ? sanitize_text_field( $_POST['demo_index'] ) : '';

		if ( empty( $demo_index ) ) {
			wp_send_json_error( array(
				'message' => __( 'Demo index not provided.', 'conjurewp' ),
			) );
		}

		$import_files = apply_filters( 'conjure_import_files', array() );
		$plugins = $this->get_demo_plugins_with_status( $demo_index, $import_files );

		wp_send_json_success( array(
			'plugins' => $plugins,
			'count'   => count( $plugins['all'] ),
		) );
	}

	/**
	 * Validate that all required plugins for a demo are active.
	 *
	 * This should be called before importing demo content to ensure
	 * all necessary plugin hooks and classes are available.
	 *
	 * @param int|string $demo_index Demo index or slug.
	 * @param array      $import_files All import files.
	 * @return bool|WP_Error True if all required plugins are active, WP_Error otherwise.
	 */
	public function validate_demo_plugins_active( $demo_index, $import_files = array() ) {
		$required_slugs = $this->get_demo_plugins( $demo_index, $import_files );

		if ( empty( $required_slugs ) ) {
			return true;
		}

		$inactive_plugins = array();
		$required_plugins = array();

		foreach ( $required_slugs as $slug => $plugin_config ) {
			// Check if plugin is marked as required.
			$is_required = false;
			if ( is_array( $plugin_config ) && isset( $plugin_config['required'] ) && $plugin_config['required'] ) {
				$is_required = true;
			}

			// Also check TGMPA config.
			if ( $this->tgmpa && isset( $this->tgmpa->plugins[ $slug ]['required'] ) ) {
				$is_required = $this->tgmpa->plugins[ $slug ]['required'];
			}

			if ( ! $is_required ) {
				continue;
			}

			$required_plugins[] = $slug;

			if ( ! $this->tgmpa || ! $this->tgmpa->is_plugin_active( $slug ) ) {
				$plugin_name = $slug;
				if ( $this->tgmpa && isset( $this->tgmpa->plugins[ $slug ]['name'] ) ) {
					$plugin_name = $this->tgmpa->plugins[ $slug ]['name'];
				}
				$inactive_plugins[] = $plugin_name;
			}
		}

		if ( ! empty( $inactive_plugins ) ) {
			return new WP_Error(
				'required_plugins_inactive',
				sprintf(
					/* translators: %s: comma-separated list of plugin names */
					__( 'The following required plugins must be activated before importing: %s', 'conjurewp' ),
					implode( ', ', $inactive_plugins )
				)
			);
		}

		$this->logger->info( 'All required plugins are active for demo: ' . $demo_index );

		return true;
	}
}

