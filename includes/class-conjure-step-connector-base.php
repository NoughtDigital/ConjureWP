<?php
/**
 * Base class for filesystem step connectors.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conjure step connector base class.
 */
abstract class Conjure_Step_Connector_Base {

	/**
	 * Main Conjure instance.
	 *
	 * @var Conjure
	 */
	protected $conjure;

	/**
	 * Connector definition.
	 *
	 * @var array
	 */
	protected $definition = array();

	/**
	 * Constructor.
	 *
	 * @param Conjure $conjure    Main Conjure instance.
	 * @param array   $definition Connector definition.
	 */
	public function __construct( $conjure, $definition = array() ) {
		$this->conjure    = $conjure;
		$this->definition = is_array( $definition ) ? $definition : array();
	}

	/**
	 * Get the connector identifier.
	 *
	 * @return string
	 */
	public function get_id() {
		return isset( $this->definition['id'] ) ? sanitize_key( $this->definition['id'] ) : '';
	}

	/**
	 * Get the connector name.
	 *
	 * @return string
	 */
	public function get_name() {
		return isset( $this->definition['name'] ) ? (string) $this->definition['name'] : ucfirst( $this->get_id() );
	}

	/**
	 * Get the connector description.
	 *
	 * @return string
	 */
	public function get_description() {
		return isset( $this->definition['description'] ) ? (string) $this->definition['description'] : '';
	}

	/**
	 * Get the connector source.
	 *
	 * @return string
	 */
	public function get_source() {
		return isset( $this->definition['source'] ) ? sanitize_key( $this->definition['source'] ) : 'steps_dir';
	}

	/**
	 * Get the connector base path.
	 *
	 * @return string
	 */
	public function get_path() {
		return isset( $this->definition['path'] ) ? (string) $this->definition['path'] : '';
	}

	/**
	 * Get the wizard step key for this connector.
	 *
	 * @return string
	 */
	public function get_step_key() {
		return isset( $this->definition['step_key'] ) ? sanitize_key( $this->definition['step_key'] ) : $this->get_id();
	}

	/**
	 * Get the wizard step name for this connector.
	 *
	 * @return string
	 */
	public function get_step_name() {
		return isset( $this->definition['step_name'] ) ? (string) $this->definition['step_name'] : $this->get_name();
	}

	/**
	 * Get connector plugin metadata.
	 *
	 * @return array
	 */
	public function get_plugin() {
		$plugin = isset( $this->definition['plugin'] ) && is_array( $this->definition['plugin'] ) ? $this->definition['plugin'] : array();

		return wp_parse_args(
			$plugin,
			array(
				'name'             => $this->get_name(),
				'slug'             => '',
				'file'             => '',
				'active_callback'  => '',
				'version_constant' => '',
			)
		);
	}

	/**
	 * Get registered features for this connector.
	 *
	 * @return array
	 */
	public function get_features() {
		return array();
	}

	/**
	 * Resolve features after admin settings and theme overrides.
	 *
	 * @param array $settings Stored connector settings.
	 * @return array
	 */
	public function get_resolved_features( $settings = array() ) {
		$feature_definitions = $this->get_features();
		$feature_definitions = apply_filters( 'conjure_connector_features', $feature_definitions, $this->get_id(), $this->conjure );
		$feature_definitions = apply_filters( 'conjure_connector_features_' . $this->get_id(), $feature_definitions, $this->conjure );

		$disabled_features = apply_filters( 'conjure_connector_disabled_features', array(), $this->get_id(), $this->conjure );
		$disabled_features = apply_filters( 'conjure_connector_disabled_features_' . $this->get_id(), $disabled_features, $this->conjure );
		$disabled_features = array_map( 'sanitize_key', is_array( $disabled_features ) ? $disabled_features : array() );

		$resolved = array();

		foreach ( $feature_definitions as $feature_id => $feature ) {
			$feature_id = sanitize_key( $feature_id );

			if ( empty( $feature_id ) || ! is_array( $feature ) ) {
				continue;
			}

			$feature = wp_parse_args(
				$feature,
				array(
					'label'           => ucfirst( str_replace( '_', ' ', $feature_id ) ),
					'description'     => '',
					'default_enabled' => true,
				)
			);

			$saved_enabled = isset( $settings['features'][ $feature_id ] ) ? (bool) $settings['features'][ $feature_id ] : (bool) $feature['default_enabled'];
			$enabled       = $saved_enabled;

			if ( in_array( $feature_id, $disabled_features, true ) ) {
				$enabled = false;
			}

			$enabled = (bool) apply_filters( 'conjure_connector_feature_enabled', $enabled, $this->get_id(), $feature_id, $feature, $this->conjure );
			$enabled = (bool) apply_filters( 'conjure_connector_feature_enabled_' . $this->get_id(), $enabled, $feature_id, $feature, $this->conjure );

			$resolved[ $feature_id ] = array(
				'label'           => $feature['label'],
				'description'     => $feature['description'],
				'default_enabled' => (bool) $feature['default_enabled'],
				'saved_enabled'   => $saved_enabled,
				'enabled'         => $enabled,
				'locked'          => ( $saved_enabled !== $enabled ),
			);
		}

		return $resolved;
	}

