<?php
/**
 * AJAX handlers for demo import flows.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Conjure_Import_Ajax_Handler {

	/** @var Conjure */
	private $conjure;

	/** @var Conjure_Logger */
	private $logger;

	public function __construct( $conjure ) {
		$this->conjure = $conjure;
		$this->logger  = $conjure->logger;
	}

	public function _ajax_install_plugin() {
		// Verify nonce and check permissions.
		if ( ! check_ajax_referer( 'conjure_nonce', 'wpnonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Security check failed.', 'ConjureWP' ),
				)
			);
		}

		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'You do not have permission to install plugins.', 'ConjureWP' ),
				)
			);
		}

		$slug = isset( $_POST['slug'] ) ? sanitize_key( $_POST['slug'] ) : '';

		if ( empty( $slug ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Plugin slug not provided.', 'ConjureWP' ),
				)
			);
		}

		$installer = $this->conjure->demo_plugin_manager->get_installer();

		$this->conjure->logger->info( "Installing plugin via AJAX: {$slug}" );

		// Install and activate the plugin.
		$result = $installer->install_and_activate( $slug );

		if ( is_wp_error( $result ) ) {
			$this->conjure->logger->error( 'Plugin installation failed: ' . $result->get_error_message() );

			wp_send_json_error(
				array(
					'message' => $result->get_error_message(),
				)
			);
		}

		$this->conjure->logger->info( "Successfully installed and activated plugin: {$slug}" );

		// Check if all plugins are installed.
		$selected_demo_index = get_transient( 'conjure_selected_demo_index' );
		$plugins             = $this->get_plugins( $selected_demo_index );

		// Check if there's any remaining work to do (install or activate).
		$has_work_remaining = ! empty( $plugins['install'] ) || ! empty( $plugins['activate'] );

		if ( ! $has_work_remaining ) {
			// All plugins complete, mark step as done.
			$this->conjure->mark_step_completed( 'plugins' );

			$this->conjure->logger->info( 'All plugins installed and activated, step marked as complete' );

			wp_send_json_success(
				array(
					'message'   => esc_html__( 'All plugins installed successfully!', 'ConjureWP' ),
					'done'      => true,
					'completed' => true,
				)
			);
		}

		wp_send_json_success(
			array(
				'message' => sprintf(
				 /* translators: %s: plugin slug */
					esc_html__( 'Successfully installed %s', 'ConjureWP' ),
					$slug
				),
				'done' => false,
			)
		);
	}

	/**
	 * Do content's AJAX
	 *
	 * @internal    Used as a callback.
	 */
	public function _ajax_content() {
		// Wrap the entire AJAX handler in try-catch to prevent unhandled errors.
		try {
			static $content = null;

			// Validate POST data exists.
			if ( ! isset( $_POST['selected_index'] ) ) {
				$this->conjure->logger->error( __( 'Content import AJAX missing selected_index', 'ConjureWP' ) );
				wp_send_json_error(
					array(
						'error'   => 1,
						'message' => esc_html__( 'Missing import index!', 'ConjureWP' ),
					)
				);
			}

			$selected_import = absint( wp_unslash( $_POST['selected_index'] ) );
			$content_key     = isset( $_POST['content'] ) ? sanitize_key( wp_unslash( $_POST['content'] ) ) : '';

			if ( null === $content ) {
				$content = $this->get_import_data( $selected_import );

				// Check if we got valid import data.
				if ( empty( $content ) || ! is_array( $content ) ) {
					$this->conjure->logger->error(
						__( 'Failed to get import data for selected index', 'ConjureWP' ),
						array( 'selected_import' => $selected_import )
					);
					wp_send_json_error(
						array(
							'error'   => 1,
							'message' => esc_html__( 'Failed to load import configuration!', 'ConjureWP' ),
						)
					);
				}
			}

			if ( ! check_ajax_referer( 'conjure_nonce', 'wpnonce' ) || empty( $content_key ) || ! isset( $content[ $content_key ] ) ) {
				$this->conjure->logger->error(
					__( 'The content importer AJAX call failed to start, because of incorrect data', 'ConjureWP' ),
					array(
						'content_key' => ! empty( $content_key ) ? $content_key : 'not set',
						'available_keys' => array_keys( $content ),
					)
				);

				wp_send_json_error(
					array(
						'error'   => 1,
						'message' => esc_html__( 'Invalid content!', 'ConjureWP' ),
					)
				);
			}

			$json         = false;
			$this_content = $content[ $content_key ];

			if ( isset( $_POST['proceed'] ) ) {
				if ( 'content' === $content_key && isset( $this->conjure->importer->options ) ) {
					$fetch_attachments = true;
					if ( isset( $_POST['fetch_attachments'] ) ) {
						$fetch_attachments = (bool) absint( wp_unslash( $_POST['fetch_attachments'] ) );
					}
					$this->conjure->importer->options['fetch_attachments'] = $fetch_attachments;
				}

				if ( is_callable( $this_content['install_callback'] ) ) {
					$this->conjure->logger->info(
						__( 'The content import AJAX call will be executed with this import data', 'ConjureWP' ),
						array(
							'title' => $this_content['title'],
							'data'  => $this_content['data'],
						)
					);

					// Wrap the callback execution in try-catch.
					try {
						// Use output buffering to catch any unexpected output.
						ob_start();
						$logs = call_user_func( $this_content['install_callback'], $this_content['data'] );
						$callback_output = ob_get_clean();

						if ( ! empty( $callback_output ) ) {
							$this->conjure->logger->warning(
								__( 'Import callback produced output', 'ConjureWP' ),
								array( 'output' => $callback_output )
							);
						}

						if ( is_wp_error( $logs ) ) {
							wp_send_json_error(
								array(
									'error'   => 1,
									'message' => conjurewp_safe_import_message( $logs, $this_content['pending'] ),
									'logs'    => '',
									'errors'  => conjurewp_safe_import_message( $logs, $this_content['pending'] ),
								)
							);
						}

						if ( $logs ) {
							$json = array(
								'done'    => 1,
								'message' => $this_content['success'],
								'debug'   => '',
								'logs'    => $logs,
								'errors'  => '',
							);

							// The content import ended, so we should mark that all posts were imported.
							if ( 'content' === $content_key ) {
								$json['num_of_imported_posts'] = 'all';
							}
						} else {
							$this->conjure->logger->warning(
								__( 'Import callback returned empty/false result', 'ConjureWP' ),
								array( 'content_type' => $content_key )
							);
						}
					} catch ( \Exception $e ) {
						$error_message = sprintf(
							/* translators: %s: Exception message. */
							__( 'Exception during content import: %s', 'ConjureWP' ),
							conjurewp_safe_error_message( $e )
						);
						$this->conjure->logger->error( $error_message, array( 'trace' => $e->getTraceAsString() ) );

						wp_send_json_error(
							array(
								'error'   => 1,
								'message' => $error_message,
								'logs'    => '',
								'errors'  => conjurewp_safe_error_message( $e ),
							)
						);
					} catch ( \Error $e ) {
						$error_message = sprintf(
							/* translators: %s: Fatal error message. */
							__( 'Fatal error during content import: %s', 'ConjureWP' ),
							conjurewp_safe_error_message( $e )
						);
						$this->conjure->logger->error( $error_message, array( 'trace' => $e->getTraceAsString() ) );

						wp_send_json_error(
							array(
								'error'   => 1,
								'message' => $error_message,
								'logs'    => '',
								'errors'  => conjurewp_safe_error_message( $e ),
							)
						);
					}
				} else {
					$this->conjure->logger->error(
						__( 'Import callback is not callable', 'ConjureWP' ),
						array(
							'callback' => $this_content['install_callback'],
							'content_type' => $content_key,
						)
					);
				}
			} else {
				$json = array(
					'url'            => admin_url( 'admin-ajax.php' ),
					'action'         => 'conjure_content',
					'proceed'        => 'true',
					'content'        => $content_key,
					'_wpnonce'       => wp_create_nonce( 'conjure_nonce' ),
					'selected_index' => $selected_import,
					'message'        => $this_content['installing'],
					'logs'           => '',
					'errors'         => '',
				);
			}

			if ( $json ) {
				$json['hash'] = md5( serialize( $json ) );
				wp_send_json( $json );
			} else {
				$this->conjure->logger->error(
					__( 'The content import AJAX call failed with this passed data', 'ConjureWP' ),
					array(
						'selected_content_index' => $selected_import,
						'importing_content'      => $content_key,
						'importing_data'         => $this_content['data'],
					)
				);

				wp_send_json(
					array(
						'error'   => 1,
						'message' => esc_html__( 'Error', 'ConjureWP' ),
						'logs'    => '',
						'errors'  => '',
					)
				);
			}
		} catch ( \Exception $e ) {
			$this->conjure->logger->error(
				__( 'Uncaught exception in content import AJAX handler', 'ConjureWP' ),
				array(
					'message' => $e->getMessage(),
					'trace' => $e->getTraceAsString(),
				)
			);

			wp_send_json_error(
				array(
					'error'   => 1,
					'message' => sprintf(
						/* translators: %s: error message */
						esc_html__( 'Import error: %s', 'ConjureWP' ),
						conjurewp_safe_error_message( $e )
					),
					'logs'    => '',
					'errors'  => conjurewp_safe_error_message( $e ),
				)
			);
		} catch ( \Error $e ) {
			$this->conjure->logger->error(
				__( 'Fatal error in content import AJAX handler', 'ConjureWP' ),
				array(
					'message' => $e->getMessage(),
					'trace' => $e->getTraceAsString(),
				)
			);

			wp_send_json_error(
				array(
					'error'   => 1,
					'message' => sprintf(
						/* translators: %s: error message */
						esc_html__( 'Fatal import error: %s', 'ConjureWP' ),
						conjurewp_safe_error_message( $e )
					),
					'logs'    => '',
					'errors'  => conjurewp_safe_error_message( $e ),
				)
			);
		}
	}


	/**
	 * AJAX call to retrieve total items (posts, pages, CPT, attachments) for the content import.
	 */
	public function _ajax_get_total_content_import_items() {
		// Wrap in try-catch to prevent errors from breaking the AJAX response.
		try {
			// Catch any output from plugins that might interfere with JSON response.
			ob_start();

			if ( ! check_ajax_referer( 'conjure_nonce', 'wpnonce' ) || empty( $_POST['selected_index'] ) ) {
				ob_end_clean();
				$this->conjure->logger->error( __( 'The content importer AJAX call for retrieving total content import items failed to start, because of incorrect data.', 'ConjureWP' ) );

				wp_send_json_error(
					array(
						'error'   => 1,
						'message' => esc_html__( 'Invalid data!', 'ConjureWP' ),
					)
				);
			}

			$selected_import = intval( $_POST['selected_index'] );
			$import_files    = $this->conjure->get_import_files_paths( $selected_import );

			// Check if we have valid content file.
			if ( empty( $import_files['content'] ) || ! file_exists( $import_files['content'] ) ) {
				ob_end_clean();
				$this->conjure->logger->warning( 'Content file not found for counting import items' );
				wp_send_json_success( 0 );
			}

			$total_items = $this->conjure->importer->get_number_of_posts_to_import( $import_files['content'] );

			// Clean any buffered output before sending JSON.
			$buffered_output = ob_get_clean();
			if ( ! empty( $buffered_output ) ) {
				$this->conjure->logger->warning(
					'Unexpected output during content item counting',
					array( 'output' => $buffered_output )
				);
			}

			wp_send_json_success( $total_items );

		} catch ( \Exception $e ) {
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}

			$this->conjure->logger->error(
				'Exception in _ajax_get_total_content_import_items',
				array(
					'message' => $e->getMessage(),
					'trace' => $e->getTraceAsString(),
				)
			);

			// Return 0 instead of error to allow import to proceed without progress bar.
			wp_send_json_success( 0 );

		} catch ( \Error $e ) {
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}

			$this->conjure->logger->error(
				'Fatal error in _ajax_get_total_content_import_items',
				array(
					'message' => $e->getMessage(),
					'trace' => $e->getTraceAsString(),
				)
			);

			// Return 0 instead of error to allow import to proceed without progress bar.
			wp_send_json_success( 0 );
		}
	}

	/**
	 * AJAX handler for live health metrics checks.
	 */
	public function _ajax_get_health_metrics() {
		// Wrap in try-catch to prevent errors from breaking the AJAX response.
		try {
			// Catch any output from plugins that might interfere with JSON response.
			ob_start();

			if ( ! check_ajax_referer( 'conjure_nonce', 'wpnonce', false ) ) {
				ob_end_clean();
				wp_send_json_error(
					array(
						'message' => esc_html__( 'Security check failed.', 'ConjureWP' ),
					)
				);
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				ob_end_clean();
				wp_send_json_error(
					array(
						'message' => esc_html__( 'You do not have permission to perform this action.', 'ConjureWP' ),
					)
				);
			}

			require_once trailingslashit( $this->conjure->base_path ) . $this->conjure->directory . '/includes/class-conjure-server-health.php';
			$server_health = new Conjure_Server_Health();

			$metrics = $server_health->get_telemetry_metrics();

			// Clean any buffered output before sending JSON.
			$buffered_output = ob_get_clean();
			if ( ! empty( $buffered_output ) ) {
				$this->conjure->logger->warning(
					'Unexpected output during health metrics check',
					array( 'output' => $buffered_output )
				);
			}

			wp_send_json_success( $metrics );

		} catch ( \Exception $e ) {
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}

			$this->conjure->logger->error(
				'Exception in _ajax_get_health_metrics',
				array(
					'message' => $e->getMessage(),
					'trace' => $e->getTraceAsString(),
				)
			);

			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %s: error message */
						esc_html__( 'Health check error: %s', 'ConjureWP' ),
						conjurewp_safe_error_message( $e )
					),
				)
			);

		} catch ( \Error $e ) {
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}

			$this->conjure->logger->error(
				'Fatal error in _ajax_get_health_metrics',
				array(
					'message' => $e->getMessage(),
					'trace' => $e->getTraceAsString(),
				)
			);

			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %s: error message */
						esc_html__( 'Fatal health check error: %s', 'ConjureWP' ),
						conjurewp_safe_error_message( $e )
					),
				)
			);
		}
	}


	/**
	 * Get import data from the selected import.
	 * Which data does the selected import have for the import.
	 *
	 * @param int $selected_import_index The index of the predefined demo import.
	 *
	 * @return bool|array
	 */
	public function get_import_data_info( $selected_import_index = 0 ) {
		$import_data = array(
			'content'      => false,
			'widgets'      => false,
			'options'      => false,
			'sliders'      => false,
			'redux'        => false,
			'acf_json'     => false,
			'gf_forms'     => false,
			'gf_entries'   => false,
			'after_import' => false,
		);

		if ( class_exists( 'Conjure_Connector_Upload_Registry' ) ) {
			$import_data = array_merge( $import_data, Conjure_Connector_Upload_Registry::get_import_info_defaults() );
		}

		// If in manual upload mode (no registered files), return empty structure
		if ( empty( $this->conjure->import_files[ $selected_import_index ] ) ) {
			// Check if we're in manual upload mode
			if ( $this->conjure->is_manual_upload_mode() ) {
				// Return all false - manual upload will be handled by the UI
				return $import_data;
			}
			return false;
		}

		if (
			! empty( $this->conjure->import_files[ $selected_import_index ]['import_file_url'] ) ||
			! empty( $this->conjure->import_files[ $selected_import_index ]['local_import_file'] )
		) {
			$import_data['content'] = true;
		}

		if (
			! empty( $this->conjure->import_files[ $selected_import_index ]['import_widget_file_url'] ) ||
			! empty( $this->conjure->import_files[ $selected_import_index ]['local_import_widget_file'] )
		) {
			$import_data['widgets'] = true;
		}

		if (
			! empty( $this->conjure->import_files[ $selected_import_index ]['import_customizer_file_url'] ) ||
			! empty( $this->conjure->import_files[ $selected_import_index ]['local_import_customizer_file'] )
		) {
			$import_data['options'] = true;
		}

		if (
			! empty( $this->conjure->import_files[ $selected_import_index ]['import_rev_slider_file_url'] ) ||
			! empty( $this->conjure->import_files[ $selected_import_index ]['local_import_rev_slider_file'] )
		) {
			$import_data['sliders'] = true;
		}

		if (
			! empty( $this->conjure->import_files[ $selected_import_index ]['import_redux'] ) ||
			! empty( $this->conjure->import_files[ $selected_import_index ]['local_import_redux'] )
		) {
			$import_data['redux'] = true;
		}

		if (
			! empty( $this->conjure->import_files[ $selected_import_index ]['import_acf_json'] ) ||
			! empty( $this->conjure->import_files[ $selected_import_index ]['local_import_acf_json'] )
		) {
			$import_data['acf_json'] = true;
		}

		if ( ! empty( $this->conjure->import_files[ $selected_import_index ]['local_import_gf_forms'] ) ) {
			$import_data['gf_forms'] = true;
		}

		if ( ! empty( $this->conjure->import_files[ $selected_import_index ]['local_import_gf_entries'] ) ) {
			$import_data['gf_entries'] = true;
		}

		if ( class_exists( 'Conjure_Connector_Upload_Registry' ) ) {
			foreach ( Conjure_Connector_Upload_Registry::get_definitions() as $slug => $definition ) {
				if ( ! empty( $this->conjure->import_files[ $selected_import_index ][ $definition['local_config_key'] ] ) ) {
					$import_data[ $slug ] = true;
				}
			}
		}

		if ( false !== has_action( 'conjure_after_all_import' ) ) {
			$import_data['after_import'] = true;
		}

		return $import_data;
	}


	/**
	 * Get the import files/data.
	 *
	 * @param int $selected_import_index The index of the predefined demo import.
	 *
	 * @return    array
	 */
	private function get_import_data( $selected_import_index = 0 ) {
		$content = array();

		$import_files = $this->conjure->get_import_files_paths( $selected_import_index );

		if ( ! empty( $import_files['images'] ) ) {
			$content['images'] = array(
				'title'            => esc_html__( 'Images & Media', 'ConjureWP' ),
				'description'      => esc_html__( 'Media library files from zip.', 'ConjureWP' ),
				'pending'          => esc_html__( 'Pending', 'ConjureWP' ),
				'installing'       => esc_html__( 'Installing', 'ConjureWP' ),
				'success'          => esc_html__( 'Success', 'ConjureWP' ),
				'install_callback' => array( $this->conjure, 'import_media_zip' ),
				'checked'          => $this->conjure->is_possible_upgrade() ? 0 : 1,
				'data'             => $import_files['images'],
			);
		}

		if ( ! empty( $import_files['content'] ) ) {
			$content['content'] = array(
				'title'            => esc_html__( 'Content', 'ConjureWP' ),
				'description'      => esc_html__( 'Demo content data.', 'ConjureWP' ),
				'pending'          => esc_html__( 'Pending', 'ConjureWP' ),
				'installing'       => esc_html__( 'Installing', 'ConjureWP' ),
				'success'          => esc_html__( 'Success', 'ConjureWP' ),
				'checked'          => $this->conjure->is_possible_upgrade() ? 0 : 1,
				'install_callback' => array( $this->conjure->importer, 'import' ),
				'data'             => $import_files['content'],
			);
		}

		if ( ! empty( $import_files['widgets'] ) ) {
			$content['widgets'] = array(
				'title'            => esc_html__( 'Widgets', 'ConjureWP' ),
				'description'      => esc_html__( 'Sample widgets data.', 'ConjureWP' ),
				'pending'          => esc_html__( 'Pending', 'ConjureWP' ),
				'installing'       => esc_html__( 'Installing', 'ConjureWP' ),
				'success'          => esc_html__( 'Success', 'ConjureWP' ),
				'install_callback' => array( 'Conjure_Widget_Importer', 'import' ),
				'checked'          => $this->conjure->is_possible_upgrade() ? 0 : 1,
				'data'             => $import_files['widgets'],
			);
		}

		// Revolution Slider import (available in free and premium).
		$can_use_advanced_imports = class_exists( 'Conjure_Freemius' ) ? Conjure_Freemius::can_use_advanced_imports() : true;

		if ( ! empty( $import_files['sliders'] ) && $can_use_advanced_imports ) {
			$content['sliders'] = array(
				'title'            => esc_html__( 'Revolution Slider', 'ConjureWP' ),
				'description'      => esc_html__( 'Sample Revolution sliders data.', 'ConjureWP' ),
				'pending'          => esc_html__( 'Pending', 'ConjureWP' ),
				'installing'       => esc_html__( 'Installing', 'ConjureWP' ),
				'success'          => esc_html__( 'Success', 'ConjureWP' ),
				'install_callback' => array( $this->conjure, 'import_revolution_sliders' ),
				'checked'          => $this->conjure->is_possible_upgrade() ? 0 : 1,
				'data'             => $import_files['sliders'],
			);
		}

		if ( ! empty( $import_files['options'] ) ) {
			$content['options'] = array(
				'title'            => esc_html__( 'Options', 'ConjureWP' ),
				'description'      => esc_html__( 'Sample theme options data.', 'ConjureWP' ),
				'pending'          => esc_html__( 'Pending', 'ConjureWP' ),
				'installing'       => esc_html__( 'Installing', 'ConjureWP' ),
				'success'          => esc_html__( 'Success', 'ConjureWP' ),
				'install_callback' => array( 'Conjure_Customizer_Importer', 'import' ),
				'checked'          => $this->conjure->is_possible_upgrade() ? 0 : 1,
				'data'             => $import_files['options'],
			);
		}

		// Redux Framework options import (available in free and premium).
		if ( ! empty( $import_files['redux'] ) && $can_use_advanced_imports ) {
			$content['redux'] = array(
				'title'            => esc_html__( 'Redux Options', 'ConjureWP' ),
				'description'      => esc_html__( 'Redux framework options.', 'ConjureWP' ),
				'pending'          => esc_html__( 'Pending', 'ConjureWP' ),
				'installing'       => esc_html__( 'Installing', 'ConjureWP' ),
				'success'          => esc_html__( 'Success', 'ConjureWP' ),
				'install_callback' => array( 'Conjure_Redux_Importer', 'import' ),
				'checked'          => $this->conjure->is_possible_upgrade() ? 0 : 1,
				'data'             => $import_files['redux'],
			);
		}

		if ( ! empty( $import_files['acf_json'] ) && ( class_exists( 'ACF' ) || function_exists( 'acf' ) ) ) {
			$content['acf_json'] = array(
				'title'            => esc_html__( 'ACF JSON', 'ConjureWP' ),
				'description'      => esc_html__( 'ACF field group local JSON files.', 'ConjureWP' ),
				'pending'          => esc_html__( 'Pending', 'ConjureWP' ),
				'installing'       => esc_html__( 'Installing', 'ConjureWP' ),
				'success'          => esc_html__( 'Success', 'ConjureWP' ),
				'install_callback' => array( 'Conjure_ACF_JSON_Importer', 'import' ),
				'checked'          => $this->conjure->is_possible_upgrade() ? 0 : 1,
				'data'             => $import_files['acf_json'],
			);
		}

		if ( ! empty( $import_files['gf_forms'] ) && ( class_exists( 'GFAPI' ) || class_exists( 'GFForms' ) ) ) {
			$content['gf_forms'] = array(
				'title'            => esc_html__( 'Gravity Forms', 'ConjureWP' ),
				'description'      => esc_html__( 'Imported Gravity Forms definitions.', 'ConjureWP' ),
				'pending'          => esc_html__( 'Pending', 'ConjureWP' ),
				'installing'       => esc_html__( 'Installing', 'ConjureWP' ),
				'success'          => esc_html__( 'Success', 'ConjureWP' ),
				'install_callback' => array( 'Conjure_Gravity_Forms_Importer', 'import_forms' ),
				'checked'          => $this->conjure->is_possible_upgrade() ? 0 : 1,
				'data'             => $import_files['gf_forms'],
			);
		}

		if ( ! empty( $import_files['gf_entries'] ) && ( class_exists( 'GFAPI' ) || class_exists( 'GFForms' ) ) ) {
			$content['gf_entries'] = array(
				'title'            => esc_html__( 'Gravity Forms Entries', 'ConjureWP' ),
				'description'      => esc_html__( 'Imported Gravity Forms submission entries.', 'ConjureWP' ),
				'pending'          => esc_html__( 'Pending', 'ConjureWP' ),
				'installing'       => esc_html__( 'Installing', 'ConjureWP' ),
				'success'          => esc_html__( 'Success', 'ConjureWP' ),
				'install_callback' => array( 'Conjure_Gravity_Forms_Importer', 'import_entries' ),
				'checked'          => $this->conjure->is_possible_upgrade() ? 0 : 1,
				'data'             => $import_files['gf_entries'],
			);
		}

		if ( class_exists( 'Conjure_Connector_Upload_Registry' ) ) {
			$content = Conjure_Connector_Upload_Registry::build_import_content_steps( $content, $import_files, $this->conjure );
		}

		if ( false !== has_action( 'conjure_after_all_import' ) ) {
			$content['after_import'] = array(
				'title'            => esc_html__( 'After import setup', 'ConjureWP' ),
				'description'      => esc_html__( 'After import setup.', 'ConjureWP' ),
				'pending'          => esc_html__( 'Pending', 'ConjureWP' ),
				'installing'       => esc_html__( 'Installing', 'ConjureWP' ),
				'success'          => esc_html__( 'Success', 'ConjureWP' ),
				'install_callback' => array( $this->conjure->hooks, 'after_all_import_action' ),
				'checked'          => $this->conjure->is_possible_upgrade() ? 0 : 1,
				'data'             => $selected_import_index,
			);
		}

		// Hook at line 2574: Allow filtering of base content before returning.
		// Health telemetry is handled separately in drawer rendering, but this hook
		// can be used to add health check items to import content if needed.
		$content = apply_filters( 'conjure_get_base_content', $content, $this->conjure );

		return $content;
	}

	/**
	 * AJAX handler to refresh import step list HTML for the selected demo.
	 *
	 * @return void
	 */
	public function update_selected_import_data_info() {
		// Wrap in try-catch to prevent errors from breaking the AJAX response.
		try {
			// Catch any output from plugins that might interfere with JSON response.
			ob_start();

			if ( ! check_ajax_referer( 'conjure_nonce', 'wpnonce', false ) ) {
				ob_end_clean();
				wp_send_json_error( array( 'message' => esc_html__( 'Security check failed.', 'ConjureWP' ) ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				ob_end_clean();
				wp_send_json_error( array( 'message' => esc_html__( 'You do not have permission to perform this action.', 'ConjureWP' ) ) );
			}

			$selected_index = ! isset( $_POST['selected_index'] ) ? false : intval( $_POST['selected_index'] );

			if ( false === $selected_index ) {
				ob_end_clean();
				wp_send_json_error( array( 'message' => esc_html__( 'Invalid demo selection.', 'ConjureWP' ) ) );
			}

			// Store the selected demo index for demo-specific plugin installation.
			set_transient( 'conjure_selected_demo_index', $selected_index, HOUR_IN_SECONDS );

			$this->conjure->logger->info( 'Demo selected: ' . $selected_index );

			$import_info = $this->get_import_data_info( $selected_index );

			// Check if we got valid import info.
			if ( false === $import_info ) {
				ob_end_clean();
				$this->conjure->logger->error( 'Failed to get import data info for demo: ' . $selected_index );
				wp_send_json_error( array( 'message' => esc_html__( 'Failed to load demo configuration.', 'ConjureWP' ) ) );
			}

			$import_info_html = $this->get_import_steps_html( $import_info );

			// Get demo-specific plugins if available.
			$demo_plugins = array();
			if ( $this->conjure->demo_plugin_manager ) {
				$demo_plugins = $this->conjure->demo_plugin_manager->get_demo_plugins_with_status( $selected_index, $this->conjure->import_files );
			}

			// Clean any buffered output before sending JSON.
			$buffered_output = ob_get_clean();
			if ( ! empty( $buffered_output ) ) {
				$this->conjure->logger->warning(
					'Unexpected output during demo selection update',
					array( 'output' => $buffered_output )
				);
			}

			wp_send_json_success(
				array(
					'import_info_html' => $import_info_html,
					'demo_plugins'     => $demo_plugins,
					'has_plugins'      => ! empty( $demo_plugins['all'] ),
				)
			);

		} catch ( \Exception $e ) {
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}

			$this->conjure->logger->error(
				'Exception in update_selected_import_data_info',
				array(
					'message' => $e->getMessage(),
					'trace' => $e->getTraceAsString(),
				)
			);

			wp_send_json_error(
				array(
					'message' => sprintf(
					 /* translators: %s: error message */
						esc_html__( 'Error loading demo: %s', 'ConjureWP' ),
						conjurewp_safe_error_message( $e )
					),
				)
			);

		} catch ( \Error $e ) {
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}

			$this->conjure->logger->error(
				'Fatal error in update_selected_import_data_info',
				array(
					'message' => $e->getMessage(),
					'trace' => $e->getTraceAsString(),
				)
			);

			wp_send_json_error(
				array(
					'message' => sprintf(
					 /* translators: %s: error message */
						esc_html__( 'Fatal error loading demo: %s', 'ConjureWP' ),
						conjurewp_safe_error_message( $e )
					),
				)
			);
		}
	}

	/**
	 * Get the import steps HTML output.
	 *
	 * @param array $import_info The import info to prepare the HTML for.
	 *
	 * @return string
	 */
	public function get_import_steps_html( $import_info ) {
		// Validate input.
		if ( ! is_array( $import_info ) || empty( $import_info ) ) {
			$this->conjure->logger->warning( 'get_import_steps_html called with invalid import_info' );
			return '<li class="conjure__drawer--import-content__list-item">No import options available.</li>';
		}

		$uploaded_files  = get_transient( 'conjure_uploaded_files' );
		$upload_handler  = $this->conjure->file_upload_handler;
		$upload_options  = $upload_handler
			? $upload_handler->get_manual_upload_sections( is_array( $uploaded_files ) ? $uploaded_files : array() )
			: array();

		ob_start();
		?>
			<?php foreach ( $import_info as $slug => $available ) : ?>
				<?php
				if ( ! $available ) {
					continue;
				}

				$has_upload_section = isset( $upload_options[ $slug ] );
				$has_file           = $has_upload_section && ! empty( $uploaded_files[ $slug ] );
				$file_info          = $has_file ? $uploaded_files[ $slug ] : null;
				$list_item_classes  = array(
					'conjure__drawer--import-content__list-item',
					'status',
					'status--Pending',
				);

				if ( $has_upload_section ) {
					$list_item_classes[] = 'conjure__drawer--upload__item';
					$list_item_classes[] = 'has-inline-upload';
				}
				?>

				<li class="<?php echo esc_attr( implode( ' ', $list_item_classes ) ); ?>" data-content="<?php echo esc_attr( $slug ); ?>" data-upload-type="<?php echo esc_attr( $slug ); ?>">
					<div class="conjure__upload-zone-wrapper">
						<input type="checkbox" name="default_content[<?php echo esc_attr( $slug ); ?>]" class="checkbox checkbox-<?php echo esc_attr( $slug ); ?> js-conjure-upload-checkbox" id="default_content_<?php echo esc_attr( $slug ); ?>" value="1">
						<label for="default_content_<?php echo esc_attr( $slug ); ?>">
							<i></i><span><?php echo esc_html( ucfirst( str_replace( '_', ' ', $slug ) ) ); ?></span>
						</label>

						<?php if ( $has_upload_section && $upload_handler ) : ?>
							<?php echo wp_kses_post( $upload_handler->render_upload_zone_markup( $slug, $upload_options[ $slug ], $has_file, $file_info ) ); ?>
						<?php endif; ?>
					</div>
				</li>

			<?php endforeach; ?>
		<?php

		return ob_get_clean();
	}


	/**
	 * AJAX call for cleanup after the importing steps are done -> import finished.
	 */
	public function import_finished() {
		// Wrap in try-catch to prevent errors from breaking the AJAX response.
		try {
			// Catch any output from plugins that might interfere with JSON response.
			ob_start();

			if ( ! check_ajax_referer( 'conjure_nonce', 'wpnonce', false ) ) {
				ob_end_clean();
				wp_send_json_error( array( 'message' => esc_html__( 'Security check failed.', 'ConjureWP' ) ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				ob_end_clean();
				wp_send_json_error( array( 'message' => esc_html__( 'You do not have permission to perform this action.', 'ConjureWP' ) ) );
			}

			delete_transient( 'conjure_import_file_base_name' );
			$this->conjure->cleanup_uploaded_files();

			// Mark content import step as completed.
			$this->conjure->mark_step_completed( 'content' );

			// Clean any buffered output before sending JSON.
			$buffered_output = ob_get_clean();
			if ( ! empty( $buffered_output ) ) {
				$this->conjure->logger->warning(
					'Unexpected output during import finish cleanup',
					array( 'output' => $buffered_output )
				);
			}

			wp_send_json_success();

		} catch ( \Exception $e ) {
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}

			$this->conjure->logger->error(
				'Exception in import_finished',
				array(
					'message' => $e->getMessage(),
					'trace' => $e->getTraceAsString(),
				)
			);

			wp_send_json_error(
				array(
					'message' => sprintf(
					 /* translators: %s: error message */
						esc_html__( 'Error finishing import: %s', 'ConjureWP' ),
						conjurewp_safe_error_message( $e )
					),
				)
			);

		} catch ( \Error $e ) {
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}

			$this->conjure->logger->error(
				'Fatal error in import_finished',
				array(
					'message' => $e->getMessage(),
					'trace' => $e->getTraceAsString(),
				)
			);

			wp_send_json_error(
				array(
					'message' => sprintf(
					 /* translators: %s: error message */
						esc_html__( 'Fatal error finishing import: %s', 'ConjureWP' ),
						conjurewp_safe_error_message( $e )
					),
				)
			);
		}
	}

}
