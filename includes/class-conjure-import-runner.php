<?php
/**
 * Shared demo import orchestration for REST API and WP-CLI.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs demo imports using the main Conjure instance.
 */
class Conjure_Import_Runner {

	const JOB_HOOK = 'conjurewp_rest_import_job';

	/**
	 * Main Conjure instance.
	 *
	 * @var Conjure
	 */
	private $conjure;

	/**
	 * Logger instance.
	 *
	 * @var Conjure_Logger
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param Conjure $conjure Main Conjure instance.
	 */
	public function __construct( $conjure ) {
		$this->conjure = $conjure;
		$this->logger  = $conjure->logger;
	}

	/**
	 * Register background import cron handler (once).
	 */
	public static function register_job_handler() {
		if ( has_action( self::JOB_HOOK, array( __CLASS__, 'process_background_job' ) ) ) {
			return;
		}

		add_action( self::JOB_HOOK, array( __CLASS__, 'process_background_job' ) );
	}

	/**
	 * Transient key for a background import job.
	 *
	 * @param string $job_id Job UUID.
	 * @return string
	 */
	public static function job_transient_key( $job_id ) {
		return 'conjurewp_import_job_' . sanitize_key( $job_id );
	}

	/**
	 * Get registered import files.
	 *
	 * @return array
	 */
	public function get_import_files() {
		if ( ! did_action( 'conjurewp_register_import_files' ) ) {
			do_action( 'conjurewp_register_import_files' );
		}

		return is_array( $this->conjure->import_files ) ? $this->conjure->import_files : array();
	}

	/**
	 * Generate a slug from import name.
	 *
	 * @param string $name Import name.
	 * @return string
	 */
	public function generate_slug( $name ) {
		return sanitize_title( $name );
	}

	/**
	 * Find demo index by slug or numeric index.
	 *
	 * @param string|int $demo         Demo slug or index.
	 * @param array|null $import_files Optional import files list.
	 * @return int|false
	 */
	public function find_demo_index( $demo, $import_files = null ) {
		if ( null === $import_files ) {
			$import_files = $this->get_import_files();
		}

		if ( is_numeric( $demo ) ) {
			$index = (int) $demo;
			return isset( $import_files[ $index ] ) ? $index : false;
		}

		foreach ( $import_files as $index => $import_file ) {
			if ( $this->generate_slug( $import_file['import_file_name'] ) === $demo ) {
				return $index;
			}
		}

		return false;
	}

	/**
	 * Resolve import file paths for a demo index.
	 *
	 * @param int $selected_index Demo index.
	 * @return array
	 */
	public function get_import_files_paths( $selected_index ) {
		if ( ! empty( $this->conjure->import_service ) && $this->conjure->import_service instanceof Conjure_Import_Service ) {
			return $this->conjure->import_service->get_import_files_paths( $selected_index );
		}

		return $this->conjure->get_import_files_paths( $selected_index );
	}

	/**
	 * Execute the import process.
	 *
	 * @param array $import_files   Resolved file paths.
	 * @param array $import_options What to import.
	 * @param int   $selected_index Selected demo index.
	 * @return array
	 */
	public function execute_import( $import_files, $import_options, $selected_index ) {
		$results = array(
			'content'    => null,
			'widgets'    => null,
			'options'    => null,
			'sliders'    => null,
			'redux'      => null,
			'acf_json'   => null,
			'gf_forms'   => null,
			'gf_entries' => null,
		);

		if ( class_exists( 'Conjure_Connector_Upload_Registry' ) ) {
			foreach ( array_keys( Conjure_Connector_Upload_Registry::get_definitions() ) as $connector_slug ) {
				$results[ $connector_slug ] = null;
			}
		}

		do_action( 'import_start' );

		if ( ! empty( $import_options['content'] ) && ! empty( $import_files['content'] ) ) {
			$results['content'] = $this->import_content( $import_files['content'] );
		}

		if ( ! empty( $import_options['widgets'] ) && ! empty( $import_files['widgets'] ) ) {
			$results['widgets'] = $this->import_widgets( $import_files['widgets'] );
		}

		if ( ! empty( $import_options['options'] ) && ! empty( $import_files['options'] ) ) {
			$results['options'] = $this->import_options( $import_files['options'] );
		}

		if ( ! empty( $import_options['sliders'] ) && ! empty( $import_files['sliders'] ) ) {
			$results['sliders'] = $this->import_sliders( $import_files['sliders'] );
		}

		if ( ! empty( $import_options['redux'] ) && ! empty( $import_files['redux'] ) ) {
			$results['redux'] = $this->import_redux( $import_files['redux'] );
		}

		if ( ! empty( $import_options['acf_json'] ) && ! empty( $import_files['acf_json'] ) ) {
			$results['acf_json'] = $this->import_acf_json( $import_files['acf_json'] );
		}

		if ( ! empty( $import_options['gf_forms'] ) && ! empty( $import_files['gf_forms'] ) ) {
			$results['gf_forms'] = $this->import_gf_forms( $import_files['gf_forms'] );
		}

		if ( ! empty( $import_options['gf_entries'] ) && ! empty( $import_files['gf_entries'] ) ) {
			$results['gf_entries'] = $this->import_gf_entries( $import_files['gf_entries'] );
		}

		if ( class_exists( 'Conjure_Connector_Upload_Registry' ) ) {
			foreach ( Conjure_Connector_Upload_Registry::get_definitions() as $slug => $definition ) {
				if ( empty( $import_options[ $slug ] ) || empty( $import_files[ $slug ] ) ) {
					continue;
				}

				$results[ $slug ] = Conjure_Connector_Upload_Registry::run_import( $slug, $import_files[ $slug ] );
			}
		}

		do_action( 'import_end' );
		do_action( 'conjure_after_all_import', $selected_index );
		delete_transient( 'conjure_import_file_base_name' );

		return $results;
	}

