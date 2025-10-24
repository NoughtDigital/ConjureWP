<?php
/**
 * WP-CLI commands for Conjure WP
 *
 * Enables command-line demo imports for hosts and CI scripts
 * to bootstrap WordPress sites without browser interaction.
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
 * WP-CLI commands for Conjure WP theme setup and content import.
 */
class Conjure_CLI {

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
	 * Constructor.
	 *
	 * @param Conjure $conjure Main Conjure instance.
	 */
	public function __construct( $conjure ) {
		$this->conjure = $conjure;
		$this->logger  = $conjure->logger;
	}

	/**
	 * Lists all available demo imports.
	 *
	 * ## EXAMPLES
	 *
	 *     wp conjure list
	 *
	 * @when after_wp_load
	 */
	public function list_demos( $args, $assoc_args ) {
		$import_files = $this->get_import_files();

		if ( empty( $import_files ) ) {
			WP_CLI::warning( 'No demo imports are registered.' );
			WP_CLI::line( 'To register demo imports, use the conjure_import_files filter in your theme.' );
			return;
		}

		WP_CLI::line( 'Available demo imports:' );
		WP_CLI::line( '' );

		$items = array();
		foreach ( $import_files as $index => $import_file ) {
			$items[] = array(
				'Index' => $index,
				'Name'  => $import_file['import_file_name'],
				'Slug'  => $this->generate_slug( $import_file['import_file_name'] ),
			);
		}

		WP_CLI\Utils\format_items( 'table', $items, array( 'Index', 'Name', 'Slug' ) );
	}

	/**
	 * Imports demo content for a specific demo.
	 *
	 * ## OPTIONS
	 *
	 * [--demo=<slug|index>]
	 * : The demo slug or numeric index to import. Use 'wp conjure list' to see available demos.
	 *
	 * [--content]
	 * : Import content (posts, pages, etc). Default: true if available.
	 *
	 * [--widgets]
	 * : Import widgets. Default: true if available.
	 *
	 * [--options]
	 * : Import customizer options. Default: true if available.
	 *
	 * [--sliders]
	 * : Import Revolution Sliders. Default: true if available.
	 *
	 * [--redux]
	 * : Import Redux options. Default: true if available.
	 *
	 * [--skip-content]
	 * : Skip content import.
	 *
	 * [--skip-widgets]
	 * : Skip widgets import.
	 *
	 * [--skip-options]
	 * : Skip options import.
	 *
	 * [--skip-sliders]
	 * : Skip sliders import.
	 *
	 * [--skip-redux]
	 * : Skip redux import.
	 *
	 * ## EXAMPLES
	 *
	 *     # Import demo by slug
	 *     wp conjure import --demo=demo-content
	 *
	 *     # Import demo by index
	 *     wp conjure import --demo=0
	 *
	 *     # Import only content, skip everything else
	 *     wp conjure import --demo=0 --skip-widgets --skip-options
	 *
	 * @when after_wp_load
	 */
	public function import( $args, $assoc_args ) {
		// Verify user capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			WP_CLI::error( 'You do not have permission to run imports.' );
		}

		// Get the demo parameter.
		$demo = WP_CLI\Utils\get_flag_value( $assoc_args, 'demo', null );

		if ( null === $demo ) {
			WP_CLI::error( 'You must specify a demo using --demo=<slug|index>. Use "wp conjure list" to see available demos.' );
		}

		// Get import files.
		$import_files = $this->get_import_files();

		if ( empty( $import_files ) ) {
			WP_CLI::error( 'No demo imports are registered. Use the conjure_import_files filter to register demos.' );
		}

		// Find the demo by slug or index.
		$selected_index = $this->find_demo_index( $demo, $import_files );

		if ( false === $selected_index ) {
			WP_CLI::error( sprintf( 'Demo "%s" not found. Use "wp conjure list" to see available demos.', $demo ) );
		}

		$selected_import = $import_files[ $selected_index ];

		WP_CLI::line( '' );
		WP_CLI::success( sprintf( 'Starting import for: %s', $selected_import['import_file_name'] ) );
		WP_CLI::line( '' );

		// Parse import options.
		$import_options = array(
			'content' => ! isset( $assoc_args['skip-content'] ),
			'widgets' => ! isset( $assoc_args['skip-widgets'] ),
			'options' => ! isset( $assoc_args['skip-options'] ),
			'sliders' => ! isset( $assoc_args['skip-sliders'] ),
			'redux'   => ! isset( $assoc_args['skip-redux'] ),
		);

		// Get import files paths.
		$import_file_paths = $this->get_import_files_paths( $selected_index );

		// Execute the imports.
		$this->execute_import( $import_file_paths, $import_options, $selected_index );

