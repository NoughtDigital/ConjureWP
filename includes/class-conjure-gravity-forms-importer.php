<?php
/**
 * Gravity Forms import helpers (forms and entries).
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Imports Gravity Forms exports from JSON, XML, CSV, or zip archives.
 */
class Conjure_Gravity_Forms_Importer {

	/**
	 * Import Gravity Forms from an export file or directory.
	 *
	 * @param string $file_path Path to .json, .xml, .zip, or directory.
	 * @return bool|WP_Error
	 */
	public static function import_forms( $file_path ) {
		if ( ! self::is_gravity_forms_active() ) {
			return new WP_Error(
				'gf_inactive',
				__( 'Gravity Forms is not active. Form import was skipped.', 'ConjureWP' )
			);
		}

		if ( empty( $file_path ) ) {
			return new WP_Error( 'gf_forms_missing', __( 'Gravity Forms import file was not found.', 'ConjureWP' ) );
		}

		if ( is_dir( $file_path ) ) {
			return self::import_forms_from_directory( $file_path );
		}

		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'gf_forms_missing', __( 'Gravity Forms import file was not found.', 'ConjureWP' ) );
		}

		$extension = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );

		if ( 'zip' === $extension ) {
			return self::import_forms_from_zip( $file_path );
		}

		$imported = self::import_forms_file( $file_path );

		if ( is_wp_error( $imported ) ) {
			return $imported;
		}

		if ( $imported < 1 ) {
			return new WP_Error(
				'gf_forms_empty',
				__( 'No Gravity Forms could be imported from the file provided.', 'ConjureWP' )
			);
		}

		return true;
	}

	/**
	 * Import Gravity Forms entries.
	 *
	 * @param string|array $source File path, or array with file_path and optional form_id.
	 * @return bool|WP_Error
	 */
	public static function import_entries( $source ) {
		if ( ! self::is_gravity_forms_active() ) {
			return new WP_Error(
				'gf_inactive',
				__( 'Gravity Forms is not active. Entry import was skipped.', 'ConjureWP' )
			);
		}

		$file_path = $source;
		$form_id   = 0;

		if ( is_array( $source ) ) {
			$file_path = isset( $source['file_path'] ) ? $source['file_path'] : '';
			$form_id   = isset( $source['form_id'] ) ? (int) $source['form_id'] : 0;
		}

		if ( empty( $file_path ) ) {
			return new WP_Error( 'gf_entries_missing', __( 'Gravity Forms entries file was not found.', 'ConjureWP' ) );
		}

		if ( is_dir( $file_path ) ) {
			return self::import_entries_from_directory( $file_path, $form_id );
		}

		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'gf_entries_missing', __( 'Gravity Forms entries file was not found.', 'ConjureWP' ) );
		}

		$extension = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );

		if ( 'zip' === $extension ) {
			return self::import_entries_from_zip( $file_path, $form_id );
		}

		if ( 'csv' === $extension ) {
			return self::import_entries_from_csv( $file_path, $form_id );
		}

		if ( 'json' === $extension ) {
			return self::import_entries_from_json_file( $file_path, $form_id );
		}

		return new WP_Error(
			'gf_entries_invalid_type',
			__( 'Gravity Forms entries import requires a .json, .csv, or .zip file.', 'ConjureWP' )
		);
	}

	/**
	 * @return bool
	 */
	protected static function is_gravity_forms_active() {
		return class_exists( 'GFAPI' ) || class_exists( 'GFForms' );
	}

	/**
	 * Import a single forms export file.
	 *
	 * @param string $file_path File path.
	 * @return int|WP_Error Number imported or error.
	 */
	protected static function import_forms_file( $file_path ) {
		if ( class_exists( 'GFExport' ) && method_exists( 'GFExport', 'import_file' ) ) {
			$forms = null;
			$count = GFExport::import_file( $file_path, $forms );

			if ( -1 === (int) $count ) {
				return new WP_Error(
					'gf_forms_version',
					__( 'The Gravity Forms export version is not compatible with this site.', 'ConjureWP' )
				);
			}

			return max( 0, (int) $count );
		}

		$contents = file_get_contents( $file_path );

		if ( false === $contents ) {
			return new WP_Error( 'gf_forms_read_failed', __( 'Could not read the Gravity Forms export file.', 'ConjureWP' ) );
		}

		if ( class_exists( 'GFExport' ) && method_exists( 'GFExport', 'import_json' ) ) {
			$forms = null;
			$count = GFExport::import_json( $contents, $forms );

			if ( -1 === (int) $count ) {
				return new WP_Error(
					'gf_forms_version',
					__( 'The Gravity Forms export version is not compatible with this site.', 'ConjureWP' )
				);
			}

			return max( 0, (int) $count );
		}

		return new WP_Error(
			'gf_forms_unavailable',
			__( 'Gravity Forms export tools are not available on this site.', 'ConjureWP' )
		);
	}

	/**
	 * @param string $directory Directory path.
	 * @return bool|WP_Error
	 */
	protected static function import_forms_from_directory( $directory ) {
		$imported = 0;
		$errors   = array();

		foreach ( self::collect_files_by_extensions( $directory, array( 'json', 'xml' ) ) as $file ) {
			$result = self::import_forms_file( $file );

			if ( is_wp_error( $result ) ) {
				$errors[] = $result->get_error_message();
			} elseif ( $result > 0 ) {
				$imported += (int) $result;
			}
		}

		if ( $imported < 1 ) {
			$message = ! empty( $errors ) ? implode( ' ', array_unique( $errors ) ) : __( 'No Gravity Forms export files were found in the directory.', 'ConjureWP' );

			return new WP_Error( 'gf_forms_empty', $message );
		}

		return true;
	}

	/**
	 * @param string $zip_path Zip path.
	 * @return bool|WP_Error
	 */
	protected static function import_forms_from_zip( $zip_path ) {
		$extract_to = self::extract_zip( $zip_path, 'conjure-gf-forms-' );

		if ( is_wp_error( $extract_to ) ) {
			return $extract_to;
		}

		$result = self::import_forms_from_directory( $extract_to );
		self::remove_directory( $extract_to );

		return $result;
	}

	/**
	 * @param string $directory Directory path.
	 * @param int    $form_id   Optional target form ID.
	 * @return bool|WP_Error
	 */
	protected static function import_entries_from_directory( $directory, $form_id = 0 ) {
		$imported = 0;
		$errors   = array();

		foreach ( self::collect_files_by_extensions( $directory, array( 'json', 'csv' ) ) as $file ) {
			$extension = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );

			if ( 'csv' === $extension ) {
				$result = self::import_entries_from_csv( $file, $form_id );
			} else {
				$result = self::import_entries_from_json_file( $file, $form_id );
			}

			if ( is_wp_error( $result ) ) {
				$errors[] = $result->get_error_message();
			} elseif ( true === $result ) {
				++$imported;
			}
		}

		if ( $imported < 1 ) {
			$message = ! empty( $errors ) ? implode( ' ', array_unique( $errors ) ) : __( 'No Gravity Forms entry files were found in the directory.', 'ConjureWP' );

			return new WP_Error( 'gf_entries_empty', $message );
		}

		return true;
	}

	/**
	 * @param string $zip_path Zip path.
	 * @param int    $form_id  Optional target form ID.
	 * @return bool|WP_Error
	 */
	protected static function import_entries_from_zip( $zip_path, $form_id = 0 ) {
		$extract_to = self::extract_zip( $zip_path, 'conjure-gf-entries-' );

		if ( is_wp_error( $extract_to ) ) {
			return $extract_to;
		}

		$result = self::import_entries_from_directory( $extract_to, $form_id );
		self::remove_directory( $extract_to );

		return $result;
	}

	/**
	 * @param string $file_path JSON file path.
	 * @param int    $form_id   Optional form ID.
	 * @return bool|WP_Error
	 */
	protected static function import_entries_from_json_file( $file_path, $form_id = 0 ) {
		$raw = file_get_contents( $file_path );

		if ( false === $raw ) {
			return new WP_Error( 'gf_entries_read_failed', __( 'Could not read the entries JSON file.', 'ConjureWP' ) );
		}

		$data = function_exists( 'conjurewp_json_decode' )
			? conjurewp_json_decode( $raw, true )
			: json_decode( $raw, true );

		if ( ! is_array( $data ) ) {
			return new WP_Error( 'gf_entries_invalid_json', __( 'The entries file does not contain valid JSON.', 'ConjureWP' ) );
		}

		return self::import_entries_data( $data, $form_id );
	}

	/**
	 * @param string $file_path CSV file path.
	 * @param int    $form_id   Target form ID (required when not in file).
	 * @return bool|WP_Error
	 */
	protected static function import_entries_from_csv( $file_path, $form_id = 0 ) {
		if ( $form_id < 1 ) {
			return new WP_Error(
				'gf_entries_form_required',
				__( 'CSV entry import requires the target form to exist on this site. Import forms first, or use a JSON export that includes form_id.', 'ConjureWP' )
			);
		}

		if ( ! method_exists( 'GFAPI', 'get_form' ) || ! GFAPI::get_form( $form_id ) ) {
			return new WP_Error(
				'gf_entries_form_missing',
				__( 'The target Gravity Form for entry import does not exist.', 'ConjureWP' )
			);
		}

		$handle = fopen( $file_path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		if ( false === $handle ) {
			return new WP_Error( 'gf_entries_read_failed', __( 'Could not read the entries CSV file.', 'ConjureWP' ) );
		}

		$headers = fgetcsv( $handle );

		if ( empty( $headers ) || ! is_array( $headers ) ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			return new WP_Error( 'gf_entries_csv_invalid', __( 'The entries CSV file has no header row.', 'ConjureWP' ) );
		}

		$field_map = self::map_csv_headers_to_field_ids( $form_id, $headers );
		$entries   = array();

		while ( ( $row = fgetcsv( $handle ) ) !== false ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
			if ( empty( $row ) || ! is_array( $row ) ) {
				continue;
			}

			$entry = array( 'form_id' => $form_id );

			foreach ( $row as $index => $value ) {
				if ( ! isset( $headers[ $index ], $field_map[ $headers[ $index ] ] ) ) {
					continue;
				}

				$field_id = $field_map[ $headers[ $index ] ];
				$entry[ (string) $field_id ] = $value;
			}

			if ( count( $entry ) > 1 ) {
				$entries[] = $entry;
			}
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		if ( empty( $entries ) ) {
			return new WP_Error( 'gf_entries_csv_empty', __( 'No entries could be mapped from the CSV file.', 'ConjureWP' ) );
		}

		return self::persist_entries( $entries, $form_id );
	}

	/**
	 * @param array $data    Parsed JSON.
	 * @param int   $form_id Optional form ID override.
	 * @return bool|WP_Error
	 */
	protected static function import_entries_data( $data, $form_id = 0 ) {
		if ( isset( $data['entries'] ) && is_array( $data['entries'] ) ) {
			if ( $form_id < 1 && ! empty( $data['form_id'] ) ) {
				$form_id = (int) $data['form_id'];
			}

			return self::persist_entries( $data['entries'], $form_id );
		}

		if ( self::looks_like_entry( $data ) ) {
			return self::persist_entries( array( $data ), $form_id );
		}

		$entries = array();

		foreach ( $data as $item ) {
			if ( is_array( $item ) && self::looks_like_entry( $item ) ) {
				$entries[] = $item;
			}
		}

		if ( empty( $entries ) ) {
			return new WP_Error(
				'gf_entries_invalid_json',
				__( 'The entries JSON file does not contain recognisable Gravity Forms entry data.', 'ConjureWP' )
			);
		}

		return self::persist_entries( $entries, $form_id );
	}

	/**
	 * @param array $entries Entry payloads.
	 * @param int   $form_id Optional default form ID.
	 * @return bool|WP_Error
	 */
	protected static function persist_entries( $entries, $form_id = 0 ) {
		if ( ! method_exists( 'GFAPI', 'add_entries' ) ) {
			return new WP_Error( 'gf_api_unavailable', __( 'Gravity Forms API is not available for entry import.', 'ConjureWP' ) );
		}

		$prepared = array();

		foreach ( $entries as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			unset( $entry['id'] );

			if ( $form_id > 0 ) {
				$entry['form_id'] = $form_id;
			}

			if ( empty( $entry['form_id'] ) ) {
				continue;
			}

			$target_form_id = (int) $entry['form_id'];

			if ( ! GFAPI::get_form( $target_form_id ) ) {
				continue;
			}

			$prepared[] = $entry;
		}

		if ( empty( $prepared ) ) {
			return new WP_Error(
				'gf_entries_no_form',
				__( 'No entries could be imported. Import your forms first, or include form_id in the export.', 'ConjureWP' )
			);
		}

		$default_form_id = $form_id > 0 ? $form_id : (int) $prepared[0]['form_id'];
		$result          = GFAPI::add_entries( $prepared, $default_form_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		Conjure_Logger::get_instance()->info(
			__( 'Gravity Forms entries imported', 'ConjureWP' ),
			array( 'count' => count( $prepared ) )
		);

		return true;
	}

	/**
	 * @param array $entry Possible entry.
	 * @return bool
	 */
	protected static function looks_like_entry( $entry ) {
		return is_array( $entry ) && ! empty( $entry['form_id'] );
	}

	/**
	 * Map CSV headers to Gravity Forms field IDs where possible.
	 *
	 * @param int   $form_id Form ID.
	 * @param array $headers CSV headers.
	 * @return array Header => field ID.
	 */
	protected static function map_csv_headers_to_field_ids( $form_id, $headers ) {
		$map  = array();
		$form = GFAPI::get_form( $form_id );

		if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
			return $map;
		}

		$label_map = array();

		foreach ( $form['fields'] as $field ) {
			$field_id = is_object( $field ) ? ( isset( $field->id ) ? $field->id : 0 ) : ( isset( $field['id'] ) ? $field['id'] : 0 );

			if ( empty( $field_id ) ) {
				continue;
			}

			$label = is_object( $field ) ? ( isset( $field->label ) ? $field->label : '' ) : ( isset( $field['label'] ) ? $field['label'] : '' );
			if ( '' !== $label ) {
				$label_map[ strtolower( trim( $label ) ) ] = (string) $field_id;
			}

			$label_map[ (string) $field_id ] = (string) $field_id;

			$inputs = is_object( $field ) ? ( isset( $field->inputs ) ? $field->inputs : array() ) : ( isset( $field['inputs'] ) ? $field['inputs'] : array() );

			if ( ! empty( $inputs ) && is_array( $inputs ) ) {
				foreach ( $inputs as $input ) {
					if ( empty( $input['id'] ) ) {
						continue;
					}

					$input_label = isset( $input['label'] ) ? $input['label'] : '';
					if ( '' !== $input_label ) {
						$label_map[ strtolower( trim( $input_label ) ) ] = (string) $input['id'];
					}

					$label_map[ (string) $input['id'] ] = (string) $input['id'];
				}
			}
		}

		foreach ( $headers as $header ) {
			$key = strtolower( trim( (string) $header ) );

			if ( isset( $label_map[ $key ] ) ) {
				$map[ $header ] = $label_map[ $key ];
			}
		}

		return $map;
	}

	/**
	 * @param string $root       Search root.
	 * @param array  $extensions Allowed extensions.
	 * @return array
	 */
	protected static function collect_files_by_extensions( $root, $extensions ) {
		$files    = array();
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $file ) {
			if ( ! $file->isFile() ) {
				continue;
			}

			$extension = strtolower( $file->getExtension() );

			if ( ! in_array( $extension, $extensions, true ) ) {
				continue;
			}

			$real_path = realpath( $file->getPathname() );
			$real_root = realpath( $root );

			if ( false === $real_path || false === $real_root || 0 !== strpos( $real_path, $real_root ) ) {
				continue;
			}

			$files[] = $real_path;
		}

		return array_values( array_unique( $files ) );
	}

	/**
	 * @param string $zip_path   Zip file.
	 * @param string $dir_prefix Temp directory prefix.
	 * @return string|WP_Error
	 */
	protected static function extract_zip( $zip_path, $dir_prefix ) {
		if ( ! function_exists( 'unzip_file' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$upload_dir = wp_upload_dir();
		$extract_to = trailingslashit( $upload_dir['basedir'] ) . $dir_prefix . gmdate( 'Y-m-d-His' );

		wp_mkdir_p( $extract_to );

		$unzipped = unzip_file( $zip_path, $extract_to );

		if ( is_wp_error( $unzipped ) ) {
			self::remove_directory( $extract_to );
			return new WP_Error(
				'gf_zip_extract_failed',
				__( 'Failed to extract the uploaded zip archive.', 'ConjureWP' )
			);
		}

		return $extract_to;
	}

	/**
	 * @param string $dir Directory.
	 * @return void
	 */
	protected static function remove_directory( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $iterator as $file ) {
			if ( $file->isDir() ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
				rmdir( $file->getPathname() );
			} else {
				wp_delete_file( $file->getPathname() );
			}
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		rmdir( $dir );
	}
}
