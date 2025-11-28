<?php
/**
 * Downloader Class
 *
 * This file contains the file downloader functionality.
 *
 * @package Conjure WP
 */

/**
 * Class for downloading a file from a given URL.
 *
 * @package Conjure WP
 */
class Conjure_Downloader {
	/**
	 * Holds full path to where the files will be saved.
	 *
	 * @var string
	 */
	private $download_directory_path = '';

	/**
	 * Constructor method.
	 *
	 * @param string $download_directory_path Full path to where the files will be saved.
	 */
	public function __construct( $download_directory_path = '' ) {
		$this->set_download_directory_path( $download_directory_path );
	}


	/**
	 * Download file from a given URL using streaming to avoid memory issues.
	 *
	 * @param string $url URL of file to download.
	 * @param string $filename Filename of the file to save.
	 * @return string|WP_Error Full path to the downloaded file or WP_Error object with error message.
	 */
	public function download_file( $url, $filename ) {
		// Test if the URL to the file is defined.
		if ( empty( $url ) ) {
			$error = new \WP_Error(
				'missing_url',
				__( 'Missing URL for downloading a file!', 'conjurewp' )
			);
			
			Conjure_Logger::get_instance()->error(
				$error->get_error_message(),
				array(
					'url'      => $url,
					'filename' => $filename,
				)
			);
			
			return $error;
		}

		// Rate limiting: check download count for this IP.
		$rate_limit_result = $this->check_rate_limit();
		if ( is_wp_error( $rate_limit_result ) ) {
			Conjure_Logger::get_instance()->warning(
				$rate_limit_result->get_error_message(),
				array(
					'url'      => $url,
					'filename' => $filename,
					'ip'       => $this->get_client_ip(),
				)
			);
			return $rate_limit_result;
		}

		// Sanitize filename to prevent directory traversal.
		$filename = sanitize_file_name( $filename );

		// Ensure the destination directory exists.
		if ( ! file_exists( $this->download_directory_path ) ) {
			if ( ! wp_mkdir_p( $this->download_directory_path ) ) {
				$error = new \WP_Error(
					'directory_creation_failed',
					sprintf(
						/* translators: %s: directory path */
						__( 'Could not create directory: %s', 'conjurewp' ),
						$this->download_directory_path
					)
				);

				Conjure_Logger::get_instance()->error(
					$error->get_error_message(),
					array(
						'url'      => $url,
						'filename' => $filename,
						'path'     => $this->download_directory_path,
					)
				);

				return $error;
			}
		}

		// Build the full destination path.
		$destination_path = $this->download_directory_path . $filename;

		// Try download with retry mechanism.
		$response = $this->download_with_retry( $url, $destination_path );

		// Test if the get request was not successful.
		if ( is_wp_error( $response ) ) {
			Conjure_Logger::get_instance()->error(
				$response->get_error_message(),
				array(
					'url'      => $url,
					'filename' => $filename,
				)
			);

			return $response;
		}

		// Check HTTP response code.
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$response_error = $this->get_error_from_response( $response );

			$error = new \WP_Error(
				'download_error',
				sprintf(
					/* translators: 1: strong tag start, 2: URL, 3: strong tag end, 4: line break, 5: error code, 6: error message */
					__( 'Failed to download file from: %1$s%2$s%3$s%4$sHTTP Error: %5$s - %6$s%4$sPlease check the URL and try again.', 'conjurewp' ),
					'<strong>',
					$url,
					'</strong>',
					'<br>',
					$response_error['error_code'],
					$response_error['error_message']
				)
			);

			Conjure_Logger::get_instance()->error(
				$error->get_error_message(),
				array(
					'url'      => $url,
					'filename' => $filename,
					'http_code' => $response_error['error_code'],
				)
			);

			return $error;
		}

		// Verify that the file was actually written to disk.
		if ( ! file_exists( $destination_path ) || 0 === filesize( $destination_path ) ) {
			$error = new \WP_Error(
				'file_not_saved',
				sprintf(
					/* translators: 1: filename, 2: destination path */
					__( 'Failed to save file "%1$s" to disk at path: %2$s. Please check directory permissions.', 'conjurewp' ),
					$filename,
					$destination_path
				)
			);

			Conjure_Logger::get_instance()->error(
				$error->get_error_message(),
				array(
					'url'      => $url,
					'filename' => $filename,
					'path'     => $destination_path,
					'exists'   => file_exists( $destination_path ),
					'size'     => file_exists( $destination_path ) ? filesize( $destination_path ) : 0,
				)
			);

			return $error;
		}

