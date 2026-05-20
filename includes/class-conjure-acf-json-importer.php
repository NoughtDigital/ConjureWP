<?php
/**
 * ACF local JSON importer.
 *
 * Copies field group JSON into the active theme's ACF local JSON directory.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Imports ACF field group JSON files from a single .json file or .zip archive.
 */
class Conjure_ACF_JSON_Importer {

	/**
	 * Import ACF JSON from a file path (.json or .zip).
	 *
	 * @param string $file_path Absolute path to the uploaded file.
	 * @return bool|WP_Error True on success, false or WP_Error on failure.
	 */
	public static function import( $file_path ) {
		if ( ! class_exists( 'ACF' ) && ! function_exists( 'acf' ) ) {
			return new WP_Error(
				'acf_inactive',
				__( 'Advanced Custom Fields is not active. ACF JSON import was skipped.', 'ConjureWP' )
			);
		}

		if ( empty( $file_path ) ) {
			return new WP_Error(
				'acf_json_missing',
				__( 'ACF JSON import file was not found.', 'ConjureWP' )
			);
		}

		if ( is_dir( $file_path ) ) {
			return self::import_from_directory( $file_path );
		}

		if ( ! file_exists( $file_path ) ) {
			return new WP_Error(
				'acf_json_missing',
				__( 'ACF JSON import file was not found.', 'ConjureWP' )
			);
		}

		$target_dir = self::get_target_directory();

		if ( is_wp_error( $target_dir ) ) {
			return $target_dir;
		}

		$extension = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );

		if ( 'zip' === $extension ) {
			return self::import_from_zip( $file_path, $target_dir );
		}

		if ( 'json' === $extension ) {
			$result = self::import_json_file( $file_path, $target_dir );

			if ( true === $result ) {
				self::maybe_trigger_acf_sync();
			}

			return $result;
		}

