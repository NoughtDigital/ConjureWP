<?php
/**
 * ConjureWP Demo Content Helpers
 *
 * Helper functions for locating and managing demo content across different locations.
 * This ensures demo content survives plugin updates by supporting multiple locations.
 *
 * @package   ConjureWP
 * @version   1.0.0
 * @link      https://conjurewp.com/
 * @author    Jake Henshall, from nought.digital
 * @copyright Copyright (c) 2018, Conjure WP of Inventionn LLC
 * @licence   Licenced GPLv3 for Open Source Use
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ConjureWP Demo Helpers Class
 *
 * Provides utilities for managing demo content in update-safe locations.
 */
class Conjure_Demo_Helpers {

	/**
	 * Get the custom demo content directory path.
	 *
	 * Priority order (theme-first approach):
	 * 1. Filtered custom path (via 'conjurewp_custom_demo_path') - THEME LEVEL CONTROL
	 * 2. Theme directory /conjurewp-demos/
	 * 3. Uploads directory /conjurewp-demos/
	 * 4. wp-config.php constant CONJUREWP_DEMO_PATH - SERVER LEVEL OVERRIDE
	 * 5. Plugin directory /demo/ (default, but NOT update-safe)
	 *
	 * @param string $theme_slug Optional theme slug to use for theme-specific demos.
	 * @return string Path to demo content directory.
	 */
	public static function get_demo_directory( $theme_slug = '' ) {
		// Allow custom path via filter (HIGHEST PRIORITY - theme-level control).
		$custom_path = apply_filters( 'conjurewp_custom_demo_path', '', $theme_slug );
		if ( ! empty( $custom_path ) && is_dir( $custom_path ) ) {
			return trailingslashit( $custom_path );
		}

		// Check theme directory (survives plugin updates).
		$theme_path = self::get_theme_demo_directory( $theme_slug );
		if ( is_dir( $theme_path ) ) {
			return $theme_path;
		}

		// Check uploads directory (survives both plugin AND theme updates).
		$uploads_path = self::get_uploads_demo_directory( $theme_slug );
		if ( is_dir( $uploads_path ) ) {
			return $uploads_path;
		}

		// Check wp-config.php constant (server-level override for special cases).
		if ( defined( 'CONJUREWP_DEMO_PATH' ) && is_dir( CONJUREWP_DEMO_PATH ) ) {
			$path = trailingslashit( CONJUREWP_DEMO_PATH );
			if ( ! empty( $theme_slug ) ) {
				$theme_path = $path . $theme_slug;
				if ( is_dir( $theme_path ) ) {
					return trailingslashit( $theme_path );
				}
			}
			return $path;
		}

		// Fallback to plugin directory (NOT update-safe - for examples only).
		return trailingslashit( CONJUREWP_PLUGIN_DIR . 'demo' );
	}

	/**
	 * Get theme-specific demo directory.
	 *
	 * @param string $theme_slug Optional theme slug for theme-specific demos.
	 * @return string Path to theme demo directory.
	 */
	public static function get_theme_demo_directory( $theme_slug = '' ) {
		$theme = wp_get_theme();
		$theme_dir = get_template_directory();

		if ( ! empty( $theme_slug ) ) {
			return trailingslashit( $theme_dir . '/conjurewp-demos/' . $theme_slug );
		}

		return trailingslashit( $theme_dir . '/conjurewp-demos' );
	}

	/**
	 * Get uploads-based demo directory (survives all updates).
	 *
	 * @param string $theme_slug Optional theme slug for theme-specific demos.
	 * @return string Path to uploads demo directory.
	 */
	public static function get_uploads_demo_directory( $theme_slug = '' ) {
		$upload_dir = wp_upload_dir();
		$base_path = trailingslashit( $upload_dir['basedir'] ) . 'conjurewp-demos';

		if ( ! empty( $theme_slug ) ) {
			return trailingslashit( $base_path . '/' . $theme_slug );
		}

		return trailingslashit( $base_path );
	}

	/**
	 * Check if demo files exist in a directory.
	 *
	 * @param string $directory Path to check.
	 * @return bool True if demo files exist.
	 */
	public static function has_demo_files( $directory ) {
		$directory = trailingslashit( $directory );

		// Check for common demo files.
		$demo_files = array(
			'content.xml',
			'widgets.json',
			'widgets.wie',
			'customizer.dat',
		);

		$found_files = array();
		foreach ( $demo_files as $file ) {
			if ( file_exists( $directory . $file ) ) {
				$found_files[] = $file;
			}
		}

		$logger = Conjure_Logger::get_instance();
		$logger->debug(
			'Checked directory for demo files: ' . basename( $directory ),
			array(
				'path' => $directory,
				'found' => ! empty( $found_files ),
				'files_found' => $found_files,
			)
		);

		return ! empty( $found_files );
	}