	/**
	 * Queue a background REST import job.
	 *
	 * @param int   $selected_index Demo index.
	 * @param array $import_options Import options.
	 * @return string|WP_Error Job ID or error.
	 */
	public function queue_background_import( $selected_index, $import_options ) {
		if ( ! function_exists( 'wp_generate_uuid4' ) ) {
			return new WP_Error(
				'rest_import_async_unavailable',
				__( 'Background imports require WordPress 4.7 or newer.', 'ConjureWP' ),
				array( 'status' => 500 )
			);
		}

		$job_id = wp_generate_uuid4();
		$job    = array(
			'status'         => 'pending',
			'created'        => time(),
			'user_id'        => get_current_user_id(),
			'selected_index' => (int) $selected_index,
			'import_options' => $import_options,
			'result'         => null,
			'error'          => null,
		);

		set_transient( self::job_transient_key( $job_id ), $job, HOUR_IN_SECONDS );

		self::register_job_handler();
		wp_schedule_single_event( time() + 1, self::JOB_HOOK, array( $job_id ) );

		if ( ! wp_next_scheduled( self::JOB_HOOK, array( $job_id ) ) ) {
			delete_transient( self::job_transient_key( $job_id ) );
			return new WP_Error(
				'rest_import_async_failed',
				__( 'Unable to schedule background import.', 'ConjureWP' ),
				array( 'status' => 500 )
			);
		}

		return $job_id;
	}

	/**
	 * Get background job status.
	 *
	 * @param string $job_id Job ID.
	 * @return array|false
	 */
	public function get_job_status( $job_id ) {
		$job = get_transient( self::job_transient_key( $job_id ) );

		return is_array( $job ) ? $job : false;
	}

	/**
	 * Cron callback for background imports.
	 *
	 * @param string $job_id Job ID.
	 */
	public static function process_background_job( $job_id ) {
		$job = get_transient( self::job_transient_key( $job_id ) );

		if ( ! is_array( $job ) ) {
			return;
		}

		$conjure = function_exists( 'conjurewp_get_conjure' ) ? conjurewp_get_conjure() : null;

		if ( ! $conjure instanceof Conjure ) {
			$job['status'] = 'failed';
			$job['error']  = __( 'ConjureWP is not available.', 'ConjureWP' );
			set_transient( self::job_transient_key( $job_id ), $job, HOUR_IN_SECONDS );
			return;
		}

		$runner = new self( $conjure );
		$job['status'] = 'running';
		set_transient( self::job_transient_key( $job_id ), $job, HOUR_IN_SECONDS );

		try {
			$paths  = $runner->get_import_files_paths( $job['selected_index'] );
			$result = $runner->execute_import( $paths, $job['import_options'], $job['selected_index'] );

			$job['status'] = 'completed';
			$job['result'] = $result;
		} catch ( Exception $e ) {
			$runner->logger->error(
				'Background REST import failed',
				array(
					'job_id'  => $job_id,
					'message' => $e->getMessage(),
				)
			);

			$job['status'] = 'failed';
			$job['error']  = conjurewp_safe_error_message( $e );
		}

		set_transient( self::job_transient_key( $job_id ), $job, HOUR_IN_SECONDS );
	}

