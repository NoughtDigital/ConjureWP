<?php
/**
 * Plugin-specific demo import handlers for connector upload sections.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Elementor templates and kits.
 */
class Conjure_Elementor_Importer {

	/**
	 * @param string $source File or directory path.
	 * @return bool|WP_Error
	 */
	public static function import( $source ) {
		if ( ! ( did_action( 'elementor/loaded' ) || class_exists( '\Elementor\Plugin' ) ) ) {
			return new WP_Error( 'elementor_inactive', __( 'Elementor is not active. Template import was skipped.', 'ConjureWP' ) );
		}

		if ( empty( $source ) ) {
			return new WP_Error( 'elementor_missing', __( 'Elementor import file was not found.', 'ConjureWP' ) );
		}

		if ( is_dir( $source ) ) {
			return self::import_directory( $source );
		}

		if ( ! file_exists( $source ) ) {
			return new WP_Error( 'elementor_missing', __( 'Elementor import file was not found.', 'ConjureWP' ) );
		}

		$extension = strtolower( pathinfo( $source, PATHINFO_EXTENSION ) );

		if ( 'zip' === $extension ) {
			return self::import_zip( $source );
		}

		if ( 'json' === $extension ) {
			return self::import_json_file( $source );
		}

		return new WP_Error( 'elementor_invalid_type', __( 'Elementor import requires a .json or .zip file.', 'ConjureWP' ) );
	}

	/**
	 * @param string $file_path JSON path.
	 * @return bool|WP_Error
	 */
	protected static function import_json_file( $file_path ) {
		$data = Conjure_Import_Archive_Helper::read_json_file( $file_path );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$imported = self::import_template_data( $data );

		return $imported > 0 ? true : new WP_Error( 'elementor_empty', __( 'No Elementor templates could be imported.', 'ConjureWP' ) );
	}

	/**
	 * @param string $directory Directory.
	 * @return bool|WP_Error
	 */
	protected static function import_directory( $directory ) {
		$imported = 0;

		foreach ( Conjure_Import_Archive_Helper::collect_files( $directory, array( 'json' ) ) as $file ) {
			$result = self::import_json_file( $file );

			if ( true === $result ) {
				++$imported;
			}
		}

		if ( $imported < 1 ) {
			return new WP_Error( 'elementor_empty', __( 'No Elementor template JSON files were found.', 'ConjureWP' ) );
		}

		return true;
	}

	/**
	 * @param string $zip_path Zip path.
	 * @return bool|WP_Error
	 */
	protected static function import_zip( $zip_path ) {
		$extract_to = Conjure_Import_Archive_Helper::extract_zip( $zip_path, 'conjure-elementor-' );

		if ( is_wp_error( $extract_to ) ) {
			return $extract_to;
		}

		$result = self::import_directory( $extract_to );
		Conjure_Import_Archive_Helper::remove_directory( $extract_to );

		return $result;
	}

	/**
	 * @param array $data Template payload.
	 * @return int
	 */
	protected static function import_template_data( $data ) {
		if ( empty( $data ) || ! class_exists( '\Elementor\Plugin' ) ) {
			return 0;
		}

		$manager = \Elementor\Plugin::$instance->templates_manager;
		$items   = array();

		if ( isset( $data['content'] ) || isset( $data['title'] ) ) {
			$items[] = $data;
		} else {
			foreach ( $data as $key => $item ) {
				if ( 'version' === $key || ! is_array( $item ) ) {
					continue;
				}
				$items[] = $item;
			}
		}

		$imported = 0;

		foreach ( $items as $item ) {
			$result = $manager->import_template(
				array(
					'fileData' => $item,
					'fileName' => sanitize_file_name( ( isset( $item['title'] ) ? $item['title'] : 'template' ) . '.json' ),
				)
			);

			if ( ! is_wp_error( $result ) && ! empty( $result ) ) {
				++$imported;
			}
		}

		return $imported;
	}
}