		WP_CLI::line( '' );
		WP_CLI::success( 'Import completed successfully!' );
		WP_CLI::line( '' );
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
	 */
	private function execute_import( $import_files, $import_options, $selected_index ) {
		// Before import setup.
		do_action( 'import_start' );

		// Import content.
		if ( $import_options['content'] && ! empty( $import_files['content'] ) ) {
			$this->import_content( $import_files['content'] );
		}

		// Import widgets.
		if ( $import_options['widgets'] && ! empty( $import_files['widgets'] ) ) {
			$this->import_widgets( $import_files['widgets'] );
		}

		// Import customizer options.
		if ( $import_options['options'] && ! empty( $import_files['options'] ) ) {
			$this->import_options( $import_files['options'] );
		}

		// Import sliders.
		if ( $import_options['sliders'] && ! empty( $import_files['sliders'] ) ) {
			$this->import_sliders( $import_files['sliders'] );
		}

		// Import Redux options.
		if ( $import_options['redux'] && ! empty( $import_files['redux'] ) ) {
			$this->import_redux( $import_files['redux'] );
		}

		// After import setup.
		do_action( 'import_end' );

		// Run after all import actions.
		do_action( 'conjure_after_all_import', $selected_index );

		// Cleanup.
		delete_transient( 'conjure_import_file_base_name' );
	}

	/**
	 * Import content (posts, pages, etc).
	 *
	 * @param string $file_path Path to content XML file.
	 */
	private function import_content( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			WP_CLI::warning( sprintf( 'Content file not found: %s', $file_path ) );
			return;
		}

		WP_CLI::line( 'Importing content...' );

		// Get importer instance.
		$reflection = new ReflectionClass( $this->conjure );
		$property   = $reflection->getProperty( 'importer' );
		$property->setAccessible( true );
		$importer = $property->getValue( $this->conjure );

		// Get total items for progress.
		$total = $importer->get_number_of_posts_to_import( $file_path );

		if ( $total > 0 ) {
			WP_CLI::line( sprintf( 'Found %d items to import...', $total ) );
		}

		// Set up a progress bar if we have items to import.
		$progress = null;
		if ( $total > 0 ) {
			$progress = \WP_CLI\Utils\make_progress_bar( 'Importing content', $total );
		}

		// Hook into import process to update progress.
		if ( $progress ) {
			add_action(
				'wxr_importer.processed.post',
				function() use ( $progress ) {
					$progress->tick();
				}
			);
		}

		// Execute import.
		$result = $importer->import( $file_path );

		if ( $progress ) {
			$progress->finish();
		}

		if ( is_wp_error( $result ) ) {
			WP_CLI::warning( sprintf( 'Content import failed: %s', $result->get_error_message() ) );
		} else {
			WP_CLI::success( 'Content imported successfully.' );
		}
	}

	/**
	 * Import widgets.
	 *
	 * @param string $file_path Path to widgets JSON file.
	 */
	private function import_widgets( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			WP_CLI::warning( sprintf( 'Widgets file not found: %s', $file_path ) );
			return;
		}

		WP_CLI::line( 'Importing widgets...' );

		$result = Conjure_Widget_Importer::import( $file_path );

		if ( is_wp_error( $result ) ) {
			WP_CLI::warning( sprintf( 'Widget import failed: %s', $result->get_error_message() ) );
		} else {
			WP_CLI::success( 'Widgets imported successfully.' );
		}
	}

	/**
	 * Import customizer options.
	 *
	 * @param string $file_path Path to customizer DAT file.
	 */
	private function import_options( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			WP_CLI::warning( sprintf( 'Options file not found: %s', $file_path ) );
			return;
		}

		WP_CLI::line( 'Importing customizer options...' );

		$result = Conjure_Customizer_Importer::import( $file_path );

		if ( is_wp_error( $result ) ) {
			WP_CLI::warning( sprintf( 'Options import failed: %s', $result->get_error_message() ) );
		} else {
			WP_CLI::success( 'Customizer options imported successfully.' );
		}
	}

	/**
	 * Import Revolution Sliders.
	 *
	 * @param string $file_path Path to slider zip file.
	 */
	private function import_sliders( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			WP_CLI::warning( sprintf( 'Sliders file not found: %s', $file_path ) );
			return;
		}

		if ( ! class_exists( 'RevSlider', false ) ) {
			WP_CLI::warning( 'Revolution Slider plugin is not active. Skipping slider import.' );
			return;
		}

		WP_CLI::line( 'Importing Revolution Sliders...' );

		$reflection = new ReflectionClass( $this->conjure );
		$method     = $reflection->getMethod( 'import_revolution_sliders' );
		$method->setAccessible( true );
		$result = $method->invoke( $this->conjure, $file_path );

		if ( 'failed' === $result ) {
			WP_CLI::warning( 'Slider import failed.' );
		} else {
			WP_CLI::success( 'Sliders imported successfully.' );
		}
	}

	/**
	 * Import Redux options.
	 *
	 * @param array $redux_files Redux files configuration.
	 */
	private function import_redux( $redux_files ) {
		if ( empty( $redux_files ) || ! is_array( $redux_files ) ) {
			return;
		}

		WP_CLI::line( 'Importing Redux options...' );

		foreach ( $redux_files as $redux_file ) {
			if ( empty( $redux_file['file_path'] ) || ! file_exists( $redux_file['file_path'] ) ) {
				WP_CLI::warning( sprintf( 'Redux file not found: %s', $redux_file['file_path'] ?? 'unknown' ) );
				continue;
			}

			$result = Conjure_Redux_Importer::import( $redux_file );

			if ( is_wp_error( $result ) ) {
				WP_CLI::warning( sprintf( 'Redux import failed: %s', $result->get_error_message() ) );
			}
		}

		WP_CLI::success( 'Redux options imported successfully.' );
	}
}