	/**
	 * Build default connector settings.
	 *
	 * @return array
	 */
	public function get_default_settings() {
		$features = array();

		foreach ( $this->get_features() as $feature_id => $feature ) {
			if ( empty( $feature_id ) || ! is_array( $feature ) ) {
				continue;
			}

			$features[ sanitize_key( $feature_id ) ] = ! empty( $feature['default_enabled'] );
		}

		return array(
			'enabled'  => false,
			'features' => $features,
		);
	}

	/**
	 * Get saved connector settings.
	 *
	 * @return array
	 */
	public function get_settings() {
		$all_settings = get_option( 'conjure_' . $this->conjure->slug . '_connector_settings', array() );
		$settings     = isset( $all_settings[ $this->get_id() ] ) && is_array( $all_settings[ $this->get_id() ] ) ? $all_settings[ $this->get_id() ] : array();
		$defaults     = $this->get_default_settings();
		$settings     = wp_parse_args( $settings, $defaults );
		$features     = array();

		foreach ( $defaults['features'] as $feature_id => $default_enabled ) {
			$features[ $feature_id ] = isset( $settings['features'][ $feature_id ] ) ? (bool) $settings['features'][ $feature_id ] : (bool) $default_enabled;
		}

		$settings['features'] = $features;

		return $settings;
	}

	/**
	 * Get enabled features for the current connector state.
	 *
	 * @return array
	 */
	public function get_enabled_features() {
		return array_filter(
			$this->get_resolved_features( $this->get_settings() ),
			static function ( $feature ) {
				return ! empty( $feature['enabled'] );
			}
		);
	}