/**
 * Contact Form 7 forms.
 */
class Conjure_CF7_Importer {

	/**
	 * @param string $source File or directory.
	 * @return bool|WP_Error
	 */
	public static function import( $source ) {
		if ( ! ( defined( 'WPCF7_VERSION' ) || class_exists( 'WPCF7' ) ) ) {
			return new WP_Error( 'cf7_inactive', __( 'Contact Form 7 is not active. Form import was skipped.', 'ConjureWP' ) );
		}

		if ( empty( $source ) || ( ! is_dir( $source ) && ! file_exists( $source ) ) ) {
			return new WP_Error( 'cf7_missing', __( 'Contact Form 7 import file was not found.', 'ConjureWP' ) );
		}

		if ( is_dir( $source ) ) {
			$imported = 0;
			foreach ( Conjure_Import_Archive_Helper::collect_files( $source, array( 'txt', 'json' ) ) as $file ) {
				$result = self::import_file( $file );
				if ( true === $result ) {
					++$imported;
				}
			}
			return $imported > 0 ? true : new WP_Error( 'cf7_empty', __( 'No Contact Form 7 files were found.', 'ConjureWP' ) );
		}

		$extension = strtolower( pathinfo( $source, PATHINFO_EXTENSION ) );

		if ( 'zip' === $extension ) {
			$extract_to = Conjure_Import_Archive_Helper::extract_zip( $source, 'conjure-cf7-' );
			if ( is_wp_error( $extract_to ) ) {
				return $extract_to;
			}
			$result = self::import( $extract_to );
			Conjure_Import_Archive_Helper::remove_directory( $extract_to );
			return $result;
		}

		return self::import_file( $source );
	}

	/**
	 * @param string $file_path File path.
	 * @return bool|WP_Error
	 */
	protected static function import_file( $file_path ) {
		$extension = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );

		if ( 'json' === $extension ) {
			$data = Conjure_Import_Archive_Helper::read_json_file( $file_path );
			if ( is_wp_error( $data ) ) {
				return $data;
			}
			return self::import_properties( $data );
		}

		$raw = file_get_contents( $file_path );

		if ( false === $raw ) {
			return new WP_Error( 'cf7_read_failed', __( 'Could not read the Contact Form 7 export file.', 'ConjureWP' ) );
		}

		return self::import_export_text( $raw, basename( $file_path, '.txt' ) );
	}

	/**
	 * @param string $raw   Export text.
	 * @param string $title Form title fallback.
	 * @return bool|WP_Error
	 */
	protected static function import_export_text( $raw, $title = '' ) {
		if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
			return new WP_Error( 'cf7_unavailable', __( 'Contact Form 7 is not available.', 'ConjureWP' ) );
		}

		$contact_form = WPCF7_ContactForm::get_template(
			array(
				'title' => $title ? $title : __( 'Imported form', 'ConjureWP' ),
			)
		);

		$properties         = $contact_form->get_properties();
		$properties['form'] = trim( $raw );
		$contact_form->set_properties( $properties );
		$result = $contact_form->save();

		if ( ! $result ) {
			return new WP_Error( 'cf7_save_failed', __( 'Could not save the Contact Form 7 form.', 'ConjureWP' ) );
		}

		return true;
	}

	/**
	 * @param array $data Form properties.
	 * @return bool|WP_Error
	 */
	protected static function import_properties( $data ) {
		if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
			return new WP_Error( 'cf7_unavailable', __( 'Contact Form 7 is not available.', 'ConjureWP' ) );
		}

		$title = ! empty( $data['title'] ) ? $data['title'] : __( 'Imported form', 'ConjureWP' );
		$contact_form = WPCF7_ContactForm::get_template( array( 'title' => $title ) );
		$properties   = $contact_form->get_properties();

		foreach ( array( 'form', 'mail', 'mail_2', 'messages', 'additional_settings' ) as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$properties[ $key ] = $data[ $key ];
			}
		}

		$contact_form->set_properties( $properties );

		if ( ! $contact_form->save() ) {
			return new WP_Error( 'cf7_save_failed', __( 'Could not save the Contact Form 7 form.', 'ConjureWP' ) );
		}

		return true;
	}
}

