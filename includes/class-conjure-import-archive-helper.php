<?php
/**
 * Shared zip and directory helpers for plugin import files.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Archive utilities used by connector importers.
 */
class Conjure_Import_Archive_Helper {

	/**
	 * Extract a zip archive to a temporary directory.
	 *
	 * @param string $zip_path   Zip file path.
	 * @param string $dir_prefix Temp directory prefix.
	 * @return string|WP_Error
	 */
	public static function extract_zip( $zip_path, $dir_prefix ) {
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
				'conjure_zip_extract_failed',
				__( 'Failed to extract the uploaded zip archive.', 'ConjureWP' )
			);
		}

		return $extract_to;
	}

	/**
	 * Collect files by extension under a directory.
	 *
	 * @param string $root       Search root.
	 * @param array  $extensions Allowed extensions without dots.
	 * @return array
	 */
	public static function collect_files( $root, $extensions ) {
		if ( ! is_dir( $root ) ) {
			return array();
		}

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
	 * Read and decode a JSON file.
	 *
	 * @param string $file_path File path.
	 * @return array|WP_Error
	 */
	public static function read_json_file( $file_path ) {
		$raw = file_get_contents( $file_path );

		if ( false === $raw ) {
			return new WP_Error(
				'conjure_json_read_failed',
				__( 'Could not read the JSON file.', 'ConjureWP' )
			);
		}

		$data = function_exists( 'conjurewp_json_decode' )
			? conjurewp_json_decode( $raw, true )
			: json_decode( $raw, true );

		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'conjure_json_invalid',
				__( 'The file does not contain valid JSON.', 'ConjureWP' )
			);
		}

		return $data;
	}

	/**
	 * Recursively remove a directory.
	 *
	 * @param string $dir Directory path.
	 * @return void
	 */
	public static function remove_directory( $dir ) {
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