	/**
	 * Get demo file path with fallback support.
	 *
	 * Checks multiple locations for a demo file and returns the first found.
	 *
	 * @param string $filename   The demo file name.
	 * @param string $theme_slug Optional theme slug.
	 * @return string|false Path to demo file or false if not found.
	 */
	public static function get_demo_file( $filename, $theme_slug = '' ) {
		$locations = array(
			self::get_theme_demo_directory( $theme_slug ),
			self::get_uploads_demo_directory( $theme_slug ),
			self::get_demo_directory(), // Fallback to plugin directory.
		);

		// Allow filtering of locations.
		$locations = apply_filters( 'conjurewp_demo_file_locations', $locations, $filename, $theme_slug );

		foreach ( $locations as $location ) {
			$file_path = trailingslashit( $location ) . $filename;
			if ( file_exists( $file_path ) ) {
				return $file_path;
			}
		}

		return false;
	}

	/**
	 * Create uploads demo directory if it doesn't exist.
	 *
	 * @param string $theme_slug Optional theme slug.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public static function create_uploads_demo_directory( $theme_slug = '' ) {
		$directory = self::get_uploads_demo_directory( $theme_slug );

		if ( is_dir( $directory ) ) {
			return true;
		}

		if ( ! wp_mkdir_p( $directory ) ) {
			return new WP_Error(
				'directory_creation_failed',
				__( 'Failed to create demo content directory.', 'conjurewp' )
			);
		}

		// Add .htaccess for security (Apache/LiteSpeed).
		$htaccess_file = $directory . '.htaccess';
		if ( ! file_exists( $htaccess_file ) ) {
			$htaccess_content = "# Deny all access to demo directory\nRequire all denied";
			file_put_contents( $htaccess_file, $htaccess_content );
		}

		// Add index.php placeholder to prevent directory listing.
		$index_file = $directory . 'index.php';
		if ( ! file_exists( $index_file ) ) {
			file_put_contents( $index_file, "<?php\n// Silence is golden.\n" );
		}

		return true;
	}

	/**
	 * Get all available demo locations with their status.
	 *
	 * Useful for debugging and displaying to developers.
	 *
	 * @param string $theme_slug Optional theme slug.
	 * @return array Array of demo locations with status.
	 */
	public static function get_demo_locations_status( $theme_slug = '' ) {
		$locations = array();

		// Theme directory.
		$theme_path = self::get_theme_demo_directory( $theme_slug );
		$locations['theme'] = array(
			'path'        => $theme_path,
			'exists'      => is_dir( $theme_path ),
			'has_demos'   => is_dir( $theme_path ) && self::has_demo_files( $theme_path ),
			'update_safe' => true,
			'priority'    => 1,
			'description' => __( 'Theme directory (survives plugin updates)', 'conjurewp' ),
		);

		// Uploads directory.
		$uploads_path = self::get_uploads_demo_directory( $theme_slug );
		$locations['uploads'] = array(
			'path'        => $uploads_path,
			'exists'      => is_dir( $uploads_path ),
			'has_demos'   => is_dir( $uploads_path ) && self::has_demo_files( $uploads_path ),
			'update_safe' => true,
			'priority'    => 2,
			'description' => __( 'Uploads directory (survives plugin AND theme updates)', 'conjurewp' ),
		);

		// Plugin directory.
		$plugin_path = trailingslashit( CONJUREWP_PLUGIN_DIR . 'demo' );
		$locations['plugin'] = array(
			'path'        => $plugin_path,
			'exists'      => is_dir( $plugin_path ),
			'has_demos'   => is_dir( $plugin_path ) && self::has_demo_files( $plugin_path ),
			'update_safe' => false,
			'priority'    => 3,
			'description' => __( 'Plugin directory (NOT update-safe, for examples only)', 'conjurewp' ),
		);

		return apply_filters( 'conjurewp_demo_locations_status', $locations, $theme_slug );
	}

	/**
	 * Get recommended demo location for developers.
	 *
	 * @param bool $for_premium True if for premium/commercial themes.
	 * @return string Recommended demo path.
	 */
	public static function get_recommended_demo_location( $for_premium = false ) {
		if ( $for_premium ) {
			// For premium themes, uploads directory survives all updates.
			return self::get_uploads_demo_directory();
		}

		// For regular themes, theme directory is best.
		return self::get_theme_demo_directory();
	}