/**
 * WP Rocket settings JSON.
 */
class Conjure_WP_Rocket_Importer {

	/**
	 * @param string $source File path.
	 * @return bool|WP_Error
	 */
	public static function import( $source ) {
		if ( ! defined( 'WP_ROCKET_VERSION' ) ) {
			return new WP_Error( 'wp_rocket_inactive', __( 'WP Rocket is not active. Settings import was skipped.', 'ConjureWP' ) );
		}

		if ( empty( $source ) || ! file_exists( $source ) ) {
			return new WP_Error( 'wp_rocket_missing', __( 'WP Rocket settings file was not found.', 'ConjureWP' ) );
		}

		$data = Conjure_Import_Archive_Helper::read_json_file( $source );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		if ( isset( $data['settings'] ) && is_array( $data['settings'] ) ) {
			$data = $data['settings'];
		}

		if ( empty( $data ) || ! is_array( $data ) ) {
			return new WP_Error( 'wp_rocket_invalid', __( 'The WP Rocket settings file is empty or invalid.', 'ConjureWP' ) );
		}

		update_option( 'wp_rocket_settings', $data );

		if ( function_exists( 'rocket_generate_config_file' ) ) {
			rocket_generate_config_file();
		}

		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}

		return true;
	}
}

/**
 * Whippet performance settings JSON.
 */
class Conjure_Whippet_Importer {

	/**
	 * @param string $source File path.
	 * @return bool|WP_Error
	 */
	public static function import( $source ) {
		if ( ! defined( 'WHIPPET_VERSION' ) ) {
			return new WP_Error( 'whippet_inactive', __( 'Whippet is not active. Settings import was skipped.', 'ConjureWP' ) );
		}

		if ( empty( $source ) || ! file_exists( $source ) ) {
			return new WP_Error( 'whippet_missing', __( 'Whippet settings file was not found.', 'ConjureWP' ) );
		}

		$data = Conjure_Import_Archive_Helper::read_json_file( $source );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$updated = 0;

		if ( isset( $data['options'] ) && is_array( $data['options'] ) ) {
			foreach ( $data['options'] as $option_name => $value ) {
				if ( is_string( $option_name ) ) {
					update_option( $option_name, $value );
					++$updated;
				}
			}
		} else {
			foreach ( $data as $option_name => $value ) {
				if ( is_string( $option_name ) && 0 === strpos( $option_name, 'whippet' ) ) {
					update_option( $option_name, $value );
					++$updated;
				}
			}
		}

		if ( $updated < 1 && ! empty( $data ) ) {
			update_option( 'whippet_settings', $data );
			$updated = 1;
		}

		return $updated > 0 ? true : new WP_Error( 'whippet_invalid', __( 'No Whippet settings could be applied from the file.', 'ConjureWP' ) );
	}
}

/**
 * WPForms form exports.
 */
class Conjure_WPForms_Importer {

