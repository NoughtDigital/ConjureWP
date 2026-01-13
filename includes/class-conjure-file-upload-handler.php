<?php
/**
 * File Upload Handler class
 *
 * Handles file upload and manual import mode.
 *
 * @package   Conjure WP
 * @version   @@pkg.version
 * @link      https://conjurewp.com/
 * @author    Jake Henshall, from Nought.digital
 * @copyright Copyright (c) 2018, Conjure WP of Nought Digital
 * @license   Licensed GPLv3 for Open Source Use
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conjure File Upload Handler class.
 */
class Conjure_File_Upload_Handler {

	/**
	 * Reference to main Conjure instance.
	 *
	 * @var Conjure
	 */
	protected $conjure;

	/**
	 * Logger instance.
	 *
	 * @var Conjure_Logger
	 */
	protected $logger;

	/**
	 * Wizard UI instance.
	 *
	 * @var Conjure_Wizard_UI
	 */
	protected $wizard_ui;

	/**
	 * Constructor.
	 *
	 * @param Conjure           $conjure Main Conjure instance.
	 * @param Conjure_Wizard_UI $wizard_ui Wizard UI instance.
	 */
	public function __construct( $conjure, $wizard_ui ) {
		$this->conjure    = $conjure;
		$this->logger     = $conjure->logger;
		$this->wizard_ui  = $wizard_ui;
	}

	/**
	 * Get the upload directory for Conjure files.
	 *
	 * @return string|false
	 */
	public function get_upload_dir() {
		$upload_dir = wp_upload_dir();
		$conjure_dir = trailingslashit( $upload_dir['basedir'] ) . 'conjure-uploads/';

		if ( ! file_exists( $conjure_dir ) ) {
			$mkdir_result = wp_mkdir_p( $conjure_dir );
			
			if ( ! $mkdir_result || ! file_exists( $conjure_dir ) ) {
				$error_message = sprintf(
					__( 'Failed to create upload directory: %s', 'conjurewp' ),
					$conjure_dir
				);
				
				$this->logger->error( $error_message );
				
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					trigger_error( $error_message, E_USER_ERROR );
				}
				
				return false;
			}
			
			// Add .htaccess for security (Apache 2.4+ syntax).
			$htaccess_content = "# Deny access to all files in this directory\n";
			$htaccess_content .= "<IfModule mod_authz_core.c>\n";
			$htaccess_content .= "    Require all denied\n";
			$htaccess_content .= "</IfModule>\n";
			$htaccess_content .= "<IfModule !mod_authz_core.c>\n";
			$htaccess_content .= "    Order deny,allow\n";
			$htaccess_content .= "    Deny from all\n";
			$htaccess_content .= "</IfModule>\n";
			$htaccess_file = $conjure_dir . '.htaccess';
			$htaccess_result = file_put_contents( $htaccess_file, $htaccess_content );
			
			if ( false === $htaccess_result ) {
				$error_message = sprintf(
					__( 'Failed to create .htaccess file in upload directory: %s', 'conjurewp' ),
					$conjure_dir
				);
				
				$this->logger->error( $error_message );
			}
			
			// Add index.php to prevent directory listing.
			$index_file = $conjure_dir . 'index.php';
			$index_result = file_put_contents( $index_file, '<?php // Silence is golden.' );
			
			if ( false === $index_result ) {
				$error_message = sprintf(
					__( 'Failed to create index.php file in upload directory: %s', 'conjurewp' ),
					$conjure_dir
				);
				
				$this->logger->error( $error_message );
			}
		}

