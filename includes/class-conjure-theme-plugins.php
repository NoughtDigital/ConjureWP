<?php
/**
 * Theme Plugin Bundling Class
 *
 * Allows theme developers to bundle plugins with their theme and have ConjureWP
 * automatically discover and install them.
 *
 * @package   ConjureWP
 * @version   1.0.0
 * @link      https://conjurewp.com/
 * @author    Jake Henshall, from nought.digital
 * @copyright Copyright (c) 2018, Conjure WP of Inventionn LLC
 * @license   GPLv3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme Plugin Bundling Class
 * 
 * Theme developers can create a /conjurewp-plugins/ folder in their theme with:
 * - plugins.json (configuration file)
 * - plugin ZIP files
 * 
 * ConjureWP will automatically discover and register these plugins.
 */
class Conjure_Theme_Plugins {

	/**
	 * Theme plugin directory name.
	 *
	 * @var string
	 */
	const PLUGIN_DIR = 'conjurewp-plugins';

	/**
	 * Configuration file name.
	 *
	 * @var string
	 */
	const CONFIG_FILE = 'plugins.json';

	/**
	 * Get theme plugin directory path.
	 *
	 * @return string|false Theme plugin directory path or false if not exists.
	 */
	public static function get_theme_plugin_dir() {
		$theme_dir = get_template_directory();
		$plugin_dir_name = apply_filters( 'conjurewp_theme_plugin_dir', self::PLUGIN_DIR );
		$plugin_dir = trailingslashit( $theme_dir ) . $plugin_dir_name;

		if ( file_exists( $plugin_dir ) && is_dir( $plugin_dir ) ) {
			return apply_filters( 'conjurewp_theme_plugin_dir_path', $plugin_dir );
		}

		return false;
	}

	/**
	 * Get theme plugin configuration with caching.
	 *
	 * Reads and validates the plugins.json configuration file from the theme's
	 * conjurewp-plugins directory. Results are cached for 1 hour by default.
	 *
	 * @since 2.0.0
	 *
	 * @param bool $force_refresh Optional. Force refresh cache. Default false.
	 * @return array|false Plugin configuration array on success, false on failure.
	 */
	public static function get_theme_plugin_config( $force_refresh = false ) {
		// Check cache first unless force refresh.
		if ( ! $force_refresh ) {
			$cached_config = get_transient( 'conjurewp_theme_plugins_config' );
			if ( false !== $cached_config ) {
				return $cached_config;
			}
		}

		$plugin_dir = self::get_theme_plugin_dir();

		if ( ! $plugin_dir ) {
			return false;
		}

		$config_file = trailingslashit( $plugin_dir ) . self::CONFIG_FILE;

		if ( ! file_exists( $config_file ) ) {
			return false;
		}

		$config_content = file_get_contents( $config_file );
		$config = json_decode( $config_content, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			Conjure_Logger::get_instance()->error(
				'Failed to parse theme plugin configuration',
				array(
					'file'  => $config_file,
					'error' => json_last_error_msg(),
				)
			);
			return false;
		}

		// Validate JSON structure.
		$validation = self::validate_json_structure( $config, $config_file );
		if ( is_wp_error( $validation ) ) {
			Conjure_Logger::get_instance()->error(
				'Invalid theme plugin configuration structure',
				array(
					'file'  => $config_file,
					'error' => $validation->get_error_message(),
				)
			);
			return false;
		}

		// Cache for 1 hour.
		$cache_duration = apply_filters( 'conjurewp_theme_plugins_cache_duration', HOUR_IN_SECONDS );
		set_transient( 'conjurewp_theme_plugins_config', $config, $cache_duration );

		return $config;
	}