	/**
	 * Import content (posts, pages, etc).
	 *
	 * @param string $file_path Path to content XML file.
	 * @return array
	 */
	public function import_content( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			$this->logger->error( 'Content import file missing', array( 'path' => $file_path ) );
			return $this->failure_result( __( 'Content import file was not found.', 'ConjureWP' ) );
		}

		$importer = $this->conjure->importer;
		$total    = $importer->get_number_of_posts_to_import( $file_path );
		$result   = $importer->import( $file_path );

		if ( is_wp_error( $result ) ) {
			$this->logger->error( 'Content import failed', array( 'error' => $result->get_error_message() ) );
			return $this->failure_result(
				__( 'Content import failed.', 'ConjureWP' ),
				$result
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Content imported successfully.', 'ConjureWP' ),
			'total'   => $total,
		);
	}

	/**
	 * Import widgets.
	 *
	 * @param string $file_path Path to widgets JSON file.
	 * @return array
	 */
	public function import_widgets( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			$this->logger->error( 'Widgets import file missing', array( 'path' => $file_path ) );
			return $this->failure_result( __( 'Widgets import file was not found.', 'ConjureWP' ) );
		}

		$result = Conjure_Widget_Importer::import( $file_path );

		if ( is_wp_error( $result ) ) {
			$this->logger->error( 'Widget import failed', array( 'error' => $result->get_error_message() ) );
			return $this->failure_result( __( 'Widget import failed.', 'ConjureWP' ), $result );
		}

		if ( false === $result ) {
			return $this->failure_result( __( 'Widget import failed.', 'ConjureWP' ) );
		}

