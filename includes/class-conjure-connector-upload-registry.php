<?php
/**
 * Registry for connector-specific manual upload and demo import sections.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central registry for plugin connector upload/import definitions.
 */
class Conjure_Connector_Upload_Registry {

	/**
	 * @return array
	 */
	public static function get_definitions() {
		$definitions = array(
			'elementor' => array(
				'extensions'       => array( 'json', 'zip' ),
				'local_config_key' => 'local_import_elementor',
				'inactive_code'    => 'elementor_inactive',
				'import_callback'  => array( 'Conjure_Elementor_Importer', 'import' ),
				'demo_filenames'   => array( 'elementor.json', 'elementor-templates.json', 'elementor-kit.zip' ),
				'demo_directories' => array( 'elementor', 'elementor-templates' ),
				'section'          => array(
					'title'       => __( 'Elementor Templates', 'ConjureWP' ),
					'description' => __( 'Import Elementor template .json files or a template kit .zip', 'ConjureWP' ),
					'tooltip'     => __( 'Export templates from Elementor → Templates → Export, or upload a Site Kit zip.', 'ConjureWP' ),
					'accept'      => '.json,.zip',
				),
				'import_labels'    => array(
					'title'       => __( 'Elementor Templates', 'ConjureWP' ),
					'description' => __( 'Elementor templates and kits.', 'ConjureWP' ),
				),
				'skip_rest_param'  => 'skip_elementor',
			),
			'cf7' => array(
				'extensions'       => array( 'txt', 'json', 'zip' ),
				'local_config_key' => 'local_import_cf7',
				'inactive_code'    => 'cf7_inactive',
				'import_callback'  => array( 'Conjure_CF7_Importer', 'import' ),
				'demo_filenames'   => array( 'contact-form-7.txt', 'cf7-form.txt', 'cf7-forms.json' ),
				'demo_directories' => array( 'cf7', 'contact-form-7' ),
				'section'          => array(
					'title'       => __( 'Contact Form 7', 'ConjureWP' ),
					'description' => __( 'Import exported CF7 form .txt or .json files', 'ConjureWP' ),
					'tooltip'     => __( 'Use the form export text from Contact Form 7, or a JSON file with form properties.', 'ConjureWP' ),
					'accept'      => '.txt,.json,.zip',
				),
				'import_labels'    => array(
					'title'       => __( 'Contact Form 7', 'ConjureWP' ),
					'description' => __( 'Contact Form 7 forms.', 'ConjureWP' ),
				),
				'skip_rest_param'  => 'skip_cf7',
			),
			'wp_rocket' => array(
				'extensions'       => array( 'json' ),
				'local_config_key' => 'local_import_wp_rocket',
				'inactive_code'    => 'wp_rocket_inactive',
				'import_callback'  => array( 'Conjure_WP_Rocket_Importer', 'import' ),
				'demo_filenames'   => array( 'wp-rocket-settings.json', 'wp-rocket.json' ),
				'section'          => array(
					'title'       => __( 'WP Rocket Settings', 'ConjureWP' ),
					'description' => __( 'Import WP Rocket settings JSON from Tools → Import settings', 'ConjureWP' ),
					'tooltip'     => __( 'Upload the .json file exported from WP Rocket. Filename format is preserved when possible.', 'ConjureWP' ),
					'accept'      => '.json',
				),
				'import_labels'    => array(
					'title'       => __( 'WP Rocket Settings', 'ConjureWP' ),
					'description' => __( 'WP Rocket configuration.', 'ConjureWP' ),
				),
				'skip_rest_param'  => 'skip_wp_rocket',
			),
			'whippet' => array(
				'extensions'       => array( 'json' ),
				'local_config_key' => 'local_import_whippet',
				'inactive_code'    => 'whippet_inactive',
				'import_callback'  => array( 'Conjure_Whippet_Importer', 'import' ),
				'demo_filenames'   => array( 'whippet-settings.json', 'whippet.json' ),
				'section'          => array(
					'title'       => __( 'Whippet Settings', 'ConjureWP' ),
					'description' => __( 'Import Whippet performance settings JSON', 'ConjureWP' ),
					'tooltip'     => __( 'JSON object of Whippet option names and values, or an { "options": { ... } } wrapper.', 'ConjureWP' ),
					'accept'      => '.json',
				),
				'import_labels'    => array(
					'title'       => __( 'Whippet Settings', 'ConjureWP' ),
					'description' => __( 'Whippet performance settings.', 'ConjureWP' ),
				),
				'skip_rest_param'  => 'skip_whippet',
			),
			'wpforms' => array(
				'extensions'       => array( 'json', 'zip' ),
				'local_config_key' => 'local_import_wpforms',
				'inactive_code'    => 'wpforms_inactive',
				'import_callback'  => array( 'Conjure_WPForms_Importer', 'import' ),
				'demo_filenames'   => array( 'wpforms.json', 'wpforms-export.json' ),
				'demo_directories' => array( 'wpforms' ),
				'section'          => array(
					'title'       => __( 'WPForms', 'ConjureWP' ),
					'description' => __( 'Import WPForms export .json or a .zip of form exports', 'ConjureWP' ),
					'tooltip'     => __( 'Use WPForms Tools → Export Forms, then upload the JSON export here.', 'ConjureWP' ),
					'accept'      => '.json,.zip',
				),
				'import_labels'    => array(
					'title'       => __( 'WPForms', 'ConjureWP' ),
					'description' => __( 'WPForms form definitions.', 'ConjureWP' ),
				),
				'skip_rest_param'  => 'skip_wpforms',
			),
			'bricks' => array(
				'extensions'       => array( 'json', 'zip' ),
				'local_config_key' => 'local_import_bricks',
				'inactive_code'    => 'bricks_inactive',
				'import_callback'  => array( 'Conjure_Bricks_Importer', 'import' ),
				'demo_filenames'   => array( 'bricks-template.json', 'bricks.json' ),
				'demo_directories' => array( 'bricks', 'bricks-templates' ),
				'section'          => array(
					'title'       => __( 'Bricks Templates', 'ConjureWP' ),
					'description' => __( 'Import Bricks template .json files or a .zip archive', 'ConjureWP' ),
					'tooltip'     => __( 'Upload JSON exported from Bricks templates library.', 'ConjureWP' ),
					'accept'      => '.json,.zip',
				),
				'import_labels'    => array(
					'title'       => __( 'Bricks Templates', 'ConjureWP' ),
					'description' => __( 'Bricks builder templates.', 'ConjureWP' ),
				),
				'skip_rest_param'  => 'skip_bricks',
			),
			'litespeed' => array(
				'extensions'       => array( 'json', 'dat' ),
				'local_config_key' => 'local_import_litespeed',
				'inactive_code'    => 'litespeed_inactive',
				'import_callback'  => array( 'Conjure_LiteSpeed_Importer', 'import' ),
				'demo_filenames'   => array( 'litespeed.json', 'litespeed-cache.json' ),
				'section'          => array(
					'title'       => __( 'LiteSpeed Cache', 'ConjureWP' ),
					'description' => __( 'Import LiteSpeed Cache configuration (.json or .dat export)', 'ConjureWP' ),
					'accept'      => '.json,.dat',
				),
				'import_labels'    => array(
					'title'       => __( 'LiteSpeed Cache', 'ConjureWP' ),
					'description' => __( 'LiteSpeed Cache configuration.', 'ConjureWP' ),
				),
				'skip_rest_param'  => 'skip_litespeed',
			),
			'yoast_seo' => array(
				'extensions'       => array( 'json' ),
				'local_config_key' => 'local_import_yoast_seo',
				'inactive_code'    => 'yoast_inactive',
				'import_callback'  => array( 'Conjure_Yoast_SEO_Importer', 'import' ),
				'demo_filenames'   => array( 'yoast-seo.json', 'yoast-settings.json' ),
				'section'          => array(
					'title'       => __( 'Yoast SEO Settings', 'ConjureWP' ),
					'description' => __( 'Import Yoast SEO settings JSON export', 'ConjureWP' ),
					'accept'      => '.json',
				),
				'import_labels'    => array(
					'title'       => __( 'Yoast SEO Settings', 'ConjureWP' ),
					'description' => __( 'Yoast SEO configuration.', 'ConjureWP' ),
				),
				'skip_rest_param'  => 'skip_yoast_seo',
			),
			'rank_math' => array(
				'extensions'       => array( 'json' ),
				'local_config_key' => 'local_import_rank_math',
				'inactive_code'    => 'rank_math_inactive',
				'import_callback'  => array( 'Conjure_Rank_Math_Importer', 'import' ),
				'demo_filenames'   => array( 'rank-math.json', 'rank-math-settings.json' ),
				'section'          => array(
					'title'       => __( 'Rank Math Settings', 'ConjureWP' ),
					'description' => __( 'Import Rank Math SEO settings JSON export', 'ConjureWP' ),
					'accept'      => '.json',
				),
				'import_labels'    => array(
					'title'       => __( 'Rank Math Settings', 'ConjureWP' ),
					'description' => __( 'Rank Math SEO configuration.', 'ConjureWP' ),
				),
				'skip_rest_param'  => 'skip_rank_math',
			),
			'woocommerce' => array(
				'extensions'       => array( 'csv', 'zip' ),
				'local_config_key' => 'local_import_woocommerce',
				'inactive_code'    => 'woocommerce_inactive',
				'import_callback'  => array( 'Conjure_WooCommerce_Importer', 'import' ),
				'demo_filenames'   => array( 'woocommerce-products.csv', 'products.csv' ),
				'section'          => array(
					'title'       => __( 'WooCommerce Products', 'ConjureWP' ),
					'description' => __( 'Import WooCommerce product CSV (standard export format)', 'ConjureWP' ),
					'tooltip'     => __( 'Use a product CSV export from WooCommerce → Products → Export.', 'ConjureWP' ),
					'accept'      => '.csv,.zip',
				),
				'import_labels'    => array(
					'title'       => __( 'WooCommerce Products', 'ConjureWP' ),
					'description' => __( 'WooCommerce product catalogue.', 'ConjureWP' ),
				),
				'skip_rest_param'  => 'skip_woocommerce',
			),
		);

		return apply_filters( 'conjure_connector_upload_definitions', $definitions );
	}