		return $destination_path;
	}


	/**
	 * Helper function: get the right format of response errors.
	 *
	 * @param array|\WP_Error $response Array or WP_Error of the response.
	 * @return array{error_code: int|string, error_message: string} Error code and error message.
	 */
	private function get_error_from_response( $response ) {
		$response_error = array();

		if ( is_array( $response ) ) {
			$response_error['error_code']    = $response['response']['code'];
			$response_error['error_message'] = $response['response']['message'];
		} else {
			$response_error['error_code']    = $response->get_error_code();
			$response_error['error_message'] = $response->get_error_message();
		}

		return $response_error;
	}


	/**
	 * Get download_directory_path attribute.
	 */
	public function get_download_directory_path() {
		return $this->download_directory_path;
	}


	/**
	 * Set download_directory_path attribute.
	 * If no valid path is specified, the default WP upload directory will be used.
	 *
	 * @param string $download_directory_path Path, where the files will be saved.
	 */
	public function set_download_directory_path( $download_directory_path ) {
		if ( file_exists( $download_directory_path ) ) {
			$this->download_directory_path = $download_directory_path;
		} else {
			$upload_dir                    = wp_upload_dir();
			$this->download_directory_path = apply_filters( 'conjure_upload_file_path', trailingslashit( $upload_dir['basedir'] ) . 'conjurewp/' );
		}
	}

	/**
	 * Check, if the file already exists and return his full path.
	 *
	 * @param string $filename The name of the file.
	 *
	 * @return bool|string
	 */
	public function fetch_existing_file( $filename ) {
		if ( file_exists( $this->download_directory_path . $filename ) ) {
			return $this->download_directory_path . $filename;
		}

		return false;
	}

	/**
	 * Download file with retry mechanism.
	 *
	 * @param string $url              URL to download from.
	 * @param string $destination_path Full path to save file.
	 * @param int    $max_attempts     Maximum number of retry attempts. Default 3.
	 * @return array|WP_Error Response array or WP_Error on failure.
	 */
	private function download_with_retry( $url, $destination_path, $max_attempts = 3 ) {
		$max_attempts = apply_filters( 'conjurewp_download_max_attempts', $max_attempts );
		$attempt = 0;
		$last_error = null;

		while ( $attempt < $max_attempts ) {
			$attempt++;

			if ( $attempt > 1 ) {
				Conjure_Logger::get_instance()->info(
					sprintf(
						/* translators: 1: attempt number, 2: max attempts */
						__( 'Download attempt %1$d of %2$d', 'conjurewp' ),
						$attempt,
						$max_attempts
					),
					array( 'url' => $url )
				);

				// Wait before retry (exponential backoff).
				$wait_time = pow( 2, $attempt - 1 );
				sleep( $wait_time );
			}

			// Stream the file directly to disk to avoid loading it into memory.
			$response = wp_remote_get(
				$url,
				array(
					'timeout'  => apply_filters( 'conjurewp_download_timeout', 300 ),
					'stream'   => true,
					'filename' => $destination_path,
				)
			);

			// Check if successful.
			if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
				if ( $attempt > 1 ) {
					Conjure_Logger::get_instance()->info(
						sprintf(
							/* translators: %d: attempt number */
							__( 'Download successful on attempt %d', 'conjurewp' ),
							$attempt
						),
						array( 'url' => $url )
					);
				}
				return $response;
			}

			$last_error = $response;
		}

		// All attempts failed.
		if ( is_wp_error( $last_error ) ) {
			return new \WP_Error(
				'download_failed_after_retries',
				sprintf(
					/* translators: 1: number of attempts, 2: error message */
					__( 'Download failed after %1$d attempts. Last error: %2$s', 'conjurewp' ),
					$max_attempts,
					$last_error->get_error_message()
				)
			);
		}

		return $last_error;
	}

	/**
	 * Check rate limiting for downloads (10 downloads per hour per IP).
	 *
	 * @return true|WP_Error True if within limits, WP_Error if rate limit exceeded.
	 */
	private function check_rate_limit() {
		$ip = $this->get_client_ip();
		$transient_key = 'conjurewp_downloads_' . md5( $ip );
		
		$downloads = get_transient( $transient_key );
		
		// First download or expired transient.
		if ( false === $downloads ) {
			set_transient( $transient_key, 1, HOUR_IN_SECONDS );
			return true;
		}
		
		// Check if limit exceeded.
		$max_downloads = apply_filters( 'conjurewp_rate_limit_max_downloads', 10 );
		if ( $downloads >= $max_downloads ) {
			return new \WP_Error(
				'rate_limit_exceeded',
				sprintf(
					/* translators: %d: maximum downloads per hour */
					__( 'Rate limit exceeded. Maximum %d downloads per hour allowed.', 'conjurewp' ),
					$max_downloads
				)
			);
		}
		
		// Increment counter.
		set_transient( $transient_key, $downloads + 1, HOUR_IN_SECONDS );
		
		return true;
	}

	/**
	 * Get client IP address safely.
	 *
	 * @return string Client IP address.
	 */
	private function get_client_ip() {
		$ip = '';
		
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		
		// Validate IP address.
		if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}
		
		return '0.0.0.0';
	}
}
