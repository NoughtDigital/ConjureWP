<?php
/**
 * REST API endpoints for Conjure WP
 *
 * Exposes CLI import functionality over REST API for hosting dashboards
 * to trigger demo imports without shell access.
 *
 * @package   Conjure WP
 * @version   1.0.0
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
	 * Namespace for REST API routes.
	 *
	 * @var string
	 */
	private $namespace = 'conjurewp/v1';

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
	 * Register REST API routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/demos',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'list_demos' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/import',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'import_demo' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'demo'         => array(
							'description' => 'Demo slug or numeric index to import.',
							'type'        => 'string',
							'required'    => true,
						),
						'skip_content' => array(
							'description' => 'Skip content import.',
							'type'        => 'boolean',
							'default'     => false,
						),
						'skip_widgets' => array(
							'description' => 'Skip widgets import.',
							'type'        => 'boolean',
							'default'     => false,
						),
						'skip_options' => array(
							'description' => 'Skip customizer options import.',
							'type'        => 'boolean',
							'default'     => false,
						),
						'skip_sliders' => array(
							'description' => 'Skip sliders import.',
							'type'        => 'boolean',
							'default'     => false,
						),
						'skip_redux'   => array(
							'description' => 'Skip Redux options import.',
							'type'        => 'boolean',
							'default'     => false,
						),
					),
				),
			)
		);
	}

	/**
	 * Check if user has permission to access REST API endpoints.
	 *
	 * @return bool|WP_Error True if user has manage_options capability, error otherwise.
	 */
	public function check_permission() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to perform this action.', 'conjurewp' ),
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
			$import_files = $this->get_import_files();

			if ( empty( $import_files ) ) {
				return new WP_REST_Response(
					array(
						'demos' => array(),
						'message' => __( 'No demo imports are registered. Use the conjure_import_files filter to register demos.', 'conjurewp' ),
					),
					200
				);
			}

			$demos = array();
			foreach ( $import_files as $index => $import_file ) {
				$demos[] = array(
					'index' => $index,
					'name' => $import_file['import_file_name'],
					'slug' => $this->generate_slug( $import_file['import_file_name'] ),
				);
			}

			return new WP_REST_Response(
				array(
					'demos' => $demos,
				),
				200
			);

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
				__( 'An error occurred while listing demos.', 'conjurewp' ),
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
					__( 'You must specify a demo using the demo parameter.', 'conjurewp' ),
					array( 'status' => 400 )
				);
			}

			// Get import files.
			$import_files = $this->get_import_files();

			if ( empty( $import_files ) ) {
				return new WP_Error(
					'rest_no_demos',
					__( 'No demo imports are registered. Use the conjure_import_files filter to register demos.', 'conjurewp' ),
					array( 'status' => 404 )
				);
			}

			// Find the demo by slug or index.
			$selected_index = $this->find_demo_index( $demo, $import_files );

			if ( false === $selected_index ) {
				return new WP_Error(
					'rest_demo_not_found',
					sprintf(
						/* translators: %s: demo identifier */
						__( 'Demo "%s" not found.', 'conjurewp' ),
						$demo
					),
					array( 'status' => 404 )
				);
			}

			$selected_import = $import_files[ $selected_index ];

			// Parse import options.
			$import_options = array(
				'content' => ! $request->get_param( 'skip_content' ),
				'widgets' => ! $request->get_param( 'skip_widgets' ),
				'options' => ! $request->get_param( 'skip_options' ),
				'sliders' => ! $request->get_param( 'skip_sliders' ),
				'redux'   => ! $request->get_param( 'skip_redux' ),
			);

			// Get import files paths.
			$import_file_paths = $this->get_import_files_paths( $selected_index );

			// Execute the imports.
			$import_result = $this->execute_import( $import_file_paths, $import_options, $selected_index );

			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'Import completed successfully.', 'conjurewp' ),
					'demo'    => array(
						'index' => $selected_index,
						'name'  => $selected_import['import_file_name'],
						'slug'  => $this->generate_slug( $selected_import['import_file_name'] ),
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
				sprintf(
					/* translators: %s: error message */
					__( 'An error occurred during import: %s', 'conjurewp' ),
					$e->getMessage()
				),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Get registered import files.
	 *
	 * @return array
	 */
	private function get_import_files() {
		// Ensure import files are registered.
		do_action( 'admin_init' );

		// Use reflection to access protected import_files property.
		$reflection = new ReflectionClass( $this->conjure );
		$property   = $reflection->getProperty( 'import_files' );
		$property->setAccessible( true );
		$import_files = $property->getValue( $this->conjure );

		return is_array( $import_files ) ? $import_files : array();
	}

	/**
	 * Generate a slug from import name.
	 *
	 * @param string $name Import name.
	 * @return string
	 */
	private function generate_slug( $name ) {
		return sanitize_title( $name );
	}

	/**
	 * Find demo index by slug or numeric index.
	 *
	 * @param string|int $demo         Demo slug or index.
	 * @param array      $import_files Import files array.
	 * @return int|false
	 */
	private function find_demo_index( $demo, $import_files ) {
		// Check if it's a numeric index.
		if ( is_numeric( $demo ) ) {
			$index = intval( $demo );
			return isset( $import_files[ $index ] ) ? $index : false;
		}

		// Search by slug.
		foreach ( $import_files as $index => $import_file ) {
			if ( $this->generate_slug( $import_file['import_file_name'] ) === $demo ) {
				return $index;
			}
		}

		return false;
	}

	/**
	 * Get import file paths using Conjure's method.
	 *
	 * @param int $selected_index Selected import index.
	 * @return array
	 */
	private function get_import_files_paths( $selected_index ) {
		// Use reflection to call protected method.
		$reflection = new ReflectionClass( $this->conjure );
		$method     = $reflection->getMethod( 'get_import_files_paths' );
		$method->setAccessible( true );

		return $method->invoke( $this->conjure, $selected_index );
	}

	/**
	 * Execute the import process.
	 *
	 * @param array $import_files   Import file paths.
	 * @param array $import_options What to import.
	 * @param int   $selected_index Selected import index.
	 * @return array Import results.
	 */
	private function execute_import( $import_files, $import_options, $selected_index ) {
		$results = array(
			'content' => null,
			'widgets' => null,
			'options' => null,
			'sliders' => null,
			'redux'   => null,
		);

		// Before import setup.
		do_action( 'import_start' );

		// Import content.
		if ( $import_options['content'] && ! empty( $import_files['content'] ) ) {
			$results['content'] = $this->import_content( $import_files['content'] );
		}

		// Import widgets.
		if ( $import_options['widgets'] && ! empty( $import_files['widgets'] ) ) {
			$results['widgets'] = $this->import_widgets( $import_files['widgets'] );
		}

		// Import customizer options.
		if ( $import_options['options'] && ! empty( $import_files['options'] ) ) {
			$results['options'] = $this->import_options( $import_files['options'] );
		}

		// Import sliders.
		if ( $import_options['sliders'] && ! empty( $import_files['sliders'] ) ) {
			$results['sliders'] = $this->import_sliders( $import_files['sliders'] );
		}

		// Import Redux options.
		if ( $import_options['redux'] && ! empty( $import_files['redux'] ) ) {
			$results['redux'] = $this->import_redux( $import_files['redux'] );
		}

		// After import setup.
		do_action( 'import_end' );

		// Run after all import actions.
		do_action( 'conjure_after_all_import', $selected_index );

		// Cleanup.
		delete_transient( 'conjure_import_file_base_name' );

		return $results;
	}

	/**
	 * Import content (posts, pages, etc).
	 *
	 * @param string $file_path Path to content XML file.
	 * @return array Result with success status and message.
	 */
	private function import_content( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: file path */
					__( 'Content file not found: %s', 'conjurewp' ),
					$file_path
				),
			);
		}

		// Get importer instance.
		$reflection = new ReflectionClass( $this->conjure );
		$property   = $reflection->getProperty( 'importer' );
		$property->setAccessible( true );
		$importer = $property->getValue( $this->conjure );

		// Get total items for progress.
		$total = $importer->get_number_of_posts_to_import( $file_path );

		// Execute import.
		$result = $importer->import( $file_path );

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: error message */
					__( 'Content import failed: %s', 'conjurewp' ),
					$result->get_error_message()
				),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Content imported successfully.', 'conjurewp' ),
			'total'   => $total,
		);
	}

	/**
	 * Import widgets.
	 *
	 * @param string $file_path Path to widgets JSON file.
	 * @return array Result with success status and message.
	 */
	private function import_widgets( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: file path */
					__( 'Widgets file not found: %s', 'conjurewp' ),
					$file_path
				),
			);
		}

		$result = Conjure_Widget_Importer::import( $file_path );

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: error message */
					__( 'Widget import failed: %s', 'conjurewp' ),
					$result->get_error_message()
				),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Widgets imported successfully.', 'conjurewp' ),
		);
	}

	/**
	 * Import customizer options.
	 *
	 * @param string $file_path Path to customizer DAT file.
	 * @return array Result with success status and message.
	 */
	private function import_options( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: file path */
					__( 'Options file not found: %s', 'conjurewp' ),
					$file_path
				),
			);
		}

		$result = Conjure_Customizer_Importer::import( $file_path );

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: error message */
					__( 'Options import failed: %s', 'conjurewp' ),
					$result->get_error_message()
				),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Customizer options imported successfully.', 'conjurewp' ),
		);
	}

	/**
	 * Import Revolution Sliders.
	 *
	 * @param string $file_path Path to slider zip file.
	 * @return array Result with success status and message.
	 */
	private function import_sliders( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: file path */
					__( 'Sliders file not found: %s', 'conjurewp' ),
					$file_path
				),
			);
		}

		if ( ! class_exists( 'RevSlider', false ) ) {
			return array(
				'success' => false,
				'message' => __( 'Revolution Slider plugin is not active. Skipping slider import.', 'conjurewp' ),
				'skipped' => true,
			);
		}

		$reflection = new ReflectionClass( $this->conjure );
		$method     = $reflection->getMethod( 'import_revolution_sliders' );
		$method->setAccessible( true );
		$result = $method->invoke( $this->conjure, $file_path );

		if ( 'failed' === $result ) {
			return array(
				'success' => false,
				'message' => __( 'Slider import failed.', 'conjurewp' ),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Sliders imported successfully.', 'conjurewp' ),
		);
	}

	/**
	 * Import Redux options.
	 *
	 * @param array $redux_files Redux files configuration.
	 * @return array Result with success status and message.
	 */
	private function import_redux( $redux_files ) {
		if ( empty( $redux_files ) || ! is_array( $redux_files ) ) {
			return array(
				'success' => false,
				'message' => __( 'No Redux files provided.', 'conjurewp' ),
			);
		}

		$errors = array();
		foreach ( $redux_files as $redux_file ) {
			if ( empty( $redux_file['file_path'] ) || ! file_exists( $redux_file['file_path'] ) ) {
				$errors[] = sprintf(
					/* translators: %s: file path */
					__( 'Redux file not found: %s', 'conjurewp' ),
					$redux_file['file_path'] ?? 'unknown'
				);
				continue;
			}

			$result = Conjure_Redux_Importer::import( $redux_file );

			if ( is_wp_error( $result ) ) {
				$errors[] = sprintf(
					/* translators: %s: error message */
					__( 'Redux import failed: %s', 'conjurewp' ),
					$result->get_error_message()
				);
			}
		}

		if ( ! empty( $errors ) ) {
			return array(
				'success' => false,
				'message' => implode( ' ', $errors ),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Redux options imported successfully.', 'conjurewp' ),
		);
	}
}