		return new WP_Error(
			'acf_json_invalid_type',
			__( 'ACF JSON import requires a .json or .zip file.', 'ConjureWP' )
		);
	}

	/**
	 * Import all valid JSON files from a directory (e.g. demo acf-json folder).
	 *
	 * @param string $directory_path Absolute path to a directory containing JSON files.
	 * @return bool|WP_Error
	 */
	public static function import_from_directory( $directory_path ) {
		if ( ! class_exists( 'ACF' ) && ! function_exists( 'acf' ) ) {
			return new WP_Error(
				'acf_inactive',
				__( 'Advanced Custom Fields is not active. ACF JSON import was skipped.', 'ConjureWP' )
			);
		}

		if ( empty( $directory_path ) || ! is_dir( $directory_path ) ) {
			return new WP_Error(
				'acf_json_dir_missing',
				__( 'ACF JSON directory was not found.', 'ConjureWP' )
			);
		}

		$target_dir = self::get_target_directory();

		if ( is_wp_error( $target_dir ) ) {
			return $target_dir;
		}

		$imported = 0;
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $directory_path, FilesystemIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $json_file ) {
			if ( ! $json_file->isFile() || 'json' !== strtolower( $json_file->getExtension() ) ) {
				continue;
			}

			$result = self::import_json_file( $json_file->getPathname(), $target_dir );

			if ( true === $result ) {
				++$imported;
			}
		}

		if ( 0 === $imported ) {
			return new WP_Error(
				'acf_json_empty',
				__( 'No valid ACF field group JSON files were found.', 'ConjureWP' )
			);
		}

		self::maybe_trigger_acf_sync();

		return true;
	}

	/**
	 * Resolve the theme directory used for ACF local JSON.
	 *
	 * @return string|WP_Error
	 */
	public static function get_target_directory() {
		$relative_path = function_exists( 'conjurewp_get_acf_json_save_path' )
			? conjurewp_get_acf_json_save_path()
			: 'acf-json';

		$theme_dir  = get_stylesheet_directory();
		$target_dir = trailingslashit( $theme_dir ) . $relative_path;

		if ( ! wp_mkdir_p( $target_dir ) ) {
			return new WP_Error(
				'acf_json_dir_create_failed',
				__( 'Could not create the ACF JSON directory in the active theme.', 'ConjureWP' )
			);
		}

		return $target_dir;
	}

	/**
	 * Import a single JSON file into the target directory.
	 *
	 * @param string $file_path   Source JSON file.
	 * @param string $target_dir    Destination directory.
	 * @return bool|WP_Error
	 */
	protected static function import_json_file( $file_path, $target_dir ) {
		$raw = file_get_contents( $file_path );

		if ( false === $raw ) {
			return new WP_Error(
				'acf_json_read_failed',
				__( 'Could not read the ACF JSON file.', 'ConjureWP' )
			);
		}

		$data = function_exists( 'conjurewp_json_decode' )
			? conjurewp_json_decode( $raw, true )
			: json_decode( $raw, true );

		if ( ! self::is_valid_field_group_json( $data ) ) {
			return new WP_Error(
				'acf_json_invalid',
				__( 'The file does not contain a valid ACF field group export.', 'ConjureWP' )
			);
		}

		$filename    = self::build_filename( $data, basename( $file_path ) );
		$destination = trailingslashit( $target_dir ) . $filename;

		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		$written = $wp_filesystem ? $wp_filesystem->put_contents( $destination, $raw, FS_CHMOD_FILE ) : false;

		if ( ! $written && ! file_put_contents( $destination, $raw ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			return new WP_Error(
				'acf_json_write_failed',
				__( 'Could not write the ACF JSON file to the theme directory.', 'ConjureWP' )
			);
		}

		Conjure_Logger::get_instance()->debug(
			__( 'ACF field group JSON imported', 'ConjureWP' ),
			array( 'file' => $filename )
		);

		return true;
	}

	/**
	 * Extract JSON field groups from a zip archive.
	 *
	 * @param string $zip_path    Path to zip file.
	 * @param string $target_dir  Destination directory.
	 * @return bool|WP_Error
	 */
	protected static function import_from_zip( $zip_path, $target_dir ) {
		if ( ! function_exists( 'unzip_file' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$upload_dir = wp_upload_dir();
		$extract_to = trailingslashit( $upload_dir['basedir'] ) . 'conjure-acf-json-' . gmdate( 'Y-m-d-His' );

		wp_mkdir_p( $extract_to );

		$unzipped = unzip_file( $zip_path, $extract_to );

		if ( is_wp_error( $unzipped ) ) {
			self::remove_directory( $extract_to );
			return new WP_Error(
				'acf_json_zip_extract_failed',
				__( 'Failed to extract the ACF JSON zip archive.', 'ConjureWP' )
			);
		}

		$json_sources = self::collect_json_files_from_extract( $extract_to );
		$imported     = 0;
		$errors       = array();

		foreach ( $json_sources as $json_path ) {
			$result = self::import_json_file( $json_path, $target_dir );

			if ( true === $result ) {
				++$imported;
			} elseif ( is_wp_error( $result ) ) {
				$errors[] = $result->get_error_message();
			}
		}

		self::remove_directory( $extract_to );

		if ( 0 === $imported ) {
			$message = ! empty( $errors )
				? implode( ' ', array_unique( $errors ) )
				: __( 'No valid ACF field group JSON files were found in the zip archive.', 'ConjureWP' );

			return new WP_Error( 'acf_json_zip_empty', $message );
		}

		self::maybe_trigger_acf_sync();

		return true;
	}

	/**
	 * Collect JSON file paths from an extracted archive, preferring an acf-json subfolder.
	 *
	 * @param string $extract_to Extracted root directory.
	 * @return array
	 */
	protected static function collect_json_files_from_extract( $extract_to ) {
		$acf_json_dir = self::find_acf_json_directory( $extract_to );
		$search_root  = $acf_json_dir ? $acf_json_dir : $extract_to;
		$json_files   = array();

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $search_root, FilesystemIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $file ) {
			if ( ! $file->isFile() || 'json' !== strtolower( $file->getExtension() ) ) {
				continue;
			}

			$real_path = realpath( $file->getPathname() );
			$real_root = realpath( $extract_to );

			if ( false === $real_path || false === $real_root || 0 !== strpos( $real_path, $real_root ) ) {
				continue;
			}

			$json_files[] = $real_path;
		}

		return array_values( array_unique( $json_files ) );
	}

	/**
	 * Find an acf-json directory inside an extracted archive.
	 *
	 * @param string $root Extract root.
	 * @return string|false
	 */
	protected static function find_acf_json_directory( $root ) {
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ( $iterator as $file ) {
			if ( $file->isDir() && 'acf-json' === $file->getFilename() ) {
				return $file->getPathname();
			}
		}

		return false;
	}

	/**
	 * Check whether decoded JSON looks like an ACF field group export.
	 *
	 * @param mixed $data Decoded JSON.
	 * @return bool
	 */
	protected static function is_valid_field_group_json( $data ) {
		return is_array( $data )
			&& ! empty( $data['key'] )
			&& ( isset( $data['fields'] ) || isset( $data['title'] ) );
	}

	/**
	 * Build a safe destination filename for a field group.
	 *
	 * @param array  $data             Field group data.
	 * @param string $original_filename Original upload filename.
	 * @return string
	 */
	protected static function build_filename( $data, $original_filename ) {
		if ( ! empty( $data['key'] ) ) {
			return sanitize_file_name( $data['key'] ) . '.json';
		}

		return sanitize_file_name( $original_filename );
	}

	/**
	 * Ask ACF to refresh local JSON if the API is available.
	 *
	 * @return void
	 */
	protected static function maybe_trigger_acf_sync() {
		if ( function_exists( 'acf_get_local_field_groups' ) ) {
			acf_get_local_field_groups();
		}
	}

	/**
	 * Recursively remove a directory.
	 *
	 * @param string $dir Directory path.
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
