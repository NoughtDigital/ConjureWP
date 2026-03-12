<?php
/**
 * Whippet Performance step connector.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'conjurewp_is_whippet_active' ) ) {
	/**
	 * Check whether Whippet is active.
	 *
	 * @return bool
	 */
	function conjurewp_is_whippet_active() {
		return defined( 'WHIPPET_VERSION' );
	}
}

/**
 * Whippet Performance connector step.
 */
class Conjure_Step_Connector_Whippet extends Conjure_Step_Connector_Base {

	/**
	 * Feature groups for organising the step render.
	 *
	 * @var array
	 */
	protected static $feature_groups = array(
		'unloading' => array(
			'label'    => 'Asset unloading',
			'features' => array( 'asset_unloading' ),
		),
		'cleanup'   => array(
			'label'    => 'WordPress cleanup',
			'features' => array( 'disable_emoji', 'disable_embeds', 'disable_rest_endpoints' ),
		),
		'scripts'   => array(
			'label'    => 'Script optimisation',
			'features' => array( 'script_optimisation' ),
		),
	);

	/**
	 * Get available connector features.
	 *
	 * @return array
	 */
	public function get_features() {
		return array(
			'asset_unloading'        => array(
				'label'           => __( 'Enable asset unloading', 'ConjureWP' ),
				'description'     => __( 'Selectively unload CSS and JavaScript assets on a per-page basis.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'disable_emoji'          => array(
				'label'           => __( 'Disable emoji', 'ConjureWP' ),
				'description'     => __( 'Remove WordPress emoji scripts and styles for cleaner markup.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'disable_embeds'         => array(
				'label'           => __( 'Disable embeds', 'ConjureWP' ),
				'description'     => __( 'Remove WordPress oEmbed scripts to reduce page weight.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'disable_rest_endpoints' => array(
				'label'           => __( 'Disable REST endpoints', 'ConjureWP' ),
				'description'     => __( 'Restrict unnecessary REST API endpoints to reduce attack surface.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'script_optimisation'    => array(
				'label'           => __( 'Script optimisation', 'ConjureWP' ),
				'description'     => __( 'Optimise script loading with deferred and asynchronous execution.', 'ConjureWP' ),
				'default_enabled' => true,
			),
		);
	}

	/**
	 * Render the Whippet step.
	 *
	 * @return void
	 */
	public function render_step() {
		$enabled_features = $this->get_enabled_features();
		$grouped          = $this->get_grouped_features( $enabled_features );
		?>
		<form method="post">
			<?php wp_nonce_field( 'conjure' ); ?>

			<div class="conjure__content--transition">
				<?php echo wp_kses( $this->conjure->svg( array( 'icon' => 'plugins' ) ), $this->conjure->svg_allowed_html() ); ?>

				<h1><?php esc_html_e( 'Whippet Setup', 'ConjureWP' ); ?></h1>
				<p><?php esc_html_e( 'Configure your Whippet Performance asset unloading and script optimisation settings below.', 'ConjureWP' ); ?></p>

				<?php $this->render_version_update_toggle(); ?>

				<?php if ( ! $this->can_run() ) : ?>
					<p class="conjure__notice conjure__notice--warning">
						<?php esc_html_e( 'Whippet is not currently active. Please activate it first, then return to this step.', 'ConjureWP' ); ?>
					</p>
				<?php elseif ( empty( $enabled_features ) ) : ?>
					<p><?php esc_html_e( 'No Whippet features are currently enabled for this connector.', 'ConjureWP' ); ?></p>
				<?php else : ?>
					<div class="conjure__feature-groups-scroll">
						<?php foreach ( $grouped as $group_id => $group ) : ?>
							<?php if ( empty( $group['features'] ) ) { continue; } ?>
							<div class="conjure__feature-group">
								<h3 class="conjure__feature-group-title"><?php echo esc_html( $group['label'] ); ?></h3>
								<?php foreach ( $group['features'] as $fid => $feature ) : ?>
									<?php $this->render_feature_fields( $fid, $feature ); ?>
								<?php endforeach; ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<footer class="conjure__content__footer">
				<a href="<?php echo esc_url( $this->conjure->step_next_link() ); ?>" class="conjure__button conjure__button--skip"><?php esc_html_e( 'Skip', 'ConjureWP' ); ?></a>
				<?php if ( $this->can_run() && ! empty( $enabled_features ) ) : ?>
					<button type="submit" name="save_step" value="1" class="conjure__button conjure__button--next conjure__button--colorchange">
						<?php esc_html_e( 'Apply Whippet Setup', 'ConjureWP' ); ?>
					</button>
				<?php endif; ?>
			</footer>
		</form>
		<?php
	}

	/**
	 * Render the appropriate form fields for a single feature.
	 *
	 * @param string $feature_id Feature identifier.
	 * @param array  $feature    Feature data.
	 * @return void
	 */
	protected function render_feature_fields( $feature_id, $feature ) {
		switch ( $feature_id ) {
			case 'asset_unloading':
				$this->render_asset_unloading_fields();
				break;

			case 'disable_emoji':
				$this->render_disable_emoji_fields();
				break;

			case 'disable_embeds':
				$this->render_disable_embeds_fields();
				break;

			case 'disable_rest_endpoints':
				$this->render_disable_rest_endpoints_fields();
				break;

			case 'script_optimisation':
				$this->render_script_optimisation_fields();
				break;
		}
	}

	/**
	 * Render asset unloading fields.
	 *
	 * @return void
	 */
	protected function render_asset_unloading_fields() {
		$this->render_checkbox_field(
			'conjure_whippet_enable_asset_unloading',
			__( 'Enable per-page asset unloading', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_whippet_unload_dashicons',
			__( 'Unload Dashicons on the front end', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_whippet_unload_jquery_migrate',
			__( 'Unload jQuery Migrate', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_whippet_unload_gutenberg_css',
			__( 'Unload Gutenberg block CSS on non-block pages', 'ConjureWP' ),
			false
		);
	}

	/**
	 * Render disable emoji fields.
	 *
	 * @return void
	 */
	protected function render_disable_emoji_fields() {
		$this->render_checkbox_field(
			'conjure_whippet_disable_emoji_scripts',
			__( 'Remove emoji scripts', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_whippet_disable_emoji_dns_prefetch',
			__( 'Remove emoji DNS prefetch', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_whippet_disable_emoji_tinymce',
			__( 'Remove emoji TinyMCE plugin', 'ConjureWP' ),
			true
		);
	}

	/**
	 * Render disable embeds fields.
	 *
	 * @return void
	 */
	protected function render_disable_embeds_fields() {
		$this->render_checkbox_field(
			'conjure_whippet_disable_embeds',
			__( 'Disable WordPress oEmbed', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_whippet_disable_embed_discover',
			__( 'Disable oEmbed auto-discovery', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_whippet_remove_embed_rewrite',
			__( 'Remove embed rewrite rules', 'ConjureWP' ),
			true
		);
	}

	/**
	 * Render disable REST endpoints fields.
	 *
	 * @return void
	 */
	protected function render_disable_rest_endpoints_fields() {
		$this->render_checkbox_field(
			'conjure_whippet_disable_users_endpoint',
			__( 'Disable users REST endpoint for non-authenticated requests', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_whippet_disable_xmlrpc',
			__( 'Disable XML-RPC', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_whippet_remove_rsd_link',
			__( 'Remove RSD link from head', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_whippet_remove_wlwmanifest',
			__( 'Remove Windows Live Writer manifest link', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_whippet_remove_shortlink',
			__( 'Remove shortlink from head', 'ConjureWP' ),
			true
		);
	}

	/**
	 * Render script optimisation fields.
	 *
	 * @return void
	 */
	protected function render_script_optimisation_fields() {
		$this->render_checkbox_field(
			'conjure_whippet_defer_scripts',
			__( 'Defer JavaScript loading', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_whippet_async_scripts',
			__( 'Load scripts asynchronously where safe', 'ConjureWP' ),
			false
		);
		$this->render_checkbox_field(
			'conjure_whippet_remove_query_strings',
			__( 'Remove query strings from static resources', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_whippet_disable_heartbeat',
			__( 'Disable WordPress heartbeat on front end', 'ConjureWP' ),
			true
		);

		$current_frequency = get_option( 'conjure_whippet_heartbeat_admin_frequency', '60' );
		$frequency_options = array(
			'15'  => __( '15 seconds (default)', 'ConjureWP' ),
			'30'  => __( '30 seconds', 'ConjureWP' ),
			'60'  => __( '60 seconds', 'ConjureWP' ),
			'120' => __( '120 seconds', 'ConjureWP' ),
		);
		?>
		<div class="conjure__field-group">
			<label for="conjure_whippet_heartbeat_admin_frequency" class="conjure__field-label">
				<?php esc_html_e( 'Admin heartbeat frequency', 'ConjureWP' ); ?>
			</label>
			<select id="conjure_whippet_heartbeat_admin_frequency" name="conjure_whippet_heartbeat_admin_frequency" class="conjure__select">
				<?php foreach ( $frequency_options as $val => $label ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $current_frequency, $val ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	/**
	 * Render a checkbox field.
	 *
	 * @param string $name    Field name.
	 * @param string $label   Field label.
	 * @param bool   $checked Current checked state.
	 * @return void
	 */
	protected function render_checkbox_field( $name, $label, $checked ) {
		?>
		<div class="conjure__field-group conjure__field-group--checkbox">
			<label for="<?php echo esc_attr( $name ); ?>">
				<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="0" />
				<input
					type="checkbox"
					id="<?php echo esc_attr( $name ); ?>"
					name="<?php echo esc_attr( $name ); ?>"
					value="1"
					<?php checked( $checked ); ?>
				/>
				<?php echo esc_html( $label ); ?>
			</label>
		</div>
		<?php
	}

	/**
	 * Handle the Whippet step.
	 *
	 * @return bool
	 */
	public function handle_step() {
		if ( ! $this->can_run() ) {
			wp_safe_redirect( $this->conjure->step_next_link() );
			exit;
		}

		$this->maybe_update_plugin();

		$enabled_keys = array_keys( $this->get_enabled_features() );

		if ( in_array( 'asset_unloading', $enabled_keys, true ) ) {
			update_option( 'conjure_whippet_enable_asset_unloading', ! empty( $_POST['conjure_whippet_enable_asset_unloading'] ) );
			update_option( 'conjure_whippet_unload_dashicons', ! empty( $_POST['conjure_whippet_unload_dashicons'] ) );
			update_option( 'conjure_whippet_unload_jquery_migrate', ! empty( $_POST['conjure_whippet_unload_jquery_migrate'] ) );
			update_option( 'conjure_whippet_unload_gutenberg_css', ! empty( $_POST['conjure_whippet_unload_gutenberg_css'] ) );
		}

		if ( in_array( 'disable_emoji', $enabled_keys, true ) ) {
			update_option( 'conjure_whippet_disable_emoji_scripts', ! empty( $_POST['conjure_whippet_disable_emoji_scripts'] ) );
			update_option( 'conjure_whippet_disable_emoji_dns_prefetch', ! empty( $_POST['conjure_whippet_disable_emoji_dns_prefetch'] ) );
			update_option( 'conjure_whippet_disable_emoji_tinymce', ! empty( $_POST['conjure_whippet_disable_emoji_tinymce'] ) );
		}

		if ( in_array( 'disable_embeds', $enabled_keys, true ) ) {
			update_option( 'conjure_whippet_disable_embeds', ! empty( $_POST['conjure_whippet_disable_embeds'] ) );
			update_option( 'conjure_whippet_disable_embed_discover', ! empty( $_POST['conjure_whippet_disable_embed_discover'] ) );
			update_option( 'conjure_whippet_remove_embed_rewrite', ! empty( $_POST['conjure_whippet_remove_embed_rewrite'] ) );
		}

		if ( in_array( 'disable_rest_endpoints', $enabled_keys, true ) ) {
			update_option( 'conjure_whippet_disable_users_endpoint', ! empty( $_POST['conjure_whippet_disable_users_endpoint'] ) );
			update_option( 'conjure_whippet_disable_xmlrpc', ! empty( $_POST['conjure_whippet_disable_xmlrpc'] ) );
			update_option( 'conjure_whippet_remove_rsd_link', ! empty( $_POST['conjure_whippet_remove_rsd_link'] ) );
			update_option( 'conjure_whippet_remove_wlwmanifest', ! empty( $_POST['conjure_whippet_remove_wlwmanifest'] ) );
			update_option( 'conjure_whippet_remove_shortlink', ! empty( $_POST['conjure_whippet_remove_shortlink'] ) );
		}

		if ( in_array( 'script_optimisation', $enabled_keys, true ) ) {
			update_option( 'conjure_whippet_defer_scripts', ! empty( $_POST['conjure_whippet_defer_scripts'] ) );
			update_option( 'conjure_whippet_async_scripts', ! empty( $_POST['conjure_whippet_async_scripts'] ) );
			update_option( 'conjure_whippet_remove_query_strings', ! empty( $_POST['conjure_whippet_remove_query_strings'] ) );
			update_option( 'conjure_whippet_disable_heartbeat', ! empty( $_POST['conjure_whippet_disable_heartbeat'] ) );

			$allowed_frequencies = array( '15', '30', '60', '120' );
			$frequency           = isset( $_POST['conjure_whippet_heartbeat_admin_frequency'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_whippet_heartbeat_admin_frequency'] ) ) : '60';
			if ( ! in_array( $frequency, $allowed_frequencies, true ) ) {
				$frequency = '60';
			}
			update_option( 'conjure_whippet_heartbeat_admin_frequency', $frequency );
		}

		$this->conjure->mark_step_completed( $this->get_step_key() );
		wp_safe_redirect( $this->conjure->step_next_link() );
		exit;
	}

	/**
	 * Group enabled features for rendering.
	 *
	 * @param array $enabled_features Enabled features.
	 * @return array
	 */
	protected function get_grouped_features( $enabled_features ) {
		$grouped = array();

		foreach ( self::$feature_groups as $group_id => $group ) {
			$group_features = array();

			foreach ( $group['features'] as $feature_id ) {
				if ( isset( $enabled_features[ $feature_id ] ) ) {
					$group_features[ $feature_id ] = $enabled_features[ $feature_id ];
				}
			}

			if ( ! empty( $group_features ) ) {
				$grouped[ $group_id ] = array(
					'label'    => $group['label'],
					'features' => $group_features,
				);
			}
		}

		foreach ( $enabled_features as $feature_id => $feature ) {
			$found = false;

			foreach ( self::$feature_groups as $group ) {
				if ( in_array( $feature_id, $group['features'], true ) ) {
					$found = true;
					break;
				}
			}

			if ( ! $found ) {
				if ( ! isset( $grouped['other'] ) ) {
					$grouped['other'] = array(
						'label'    => __( 'Other', 'ConjureWP' ),
						'features' => array(),
					);
				}
				$grouped['other']['features'][ $feature_id ] = $feature;
			}
		}

		return $grouped;
	}
}
