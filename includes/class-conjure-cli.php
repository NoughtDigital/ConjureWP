<?php
/**
 * WP-CLI commands for Conjure WP
 *
 * @package ConjureWP
 */

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
	 * Shared import runner.
	 *
	 * @var Conjure_Import_Runner
	 */
	private $runner;

	/**
	 * Constructor.
	 *
	 * @param Conjure $conjure Main Conjure instance.
	 */
	public function __construct( $conjure ) {
		$this->conjure = $conjure;
		$this->logger  = $conjure->logger;
		$this->runner  = new Conjure_Import_Runner( $conjure );
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
		$import_files = $this->runner->get_import_files();

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
				'Slug'  => $this->runner->generate_slug( $import_file['import_file_name'] ),
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
	 * : The demo slug or numeric index to import.
	 *
	 * [--skip-content]
	 * [--skip-widgets]
	 * [--skip-options]
	 * [--skip-sliders]
	 * [--skip-redux]
	 *
	 * ## EXAMPLES
	 *
	 *     wp conjure import --demo=demo-content
	 *
	 * @when after_wp_load
	 */
	public function import( $args, $assoc_args ) {
		if ( WP_CLI::get_config( 'user' ) && ! current_user_can( 'manage_options' ) ) {
			WP_CLI::error( 'You do not have permission to run imports. User must have manage_options capability.' );
		}

		$demo = WP_CLI\Utils\get_flag_value( $assoc_args, 'demo', null );

		if ( null === $demo ) {
			WP_CLI::error( 'You must specify a demo using --demo=<slug|index>. Use "wp conjure list" to see available demos.' );
		}

		$import_files = $this->runner->get_import_files();

		if ( empty( $import_files ) ) {
			WP_CLI::error( 'No demo imports are registered. Use the conjure_import_files filter to register demos.' );
		}

		$selected_index = $this->runner->find_demo_index( $demo, $import_files );

		if ( false === $selected_index ) {
			WP_CLI::error( sprintf( 'Demo "%s" not found. Use "wp conjure list" to see available demos.', $demo ) );
		}

		$selected_import = $import_files[ $selected_index ];

		WP_CLI::line( '' );
		WP_CLI::success( sprintf( 'Starting import for: %s', $selected_import['import_file_name'] ) );
		WP_CLI::line( '' );

		$import_options = array(
			'content' => ! isset( $assoc_args['skip-content'] ),
			'widgets' => ! isset( $assoc_args['skip-widgets'] ),
			'options' => ! isset( $assoc_args['skip-options'] ),
			'sliders' => ! isset( $assoc_args['skip-sliders'] ),
			'redux'   => ! isset( $assoc_args['skip-redux'] ),
		);

		$import_file_paths = $this->runner->get_import_files_paths( $selected_index );
		$progress          = null;

		if ( ! empty( $import_options['content'] ) && ! empty( $import_file_paths['content'] ) && file_exists( $import_file_paths['content'] ) ) {
			WP_CLI::line( 'Importing content...' );
			$total = $this->conjure->importer->get_number_of_posts_to_import( $import_file_paths['content'] );

			if ( $total > 0 ) {
				WP_CLI::line( sprintf( 'Found %d items to import...', $total ) );
				$progress = \WP_CLI\Utils\make_progress_bar( 'Importing content', $total );
				add_action(
					'wxr_importer.processed.post',
					function () use ( $progress ) {
						$progress->tick();
					}
				);
			}
		}

		$results = $this->runner->execute_import( $import_file_paths, $import_options, $selected_index );

		if ( $progress ) {
			$progress->finish();
		}

		foreach ( $results as $type => $result ) {
			if ( empty( $result ) || ! is_array( $result ) ) {
				continue;
			}

			$label = ucfirst( $type );
			if ( ! empty( $result['success'] ) ) {
				WP_CLI::success( sprintf( '%s: %s', $label, $result['message'] ) );
			} elseif ( ! empty( $result['skipped'] ) ) {
				WP_CLI::warning( sprintf( '%s: %s', $label, $result['message'] ) );
			} else {
				WP_CLI::warning( sprintf( '%s: %s', $label, $result['message'] ) );
			}
		}

		WP_CLI::line( '' );
		WP_CLI::success( 'Import completed successfully!' );
		WP_CLI::line( '' );
	}

	/**
	 * Validate theme plugin configuration.
	 *
	 * @when after_wp_load
	 */
	public function validate_theme_plugins( $args, $assoc_args ) {
		WP_CLI::line( '' );
		WP_CLI::line( 'Validating theme plugin configuration...' );
		WP_CLI::line( '' );

		$plugin_dir = Conjure_Theme_Plugins::get_theme_plugin_dir();

		if ( ! $plugin_dir ) {
			WP_CLI::warning( 'No ConjureWP-plugins directory found in the current theme.' );
			WP_CLI::line( sprintf( 'Expected location: %s/ConjureWP-plugins/', get_template_directory() ) );
			return;
		}

		WP_CLI::success( sprintf( 'Found plugin directory: %s', $plugin_dir ) );

		$config_file = trailingslashit( $plugin_dir ) . 'plugins.json';

		if ( ! file_exists( $config_file ) ) {
			WP_CLI::error( 'Configuration file (plugins.json) not found in plugin directory.' );
			return;
		}

		WP_CLI::success( 'Found plugins.json configuration file.' );

		$validation_result = Conjure_Theme_Plugins::validate_config( $config_file );

		if ( is_wp_error( $validation_result ) ) {
			WP_CLI::error( sprintf( 'Configuration validation failed: %s', $validation_result->get_error_message() ) );
			return;
		}

		WP_CLI::success( sprintf( 'Configuration is valid! Found %d plugin(s) defined.', $validation_result['plugins'] ) );
		WP_CLI::line( '' );

		$plugins = Conjure_Theme_Plugins::get_bundled_plugins();

		if ( ! empty( $plugins ) ) {
			WP_CLI::line( 'Plugin Details:' );
			WP_CLI::line( '' );

			$items = array();
			foreach ( $plugins as $plugin ) {
				$source = 'WordPress.org';
				if ( ! empty( $plugin['bundled'] ) ) {
					$source = 'Bundled ZIP';
				} elseif ( ! empty( $plugin['external'] ) ) {
					$source = 'External URL';
				}

				$items[] = array(
					'Name'     => $plugin['name'],
					'Slug'     => $plugin['slug'],
					'Required' => $plugin['required'] ? 'Yes' : 'No',
					'Source'   => $source,
					'Version'  => ! empty( $plugin['version'] ) ? $plugin['version'] : '-',
				);
			}

			WP_CLI\Utils\format_items( 'table', $items, array( 'Name', 'Slug', 'Required', 'Source', 'Version' ) );
			WP_CLI::line( '' );
			WP_CLI::success( 'Theme plugin validation complete!' );
		}
	}

	/**
	 * List theme bundled plugins.
	 *
	 * @when after_wp_load
	 */
	public function list_theme_plugins( $args, $assoc_args ) {
		$plugins = Conjure_Theme_Plugins::get_bundled_plugins();

		if ( empty( $plugins ) ) {
			WP_CLI::warning( 'No theme bundled plugins found.' );
			return;
		}

		WP_CLI::line( sprintf( 'Found %d theme bundled plugin(s):', count( $plugins ) ) );
		WP_CLI::line( '' );

		$items = array();
		foreach ( $plugins as $plugin ) {
			$items[] = array(
				'Name'     => $plugin['name'],
				'Slug'     => $plugin['slug'],
				'Required' => $plugin['required'] ? 'Yes' : 'No',
			);
		}

		WP_CLI\Utils\format_items( 'table', $items, array( 'Name', 'Slug', 'Required' ) );
	}

	/**
	 * Test plugin download from external URL.
	 *
	 * @when after_wp_load
	 */
	public function test_plugin_download( $args, $assoc_args ) {
		$slug = WP_CLI\Utils\get_flag_value( $assoc_args, 'slug', null );

		if ( null === $slug ) {
			WP_CLI::error( 'You must specify a plugin slug using --slug=<slug>' );
		}

		WP_CLI::line( sprintf( 'Testing download for plugin: %s', $slug ) );
		WP_CLI::line( '' );

		$plugins = Conjure_Theme_Plugins::get_bundled_plugins();
		$plugin  = null;

		foreach ( $plugins as $p ) {
			if ( $p['slug'] === $slug ) {
				$plugin = $p;
				break;
			}
		}

		if ( ! $plugin ) {
			WP_CLI::error( sprintf( 'Plugin "%s" not found in theme configuration.', $slug ) );
		}

		WP_CLI::line( sprintf( 'Plugin Name: %s', $plugin['name'] ) );
		WP_CLI::line( sprintf( 'Required: %s', $plugin['required'] ? 'Yes' : 'No' ) );

		if ( ! empty( $plugin['source'] ) ) {
			WP_CLI::line( sprintf( 'Source: %s', $plugin['source'] ) );
			WP_CLI::line( '' );
			WP_CLI::line( 'Testing download...' );

			if ( ! empty( $plugin['bundled'] ) ) {
				if ( file_exists( $plugin['source'] ) ) {
					WP_CLI::success( sprintf( 'Bundled file exists: %s', $plugin['source'] ) );
					WP_CLI::line( sprintf( 'File size: %s', size_format( filesize( $plugin['source'] ) ) ) );
				} else {
					WP_CLI::error( sprintf( 'Bundled file not found: %s', $plugin['source'] ) );
				}
			} elseif ( ! empty( $plugin['external'] ) ) {
				$response = wp_remote_head( $plugin['source'] );

				if ( is_wp_error( $response ) ) {
					WP_CLI::error( sprintf( 'Failed to reach URL: %s', $response->get_error_message() ) );
				}

				$code = wp_remote_retrieve_response_code( $response );

				if ( 200 === $code ) {
					WP_CLI::success( 'External URL is accessible.' );
					$content_length = wp_remote_retrieve_header( $response, 'content-length' );
					if ( $content_length ) {
						WP_CLI::line( sprintf( 'File size: %s', size_format( $content_length ) ) );
					}
				} else {
					WP_CLI::error( sprintf( 'URL returned HTTP %d', $code ) );
				}
			}
		} else {
			WP_CLI::line( 'Source: WordPress.org' );
			WP_CLI::success( 'This plugin will be downloaded from WordPress.org repository.' );
		}
	}

	/**
	 * Reconcile connectors and run native-settings smoke checks.
	 *
	 * ## OPTIONS
	 *
	 * [--sync=<connector>]
	 * : Run native sync for one connector id before smoke checks.
	 *
	 * ## EXAMPLES
	 *
	 *     wp conjure connectors-smoke
	 *     wp conjure connectors-smoke --sync=yoast-seo
	 *
	 * @when after_wp_load
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 * @return void
	 */
	public function connectors_smoke( $args, $assoc_args ) {
		if ( ! $this->conjure->step_connector_manager ) {
			WP_CLI::error( 'Connector manager is not available.' );
		}

		require_once trailingslashit( $this->conjure->base_path ) . $this->conjure->directory . '/includes/class-conjure-connector-catalog.php';
		require_once trailingslashit( $this->conjure->base_path ) . $this->conjure->directory . '/includes/class-conjure-connector-native-sync.php';

		$sync_id = WP_CLI\Utils\get_flag_value( $assoc_args, 'sync', '' );

		if ( $sync_id ) {
			Conjure_Connector_Native_Sync::apply( $sync_id, array() );
			WP_CLI::line( sprintf( 'Ran native sync for "%s".', $sync_id ) );
		}

		$reconcile = $this->conjure->step_connector_manager->get_connector_reconciliation();

		WP_CLI::line( 'Connector reconciliation:' );
		WP_CLI::line( sprintf( '  On disk: %s', implode( ', ', $reconcile['on_disk'] ) ) );
		WP_CLI::line( sprintf( '  ConjureWP Pro (%s): %s', $reconcile['pro_plugin_price_label'], $reconcile['has_pro_plugin_access'] ? 'active' : 'inactive' ) );
		WP_CLI::line( sprintf( '  Marketing-only UI: %s', implode( ', ', $reconcile['marketing_only'] ) ) );
		WP_CLI::line( '' );

		$rows = array();

		foreach ( $this->conjure->step_connector_manager->get_connectors() as $connector_id => $connector ) {
			$readiness = $connector->get_readiness_status();
			$smoke     = Conjure_Connector_Native_Sync::smoke_test( $connector_id );
			$rows[]    = array(
				'ID'        => $connector_id,
				'Tier'      => $readiness['integration_tier'],
				'Pro'       => class_exists( 'Conjure_Connector_Catalog' ) && Conjure_Connector_Catalog::has_pro_plugin_access() ? 'yes' : 'no',
				'Plugin'    => $connector->is_plugin_active() ? 'active' : 'inactive',
				'Sync'      => $connector->has_native_sync() ? 'yes' : 'no',
				'Smoke'     => $smoke['pass'] ? 'pass' : 'fail',
				'Readiness' => $readiness['code'],
			);

			if ( ! $smoke['pass'] && ! empty( $smoke['messages'] ) ) {
				foreach ( $smoke['messages'] as $message ) {
					WP_CLI::warning( sprintf( '%s: %s', $connector_id, $message ) );
				}
			}
		}

		WP_CLI\Utils\format_items( 'table', $rows, array( 'ID', 'Tier', 'Pro', 'Plugin', 'Sync', 'Smoke', 'Readiness' ) );
	}
}
