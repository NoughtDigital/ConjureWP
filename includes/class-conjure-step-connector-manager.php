<?php
/**
 * Filesystem-based step connector manager.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conjure step connector manager class.
 */
class Conjure_Step_Connector_Manager {

	/**
	 * Preview query argument name.
	 *
	 * @var string
	 */
	const PREVIEW_QUERY_ARG = 'conjure_preview';

	/**
	 * Main Conjure instance.
	 *
	 * @var Conjure
	 */
	protected $conjure;

	/**
	 * Loaded connectors.
	 *
	 * @var array<string,Conjure_Step_Connector_Base>
	 */
	protected $connectors = null;

	/**
	 * Step to connector map.
	 *
	 * @var array<string,string>
	 */
	protected $step_connector_map = array();

	/**
	 * Active preview state.
	 *
	 * @var array|null
	 */
	protected $active_preview_state = null;

	/**
	 * Active preview token.
	 *
	 * @var string
	 */
	protected $active_preview_token = '';

	/**
	 * Whether preview state has been loaded.
	 *
	 * @var bool
	 */
	protected $preview_state_loaded = false;

	/**
	 * Constructor.
	 *
	 * @param Conjure $conjure Main Conjure instance.
	 */
	public function __construct( $conjure ) {
		$this->conjure = $conjure;

		add_filter( 'conjure_steps', array( $this, 'inject_steps' ), 5 );
		add_filter( 'conjure_steps', array( $this, 'apply_saved_step_order' ), 200 );
	}

	/**
	 * Build a preview URL for the submitted connector state.
	 *
	 * @param array $submitted_settings Submitted connector settings.
	 * @param array $step_order         Submitted step order.
	 * @return string
	 */
	public function get_preview_url( $submitted_settings, $step_order ) {
		$normalised_settings = array();

		foreach ( $this->get_connectors() as $connector_id => $connector ) {
			$settings = isset( $submitted_settings[ $connector_id ] ) && is_array( $submitted_settings[ $connector_id ] ) ? $submitted_settings[ $connector_id ] : array();
			$normalised_settings[ $connector_id ] = $this->normalise_connector_settings( $connector, $settings );
		}

		$token = wp_generate_uuid4();

		set_transient(
			$this->get_preview_transient_key( $token ),
			array(
				'user_id'            => get_current_user_id(),
				'connector_settings' => $normalised_settings,
				'step_order'         => $this->normalise_step_order( $step_order ),
			),
			10 * MINUTE_IN_SECONDS
		);

		return add_query_arg( self::PREVIEW_QUERY_ARG, $token, $this->conjure->get_wizard_url() );
	}

	/**
	 * Determine whether a preview state is active.
	 *
	 * @return bool
	 */
	public function is_preview_active() {
		return ! empty( $this->get_active_preview_state() );
	}

	/**
	 * Get the active preview token.
	 *
	 * @return string
	 */
	public function get_active_preview_token() {
		$this->get_active_preview_state();

		return $this->active_preview_token;
	}

	/**
	 * Get the active preview state.
	 *
	 * @return array
	 */
	public function get_active_preview_state() {
		if ( $this->preview_state_loaded ) {
			return is_array( $this->active_preview_state ) ? $this->active_preview_state : array();
		}

		$this->preview_state_loaded = true;
		$this->active_preview_state = array();

		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->active_preview_state;
		}