		return $conjure_dir;
	}

	/**
	 * AJAX handler for file uploads.
	 */
	public function ajax_upload_file() {
		if ( ! check_ajax_referer( 'conjure_nonce', 'wpnonce', false ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Security check failed.', 'conjurewp' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'You do not have permission to upload files.', 'conjurewp' ) ) );
		}

		if ( empty( $_FILES['file'] ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'No file uploaded.', 'conjurewp' ) ) );
		}

		$file = $_FILES['file'];
		$file_type = isset( $_POST['file_type'] ) ? sanitize_key( $_POST['file_type'] ) : '';

		// Validate file type.
		$allowed_types = array(
			'content' => array( 'xml' ),
			'widgets' => array( 'json', 'wie' ),
			'options' => array( 'dat', 'json' ),
			'redux' => array( 'json' ),
			'sliders' => array( 'zip' ),
			'images' => array( 'xml' ),
			'menus' => array( 'json' ),
		);

		if ( ! isset( $allowed_types[ $file_type ] ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid file type specified.', 'conjurewp' ) ) );
		}

		$file_extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

		if ( ! in_array( $file_extension, $allowed_types[ $file_type ], true ) ) {
			wp_send_json_error( array(
				'message' => sprintf(
					esc_html__( 'Invalid file extension. Allowed: %s', 'conjurewp' ),
					implode( ', ', $allowed_types[ $file_type ] )
				),
			) );
		}

		// Check for upload errors.
		if ( $file['error'] !== UPLOAD_ERR_OK ) {
			wp_send_json_error( array( 'message' => esc_html__( 'File upload error.', 'conjurewp' ) ) );
		}

		// Validate file size (max 50MB).
		$max_size = 50 * 1024 * 1024;
		if ( $file['size'] > $max_size ) {
			wp_send_json_error( array( 'message' => esc_html__( 'File is too large. Maximum size is 50MB.', 'conjurewp' ) ) );
		}

		// Move file to upload directory.
		$upload_dir = $this->get_upload_dir();
		
		if ( false === $upload_dir ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Failed to create upload directory. Please check file permissions.', 'conjurewp' ) ) );
		}
		
		$filename = $file_type . '-' . time() . '.' . $file_extension;
		$destination = $upload_dir . $filename;

		if ( ! move_uploaded_file( $file['tmp_name'], $destination ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Failed to save uploaded file.', 'conjurewp' ) ) );
		}

		// Store file info in transient.
		$uploaded_files = get_transient( 'conjure_uploaded_files' );
		if ( ! $uploaded_files ) {
			$uploaded_files = array();
		}

		$uploaded_files[ $file_type ] = array(
			'path' => $destination,
			'name' => sanitize_file_name( $file['name'] ),
			'size' => $file['size'],
			'time' => time(),
		);

		set_transient( 'conjure_uploaded_files', $uploaded_files, HOUR_IN_SECONDS );

		$this->logger->info(
			__( 'File uploaded successfully', 'conjurewp' ),
			array(
				'type' => $file_type,
				'name' => $file['name'],
				'size' => size_format( $file['size'] ),
			)
		);

		wp_send_json_success( array(
			'message' => esc_html__( 'File uploaded successfully.', 'conjurewp' ),
			'filename' => $file['name'],
			'size' => size_format( $file['size'] ),
		) );
	}

	/**
	 * AJAX handler for uploading from WordPress media library.
	 */
	public function ajax_upload_from_media() {
		if ( ! check_ajax_referer( 'conjure_nonce', 'wpnonce', false ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Security check failed.', 'conjurewp' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'You do not have permission to upload files.', 'conjurewp' ) ) );
		}

		if ( empty( $_POST['attachment_id'] ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'No file selected.', 'conjurewp' ) ) );
		}

		$attachment_id = intval( $_POST['attachment_id'] );
		$file_type = isset( $_POST['file_type'] ) ? sanitize_key( $_POST['file_type'] ) : '';

		// Validate file type.
		$allowed_types = array( 'content', 'widgets', 'options', 'redux', 'sliders', 'images', 'menus' );

		if ( ! in_array( $file_type, $allowed_types, true ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid file type specified.', 'conjurewp' ) ) );
		}

		// Get attachment file path.
		$file_path = get_attached_file( $attachment_id );

		if ( ! $file_path || ! file_exists( $file_path ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'File not found in media library.', 'conjurewp' ) ) );
		}

		// Validate file extension.
		$file_extension = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );
		$allowed_extensions = array(
			'content' => array( 'xml' ),
			'widgets' => array( 'json', 'wie' ),
			'options' => array( 'dat', 'json' ),
			'redux' => array( 'json' ),
			'sliders' => array( 'zip' ),
			'images' => array( 'xml' ),
			'menus' => array( 'json' ),
		);

		if ( ! isset( $allowed_extensions[ $file_type ] ) || ! in_array( $file_extension, $allowed_extensions[ $file_type ], true ) ) {
			wp_send_json_error( array(
				'message' => sprintf(
					esc_html__( 'Invalid file extension. Allowed: %s', 'conjurewp' ),
					implode( ', ', $allowed_extensions[ $file_type ] )
				),
			) );
		}

		// Copy file to upload directory.
		$upload_dir = $this->get_upload_dir();
		
		if ( false === $upload_dir ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Failed to create upload directory. Please check file permissions.', 'conjurewp' ) ) );
		}
		
		$filename = $file_type . '-' . time() . '.' . $file_extension;
		$destination = $upload_dir . $filename;

		if ( ! copy( $file_path, $destination ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Failed to copy file.', 'conjurewp' ) ) );
		}

		// Get file info.
		$file_size = filesize( $destination );
		$file_name = basename( get_attached_file( $attachment_id ) );

		// Store file info in transient.
		$uploaded_files = get_transient( 'conjure_uploaded_files' );
		if ( ! $uploaded_files ) {
			$uploaded_files = array();
		}

		$uploaded_files[ $file_type ] = array(
			'path' => $destination,
			'name' => sanitize_file_name( $file_name ),
			'size' => $file_size,
			'time' => time(),
		);

		set_transient( 'conjure_uploaded_files', $uploaded_files, HOUR_IN_SECONDS );

		$this->logger->info(
			__( 'File copied from media library successfully', 'conjurewp' ),
			array(
				'type' => $file_type,
				'name' => $file_name,
				'size' => size_format( $file_size ),
			)
		);

		wp_send_json_success( array(
			'message' => esc_html__( 'File uploaded successfully.', 'conjurewp' ),
			'filename' => $file_name,
			'size' => size_format( $file_size ),
		) );
	}

	/**
	 * AJAX handler for deleting uploaded files.
	 */
	public function ajax_delete_uploaded_file() {
		if ( ! check_ajax_referer( 'conjure_nonce', 'wpnonce', false ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Security check failed.', 'conjurewp' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'You do not have permission to delete files.', 'conjurewp' ) ) );
		}

		$file_type = isset( $_POST['file_type'] ) ? sanitize_key( $_POST['file_type'] ) : '';

		if ( empty( $file_type ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'No file type specified.', 'conjurewp' ) ) );
		}

		$uploaded_files = get_transient( 'conjure_uploaded_files' );

		if ( ! empty( $uploaded_files[ $file_type ] ) ) {
			$file_path = $uploaded_files[ $file_type ]['path'];

			if ( file_exists( $file_path ) ) {
				wp_delete_file( $file_path );
			}

			unset( $uploaded_files[ $file_type ] );
			set_transient( 'conjure_uploaded_files', $uploaded_files, HOUR_IN_SECONDS );

			$this->logger->info( __( 'Uploaded file deleted', 'conjurewp' ), array( 'type' => $file_type ) );
		}

		wp_send_json_success( array( 'message' => esc_html__( 'File deleted successfully.', 'conjurewp' ) ) );
	}

	/**
	 * Cleanup all uploaded files.
	 */
	public function cleanup_uploaded_files() {
		$uploaded_files = get_transient( 'conjure_uploaded_files' );

		if ( ! empty( $uploaded_files ) && is_array( $uploaded_files ) ) {
			foreach ( $uploaded_files as $file_data ) {
				if ( ! empty( $file_data['path'] ) && file_exists( $file_data['path'] ) ) {
					wp_delete_file( $file_data['path'] );
				}
			}

			delete_transient( 'conjure_uploaded_files' );

			$this->logger->info( __( 'All uploaded files cleaned up', 'conjurewp' ) );
		}
	}

	/**
	 * Check if manual upload mode is enabled (no pre-registered import files).
	 *
	 * @return bool
	 */
	public function is_manual_upload_mode() {
		return empty( $this->conjure->import_files );
	}

	/**
	 * Allow import file types (XML, JSON, DAT, WIE) in WordPress media uploads.
	 *
	 * @param array $mimes Existing allowed MIME types.
	 * @return array Modified MIME types.
	 */
	public function allow_import_file_types( $mimes ) {
		// Add support for import file types.
		$mimes['xml']  = 'application/xml';
		$mimes['json'] = 'application/json';
		$mimes['dat']  = 'application/octet-stream';
		$mimes['wie']  = 'application/json'; // Widget import/export format.

		return apply_filters( 'conjure_allowed_import_mimes', $mimes );
	}

	/**
	 * Get the manual upload zones HTML.
	 *
	 * @return string
	 */
	public function get_manual_upload_html() {
		$uploaded_files = get_transient( 'conjure_uploaded_files' );

		$upload_options = $this->get_upload_options( is_array( $uploaded_files ) ? $uploaded_files : array() );

		ob_start();
		?>

		<?php foreach ( $upload_options as $type => $option ) : ?>
			<?php
			$has_file = ! empty( $uploaded_files[ $type ] );
			$file_info = $has_file ? $uploaded_files[ $type ] : null;
			?>

			<li class="conjure__drawer--upload__item" data-upload-type="<?php echo esc_attr( $type ); ?>">
				<div class="conjure__upload-zone-wrapper">
					
						<input 
							type="checkbox" 
							name="default_content[<?php echo esc_attr( $type ); ?>]" 
							class="checkbox checkbox-<?php echo esc_attr( $type ); ?> js-conjure-upload-checkbox" 
							id="default_content_<?php echo esc_attr( $type ); ?>" 
							value="1"
							data-manual-upload="1"
							<?php checked( $has_file ); ?>
							<?php disabled( ! $has_file ); ?>
						>
					
					<label for="default_content_<?php echo esc_attr( $type ); ?>" class="conjure__upload-label">
						<i></i>
						<span class="conjure__upload-label-content">
							<span class="conjure__upload-title">
								<?php echo esc_html( $option['title'] ); ?>
								<?php if ( ! empty( $option['tooltip'] ) ) : ?>
									<span class="hint--top hint--rounded" aria-label="<?php echo esc_attr( $option['tooltip'] ); ?>">
										<?php echo wp_kses( $this->wizard_ui->svg( array( 'icon' => 'help' ) ), $this->wizard_ui->svg_allowed_html() ); ?>
									</span>
								<?php endif; ?>
							</span>
							<span class="conjure__upload-description"><?php echo esc_html( $option['description'] ); ?></span>
						</span>
					</label>

					<?php echo $this->render_upload_zone_markup( $type, $option, $has_file, $file_info ); ?>

				</div>
			</li>

		<?php endforeach; ?>

		<?php
		return ob_get_clean();
	}

	/**
	 * Retrieve the upload configuration for each content type.
	 *
	 * @param array $uploaded_files Files stored in transient storage.
	 * @return array
	 */
	private function get_upload_options( $uploaded_files ) {
		$upload_options = array(
			'content' => array(
				'title'       => esc_html__( 'Content', 'conjurewp' ),
				'description' => esc_html__( 'Posts, pages, and site structure', 'conjurewp' ),
				'accept'      => '.xml',
			),
			'images' => array(
				'title'       => esc_html__( 'Images & Media', 'conjurewp' ),
				'description' => esc_html__( 'Import media library attachments', 'conjurewp' ),
				'tooltip'     => esc_html__( 'Uncheck if replacing images or on shared hosting to speed up import', 'conjurewp' ),
				'accept'      => '.xml',
			),
			'widgets' => array(
				'title'       => esc_html__( 'Widgets', 'conjurewp' ),
				'description' => esc_html__( 'Sidebar widgets and widget areas', 'conjurewp' ),
				'accept'      => '.json,.wie',
			),
			'options' => array(
				'title'       => esc_html__( 'Theme Options', 'conjurewp' ),
				'description' => esc_html__( 'Customiser settings and theme options', 'conjurewp' ),
				'accept'      => '.dat,.json',
			),
			'sliders' => array(
				'title'       => esc_html__( 'Revolution Slider', 'conjurewp' ),
				'description' => esc_html__( 'Revolution Slider packages (.zip)', 'conjurewp' ),
				'accept'      => '.zip',
			),
			'redux' => array(
				'title'       => esc_html__( 'Redux Options', 'conjurewp' ),
				'description' => esc_html__( 'Redux framework settings', 'conjurewp' ),
				'accept'      => '.json',
			),
			'menus' => array(
				'title'       => esc_html__( 'Menus', 'conjurewp' ),
				'description' => esc_html__( 'Navigation menu assignments', 'conjurewp' ),
				'accept'      => '.json',
			),
		);

		return apply_filters( 'conjure_manual_upload_sections', $upload_options, $uploaded_files );
	}

	/**
	 * Render the reusable upload zone markup.
	 *
	 * @param string     $type      Upload type key.
	 * @param array      $option    Upload option configuration.
	 * @param bool       $has_file  Whether a file is already stored.
	 * @param array|null $file_info Information about the stored file.
	 * @return string
	 */
	private function render_upload_zone_markup( $type, $option, $has_file, $file_info ) {
		$file_name = $has_file && ! empty( $file_info['name'] ) ? $file_info['name'] : '';
		$file_size = $has_file && ! empty( $file_info['size'] ) ? size_format( $file_info['size'] ) : '';

		ob_start();
		?>
		<div class="conjure__upload-zone <?php echo $has_file ? 'has-file' : ''; ?>"
			data-type="<?php echo esc_attr( $type ); ?>"
			data-accept="<?php echo esc_attr( $option['accept'] ); ?>">

			<div class="conjure__upload-prompt" <?php echo $has_file ? 'style="display:none;"' : ''; ?>>
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
					<polyline points="17 8 12 3 7 8"></polyline>
					<line x1="12" y1="3" x2="12" y2="15"></line>
				</svg>
				<p class="conjure__upload-text">
					<strong><?php esc_html_e( 'Click to select file', 'conjurewp' ); ?></strong>
					<span class="conjure__upload-file-type"><?php echo esc_html( $option['accept'] ); ?></span>
				</p>
			</div>

			<div class="conjure__upload-success" style="display: <?php echo $has_file ? 'flex' : 'none'; ?>;" role="status" aria-live="polite">
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
					<polyline points="20 6 9 17 4 12"></polyline>
				</svg>
				<div class="conjure__file-info">
					<strong class="conjure__file-name"><?php echo esc_html( $file_name ); ?></strong>
					<span class="conjure__file-size"><?php echo esc_html( $file_size ); ?></span>
				</div>
				<button type="button" class="conjure__remove-file" data-type="<?php echo esc_attr( $type ); ?>" title="<?php esc_attr_e( 'Remove file', 'conjurewp' ); ?>" aria-label="<?php esc_attr_e( 'Remove uploaded file', 'conjurewp' ); ?>" <?php echo $has_file ? '' : 'style="display:none;"'; ?>>
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
						<line x1="18" y1="6" x2="6" y2="18"></line>
						<line x1="6" y1="6" x2="18" y2="18"></line>
					</svg>
				</button>
			</div>

			<div class="conjure__upload-progress" style="display:none;" role="status" aria-live="polite">
				<div class="conjure__progress-bar-small" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" aria-label="<?php esc_attr_e( 'Upload progress', 'conjurewp' ); ?>">
					<div class="conjure__progress-fill"></div>
				</div>
				<span class="conjure__upload-status"><?php esc_html_e( 'Uploading...', 'conjurewp' ); ?></span>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}