	/**
	 * Auto-discover demo imports from custom directory.
	 *
	 * Scans the custom demo directory for demo folders and automatically
	 * creates import configurations for them.
	 *
	 * @param string $base_path Base path to scan for demos.
	 * @return array Array of import configurations.
	 */
	public static function auto_discover_demos( $base_path = '' ) {
		$logger = Conjure_Logger::get_instance();
		
		if ( empty( $base_path ) ) {
			$base_path = self::get_demo_directory();
		}

		$base_path = trailingslashit( $base_path );
		$demos = array();

		$logger->debug( 
			'Auto-discovering demos',
			array(
				'base_path' => $base_path,
				'base_path_exists' => is_dir( $base_path ),
			)
		);

		// Check if base path has demo files directly.
		if ( self::has_demo_files( $base_path ) ) {
			$logger->info( 'Found demo files in base path: ' . $base_path );
			$demos[] = self::create_import_config_from_path( $base_path, 'Demo Content' );
		}

		// Check for subdirectories with demo files (xxx-demo/, abc-demo/, etc).
		if ( is_dir( $base_path ) ) {
			$subdirs = glob( $base_path . '*', GLOB_ONLYDIR );
			
			$logger->debug( 
				'Scanning subdirectories',
				array(
					'subdirs_found' => is_array( $subdirs ) ? count( $subdirs ) : 0,
					'subdirs' => is_array( $subdirs ) ? array_map( 'basename', $subdirs ) : array(),
				)
			);
			
			if ( is_array( $subdirs ) ) {
				foreach ( $subdirs as $subdir ) {
					if ( self::has_demo_files( $subdir ) ) {
						$demo_name = ucwords( str_replace( array( '-', '_' ), ' ', basename( $subdir ) ) );
						$logger->info( 'Found demo in subdirectory: ' . basename( $subdir ) );
						$demos[] = self::create_import_config_from_path( $subdir, $demo_name );
					}
				}
			}
		}

		// FALLBACK: If no demos found, check if current theme has demo files in root.
		// This allows themes without a dedicated demo folder to still work.
		if ( empty( $demos ) ) {
			$logger->debug( 'No demos found in base path, checking theme root...' );
			
			$theme_root = get_template_directory();
			
			$logger->debug( 
				'Checking theme root for demo files',
				array(
					'theme_root' => $theme_root,
					'theme_root_exists' => is_dir( $theme_root ),
				)
			);
			
			// Check theme root for demo files.
			if ( self::has_demo_files( $theme_root ) ) {
				$theme_name = wp_get_theme()->get( 'Name' );
				$logger->info( 'Found demo files in theme root: ' . $theme_name );
				$demos[] = self::create_import_config_from_path( $theme_root, $theme_name );
			}
		}

		$logger->info( 'Auto-discovery complete. Found ' . count( $demos ) . ' demo(s)' );

		return $demos;
	}