		$token = isset( $_GET[ self::PREVIEW_QUERY_ARG ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::PREVIEW_QUERY_ARG ] ) ) : '';

		if ( empty( $token ) ) {
			return $this->active_preview_state;
		}

		$preview_state = get_transient( $this->get_preview_transient_key( $token ) );

		if ( ! is_array( $preview_state ) ) {
			return $this->active_preview_state;
		}

		if ( empty( $preview_state['user_id'] ) || (int) $preview_state['user_id'] !== get_current_user_id() ) {
			return $this->active_preview_state;
		}

		$this->active_preview_state = $preview_state;
		$this->active_preview_token = $token;

		return $this->active_preview_state;
	}

	/**
	 * Get the connector settings option name.
	 *
	 * @return string
	 */
	public function get_option_name() {
		return 'conjure_' . $this->conjure->slug . '_connector_settings';
	}

	/**
	 * Get the saved step order option name.
	 *
	 * @return string
	 */
	public function get_step_order_option_name() {
		return 'conjure_' . $this->conjure->slug . '_step_order';
	}

	/**
	 * Get the root directories to scan for connectors.
	 *
	 * @return array
	 */
	public function get_steps_root_paths() {
		$paths = array();

		if ( function_exists( 'conjurewp_get_runtime_path' ) ) {
			$paths[] = conjurewp_get_runtime_path( 'steps' );
		}

		if ( function_exists( 'conjurewp_get_theme_embed_root_path' ) ) {
			$theme_root = conjurewp_get_theme_embed_root_path( $this->conjure->base_path );
			if ( ! empty( $theme_root ) ) {
				$paths[] = trailingslashit( $theme_root ) . 'steps';
			}
		}

		$paths = array_filter(
			array_map(
				static function ( $path ) {
					return is_string( $path ) ? untrailingslashit( $path ) : '';
				},
				$paths
			)
		);

		$paths = array_values( array_unique( $paths ) );

		return array_values(
			array_filter(
				$paths,
				static function ( $path ) {
					return is_dir( $path );
				}
			)
		);
	}

	/**
	 * Get all discovered connectors.
	 *
	 * @return array<string,Conjure_Step_Connector_Base>
	 */
	public function get_connectors() {
		if ( null !== $this->connectors ) {
			return $this->connectors;
		}

		$this->connectors = array();

		foreach ( $this->get_steps_root_paths() as $root_path ) {
			$connector_dirs = glob( trailingslashit( $root_path ) . '*', GLOB_ONLYDIR );

			if ( empty( $connector_dirs ) ) {
				continue;
			}

			sort( $connector_dirs );

			foreach ( $connector_dirs as $connector_dir ) {
				$definition = $this->load_connector_definition( $connector_dir );

				if ( empty( $definition['id'] ) || empty( $definition['class_name'] ) || empty( $definition['class_file'] ) ) {
					continue;
				}

				if ( ! file_exists( $definition['class_file'] ) ) {
					continue;
				}

				require_once $definition['class_file'];

				if ( ! class_exists( $definition['class_name'] ) ) {
					continue;
				}

				$connector = new $definition['class_name']( $this->conjure, $definition );

				if ( ! $connector instanceof Conjure_Step_Connector_Base ) {
					continue;
				}

				$this->connectors[ $connector->get_id() ] = $connector;
			}
		}

		return $this->connectors;
	}

	/**
	 * Load a connector definition from disk.
	 *
	 * @param string $connector_dir Connector directory path.
	 * @return array
	 */
	protected function load_connector_definition( $connector_dir ) {
		$definition_file = trailingslashit( $connector_dir ) . 'connector.php';

		if ( ! file_exists( $definition_file ) ) {
			return array();
		}

		$definition = include $definition_file;

		if ( ! is_array( $definition ) ) {
			return array();
		}

		$definition['id']       = isset( $definition['id'] ) ? sanitize_key( $definition['id'] ) : sanitize_key( basename( $connector_dir ) );
		$definition['name']     = isset( $definition['name'] ) ? (string) $definition['name'] : ucfirst( $definition['id'] );
		$definition['step_key'] = isset( $definition['step_key'] ) ? sanitize_key( $definition['step_key'] ) : $definition['id'];
		$definition['step_name'] = isset( $definition['step_name'] ) ? (string) $definition['step_name'] : $definition['name'];
		$definition['path']     = $connector_dir;
		$definition['source']   = 'steps_dir';

		if ( ! empty( $definition['class_file'] ) ) {
			$definition['class_file'] = trailingslashit( $connector_dir ) . ltrim( $definition['class_file'], '/\\' );
		}

		return $definition;
	}

	/**
	 * Get connector settings for all connectors.
	 *
	 * @return array
	 */
	public function get_all_settings() {
		$preview_state  = $this->get_active_preview_state();
		$saved_settings = isset( $preview_state['connector_settings'] ) && is_array( $preview_state['connector_settings'] )
			? $preview_state['connector_settings']
			: get_option( $this->get_option_name(), array() );
		$settings       = array();

		foreach ( $this->get_connectors() as $connector_id => $connector ) {
			$settings[ $connector_id ] = $this->normalise_connector_settings(
				$connector,
				isset( $saved_settings[ $connector_id ] ) && is_array( $saved_settings[ $connector_id ] ) ? $saved_settings[ $connector_id ] : array()
			);
		}

		return $settings;
	}

	/**
	 * Get settings for a specific connector.
	 *
	 * @param string $connector_id Connector identifier.
	 * @return array
	 */
	public function get_connector_settings( $connector_id ) {
		$all_settings = $this->get_all_settings();

		return isset( $all_settings[ $connector_id ] ) ? $all_settings[ $connector_id ] : array();
	}

	/**
	 * Save connector settings.
	 *
	 * @param array $submitted_settings Submitted settings keyed by connector id.
	 * @return array
	 */
	public function save_settings( $submitted_settings ) {
		$normalised_settings = array();

		foreach ( $this->get_connectors() as $connector_id => $connector ) {
			$settings = isset( $submitted_settings[ $connector_id ] ) && is_array( $submitted_settings[ $connector_id ] ) ? $submitted_settings[ $connector_id ] : array();
			$normalised_settings[ $connector_id ] = $this->normalise_connector_settings( $connector, $settings );
		}

		update_option( $this->get_option_name(), $normalised_settings );

		return $normalised_settings;
	}

	/**
	 * Get the saved step order.
	 *
	 * @return array
	 */
	public function get_saved_step_order() {
		$preview_state = $this->get_active_preview_state();
		$order         = isset( $preview_state['step_order'] ) && is_array( $preview_state['step_order'] )
			? $preview_state['step_order']
			: get_option( $this->get_step_order_option_name(), array() );

		return $this->normalise_step_order( $order );
	}

	/**
	 * Persist a custom step order.
	 *
	 * @param array $step_order Ordered step keys.
	 * @return array
	 */
	public function save_step_order( $step_order ) {
		$step_order = $this->normalise_step_order( $step_order );

		update_option( $this->get_step_order_option_name(), array_values( array_unique( $step_order ) ) );

		return $step_order;
	}

	/**
	 * Get the transient key for a preview payload.
	 *
	 * @param string $token Preview token.
	 * @return string
	 */
	protected function get_preview_transient_key( $token ) {
		return 'conjure_preview_' . md5( $this->conjure->slug . '|' . get_current_user_id() . '|' . $token );
	}

	/**
	 * Normalise a submitted step order array.
	 *
	 * @param array $step_order Raw step order.
	 * @return array
	 */
	protected function normalise_step_order( $step_order ) {
		if ( ! is_array( $step_order ) ) {
			return array();
		}

		return array_values(
			array_unique(
				array_filter(
					array_map( 'sanitize_key', $step_order )
				)
			)
		);
	}

	/**
	 * Normalise connector settings.
	 *
	 * @param Conjure_Step_Connector_Base $connector Connector instance.
	 * @param array                       $settings  Raw settings.
	 * @return array
	 */
	protected function normalise_connector_settings( $connector, $settings ) {
		$defaults = $connector->get_default_settings();
		$settings = wp_parse_args( $settings, $defaults );

		$features = array();
		foreach ( $defaults['features'] as $feature_id => $default_enabled ) {
			$features[ $feature_id ] = isset( $settings['features'][ $feature_id ] ) ? (bool) $settings['features'][ $feature_id ] : (bool) $default_enabled;
		}

		return array(
			'enabled'  => ! empty( $settings['enabled'] ),
			'features' => $features,
		);
	}

	/**
	 * Inject active connector steps before the ready step.
	 *
	 * @param array $steps Existing steps array.
	 * @return array
	 */
	public function inject_steps( $steps ) {
		if ( ! is_array( $steps ) ) {
			return $steps;
		}

		$this->step_connector_map = array();

		foreach ( $this->get_connectors() as $connector ) {
			unset( $steps[ $connector->get_step_key() ] );
		}

		$ready_step = null;
		if ( isset( $steps['ready'] ) ) {
			$ready_step = $steps['ready'];
			unset( $steps['ready'] );
		}

		foreach ( $this->get_connectors() as $connector_id => $connector ) {
			$settings        = $this->get_connector_settings( $connector_id );
			$step_definition = $connector->get_step_definition( $settings );

			if ( empty( $step_definition ) ) {
				continue;
			}

			$steps[ $connector->get_step_key() ] = $step_definition;
			$this->step_connector_map[ $connector->get_step_key() ] = $connector_id;
		}

		if ( null !== $ready_step ) {
			$steps['ready'] = $ready_step;
		}

		return $steps;
	}

	/**
	 * Apply the saved step order to the current steps.
	 *
	 * @param array $steps Existing steps.
	 * @return array
	 */
	public function apply_saved_step_order( $steps ) {
		if ( ! is_array( $steps ) || empty( $steps ) ) {
			return $steps;
		}

		$saved_order = $this->get_saved_step_order();

		if ( empty( $saved_order ) ) {
			return $steps;
		}

		$ordered_steps = array();
		$has_new_steps = false;

		foreach ( $saved_order as $step_key ) {
			if ( isset( $steps[ $step_key ] ) ) {
				$ordered_steps[ $step_key ] = $steps[ $step_key ];
				unset( $steps[ $step_key ] );
			}
		}

		if ( ! empty( $steps ) ) {
			$has_new_steps = true;

			foreach ( $steps as $step_key => $step ) {
				$ordered_steps[ $step_key ] = $step;
			}
		}

		if ( $has_new_steps && isset( $ordered_steps['ready'] ) ) {
			$ready_step = $ordered_steps['ready'];
			unset( $ordered_steps['ready'] );
			$ordered_steps['ready'] = $ready_step;
		}

		return $ordered_steps;
	}

	/**
	 * Get the connector source label for a step.
	 *
	 * @param string $step_key Step key.
	 * @return string
	 */
	public function get_step_source( $step_key ) {
		$core_steps = array( 'welcome', 'child', 'license', 'plugins', 'content', 'ready' );

		if ( in_array( $step_key, $core_steps, true ) ) {
			return 'core';
		}

		if ( isset( $this->step_connector_map[ $step_key ] ) ) {
			return 'steps_dir';
		}

		foreach ( $this->get_connectors() as $connector_id => $connector ) {
			if ( $connector->get_step_key() === $step_key ) {
				return 'steps_dir';
			}
		}

		return 'custom';
	}

	/**
	 * Get the connector id mapped to a step key.
	 *
	 * @param string $step_key Step key.
	 * @return string
	 */
	public function get_step_connector_id( $step_key ) {
		if ( isset( $this->step_connector_map[ $step_key ] ) ) {
			return $this->step_connector_map[ $step_key ];
		}

		foreach ( $this->get_connectors() as $connector_id => $connector ) {
			if ( $connector->get_step_key() === $step_key ) {
				return $connector_id;
			}
		}

		return '';
	}

	/**
	 * Get connector data for the admin screen.
	 *
	 * @return array
	 */
	public function get_admin_connector_data() {
		$data = array();

		foreach ( $this->get_connectors() as $connector_id => $connector ) {
			$settings = $this->get_connector_settings( $connector_id );
			$features = $connector->get_resolved_features( $settings );

			$data[ $connector_id ] = array(
				'id'            => $connector_id,
				'name'          => $connector->get_name(),
				'description'   => $connector->get_description(),
				'step_key'      => $connector->get_step_key(),
				'step_name'     => $connector->get_step_name(),
				'source'        => $connector->get_source(),
				'path'          => $connector->get_path(),
				'plugin'        => $connector->get_plugin(),
				'plugin_status' => $connector->get_plugin_status(),
				'settings'      => $settings,
				'features'      => $features,
				'shows_in_wizard' => $connector->should_show_in_wizard( $settings ),
			);
		}

		return $data;
	}

	/**
	 * Get the current effective step order.
	 *
	 * @param array $steps Steps array.
	 * @return array
	 */
	public function get_current_step_order( $steps ) {
		if ( ! is_array( $steps ) ) {
			return array();
		}

		return array_values( array_map( 'sanitize_key', array_keys( $steps ) ) );
	}

	/**
	 * Export connector state for JSON downloads.
	 *
	 * @return array
	 */
	public function get_export_data() {
		$data = array();

		foreach ( $this->get_connectors() as $connector_id => $connector ) {
			$settings = $this->get_connector_settings( $connector_id );

			$data[ $connector_id ] = array(
				'enabled'  => ! empty( $settings['enabled'] ),
				'features' => isset( $settings['features'] ) ? $settings['features'] : array(),
				'step_key' => $connector->get_step_key(),
			);
		}

		return $data;
	}

	/**
	 * Import connector state from a JSON payload.
	 *
	 * @param array $payload Parsed JSON payload.
	 * @return bool
	 */
	public function import_settings( $payload ) {
		if ( ! is_array( $payload ) || empty( $payload['connector_settings'] ) || ! is_array( $payload['connector_settings'] ) ) {
			return false;
		}

		$this->save_settings( $payload['connector_settings'] );

		if ( ! empty( $payload['step_order'] ) && is_array( $payload['step_order'] ) ) {
			$this->save_step_order( $payload['step_order'] );
		}

		return true;
	}
}
