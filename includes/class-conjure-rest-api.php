<?php
/**
 * REST API endpoints for Conjure WP
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API endpoints for Conjure WP demo imports.
 */
class Conjure_REST_API {

	/**
	 * Reference to the main Conjure instance.
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
	 * Shared import runner.
	 *
	 * @var Conjure_Import_Runner
	 */
	private $runner;

	/**
	 * REST API namespaces (legacy + WordPress-style).
	 *
	 * @var string[]
	 */
	private $namespaces = array( 'conjurewp/v1', 'ConjureWP/v1' );

	/**
	 * Constructor.
	 *
	 * @param Conjure $conjure Main Conjure instance.
	 */
	public function __construct( $conjure ) {
		$this->conjure = $conjure;
		$this->logger  = $conjure->logger;
		$this->runner  = new Conjure_Import_Runner( $conjure );
		Conjure_Import_Runner::register_job_handler();
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		$this->register_route(
			'/demos',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'list_demos' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		$this->register_route(
			'/import',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'import_demo' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'demo'         => array(
							'description'       => 'Demo slug or numeric index to import.',
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'skip_content' => array(
							'type'    => 'boolean',
							'default' => false,
						),
						'skip_widgets' => array(
							'type'    => 'boolean',
							'default' => false,
						),
						'skip_options' => array(
							'type'    => 'boolean',
							'default' => false,
						),
						'skip_sliders' => array(
							'type'    => 'boolean',
							'default' => false,
						),
						'skip_redux'   => array(
							'type'    => 'boolean',
							'default' => false,
						),
						'async'        => array(
							'description' => 'Queue import in the background and return a job ID.',
							'type'        => 'boolean',
							'default'     => false,
						),
					),
				),
			)
		);