	/**
	 * @param string $source File or directory.
	 * @return bool|WP_Error
	 */
	public static function import( $source ) {
		if ( ! defined( 'WPFORMS_VERSION' ) ) {
			return new WP_Error( 'wpforms_inactive', __( 'WPForms is not active. Form import was skipped.', 'ConjureWP' ) );
		}

		if ( empty( $source ) || ( ! is_dir( $source ) && ! file_exists( $source ) ) ) {
			return new WP_Error( 'wpforms_missing', __( 'WPForms import file was not found.', 'ConjureWP' ) );
		}

		if ( is_dir( $source ) ) {
			$imported = 0;
			foreach ( Conjure_Import_Archive_Helper::collect_files( $source, array( 'json' ) ) as $file ) {
				if ( true === self::import_file( $file ) ) {
					++$imported;
				}
			}
			return $imported > 0 ? true : new WP_Error( 'wpforms_empty', __( 'No WPForms JSON files were found.', 'ConjureWP' ) );
		}

		$extension = strtolower( pathinfo( $source, PATHINFO_EXTENSION ) );

		if ( 'zip' === $extension ) {
			$extract_to = Conjure_Import_Archive_Helper::extract_zip( $source, 'conjure-wpforms-' );
			if ( is_wp_error( $extract_to ) ) {
				return $extract_to;
			}
			$result = self::import( $extract_to );
			Conjure_Import_Archive_Helper::remove_directory( $extract_to );
			return $result;
		}

		return self::import_file( $source );
	}

	/**
	 * @param string $file_path JSON path.
	 * @return bool|WP_Error
	 */
	protected static function import_file( $file_path ) {
		$data = Conjure_Import_Archive_Helper::read_json_file( $file_path );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		if ( ! function_exists( 'wpforms' ) ) {
			return new WP_Error( 'wpforms_unavailable', __( 'WPForms is not available.', 'ConjureWP' ) );
		}

		$forms = isset( $data['forms'] ) && is_array( $data['forms'] ) ? $data['forms'] : array( $data );
		$imported = 0;

		foreach ( $forms as $form ) {
			if ( ! is_array( $form ) ) {
				continue;
			}

			$title = ! empty( $form['settings']['form_title'] ) ? $form['settings']['form_title'] : __( 'Imported form', 'ConjureWP' );
			$fields = isset( $form['fields'] ) ? $form['fields'] : array();
			$settings = isset( $form['settings'] ) ? $form['settings'] : array();

			$form_id = wpforms()->form->add( $title, array(), array( 'status' => 'publish' ) );

			if ( ! $form_id ) {
				continue;
			}

			$save = array(
				'fields'   => $fields,
				'settings' => $settings,
			);

			wpforms()->form->update( $form_id, $save );
			++$imported;
		}

		return $imported > 0 ? true : new WP_Error( 'wpforms_empty', __( 'No WPForms could be imported.', 'ConjureWP' ) );
	}
}

/**
 * Bricks builder templates.
 */
class Conjure_Bricks_Importer {

	/**
	 * @param string $source File or directory.
	 * @return bool|WP_Error
	 */
	public static function import( $source ) {
		if ( ! ( defined( 'BRICKS_VERSION' ) || class_exists( 'Bricks\Theme' ) ) ) {
			return new WP_Error( 'bricks_inactive', __( 'Bricks is not active. Template import was skipped.', 'ConjureWP' ) );
		}

		if ( empty( $source ) || ( ! is_dir( $source ) && ! file_exists( $source ) ) ) {
			return new WP_Error( 'bricks_missing', __( 'Bricks import file was not found.', 'ConjureWP' ) );
		}

		if ( is_dir( $source ) ) {
			$imported = 0;
			foreach ( Conjure_Import_Archive_Helper::collect_files( $source, array( 'json' ) ) as $file ) {
				if ( true === self::import_file( $file ) ) {
					++$imported;
				}
			}
			return $imported > 0 ? true : new WP_Error( 'bricks_empty', __( 'No Bricks template JSON files were found.', 'ConjureWP' ) );
		}

		$extension = strtolower( pathinfo( $source, PATHINFO_EXTENSION ) );

		if ( 'zip' === $extension ) {
			$extract_to = Conjure_Import_Archive_Helper::extract_zip( $source, 'conjure-bricks-' );
			if ( is_wp_error( $extract_to ) ) {
				return $extract_to;
			}
			$result = self::import( $extract_to );
			Conjure_Import_Archive_Helper::remove_directory( $extract_to );
			return $result;
		}

		return self::import_file( $source );
	}