	/**
	 * @param string $slug Connector slug.
	 * @return array|null
	 */
	public static function get_definition( $slug ) {
		$definitions = self::get_definitions();

		return isset( $definitions[ $slug ] ) ? $definitions[ $slug ] : null;
	}

	/**
	 * @return array Slug => bool active.
	 */
	public static function get_active_slugs() {
		$active = array();

		foreach ( self::get_definitions() as $slug => $definition ) {
			if ( self::is_connector_active( $slug ) ) {
				$active[ $slug ] = true;
			}
		}

		return $active;
	}

	/**
	 * @param string $slug Connector slug.
	 * @return bool
	 */
	public static function is_connector_active( $slug ) {
		switch ( $slug ) {
			case 'elementor':
				return did_action( 'elementor/loaded' ) || class_exists( '\Elementor\Plugin' );
			case 'cf7':
				return defined( 'WPCF7_VERSION' ) || class_exists( 'WPCF7' );
			case 'wp_rocket':
				return defined( 'WP_ROCKET_VERSION' );
			case 'whippet':
				return defined( 'WHIPPET_VERSION' );
			case 'wpforms':
				return defined( 'WPFORMS_VERSION' );
			case 'bricks':
				return defined( 'BRICKS_VERSION' ) || class_exists( 'Bricks\Theme' );
			case 'litespeed':
				return defined( 'LSCWP_V' );
			case 'yoast_seo':
				return defined( 'WPSEO_VERSION' );
			case 'rank_math':
				return defined( 'RANK_MATH_VERSION' );
			case 'woocommerce':
				return class_exists( 'WooCommerce' );
		}

		return false;
	}