	/**
	 * Validate JSON structure against schema.
	 *
	 * Performs comprehensive validation of the plugins.json structure including:
	 * - Presence of required 'plugins' array
	 * - Validation of required fields (slug, name) for each plugin
	 * - Type checking for all fields
	 * - URL validation for external sources
	 *
	 * @since 2.0.0
	 *
	 * @param array  $config      Parsed JSON configuration array.
	 * @param string $config_file File path for error context.
	 * @return true|\WP_Error True if valid, WP_Error with details if invalid.
	 */
	private static function validate_json_structure( $config, $config_file ) {
		// Check if plugins array exists.
		if ( ! isset( $config['plugins'] ) ) {
			return new WP_Error(
				'missing_plugins_array',
				__( 'Configuration must contain a "plugins" array.', 'conjurewp' )
			);
		}

		if ( ! is_array( $config['plugins'] ) ) {
			return new WP_Error(
				'invalid_plugins_type',
				__( 'The "plugins" key must be an array.', 'conjurewp' )
			);
		}

		// Validate each plugin entry.
		foreach ( $config['plugins'] as $index => $plugin ) {
			if ( ! is_array( $plugin ) ) {
				return new WP_Error(
					'invalid_plugin_entry',
					sprintf(
						/* translators: %d: Plugin index */
						__( 'Plugin at index %d must be an object/array.', 'conjurewp' ),
						$index
					)
				);
			}

			// Check required fields.
			$required_fields = array( 'slug', 'name' );
			foreach ( $required_fields as $field ) {
				if ( ! isset( $plugin[ $field ] ) || empty( $plugin[ $field ] ) ) {
					return new WP_Error(
						'missing_required_field',
						sprintf(
							/* translators: 1: field name, 2: plugin index */
							__( 'Plugin at index %2$d is missing required "%1$s" field.', 'conjurewp' ),
							$field,
							$index
						)
					);
				}
			}

			// Validate field types.
			if ( ! is_string( $plugin['slug'] ) ) {
				return new WP_Error(
					'invalid_field_type',
					sprintf(
						/* translators: %d: Plugin index */
						__( 'Plugin at index %d has invalid "slug" field type (must be string).', 'conjurewp' ),
						$index
					)
				);
			}

			// Validate optional fields if present.
			if ( isset( $plugin['required'] ) && ! is_bool( $plugin['required'] ) ) {
				return new WP_Error(
					'invalid_field_type',
					sprintf(
						/* translators: %d: Plugin index */
						__( 'Plugin at index %d has invalid "required" field type (must be boolean).', 'conjurewp' ),
						$index
					)
				);
			}

			if ( isset( $plugin['url'] ) && ! is_string( $plugin['url'] ) ) {
				return new WP_Error(
					'invalid_field_type',
					sprintf(
						/* translators: %d: Plugin index */
						__( 'Plugin at index %d has invalid "url" field type (must be string).', 'conjurewp' ),
						$index
					)
				);
			}
		}

		return true;
	}

	/**
	 * Get bundled plugins from theme.
	 *
	 * @return array Array of plugin configurations.
	 */
	public static function get_bundled_plugins() {
		$config = self::get_theme_plugin_config();

		if ( ! $config || empty( $config['plugins'] ) ) {
			return apply_filters( 'conjurewp_bundled_plugins', array() );
		}

		$plugin_dir = self::get_theme_plugin_dir();
		$plugins = array();

		foreach ( $config['plugins'] as $plugin ) {
			// Validate required fields.
			if ( empty( $plugin['slug'] ) || empty( $plugin['name'] ) ) {
				continue;
			}

			// Build plugin configuration.
			// Support both "required" and "mandatory" (backward compatibility).
			$is_required = false;
			if ( isset( $plugin['required'] ) ) {
				$is_required = (bool) $plugin['required'];
			} elseif ( isset( $plugin['mandatory'] ) ) {
				// Backward compatibility - "mandatory" still works.
				$is_required = (bool) $plugin['mandatory'];
			}

			$plugin_config = array(
				'name'        => $plugin['name'],
				'slug'        => $plugin['slug'],
				'required'    => $is_required,
				'version'     => isset( $plugin['version'] ) ? $plugin['version'] : '',
				'description' => isset( $plugin['description'] ) ? $plugin['description'] : '',
			);

			// Check plugin source: bundled ZIP, external URL, or WordPress.org.
			if ( ! empty( $plugin['file'] ) ) {
				// Option 1: Bundled plugin - ZIP file in theme directory.
				$plugin_file = trailingslashit( $plugin_dir ) . $plugin['file'];

				if ( file_exists( $plugin_file ) ) {
					$plugin_config['source'] = $plugin_file;
					$plugin_config['bundled'] = true;
				} else {
					Conjure_Logger::get_instance()->warning(
						'Bundled plugin file not found',
						array(
							'plugin' => $plugin['slug'],
							'file'   => $plugin_file,
						)
					);
					continue;
				}
		} elseif ( ! empty( $plugin['url'] ) ) {
			// Option 3: External URL (GitHub, GitLab, direct download).
			// Sanitise and validate URL.
			$url = esc_url_raw( $plugin['url'], array( 'https' ) );
			
			// Enforce HTTPS only - no HTTP allowed for security.
			if ( ! $url || 'https' !== wp_parse_url( $url, PHP_URL_SCHEME ) ) {
				Conjure_Logger::get_instance()->warning(
					'Plugin URL must use HTTPS protocol. HTTP URLs are not permitted for security reasons.',
					array(
						'plugin' => $plugin['slug'],
						'url'    => $plugin['url'],
					)
				);
				continue;
			}
			
			// Validate URL format.
			if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
				Conjure_Logger::get_instance()->warning(
					'Invalid plugin URL format',
					array(
						'plugin' => $plugin['slug'],
						'url'    => $plugin['url'],
					)
				);
				continue;
			}
			
			$plugin_config['source'] = $url;
			$plugin_config['external'] = true;
			
			// Store additional URL info if provided.
			if ( ! empty( $plugin['requires_auth'] ) ) {
				$plugin_config['requires_auth'] = true;
			}
			if ( ! empty( $plugin['access_token'] ) ) {
				$plugin_config['access_token'] = sanitize_text_field( $plugin['access_token'] );
			}
			} else {
				// Option 2: WordPress.org plugin.
				$plugin_config['bundled'] = false;
			}

			$plugins[] = $plugin_config;
		}