	/**
	 * @param string $file_path JSON path.
	 * @return bool|WP_Error
	 */
	protected static function import_file( $file_path ) {
		$data = Conjure_Import_Archive_Helper::read_json_file( $file_path );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$title   = ! empty( $data['title'] ) ? $data['title'] : basename( $file_path, '.json' );
		$content = isset( $data['content'] ) ? $data['content'] : $data;

		$post_id = wp_insert_post(
			array(
				'post_title'  => $title,
				'post_type'   => 'bricks_template',
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$meta_key = defined( 'BRICKS_DB_PAGE_CONTENT' ) ? BRICKS_DB_PAGE_CONTENT : '_bricks_page_content_2';
		update_post_meta( $post_id, $meta_key, $content );

		if ( ! empty( $data['globalClasses'] ) ) {
			update_post_meta( $post_id, '_bricks_global_classes', $data['globalClasses'] );
		}

		return true;
	}
}

/**
 * LiteSpeed Cache configuration.
 */
class Conjure_LiteSpeed_Importer {

	/**
	 * @param string $source File path.
	 * @return bool|WP_Error
	 */
	public static function import( $source ) {
		if ( ! defined( 'LSCWP_V' ) ) {
			return new WP_Error( 'litespeed_inactive', __( 'LiteSpeed Cache is not active. Config import was skipped.', 'ConjureWP' ) );
		}

		if ( empty( $source ) || ! file_exists( $source ) ) {
			return new WP_Error( 'litespeed_missing', __( 'LiteSpeed Cache config file was not found.', 'ConjureWP' ) );
		}

		if ( class_exists( 'LiteSpeed\Import' ) && method_exists( 'LiteSpeed\Import', 'import' ) ) {
			$result = \LiteSpeed\Import::import( $source );
			return $result ? true : new WP_Error( 'litespeed_failed', __( 'LiteSpeed Cache could not import the configuration file.', 'ConjureWP' ) );
		}

		$raw  = file_get_contents( $source );
		$data = function_exists( 'conjurewp_json_decode' ) ? conjurewp_json_decode( $raw, true ) : json_decode( $raw, true );

		if ( ! is_array( $data ) ) {
			return new WP_Error( 'litespeed_invalid', __( 'LiteSpeed Cache config file is not valid JSON.', 'ConjureWP' ) );
		}

		update_option( 'litespeed.conf', $data );

		return true;
	}
}

/**
 * Yoast SEO settings export.
 */
class Conjure_Yoast_SEO_Importer {

	/**
	 * @param string $source File path.
	 * @return bool|WP_Error
	 */
	public static function import( $source ) {
		if ( ! defined( 'WPSEO_VERSION' ) ) {
			return new WP_Error( 'yoast_inactive', __( 'Yoast SEO is not active. Settings import was skipped.', 'ConjureWP' ) );
		}

		if ( empty( $source ) || ! file_exists( $source ) ) {
			return new WP_Error( 'yoast_missing', __( 'Yoast SEO settings file was not found.', 'ConjureWP' ) );
		}

		$data = Conjure_Import_Archive_Helper::read_json_file( $source );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		if ( class_exists( 'WPSEO_Options' ) ) {
			if ( method_exists( 'WPSEO_Options', 'import' ) ) {
				WPSEO_Options::import( $data );
				return true;
			}

			if ( method_exists( 'WPSEO_Options', 'initialize' ) ) {
				WPSEO_Options::initialize();
			}
		}

		foreach ( $data as $option_name => $value ) {
			if ( is_string( $option_name ) && 0 === strpos( $option_name, 'wpseo' ) ) {
				update_option( $option_name, $value );
			}
		}

		return true;
	}
}

/**
 * Rank Math SEO settings export.
 */
class Conjure_Rank_Math_Importer {

	/**
	 * @param string $source File path.
	 * @return bool|WP_Error
	 */
	public static function import( $source ) {
		if ( ! defined( 'RANK_MATH_VERSION' ) ) {
			return new WP_Error( 'rank_math_inactive', __( 'Rank Math is not active. Settings import was skipped.', 'ConjureWP' ) );
		}

		if ( empty( $source ) || ! file_exists( $source ) ) {
			return new WP_Error( 'rank_math_missing', __( 'Rank Math settings file was not found.', 'ConjureWP' ) );
		}

		$data = Conjure_Import_Archive_Helper::read_json_file( $source );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		if ( isset( $data['settings'] ) && is_array( $data['settings'] ) ) {
			$data = $data['settings'];
		}

		if ( isset( $data['general'] ) ) {
			update_option( 'rank-math-options-general', $data['general'] );
		}

		if ( isset( $data['modules'] ) ) {
			update_option( 'rank_math_modules', $data['modules'] );
		}

		if ( ! isset( $data['general'] ) ) {
			update_option( 'rank_math_options', $data );
		}

		if ( class_exists( 'RankMath\Helper' ) && method_exists( 'RankMath\Helper', 'clear_cache' ) ) {
			\RankMath\Helper::clear_cache();
		}

		return true;
	}
}

/**
 * WooCommerce product CSV (requires WooCommerce).
 */
class Conjure_WooCommerce_Importer {

	/**
	 * @param string $source CSV file path.
	 * @return bool|WP_Error
	 */
	public static function import( $source ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return new WP_Error( 'woocommerce_inactive', __( 'WooCommerce is not active. Product import was skipped.', 'ConjureWP' ) );
		}

		if ( empty( $source ) || ! file_exists( $source ) ) {
			return new WP_Error( 'woocommerce_missing', __( 'WooCommerce product file was not found.', 'ConjureWP' ) );
		}

		$extension = strtolower( pathinfo( $source, PATHINFO_EXTENSION ) );

		if ( 'zip' === $extension ) {
			$extract_to = Conjure_Import_Archive_Helper::extract_zip( $source, 'conjure-woocommerce-' );
			if ( is_wp_error( $extract_to ) ) {
				return $extract_to;
			}
			foreach ( Conjure_Import_Archive_Helper::collect_files( $extract_to, array( 'csv' ) ) as $file ) {
				$result = self::import_csv( $file );
				if ( is_wp_error( $result ) ) {
					Conjure_Import_Archive_Helper::remove_directory( $extract_to );
					return $result;
				}
			}
			Conjure_Import_Archive_Helper::remove_directory( $extract_to );
			return true;
		}

		if ( 'csv' !== $extension ) {
			return new WP_Error( 'woocommerce_invalid_type', __( 'WooCommerce product import requires a .csv or .zip file.', 'ConjureWP' ) );
		}

		return self::import_csv( $source );
	}

	/**
	 * @param string $csv_path CSV path.
	 * @return bool|WP_Error
	 */
	protected static function import_csv( $csv_path ) {
		if ( ! class_exists( 'WC_Product_CSV_Importer' ) ) {
			require_once WC_ABSPATH . 'includes/import/class-wc-product-csv-importer.php';
		}

		if ( ! class_exists( 'WC_Product_CSV_Importer' ) ) {
			return new WP_Error( 'woocommerce_importer_unavailable', __( 'WooCommerce CSV importer is not available.', 'ConjureWP' ) );
		}

		$importer = new WC_Product_CSV_Importer(
			$csv_path,
			array(
				'parse'   => true,
				'mapping' => self::get_default_mapping(),
			)
		);

		$importer->import();
		$errors = $importer->get_error_messages();

		if ( ! empty( $errors ) ) {
			return new WP_Error( 'woocommerce_import_failed', implode( ' ', $errors ) );
		}

		return true;
	}

	/**
	 * @return array
	 */
	protected static function get_default_mapping() {
		return array(
			'id'         => 'id',
			'type'       => 'type',
			'sku'        => 'sku',
			'name'       => 'post_title',
			'published'  => 'post_status',
			'categories' => 'tax:product_cat',
		);
	}
}