	/**
	 * Upload sections for the manual import UI.
	 *
	 * @return array
	 */
	public static function get_upload_sections() {
		$sections = array();

		foreach ( self::get_definitions() as $slug => $definition ) {
			if ( ! self::is_connector_active( $slug ) ) {
				continue;
			}

			$sections[ $slug ] = $definition['section'];
		}

		return $sections;
	}

	/**
	 * Allowed file extensions for AJAX uploads (all connector slugs).
	 *
	 * @return array
	 */
	public static function get_allowed_extensions() {
		$allowed = array();

		foreach ( self::get_definitions() as $slug => $definition ) {
			$allowed[ $slug ] = $definition['extensions'];
		}

		return $allowed;
	}

	/**
	 * Default empty paths for manual upload mode.
	 *
	 * @return array
	 */
	public static function get_default_import_paths() {
		$paths = array();

		foreach ( array_keys( self::get_definitions() ) as $slug ) {
			$paths[ $slug ] = '';
		}

		return $paths;
	}

	/**
	 * Merge connector demo paths from import config.
	 *
	 * @param array $import_files   Current paths.
	 * @param array $selected_data  Demo config row.
	 * @return array
	 */
	public static function merge_demo_paths( $import_files, $selected_data ) {
		if ( ! is_array( $selected_data ) ) {
			return $import_files;
		}

		foreach ( self::get_definitions() as $slug => $definition ) {
			$key = $definition['local_config_key'];

			if ( empty( $selected_data[ $key ] ) ) {
				continue;
			}

			$path = $selected_data[ $key ];

			if ( is_string( $path ) && ( is_dir( $path ) || file_exists( $path ) ) ) {
				$import_files[ $slug ] = $path;
			} elseif ( is_array( $path ) && ! empty( $path['file_path'] ) && file_exists( $path['file_path'] ) ) {
				$import_files[ $slug ] = $path;
			}
		}

		return $import_files;
	}