		// Allow filtering the final plugins array.
		return apply_filters( 'conjurewp_bundled_plugins', $plugins, $plugin_dir );
	}

	/**
	 * Check if theme has bundled plugins.
	 *
	 * @return bool True if theme has bundled plugins.
	 */
	public static function has_bundled_plugins() {
		$plugins = self::get_bundled_plugins();
		return ! empty( $plugins );
	}

	/**
	 * Get mandatory plugins only.
	 *
	 * @return array Array of mandatory plugin configurations.
	 */
	public static function get_mandatory_plugins() {
		$plugins = self::get_bundled_plugins();
		return array_filter( $plugins, function( $plugin ) {
			return ! empty( $plugin['required'] );
		} );
	}

	/**
	 * Get optional plugins only.
	 *
	 * @return array Array of optional plugin configurations.
	 */
	public static function get_optional_plugins() {
		$plugins = self::get_bundled_plugins();
		return array_filter( $plugins, function( $plugin ) {
			return empty( $plugin['required'] );
		} );
	}

	/**
	 * Merge theme plugins with demo-specific plugins.
	 * 
	 * Theme-level plugins are added to all demos automatically.
	 * Mandatory plugins from theme are always included.
	 *
	 * @param array $demo_plugins Demo-specific plugin configuration.
	 * @return array Merged plugin configuration.
	 */
	public static function merge_with_demo_plugins( $demo_plugins = array() ) {
		$theme_plugins = self::get_bundled_plugins();

		if ( empty( $theme_plugins ) ) {
			return $demo_plugins;
		}

		// Ensure demo_plugins is an array.
		if ( ! is_array( $demo_plugins ) ) {
			$demo_plugins = array();
		}

		// Add theme plugins that aren't already defined in demo config.
		$demo_plugin_slugs = array_column( $demo_plugins, 'slug' );

		foreach ( $theme_plugins as $theme_plugin ) {
			// Skip if plugin already defined in demo config.
			if ( in_array( $theme_plugin['slug'], $demo_plugin_slugs, true ) ) {
				continue;
			}

			// Add theme plugin to demo plugins.
			$demo_plugins[] = $theme_plugin;
		}

		return $demo_plugins;
	}

	/**
	 * Validate JSON configuration file.
	 *
	 * @param string $json_file Path to JSON file.
	 * @return array|WP_Error Validation result or WP_Error on failure.
	 */
	public static function validate_config( $json_file ) {
		if ( ! file_exists( $json_file ) ) {
			return new WP_Error( 'file_not_found', __( 'Configuration file not found.', 'conjurewp' ) );
		}

		$content = file_get_contents( $json_file );
		$config = json_decode( $content, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error(
				'invalid_json',
				sprintf(
					/* translators: %s: JSON error message */
					__( 'Invalid JSON: %s', 'conjurewp' ),
					json_last_error_msg()
				)
			);
		}

		// Validate structure.
		if ( empty( $config['plugins'] ) || ! is_array( $config['plugins'] ) ) {
			return new WP_Error( 'invalid_structure', __( 'Configuration must contain a "plugins" array.', 'conjurewp' ) );
		}

		$errors = array();

		foreach ( $config['plugins'] as $index => $plugin ) {
			if ( empty( $plugin['slug'] ) ) {
				$errors[] = sprintf(
					/* translators: %d: Plugin index */
					__( 'Plugin at index %d is missing required "slug" field.', 'conjurewp' ),
					$index
				);
			}

			if ( empty( $plugin['name'] ) ) {
				$errors[] = sprintf(
					/* translators: %d: Plugin index */
					__( 'Plugin at index %d is missing required "name" field.', 'conjurewp' ),
					$index
				);
			}
		}

		if ( ! empty( $errors ) ) {
			return new WP_Error( 'validation_failed', implode( ' ', $errors ) );
		}

		return array(
			'valid'   => true,
			'plugins' => count( $config['plugins'] ),
		);
	}

	/**
	 * Clear cached theme plugin configuration.
	 *
	 * Use this after modifying plugins.json to force a config refresh.
	 *
	 * @since 2.0.0
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function clear_config_cache() {
		return delete_transient( 'conjurewp_theme_plugins_config' );
	}

	/**
	 * Test plugin configuration (helper for theme developers).
	 *
	 * Tests if plugins.json exists, is valid, and all bundled files are present.
	 * Useful for theme developers during development.
	 *
	 * @since 2.0.0
	 *
	 * @return array{valid: bool, errors: array, warnings: array, plugins: int} Test results.
	 */
	public static function test_plugin_config() {
		$result = array(
			'valid'    => true,
			'errors'   => array(),
			'warnings' => array(),
			'plugins'  => 0,
		);

		// Check if plugin directory exists.
		$plugin_dir = self::get_theme_plugin_dir();
		if ( ! $plugin_dir ) {
			$result['valid'] = false;
			$result['errors'][] = __( 'Theme plugin directory not found. Create a "conjurewp-plugins" folder in your theme.', 'conjurewp' );
			return $result;
		}

		// Check if config file exists.
		$config_file = trailingslashit( $plugin_dir ) . self::CONFIG_FILE;
		if ( ! file_exists( $config_file ) ) {
			$result['valid'] = false;
			$result['errors'][] = __( 'Configuration file (plugins.json) not found in plugin directory.', 'conjurewp' );
			return $result;
		}

		// Validate configuration.
		$validation = self::validate_config( $config_file );
		if ( is_wp_error( $validation ) ) {
			$result['valid'] = false;
			$result['errors'][] = $validation->get_error_message();
			return $result;
		}

		$result['plugins'] = $validation['plugins'];

		// Check each plugin.
		$plugins = self::get_bundled_plugins();
		foreach ( $plugins as $plugin ) {
			// Check bundled file exists.
			if ( ! empty( $plugin['bundled'] ) ) {
				if ( ! file_exists( $plugin['source'] ) ) {
					$result['warnings'][] = sprintf(
						/* translators: 1: plugin name, 2: file path */
						__( 'Plugin "%1$s": Bundled file not found at %2$s', 'conjurewp' ),
						$plugin['name'],
						$plugin['source']
					);
				}
			}

			// Check external URL accessibility.
			if ( ! empty( $plugin['external'] ) ) {
				$response = wp_remote_head( $plugin['source'], array( 'timeout' => 5 ) );
				if ( is_wp_error( $response ) ) {
					$result['warnings'][] = sprintf(
						/* translators: 1: plugin name, 2: error message */
						__( 'Plugin "%1$s": URL not accessible - %2$s', 'conjurewp' ),
						$plugin['name'],
						$response->get_error_message()
					);
				} elseif ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
					$result['warnings'][] = sprintf(
						/* translators: 1: plugin name, 2: HTTP code */
						__( 'Plugin "%1$s": URL returned HTTP %2$d', 'conjurewp' ),
						$plugin['name'],
						wp_remote_retrieve_response_code( $response )
					);
				}
			}
		}

		return $result;
	}

	/**
	 * Generate example configuration file content.
	 *
	 * @return string Example JSON configuration.
	 */
	public static function get_example_config() {
		$example = array(
			'plugins' => array(
				array(
					'name'        => 'Contact Form 7',
					'slug'        => 'contact-form-7',
					'required'    => true,
					'description' => 'Required for contact forms (WordPress.org)',
				),
				array(
					'name'        => 'Elementor Pro',
					'slug'        => 'elementor-pro',
					'file'        => 'elementor-pro.zip',
					'required'    => true,
					'version'     => '3.16.0',
					'description' => 'Premium page builder (bundled ZIP)',
				),
				array(
					'name'        => 'Custom Plugin',
					'slug'        => 'my-custom-plugin',
					'url'         => 'https://github.com/username/plugin/releases/download/v1.0.0/plugin.zip',
					'required'    => true,
					'version'     => '1.0.0',
					'description' => 'Custom plugin from GitHub',
				),
				array(
					'name'        => 'Private Plugin',
					'slug'        => 'private-plugin',
					'url'         => 'https://gitlab.com/api/v4/projects/12345/repository/files/plugin.zip/raw',
					'required'    => false,
					'version'     => '2.1.0',
					'description' => 'Private plugin from GitLab',
				),
				array(
					'name'        => 'Yoast SEO',
					'slug'        => 'wordpress-seo',
					'required'    => false,
					'description' => 'Recommended for SEO optimization (WordPress.org)',
				),
			),
		);

		return wp_json_encode( $example, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}
}