		$this->register_route(
			'/import/status',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'import_status' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'job' => array(
							'description'       => 'Background import job ID.',
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
	}

	/**
	 * Register a route on all supported namespaces.
	 *
	 * @param string $route Route suffix.
	 * @param array  $args  Route arguments.
	 */
	private function register_route( $route, $args ) {
		foreach ( $this->namespaces as $namespace ) {
			register_rest_route( $namespace, $route, $args );
		}
	}

	/**
	 * Check if user has permission to access REST API endpoints.
	 *
	 * @return bool|WP_Error
	 */
	public function check_permission() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to perform this action.', 'ConjureWP' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * List all available demo imports.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function list_demos( $request ) {
		try {
			$import_files = $this->runner->get_import_files();

			if ( empty( $import_files ) ) {
				return new WP_REST_Response(
					array(
						'demos'   => array(),
						'message' => __( 'No demo imports are registered. Use the conjure_import_files filter to register demos.', 'ConjureWP' ),
					),
					200
				);
			}

			$demos = array();
			foreach ( $import_files as $index => $import_file ) {
				$demos[] = array(
					'index' => $index,
					'name'  => $import_file['import_file_name'],
					'slug'  => $this->runner->generate_slug( $import_file['import_file_name'] ),
				);
			}

			return new WP_REST_Response( array( 'demos' => $demos ), 200 );
		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in REST API list_demos',
				array(
					'message' => $e->getMessage(),
					'trace'   => $e->getTraceAsString(),
				)
			);

			return new WP_Error(
				'rest_list_demos_error',
				__( 'An error occurred while listing demos.', 'ConjureWP' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Import demo content.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function import_demo( $request ) {
		try {
			$demo = $request->get_param( 'demo' );

			if ( empty( $demo ) ) {
				return new WP_Error(
					'rest_missing_param',
					__( 'You must specify a demo using the demo parameter.', 'ConjureWP' ),
					array( 'status' => 400 )
				);
			}

			$rate_limit = conjurewp_rest_import_rate_limit( get_current_user_id() );
			if ( is_wp_error( $rate_limit ) ) {
				return $rate_limit;
			}

			$import_files = $this->runner->get_import_files();

			if ( empty( $import_files ) ) {
				return new WP_Error(
					'rest_no_demos',
					__( 'No demo imports are registered. Use the conjure_import_files filter to register demos.', 'ConjureWP' ),
					array( 'status' => 404 )
				);
			}

			$selected_index = $this->runner->find_demo_index( $demo, $import_files );

			if ( false === $selected_index ) {
				return new WP_Error(
					'rest_demo_not_found',
					__( 'The requested demo was not found.', 'ConjureWP' ),
					array( 'status' => 404 )
				);
			}

			$selected_import = $import_files[ $selected_index ];
			$import_options = array(
				'content'    => ! $request->get_param( 'skip_content' ),
				'widgets'    => ! $request->get_param( 'skip_widgets' ),
				'options'    => ! $request->get_param( 'skip_options' ),
				'sliders'    => ! $request->get_param( 'skip_sliders' ),
				'redux'      => ! $request->get_param( 'skip_redux' ),
				'acf_json'   => ! $request->get_param( 'skip_acf_json' ),
				'gf_forms'   => ! $request->get_param( 'skip_gf_forms' ),
				'gf_entries' => ! $request->get_param( 'skip_gf_entries' ),
			);

			if ( class_exists( 'Conjure_Connector_Upload_Registry' ) ) {
				foreach ( Conjure_Connector_Upload_Registry::get_definitions() as $slug => $definition ) {
					$param = $definition['skip_rest_param'];
					$import_options[ $slug ] = ! $request->get_param( $param );
				}
			}

			$use_async = (bool) $request->get_param( 'async' );
			$use_async = (bool) apply_filters( 'conjurewp_rest_import_async', $use_async, $request );

			if ( $use_async ) {
				$job_id = $this->runner->queue_background_import( $selected_index, $import_options );

				if ( is_wp_error( $job_id ) ) {
					return $job_id;
				}

				return new WP_REST_Response(
					array(
						'async'   => true,
						'job'     => $job_id,
						'status'  => 'pending',
						'message' => __( 'Import queued. Poll /import/status with the job ID.', 'ConjureWP' ),
					),
					202
				);
			}

			$import_file_paths = $this->runner->get_import_files_paths( $selected_index );
			$import_result     = $this->runner->execute_import( $import_file_paths, $import_options, $selected_index );

			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'Import completed successfully.', 'ConjureWP' ),
					'demo'    => array(
						'index' => $selected_index,
						'name'  => $selected_import['import_file_name'],
						'slug'  => $this->runner->generate_slug( $selected_import['import_file_name'] ),
					),
					'options' => $import_options,
					'result'  => $import_result,
				),
				200
			);
		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in REST API import_demo',
				array(
					'message' => $e->getMessage(),
					'trace'   => $e->getTraceAsString(),
				)
			);

			return new WP_Error(
				'rest_import_error',
				conjurewp_safe_error_message( $e, __( 'An error occurred during import.', 'ConjureWP' ) ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Poll background import job status.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function import_status( $request ) {
		$job_id = $request->get_param( 'job' );
		$job    = $this->runner->get_job_status( $job_id );

		if ( ! is_array( $job ) ) {
			return new WP_Error(
				'rest_import_job_not_found',
				__( 'Import job not found or expired.', 'ConjureWP' ),
				array( 'status' => 404 )
			);
		}

		if ( ! empty( $job['user_id'] ) && (int) $job['user_id'] !== get_current_user_id() ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to view this import job.', 'ConjureWP' ),
				array( 'status' => 403 )
			);
		}

		$response = array(
			'job'    => $job_id,
			'status' => $job['status'],
		);

		if ( ! empty( $job['result'] ) ) {
			$response['result'] = $job['result'];
		}

		if ( ! empty( $job['error'] ) ) {
			$response['error'] = $job['error'];
		}

		$status_code = 'completed' === $job['status'] ? 200 : ( 'failed' === $job['status'] ? 500 : 202 );

		return new WP_REST_Response( $response, $status_code );
	}
}
