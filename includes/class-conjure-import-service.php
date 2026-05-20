<?php
/**
 * Resolves demo import file paths (local, remote, uploaded).
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Import path resolution extracted from the main Conjure class.
 */
class Conjure_Import_Service {

	/**
	 * Main Conjure instance.
	 *
	 * @var Conjure
	 */
	private $conjure;

	/**
	 * Constructor.
	 *
	 * @param Conjure $conjure Main Conjure instance.
	 */
	public function __construct( $conjure ) {
		$this->conjure = $conjure;
	}

	/**
	 * Set the import file base name (persisted in a transient).
	 */
	public function set_import_file_base_name() {
		$existing_name = get_transient( 'conjure_import_file_base_name' );

		if ( ! empty( $existing_name ) ) {
			$this->conjure->import_file_base_name = $existing_name;
		} else {
			$this->conjure->import_file_base_name = gmdate( 'Y-m-d__H-i-s' );
		}

		set_transient( 'conjure_import_file_base_name', $this->conjure->import_file_base_name, MINUTE_IN_SECONDS );
	}

	/**
	 * Get resolved import file paths for a demo index.
	 *
	 * @param int $selected_import_index Demo index.
	 * @return array
	 */
	public function get_import_files_paths( $selected_import_index ) {
		$selected_import_data = empty( $this->conjure->import_files[ $selected_import_index ] ) ? false : $this->conjure->import_files[ $selected_import_index ];

		if ( empty( $selected_import_data ) ) {
			$uploaded_files = get_transient( 'conjure_uploaded_files' );

			if ( ! empty( $uploaded_files ) && is_array( $uploaded_files ) ) {
				$import_files = array_merge(
					array(
						'content'   => '',
						'widgets'   => '',
						'options'   => '',
						'redux'     => array(),
						'sliders'   => '',
						'images'    => '',
						'menus'     => '',
						'acf_json'  => '',
						'gf_forms'  => '',
						'gf_entries' => '',
					),
					class_exists( 'Conjure_Connector_Upload_Registry' ) ? Conjure_Connector_Upload_Registry::get_default_import_paths() : array()
				);

				foreach ( $uploaded_files as $type => $file_data ) {
					if ( ! isset( $import_files[ $type ] ) || empty( $file_data['path'] ) || ! file_exists( $file_data['path'] ) ) {
						continue;
					}

					if ( 'redux' === $type ) {
						$import_files['redux'][] = array(
							'option_name' => 'redux_option_name',
							'file_path'   => $file_data['path'],
						);
					} else {
						$import_files[ $type ] = $file_data['path'];
					}
				}

				return $import_files;
			}

			return array();
		}

		$this->set_import_file_base_name();

		$base_file_name = $this->conjure->import_file_base_name;
		$import_files = array_merge(
			array(
				'content'    => '',
				'widgets'    => '',
				'options'    => '',
				'redux'      => array(),
				'sliders'    => '',
				'acf_json'   => '',
				'gf_forms'   => '',
				'gf_entries' => '',
			),
			class_exists( 'Conjure_Connector_Upload_Registry' ) ? Conjure_Connector_Upload_Registry::get_default_import_paths() : array()
		);

		$downloader = new Conjure_Downloader();

		if ( empty( $selected_import_data['import_file_url'] ) ) {
			if ( ! empty( $selected_import_data['local_import_file'] ) && file_exists( $selected_import_data['local_import_file'] ) ) {
				$import_files['content'] = $selected_import_data['local_import_file'];
			}
		} else {
			$content_filename = 'content-' . $base_file_name . '.xml';
			$import_files['content'] = $downloader->fetch_existing_file( $content_filename );

			if ( empty( $import_files['content'] ) ) {
				$import_files['content'] = $downloader->download_file( $selected_import_data['import_file_url'], $content_filename );
			}

			if ( is_wp_error( $import_files['content'] ) ) {
				$import_files['content'] = '';
			}
		}

		if ( ! empty( $selected_import_data['import_widget_file_url'] ) ) {
			$widget_filename = 'widgets-' . $base_file_name . '.json';
			$import_files['widgets'] = $downloader->fetch_existing_file( $widget_filename );

			if ( empty( $import_files['widgets'] ) ) {
				$import_files['widgets'] = $downloader->download_file( $selected_import_data['import_widget_file_url'], $widget_filename );
			}

			if ( is_wp_error( $import_files['widgets'] ) ) {
				$import_files['widgets'] = '';
			}
		} elseif ( ! empty( $selected_import_data['local_import_widget_file'] ) && file_exists( $selected_import_data['local_import_widget_file'] ) ) {
			$import_files['widgets'] = $selected_import_data['local_import_widget_file'];
		}

		if ( ! empty( $selected_import_data['import_customizer_file_url'] ) ) {
			$customizer_filename = 'options-' . $base_file_name . '.dat';
			$import_files['options'] = $downloader->fetch_existing_file( $customizer_filename );

			if ( empty( $import_files['options'] ) ) {
				$import_files['options'] = $downloader->download_file( $selected_import_data['import_customizer_file_url'], $customizer_filename );
			}

			if ( is_wp_error( $import_files['options'] ) ) {
				$import_files['options'] = '';
			}
		} elseif ( ! empty( $selected_import_data['local_import_customizer_file'] ) && file_exists( $selected_import_data['local_import_customizer_file'] ) ) {
			$import_files['options'] = $selected_import_data['local_import_customizer_file'];
		}

		if ( ! empty( $selected_import_data['import_rev_slider_file_url'] ) ) {
			$rev_slider_filename = 'slider-' . $base_file_name . '.zip';
			$import_files['sliders'] = $downloader->fetch_existing_file( $rev_slider_filename );

			if ( empty( $import_files['sliders'] ) ) {
				$import_files['sliders'] = $downloader->download_file( $selected_import_data['import_rev_slider_file_url'], $rev_slider_filename );
			}

			if ( is_wp_error( $import_files['sliders'] ) ) {
				$import_files['sliders'] = '';
			}
		} elseif ( ! empty( $selected_import_data['local_import_rev_slider_file'] ) && file_exists( $selected_import_data['local_import_rev_slider_file'] ) ) {
			$import_files['sliders'] = $selected_import_data['local_import_rev_slider_file'];
		}

		if ( ! empty( $selected_import_data['import_redux'] ) ) {
			$redux_items = array();

			foreach ( $selected_import_data['import_redux'] as $index => $redux_item ) {
				$redux_filename = 'redux-' . $index . '-' . $base_file_name . '.json';
				$file_path      = $downloader->fetch_existing_file( $redux_filename );

				if ( empty( $file_path ) ) {
					$file_path = $downloader->download_file( $redux_item['file_url'], $redux_filename );
				}

				if ( is_wp_error( $file_path ) ) {
					$file_path = '';
				}

				$redux_items[] = array(
					'option_name' => $redux_item['option_name'],
					'file_path'   => $file_path,
				);
			}

			$import_files['redux'] = $redux_items;
		} elseif ( ! empty( $selected_import_data['local_import_redux'] ) ) {
			$redux_items = array();

			foreach ( $selected_import_data['local_import_redux'] as $redux_item ) {
				if ( file_exists( $redux_item['file_path'] ) ) {
					$redux_items[] = $redux_item;
				}
			}

			$import_files['redux'] = $redux_items;
		}

		if ( ! empty( $selected_import_data['local_import_acf_json'] ) ) {
			$acf_json_path = $selected_import_data['local_import_acf_json'];

			if ( is_dir( $acf_json_path ) || file_exists( $acf_json_path ) ) {
				$import_files['acf_json'] = $acf_json_path;
			}
		}

		if ( ! empty( $selected_import_data['local_import_gf_forms'] ) ) {
			$gf_forms_path = $selected_import_data['local_import_gf_forms'];

			if ( is_dir( $gf_forms_path ) || file_exists( $gf_forms_path ) ) {
				$import_files['gf_forms'] = $gf_forms_path;
			}
		}

		if ( ! empty( $selected_import_data['local_import_gf_entries'] ) ) {
			$gf_entries = $selected_import_data['local_import_gf_entries'];

			if ( is_string( $gf_entries ) && ( is_dir( $gf_entries ) || file_exists( $gf_entries ) ) ) {
				$import_files['gf_entries'] = $gf_entries;
			} elseif ( is_array( $gf_entries ) && ! empty( $gf_entries['file_path'] ) && file_exists( $gf_entries['file_path'] ) ) {
				$import_files['gf_entries'] = $gf_entries;
			}
		}

		if ( class_exists( 'Conjure_Connector_Upload_Registry' ) ) {
			$import_files = Conjure_Connector_Upload_Registry::merge_demo_paths( $import_files, $selected_import_data );
		}

		return $import_files;
	}
}
