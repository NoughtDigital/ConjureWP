<?php
/**
 * Jetpack step connector.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'conjurewp_is_jetpack_active' ) ) {
	/**
	 * Check whether Jetpack is active.
	 *
	 * @return bool
	 */
	function conjurewp_is_jetpack_active() {
		return defined( 'JETPACK__VERSION' );
	}
}

/**
 * Jetpack connector step.
 */
class Conjure_Step_Connector_Jetpack extends Conjure_Step_Connector_Base {

	/**
	 * Feature groups for organising the step render.
	 *
	 * @var array
	 */
	protected static $feature_groups = array(
		'connection'  => array(
			'label'    => 'Connection',
			'features' => array( 'connect_wpcom' ),
		),
		'protection'  => array(
			'label'    => 'Protection',
			'features' => array( 'enable_backups', 'configure_security' ),
		),
		'performance' => array(
			'label'    => 'Performance and analytics',
			'features' => array( 'setup_analytics', 'enable_cdn' ),
		),
	);

	/**
	 * Get available connector features.
	 *
	 * @return array
	 */
	public function get_features() {
		return array(
			'connect_wpcom'      => array(
				'label'           => __( 'Connect WordPress.com', 'ConjureWP' ),
				'description'     => __( 'Connect your site to WordPress.com for Jetpack services.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'enable_backups'     => array(
				'label'           => __( 'Enable backups', 'ConjureWP' ),
				'description'     => __( 'Configure Jetpack Backup (VaultPress) for automated site backups.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'configure_security' => array(
				'label'           => __( 'Configure security', 'ConjureWP' ),
				'description'     => __( 'Enable Jetpack security features including brute force protection and downtime monitoring.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'setup_analytics'    => array(
				'label'           => __( 'Setup analytics', 'ConjureWP' ),
				'description'     => __( 'Enable Jetpack site stats and analytics tracking.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'enable_cdn'         => array(
				'label'           => __( 'Enable CDN', 'ConjureWP' ),
				'description'     => __( 'Activate Jetpack Site Accelerator for image and static file CDN.', 'ConjureWP' ),
				'default_enabled' => true,
			),
		);
	}

	/**
	 * Render the Jetpack step.
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

				<h1><?php esc_html_e( 'Jetpack Setup', 'ConjureWP' ); ?></h1>
				<p><?php esc_html_e( 'Configure your Jetpack connection, security and performance settings below.', 'ConjureWP' ); ?></p>

				<?php $this->render_version_update_toggle(); ?>

				<?php if ( ! $this->can_run() ) : ?>
					<p class="conjure__notice conjure__notice--warning">
						<?php esc_html_e( 'Jetpack is not currently active. Please activate it first, then return to this step.', 'ConjureWP' ); ?>
					</p>
				<?php elseif ( empty( $enabled_features ) ) : ?>
					<p><?php esc_html_e( 'No Jetpack features are currently enabled for this connector.', 'ConjureWP' ); ?></p>
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
						<?php esc_html_e( 'Apply Jetpack Setup', 'ConjureWP' ); ?>
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
			case 'connect_wpcom':
				$this->render_connect_wpcom_fields();
				break;

			case 'enable_backups':
				$this->render_backups_fields();
				break;

			case 'configure_security':
				$this->render_security_fields();
				break;

			case 'setup_analytics':
				$this->render_analytics_fields();
				break;

			case 'enable_cdn':
				$this->render_cdn_fields();
				break;
		}
	}

	/**
	 * Render WordPress.com connection fields.
	 *
	 * @return void
	 */
	protected function render_connect_wpcom_fields() {
		$this->render_checkbox_field(
			'conjure_jetpack_connect_wpcom',
			__( 'Enable WordPress.com connection prompt', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_jetpack_enable_sso',
			__( 'Enable WordPress.com single sign-on', 'ConjureWP' ),
			false
		);
		$this->render_checkbox_field(
			'conjure_jetpack_enable_manage',
			__( 'Enable remote site management', 'ConjureWP' ),
			true
		);
	}

	/**
	 * Render backup fields.
	 *
	 * @return void
	 */
	protected function render_backups_fields() {
		$this->render_checkbox_field(
			'conjure_jetpack_enable_backups',
			__( 'Enable Jetpack backups', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_jetpack_backup_real_time',
			__( 'Enable real-time backups (requires paid plan)', 'ConjureWP' ),
			false
		);
		$this->render_checkbox_field(
			'conjure_jetpack_enable_restore',
			__( 'Enable one-click restore', 'ConjureWP' ),
			true
		);
	}

	/**
	 * Render security fields.
	 *
	 * @return void
	 */
	protected function render_security_fields() {
		$this->render_checkbox_field(
			'conjure_jetpack_brute_force_protection',
			__( 'Enable brute force login protection', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_jetpack_downtime_monitoring',
			__( 'Enable downtime monitoring', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_jetpack_spam_filtering',
			__( 'Enable spam filtering (Akismet)', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_jetpack_secure_auth',
			__( 'Enable secure authentication', 'ConjureWP' ),
			true
		);
	}

	/**
	 * Render analytics fields.
	 *
	 * @return void
	 */
	protected function render_analytics_fields() {
		$this->render_checkbox_field(
			'conjure_jetpack_enable_stats',
			__( 'Enable Jetpack site stats', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_jetpack_enable_search',
			__( 'Enable Jetpack search (requires paid plan)', 'ConjureWP' ),
			false
		);
		$this->render_checkbox_field(
			'conjure_jetpack_enable_related_posts',
			__( 'Enable related posts', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_jetpack_hide_stats_smiley',
			__( 'Hide stats smiley face', 'ConjureWP' ),
			true
		);
	}

	/**
	 * Render CDN fields.
	 *
	 * @return void
	 */
	protected function render_cdn_fields() {
		$this->render_checkbox_field(
			'conjure_jetpack_enable_photon',
			__( 'Enable Jetpack image CDN (Photon)', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_jetpack_enable_static_cdn',
			__( 'Enable static file CDN', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_jetpack_lazy_images',
			__( 'Enable lazy loading for images', 'ConjureWP' ),
			true
		);
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
	 * Handle the Jetpack step.
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

		if ( in_array( 'connect_wpcom', $enabled_keys, true ) ) {
			update_option( 'conjure_jetpack_connect_wpcom', ! empty( $_POST['conjure_jetpack_connect_wpcom'] ) );
			update_option( 'conjure_jetpack_enable_sso', ! empty( $_POST['conjure_jetpack_enable_sso'] ) );
			update_option( 'conjure_jetpack_enable_manage', ! empty( $_POST['conjure_jetpack_enable_manage'] ) );
		}

		if ( in_array( 'enable_backups', $enabled_keys, true ) ) {
			update_option( 'conjure_jetpack_enable_backups', ! empty( $_POST['conjure_jetpack_enable_backups'] ) );
			update_option( 'conjure_jetpack_backup_real_time', ! empty( $_POST['conjure_jetpack_backup_real_time'] ) );
			update_option( 'conjure_jetpack_enable_restore', ! empty( $_POST['conjure_jetpack_enable_restore'] ) );
		}

		if ( in_array( 'configure_security', $enabled_keys, true ) ) {
			update_option( 'conjure_jetpack_brute_force_protection', ! empty( $_POST['conjure_jetpack_brute_force_protection'] ) );
			update_option( 'conjure_jetpack_downtime_monitoring', ! empty( $_POST['conjure_jetpack_downtime_monitoring'] ) );
			update_option( 'conjure_jetpack_spam_filtering', ! empty( $_POST['conjure_jetpack_spam_filtering'] ) );
			update_option( 'conjure_jetpack_secure_auth', ! empty( $_POST['conjure_jetpack_secure_auth'] ) );
		}

		if ( in_array( 'setup_analytics', $enabled_keys, true ) ) {
			update_option( 'conjure_jetpack_enable_stats', ! empty( $_POST['conjure_jetpack_enable_stats'] ) );
			update_option( 'conjure_jetpack_enable_search', ! empty( $_POST['conjure_jetpack_enable_search'] ) );
			update_option( 'conjure_jetpack_enable_related_posts', ! empty( $_POST['conjure_jetpack_enable_related_posts'] ) );
			update_option( 'conjure_jetpack_hide_stats_smiley', ! empty( $_POST['conjure_jetpack_hide_stats_smiley'] ) );
		}

		if ( in_array( 'enable_cdn', $enabled_keys, true ) ) {
			update_option( 'conjure_jetpack_enable_photon', ! empty( $_POST['conjure_jetpack_enable_photon'] ) );
			update_option( 'conjure_jetpack_enable_static_cdn', ! empty( $_POST['conjure_jetpack_enable_static_cdn'] ) );
			update_option( 'conjure_jetpack_lazy_images', ! empty( $_POST['conjure_jetpack_lazy_images'] ) );
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