	/**
	 * Create import configuration from a directory path.
	 *
	 * @param string $path      Path to demo directory.
	 * @param string $demo_name Name for the demo.
	 * @return array Import configuration.
	 */
	public static function create_import_config_from_path( $path, $demo_name = 'Demo' ) {
		$path = trailingslashit( $path );

		$config = array(
			'import_file_name' => $demo_name,
		);

		// Content file.
		if ( file_exists( $path . 'content.xml' ) ) {
			$config['local_import_file'] = $path . 'content.xml';
		}

		// Widget file.
		if ( file_exists( $path . 'widgets.json' ) ) {
			$config['local_import_widget_file'] = $path . 'widgets.json';
		} elseif ( file_exists( $path . 'widgets.wie' ) ) {
			$config['local_import_widget_file'] = $path . 'widgets.wie';
		}

		// Customiser file.
		if ( file_exists( $path . 'customizer.dat' ) ) {
			$config['local_import_customizer_file'] = $path . 'customizer.dat';
		}

		// Redux options.
		if ( file_exists( $path . 'redux-options.json' ) ) {
			$config['local_import_redux'] = array(
				array(
					'file_path'   => $path . 'redux-options.json',
					'option_name' => apply_filters( 'conjurewp_auto_redux_option_name', 'redux_options', $path ),
				),
			);
		}

		// Revolution Slider.
		if ( file_exists( $path . 'slider.zip' ) ) {
			$config['local_import_rev_slider_file'] = $path . 'slider.zip';
		}

		// Preview image.
		$preview_extensions = array( 'jpg', 'jpeg', 'png', 'gif' );
		foreach ( $preview_extensions as $ext ) {
			if ( file_exists( $path . 'preview.' . $ext ) ) {
				// Convert to URL if possible.
				$upload_dir = wp_upload_dir();
				if ( strpos( $path, $upload_dir['basedir'] ) === 0 ) {
					$relative_path = str_replace( $upload_dir['basedir'], '', $path );
					$config['import_preview_image_url'] = $upload_dir['baseurl'] . $relative_path . 'preview.' . $ext;
				}
				break;
			}
		}

		// Check for info.txt for import notice.
		if ( file_exists( $path . 'info.txt' ) ) {
			$config['import_notice'] = file_get_contents( $path . 'info.txt' );
		} elseif ( file_exists( $path . 'README.txt' ) ) {
			$config['import_notice'] = file_get_contents( $path . 'README.txt' );
		}

		// Check for meta.json for additional demo metadata.
		if ( file_exists( $path . 'meta.json' ) ) {
			$meta_content = file_get_contents( $path . 'meta.json' );
			$meta_data = json_decode( $meta_content, true );
			if ( is_array( $meta_data ) && json_last_error() === JSON_ERROR_NONE ) {
				// Extract author, estimated import size, and tags from meta.json.
				if ( ! empty( $meta_data['author'] ) ) {
					$config['demo_author'] = sanitize_text_field( $meta_data['author'] );
				}
				if ( ! empty( $meta_data['estimated_import_size'] ) ) {
					$config['demo_estimated_import_size'] = sanitize_text_field( $meta_data['estimated_import_size'] );
				}
				if ( ! empty( $meta_data['tags'] ) && is_array( $meta_data['tags'] ) ) {
					$config['demo_tags'] = array_map( 'sanitize_text_field', $meta_data['tags'] );
				}
				// Allow additional metadata fields to be passed through.
				if ( ! empty( $meta_data['description'] ) ) {
					$config['demo_description'] = sanitize_textarea_field( $meta_data['description'] );
				}
			}
		}

		return apply_filters( 'conjurewp_auto_import_config', $config, $path, $demo_name );
	}

	/**
	 * Check if auto-registration is enabled.
	 *
	 * Priority order (theme-first approach):
	 * 1. Filter hook 'conjurewp_auto_register_demos' (THEME LEVEL CONTROL)
	 * 2. wp-config.php constant CONJUREWP_AUTO_REGISTER_DEMOS (SERVER LEVEL OVERRIDE)
	 * 3. Default: true (enabled by default)
	 *
	 * @return bool True if auto-registration is enabled.
	 */
	public static function is_auto_register_enabled() {
		// Allow filter to control (HIGHEST PRIORITY - theme-level control).
		$filter_value = apply_filters( 'conjurewp_auto_register_demos', null );
		
		// If filter returns a non-null value, use it.
		if ( null !== $filter_value ) {
			return (bool) $filter_value;
		}

		// Check wp-config.php constant (server-level override).
		if ( defined( 'CONJUREWP_AUTO_REGISTER_DEMOS' ) ) {
			return (bool) CONJUREWP_AUTO_REGISTER_DEMOS;
		}

		// Default: enabled (auto-discover demos from theme directory).
		return true;
	}

	/**
	 * Helper to build import files array with automatic path detection.
	 *
	 * @param array  $config    Import configuration.
	 * @param string $theme_slug Optional theme slug.
	 * @return array Import files array ready for conjure_import_files filter.
	 */
	public static function build_import_config( $config, $theme_slug = '' ) {
		$demo_dir = self::get_demo_directory( $theme_slug );

		$defaults = array(
			'import_file_name'             => '',
			'local_import_file'            => '',
			'local_import_widget_file'     => '',
			'local_import_customizer_file' => '',
			'import_preview_image_url'     => '',
			'import_notice'                => '',
			'preview_url'                  => '',
		);

		$config = wp_parse_args( $config, $defaults );

		// Auto-detect file paths if not provided.
		if ( empty( $config['local_import_file'] ) ) {
			$content_file = self::get_demo_file( 'content.xml', $theme_slug );
			if ( $content_file ) {
				$config['local_import_file'] = $content_file;
			}
		}

		if ( empty( $config['local_import_widget_file'] ) ) {
			$widget_file = self::get_demo_file( 'widgets.json', $theme_slug );
			if ( ! $widget_file ) {
				$widget_file = self::get_demo_file( 'widgets.wie', $theme_slug );
			}
			if ( $widget_file ) {
				$config['local_import_widget_file'] = $widget_file;
			}
		}

		if ( empty( $config['local_import_customizer_file'] ) ) {
			$customizer_file = self::get_demo_file( 'customizer.dat', $theme_slug );
			if ( $customizer_file ) {
				$config['local_import_customizer_file'] = $customizer_file;
			}
		}

		return $config;
	}
}