	/**
	 * Discover connector files in a demo directory.
	 *
	 * @param array  $config Demo config.
	 * @param string $path   Demo directory with trailing slash.
	 * @return array
	 */
	public static function discover_demo_files( $config, $path ) {
		foreach ( self::get_definitions() as $slug => $definition ) {
			$key = $definition['local_config_key'];

			if ( ! empty( $config[ $key ] ) ) {
				continue;
			}

			if ( ! empty( $definition['demo_filenames'] ) ) {
				foreach ( $definition['demo_filenames'] as $filename ) {
					if ( file_exists( $path . $filename ) ) {
						$config[ $key ] = $path . $filename;
						break;
					}
				}
			}

			if ( empty( $config[ $key ] ) && ! empty( $definition['demo_directories'] ) ) {
				foreach ( $definition['demo_directories'] as $directory ) {
					if ( is_dir( $path . $directory ) ) {
						$config[ $key ] = $path . $directory;
						break;
					}
				}
			}
		}

		return $config;
	}

	/**
	 * @return array Import data info defaults (slug => false).
	 */
	public static function get_import_info_defaults() {
		$defaults = array();

		foreach ( array_keys( self::get_definitions() ) as $slug ) {
			$defaults[ $slug ] = false;
		}

		return $defaults;
	}

	/**
	 * Build import step content entries for registered demos.
	 *
	 * @param array  $content      Existing content steps.
	 * @param array  $import_files Resolved paths.
	 * @param object $conjure      Main Conjure instance.
	 * @return array
	 */
	public static function build_import_content_steps( $content, $import_files, $conjure ) {
		foreach ( self::get_definitions() as $slug => $definition ) {
			if ( empty( $import_files[ $slug ] ) || ! self::is_connector_active( $slug ) ) {
				continue;
			}

			$labels = $definition['import_labels'];

			$content[ $slug ] = array(
				'title'            => $labels['title'],
				'description'      => $labels['description'],
				'pending'          => esc_html__( 'Pending', 'ConjureWP' ),
				'installing'       => esc_html__( 'Installing', 'ConjureWP' ),
				'success'          => esc_html__( 'Success', 'ConjureWP' ),
				'install_callback' => $definition['import_callback'],
				'checked'          => $conjure->is_possible_upgrade() ? 0 : 1,
				'data'             => $import_files[ $slug ],
			);
		}

		return $content;
	}

	/**
	 * Run a connector import and normalise to runner result shape.
	 *
	 * @param string $slug   Connector slug.
	 * @param mixed  $source File path or config.
	 * @return array
	 */
	public static function run_import( $slug, $source ) {
		$definition = self::get_definition( $slug );

		if ( null === $definition || ! is_callable( $definition['import_callback'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Import handler is not available.', 'ConjureWP' ),
			);
		}

		$result = call_user_func( $definition['import_callback'], $source );

		if ( is_wp_error( $result ) ) {
			$inactive_code = $definition['inactive_code'];

			if ( $inactive_code === $result->get_error_code() ) {
				return array(
					'success' => false,
					'message' => $result->get_error_message(),
					'skipped' => true,
				);
			}

			return array(
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}

		if ( false === $result ) {
			return array(
				'success' => false,
				'message' => __( 'Import failed.', 'ConjureWP' ),
			);
		}

		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: %s: connector label */
				__( '%s imported successfully.', 'ConjureWP' ),
				$definition['import_labels']['title']
			),
		);
	}
}