		return array(
			'success' => true,
			'message' => __( 'Widgets imported successfully.', 'ConjureWP' ),
		);
	}

	/**
	 * Import customizer options.
	 *
	 * @param string $file_path Path to customizer DAT file.
	 * @return array
	 */
	public function import_options( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			$this->logger->error( 'Customizer import file missing', array( 'path' => $file_path ) );
			return $this->failure_result( __( 'Customizer options file was not found.', 'ConjureWP' ) );
		}

		$result = Conjure_Customizer_Importer::import( $file_path );

		if ( is_wp_error( $result ) ) {
			$this->logger->error( 'Customizer import failed', array( 'error' => $result->get_error_message() ) );
			return $this->failure_result( __( 'Options import failed.', 'ConjureWP' ), $result );
		}

		return array(
			'success' => true,
			'message' => __( 'Customizer options imported successfully.', 'ConjureWP' ),
		);
	}

	/**
	 * Import Revolution Sliders.
	 *
	 * @param string $file_path Path to slider zip file.
	 * @return array
	 */
	public function import_sliders( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			$this->logger->error( 'Sliders import file missing', array( 'path' => $file_path ) );
			return $this->failure_result( __( 'Sliders import file was not found.', 'ConjureWP' ) );
		}

		if ( ! class_exists( 'RevSlider', false ) ) {
			return array(
				'success' => false,
				'message' => __( 'Revolution Slider plugin is not active. Skipping slider import.', 'ConjureWP' ),
				'skipped' => true,
			);
		}

		$result = $this->conjure->import_revolution_sliders( $file_path );

		if ( 'failed' === $result ) {
			return $this->failure_result( __( 'Slider import failed.', 'ConjureWP' ) );
		}

		return array(
			'success' => true,
			'message' => __( 'Sliders imported successfully.', 'ConjureWP' ),
		);
	}

	/**
	 * Import Redux options.
	 *
	 * @param array $redux_files Redux files configuration.
	 * @return array
	 */
	public function import_redux( $redux_files ) {
		if ( empty( $redux_files ) || ! is_array( $redux_files ) ) {
			return $this->failure_result( __( 'No Redux files provided.', 'ConjureWP' ) );
		}

		$errors = array();

		foreach ( $redux_files as $redux_file ) {
			if ( empty( $redux_file['file_path'] ) || ! file_exists( $redux_file['file_path'] ) ) {
				$this->logger->error( 'Redux import file missing', array( 'redux' => $redux_file ) );
				$errors[] = __( 'A Redux import file was not found.', 'ConjureWP' );
				continue;
			}

			$result = Conjure_Redux_Importer::import( array( $redux_file ) );

			if ( is_wp_error( $result ) ) {
				$this->logger->error( 'Redux import failed', array( 'error' => $result->get_error_message() ) );
				$errors[] = conjurewp_safe_import_message( $result, __( 'Redux import failed.', 'ConjureWP' ) );
			} elseif ( false === $result ) {
				$errors[] = __( 'Redux import failed.', 'ConjureWP' );
			}
		}

		if ( ! empty( $errors ) ) {
			return array(
				'success' => false,
				'message' => implode( ' ', array_unique( $errors ) ),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Redux options imported successfully.', 'ConjureWP' ),
		);
	}

	/**
	 * Import ACF local JSON field groups into the active theme.
	 *
	 * @param string $file_or_directory Path to a .json/.zip file or acf-json directory.
	 * @return array
	 */
	public function import_acf_json( $file_or_directory ) {
		if ( empty( $file_or_directory ) ) {
			return $this->failure_result( __( 'No ACF JSON source provided.', 'ConjureWP' ) );
		}

		if ( is_dir( $file_or_directory ) ) {
			$result = Conjure_ACF_JSON_Importer::import_from_directory( $file_or_directory );
		} else {
			$result = Conjure_ACF_JSON_Importer::import( $file_or_directory );
		}

		if ( is_wp_error( $result ) ) {
			if ( 'acf_inactive' === $result->get_error_code() ) {
				return array(
					'success' => false,
					'message' => $result->get_error_message(),
					'skipped' => true,
				);
			}

			$this->logger->error( 'ACF JSON import failed', array( 'error' => $result->get_error_message() ) );
			return $this->failure_result(
				__( 'ACF JSON import failed.', 'ConjureWP' ),
				$result
			);
		}

		if ( false === $result ) {
			return $this->failure_result( __( 'ACF JSON import failed.', 'ConjureWP' ) );
		}

		return array(
			'success' => true,
			'message' => __( 'ACF field group JSON files were copied to your theme. Sync them from Custom Fields in the admin when ready.', 'ConjureWP' ),
		);
	}

	/**
	 * Import Gravity Forms.
	 *
	 * @param string $file_path Export file or directory.
	 * @return array
	 */
	public function import_gf_forms( $file_path ) {
		$result = Conjure_Gravity_Forms_Importer::import_forms( $file_path );

		if ( is_wp_error( $result ) ) {
			if ( 'gf_inactive' === $result->get_error_code() ) {
				return array(
					'success' => false,
					'message' => $result->get_error_message(),
					'skipped' => true,
				);
			}

			$this->logger->error( 'Gravity Forms import failed', array( 'error' => $result->get_error_message() ) );
			return $this->failure_result( __( 'Gravity Forms import failed.', 'ConjureWP' ), $result );
		}

		return array(
			'success' => true,
			'message' => __( 'Gravity Forms imported successfully.', 'ConjureWP' ),
		);
	}

	/**
	 * Import Gravity Forms entries.
	 *
	 * @param string|array $source File path or config array.
	 * @return array
	 */
	public function import_gf_entries( $source ) {
		$result = Conjure_Gravity_Forms_Importer::import_entries( $source );

		if ( is_wp_error( $result ) ) {
			if ( 'gf_inactive' === $result->get_error_code() ) {
				return array(
					'success' => false,
					'message' => $result->get_error_message(),
					'skipped' => true,
				);
			}

			$this->logger->error( 'Gravity Forms entries import failed', array( 'error' => $result->get_error_message() ) );
			return $this->failure_result( __( 'Gravity Forms entries import failed.', 'ConjureWP' ), $result );
		}

		return array(
			'success' => true,
			'message' => __( 'Gravity Forms entries imported successfully.', 'ConjureWP' ),
		);
	}

	/**
	 * Build a failure result with a client-safe message.
	 *
	 * @param string               $fallback User-facing fallback.
	 * @param Throwable|WP_Error|string|null $detail Logged detail.
	 * @return array
	 */
	private function failure_result( $fallback, $detail = null ) {
		if ( null !== $detail ) {
			if ( $detail instanceof Throwable ) {
				$this->logger->error( $detail->getMessage(), array( 'trace' => $detail->getTraceAsString() ) );
			} elseif ( is_wp_error( $detail ) ) {
				$this->logger->error( $detail->get_error_message() );
			} elseif ( is_string( $detail ) ) {
				$this->logger->error( $detail );
			}
		}

		return array(
			'success' => false,
			'message' => conjurewp_safe_import_message( $detail ? $detail : $fallback, $fallback ),
		);
	}
}