	/**
	 * Determine whether the related plugin is installed.
	 *
	 * @return bool
	 */
	public function is_plugin_installed() {
		$plugin = $this->get_plugin();

		if ( empty( $plugin['file'] ) ) {
			return true;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( function_exists( 'get_plugins' ) ) {
			$plugins = get_plugins();

			return isset( $plugins[ $plugin['file'] ] );
		}

		return false;
	}

	/**
	 * Determine whether the related plugin is active.
	 *
	 * @return bool
	 */
	public function is_plugin_active() {
		$plugin = $this->get_plugin();

		if ( ! empty( $plugin['active_callback'] ) && is_callable( $plugin['active_callback'] ) ) {
			return (bool) call_user_func( $plugin['active_callback'] );
		}

		if ( empty( $plugin['file'] ) ) {
			return true;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( function_exists( 'is_plugin_active' ) ) {
			return is_plugin_active( $plugin['file'] );
		}

		return false;
	}

	/**
	 * Get the plugin status for admin screens.
	 *
	 * @return array
	 */
	public function get_plugin_status() {
		$installed = $this->is_plugin_installed();
		$active    = $this->is_plugin_active();
		$plugin    = $this->get_plugin();
		$name      = ! empty( $plugin['name'] ) ? $plugin['name'] : $this->get_name();

		if ( $installed && $active ) {
			$label = sprintf(
				/* translators: %s: plugin name */
				__( '%s active', 'ConjureWP' ),
				$name
			);
		} elseif ( $installed ) {
			$label = sprintf(
				/* translators: %s: plugin name */
				__( '%s installed but inactive', 'ConjureWP' ),
				$name
			);
		} else {
			$label = sprintf(
				/* translators: %s: plugin name */
				__( '%s not installed', 'ConjureWP' ),
				$name
			);
		}

		return array(
			'installed' => $installed,
			'active'    => $active,
			'label'     => $label,
		);
	}

	/**
	 * Determine whether the connector can appear in the wizard.
	 *
	 * @param array $settings Resolved connector settings.
	 * @return bool
	 */
	public function should_show_in_wizard( $settings = array() ) {
		if ( empty( $settings['enabled'] ) ) {
			return false;
		}

		foreach ( $this->get_resolved_features( $settings ) as $feature ) {
			if ( ! empty( $feature['enabled'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether the connector can actually execute its features at runtime.
	 *
	 * @return bool
	 */
	public function can_run() {
		return $this->is_plugin_active();
	}

	/**
	 * Get the installed version of the connector's plugin.
	 *
	 * @return string
	 */
	public function get_installed_version() {
		$plugin = $this->get_plugin();

		if ( ! empty( $plugin['version_constant'] ) && defined( $plugin['version_constant'] ) ) {
			return (string) constant( $plugin['version_constant'] );
		}

		if ( ! empty( $plugin['file'] ) ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$plugin_file = trailingslashit( WP_PLUGIN_DIR ) . $plugin['file'];

			if ( file_exists( $plugin_file ) && function_exists( 'get_plugin_data' ) ) {
				$data = get_plugin_data( $plugin_file, false, false );

				if ( ! empty( $data['Version'] ) ) {
					return (string) $data['Version'];
				}
			}
		}

		return '';
	}

	/**
	 * Get the latest available version from the WordPress.org API.
	 *
	 * Caches the result for 12 hours. Returns an empty string for plugins
	 * not hosted on WordPress.org (e.g. Bricks, Gravity Forms).
	 *
	 * @return string
	 */
	public function get_latest_version() {
		$plugin = $this->get_plugin();
		$slug   = ! empty( $plugin['slug'] ) ? $plugin['slug'] : '';

		if ( empty( $slug ) ) {
			return '';
		}

		$transient_key = 'conjure_latest_ver_' . sanitize_key( $slug );
		$cached        = get_transient( $transient_key );

		if ( false !== $cached ) {
			return (string) $cached;
		}

		$update_plugins = get_site_transient( 'update_plugins' );

		if ( is_object( $update_plugins ) ) {
			$file = ! empty( $plugin['file'] ) ? $plugin['file'] : '';

			if ( $file && ! empty( $update_plugins->response[ $file ]->new_version ) ) {
				$version = (string) $update_plugins->response[ $file ]->new_version;
				set_transient( $transient_key, $version, 12 * HOUR_IN_SECONDS );

				return $version;
			}

			if ( $file && ! empty( $update_plugins->no_update[ $file ]->new_version ) ) {
				$version = (string) $update_plugins->no_update[ $file ]->new_version;
				set_transient( $transient_key, $version, 12 * HOUR_IN_SECONDS );

				return $version;
			}
		}

		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}

		if ( function_exists( 'plugins_api' ) ) {
			$api = plugins_api(
				'plugin_information',
				array(
					'slug'   => $slug,
					'fields' => array( 'version' => true ),
				)
			);

			if ( ! is_wp_error( $api ) && ! empty( $api->version ) ) {
				set_transient( $transient_key, (string) $api->version, 12 * HOUR_IN_SECONDS );

				return (string) $api->version;
			}
		}

		set_transient( $transient_key, '', 6 * HOUR_IN_SECONDS );

		return '';
	}

	/**
	 * Check whether an update is available for the connector's plugin.
	 *
	 * @return bool
	 */
	public function has_update_available() {
		$installed = $this->get_installed_version();
		$latest    = $this->get_latest_version();

		if ( empty( $installed ) || empty( $latest ) ) {
			return false;
		}

		return version_compare( $installed, $latest, '<' );
	}

	/**
	 * Get a summary of the plugin version state.
	 *
	 * @return array
	 */
	public function get_version_info() {
		return array(
			'installed'        => $this->get_installed_version(),
			'latest'           => $this->get_latest_version(),
			'update_available' => $this->has_update_available(),
		);
	}

	/**
	 * Render the plugin version badge and optional update toggle inside a wizard step.
	 *
	 * @return void
	 */
	public function render_version_update_toggle() {
		if ( ! $this->is_plugin_installed() ) {
			return;
		}

		$version_info = $this->get_version_info();
		$installed    = $version_info['installed'];
		$latest       = $version_info['latest'];
		$has_update   = $version_info['update_available'];
		$plugin       = $this->get_plugin();
		$plugin_name  = ! empty( $plugin['name'] ) ? $plugin['name'] : $this->get_name();
		$field_name   = 'conjure_update_plugin_' . sanitize_key( $this->get_id() );

		if ( empty( $installed ) ) {
			return;
		}
		?>
		<div class="conjure__field-group conjure__field-group--version">
			<div class="conjure__version-row">
				<span class="conjure__version-label">
					<?php
					printf(
						/* translators: 1: plugin name, 2: version number */
						esc_html__( '%1$s v%2$s installed', 'ConjureWP' ),
						esc_html( $plugin_name ),
						esc_html( $installed )
					);
					?>
				</span>
				<?php if ( $has_update && ! empty( $latest ) ) : ?>
					<span class="conjure__version-badge conjure__version-badge--update">
						<?php
						printf(
							/* translators: %s: latest version number */
							esc_html__( 'v%s available', 'ConjureWP' ),
							esc_html( $latest )
						);
						?>
					</span>
				<?php else : ?>
					<span class="conjure__version-badge conjure__version-badge--current">
						<?php esc_html_e( 'Up to date', 'ConjureWP' ); ?>
					</span>
				<?php endif; ?>
			</div>
			<?php if ( $has_update && current_user_can( 'update_plugins' ) ) : ?>
				<div class="conjure__field-group conjure__field-group--checkbox">
					<label for="<?php echo esc_attr( $field_name ); ?>">
						<input type="hidden" name="<?php echo esc_attr( $field_name ); ?>" value="0" />
						<input
							type="checkbox"
							id="<?php echo esc_attr( $field_name ); ?>"
							name="<?php echo esc_attr( $field_name ); ?>"
							value="1"
						/>
						<?php
						printf(
							/* translators: 1: plugin name, 2: latest version number */
							esc_html__( 'Update %1$s to v%2$s during this step', 'ConjureWP' ),
							esc_html( $plugin_name ),
							esc_html( $latest )
						);
						?>
					</label>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Process a plugin update if the update toggle was checked.
	 *
	 * Call this at the start of handle_step() before other processing.
	 *
	 * @return bool Whether an update was performed.
	 */
	public function maybe_update_plugin() {
		$field_name = 'conjure_update_plugin_' . sanitize_key( $this->get_id() );

		if ( empty( $_POST[ $field_name ] ) ) {
			return false;
		}

		if ( ! current_user_can( 'update_plugins' ) ) {
			return false;
		}

		if ( ! $this->has_update_available() ) {
			return false;
		}

		$plugin = $this->get_plugin();

		if ( empty( $plugin['file'] ) ) {
			return false;
		}

		if ( ! class_exists( 'Plugin_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		if ( ! class_exists( 'Plugin_Upgrader' ) ) {
			return false;
		}

		$skin     = new \Automatic_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader( $skin );
		$result   = $upgrader->upgrade( $plugin['file'] );

		$transient_key = 'conjure_latest_ver_' . sanitize_key( $plugin['slug'] );
		delete_transient( $transient_key );

		return ( true === $result || null === $result );
	}

	/**
	 * Build the wizard step definition for this connector.
	 *
	 * @param array $settings Resolved connector settings.
	 * @return array
	 */
	public function get_step_definition( $settings = array() ) {
		if ( ! $this->should_show_in_wizard( $settings ) ) {
			return array();
		}

		$definition = array(
			'name'    => $this->get_step_name(),
			'view'    => array( $this, 'render_step' ),
			'handler' => array( $this, 'handle_step' ),
		);

		if ( ! $this->can_run() ) {
			$definition['can_run'] = false;
		}

		return $definition;
	}

	/**
	 * Render the wizard step.
	 *
	 * @return void
	 */
	abstract public function render_step();

	/**
	 * Handle wizard step submissions.
	 *
	 * @return bool
	 */
	abstract public function handle_step();
}
