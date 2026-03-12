<?php
/**
 * Wordfence step connector.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'conjurewp_is_wordfence_active' ) ) {
	/**
	 * Check whether Wordfence is active.
	 *
	 * @return bool
	 */
	function conjurewp_is_wordfence_active() {
		return defined( 'WORDFENCE_VERSION' );
	}
}

/**
 * Wordfence connector step.
 */
class Conjure_Step_Connector_Wordfence extends Conjure_Step_Connector_Base {

	/**
	 * Feature groups for organising the step render.
	 *
	 * @var array
	 */
	protected static $feature_groups = array(
		'firewall' => array(
			'label'    => 'Firewall',
			'features' => array( 'enable_firewall' ),
		),
		'login' => array(
			'label'    => 'Login security',
			'features' => array( 'login_protection', 'brute_force_limits' ),
		),
		'scanning' => array(
			'label'    => 'Scanning and alerts',
			'features' => array( 'malware_scan', 'email_alerts' ),
		),
	);

	/**
	 * Get available connector features.
	 *
	 * @return array
	 */
	public function get_features() {
		return array(
			'enable_firewall'   => array(
				'label'           => __( 'Enable firewall', 'ConjureWP' ),
				'description'     => __( 'Activate the Wordfence web application firewall for real-time threat protection.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'login_protection'  => array(
				'label'           => __( 'Configure login protection', 'ConjureWP' ),
				'description'     => __( 'Harden the WordPress login page with two-factor authentication and CAPTCHA.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'malware_scan'      => array(
				'label'           => __( 'Malware scan', 'ConjureWP' ),
				'description'     => __( 'Configure automated malware and file change scanning.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'email_alerts'      => array(
				'label'           => __( 'Email alerts', 'ConjureWP' ),
				'description'     => __( 'Configure email notifications for security events and scan results.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'brute_force_limits' => array(
				'label'           => __( 'Brute force limits', 'ConjureWP' ),
				'description'     => __( 'Configure lockout thresholds to prevent brute force login attacks.', 'ConjureWP' ),
				'default_enabled' => true,
			),
		);
	}

	/**
	 * Render the Wordfence step.
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

				<h1><?php esc_html_e( 'Wordfence Setup', 'ConjureWP' ); ?></h1>
				<p><?php esc_html_e( 'Configure your Wordfence security defaults below.', 'ConjureWP' ); ?></p>

				<?php $this->render_version_update_toggle(); ?>

				<?php if ( ! $this->can_run() ) : ?>
					<p class="conjure__notice conjure__notice--warning">
						<?php esc_html_e( 'Wordfence is not currently active. Please activate it first, then return to this step.', 'ConjureWP' ); ?>
					</p>
				<?php elseif ( empty( $enabled_features ) ) : ?>
					<p><?php esc_html_e( 'No Wordfence features are currently enabled for this connector.', 'ConjureWP' ); ?></p>
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
						<?php esc_html_e( 'Apply Wordfence Setup', 'ConjureWP' ); ?>
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
			case 'enable_firewall':
				$this->render_firewall_fields();
				break;

			case 'login_protection':
				$this->render_login_protection_fields();
				break;

			case 'malware_scan':
				$this->render_malware_scan_fields();
				break;

			case 'email_alerts':
				$this->render_email_alerts_fields();
				break;

			case 'brute_force_limits':
				$this->render_brute_force_fields();
				break;
		}
	}

	/**
	 * Render firewall fields.
	 *
	 * @return void
	 */
	protected function render_firewall_fields() {
		$this->render_checkbox_field(
			'conjure_wf_enable_firewall',
			__( 'Enable Wordfence firewall', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_wf_auto_update_firewall_rules',
			__( 'Auto-update firewall rules', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_wf_enable_rate_limiting',
			__( 'Enable rate limiting', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_wf_block_fake_crawlers',
			__( 'Block fake Google crawlers', 'ConjureWP' ),
			true
		);
	}

	/**
	 * Render login protection fields.
	 *
	 * @return void
	 */
	protected function render_login_protection_fields() {
		$this->render_checkbox_field(
			'conjure_wf_enable_2fa',
			__( 'Enable two-factor authentication', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_wf_enforce_strong_passwords',
			__( 'Enforce strong passwords', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_wf_disable_xml_rpc',
			__( 'Disable XML-RPC authentication', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_wf_mask_login_errors',
			__( 'Mask login error messages', 'ConjureWP' ),
			true
		);
	}

	/**
	 * Render malware scan fields.
	 *
	 * @return void
	 */
	protected function render_malware_scan_fields() {
		$this->render_checkbox_field(
			'conjure_wf_enable_auto_scan',
			__( 'Enable automated malware scanning', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_wf_scan_core_files',
			__( 'Scan WordPress core files for changes', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_wf_scan_themes',
			__( 'Scan theme files', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_wf_scan_plugins',
			__( 'Scan plugin files', 'ConjureWP' ),
			true
		);
	}

	/**
	 * Render email alerts fields.
	 *
	 * @return void
	 */
	protected function render_email_alerts_fields() {
		$alert_email = get_option( 'admin_email', '' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_wf_alert_email" class="conjure__field-label">
				<?php esc_html_e( 'Alert email address', 'ConjureWP' ); ?>
			</label>
			<input
				type="email"
				id="conjure_wf_alert_email"
				name="conjure_wf_alert_email"
				class="conjure__input"
				value="<?php echo esc_attr( $alert_email ); ?>"
			/>
		</div>
		<?php
		$this->render_checkbox_field(
			'conjure_wf_alert_on_critical',
			__( 'Send alerts for critical issues', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_wf_alert_on_warnings',
			__( 'Send alerts for warnings', 'ConjureWP' ),
			false
		);
		$this->render_checkbox_field(
			'conjure_wf_alert_on_block',
			__( 'Send alerts when IP addresses are blocked', 'ConjureWP' ),
			false
		);
	}

	/**
	 * Render brute force limit fields.
	 *
	 * @return void
	 */
	protected function render_brute_force_fields() {
		$max_failures = get_option( 'conjure_wf_max_login_failures', '5' );
		$lockout_dur  = get_option( 'conjure_wf_lockout_duration', '30' );
		$count_period = get_option( 'conjure_wf_count_failures_period', '120' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_wf_max_login_failures" class="conjure__field-label">
				<?php esc_html_e( 'Max login failures before lockout', 'ConjureWP' ); ?>
			</label>
			<input
				type="number"
				id="conjure_wf_max_login_failures"
				name="conjure_wf_max_login_failures"
				class="conjure__input"
				value="<?php echo esc_attr( $max_failures ); ?>"
				min="1"
				max="50"
			/>
		</div>
		<div class="conjure__field-group">
			<label for="conjure_wf_lockout_duration" class="conjure__field-label">
				<?php esc_html_e( 'Lockout duration (minutes)', 'ConjureWP' ); ?>
			</label>
			<input
				type="number"
				id="conjure_wf_lockout_duration"
				name="conjure_wf_lockout_duration"
				class="conjure__input"
				value="<?php echo esc_attr( $lockout_dur ); ?>"
				min="1"
				max="1440"
			/>
		</div>
		<div class="conjure__field-group">
			<label for="conjure_wf_count_failures_period" class="conjure__field-label">
				<?php esc_html_e( 'Count failures over period (minutes)', 'ConjureWP' ); ?>
			</label>
			<input
				type="number"
				id="conjure_wf_count_failures_period"
				name="conjure_wf_count_failures_period"
				class="conjure__input"
				value="<?php echo esc_attr( $count_period ); ?>"
				min="1"
				max="1440"
			/>
		</div>
		<?php
		$this->render_checkbox_field(
			'conjure_wf_immediately_block_invalid_users',
			__( 'Immediately block attempts with invalid usernames', 'ConjureWP' ),
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
	 * Handle the Wordfence step.
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

		if ( in_array( 'enable_firewall', $enabled_keys, true ) ) {
			update_option( 'conjure_wf_enable_firewall', ! empty( $_POST['conjure_wf_enable_firewall'] ) );
			update_option( 'conjure_wf_auto_update_firewall_rules', ! empty( $_POST['conjure_wf_auto_update_firewall_rules'] ) );
			update_option( 'conjure_wf_enable_rate_limiting', ! empty( $_POST['conjure_wf_enable_rate_limiting'] ) );
			update_option( 'conjure_wf_block_fake_crawlers', ! empty( $_POST['conjure_wf_block_fake_crawlers'] ) );
		}

		if ( in_array( 'login_protection', $enabled_keys, true ) ) {
			update_option( 'conjure_wf_enable_2fa', ! empty( $_POST['conjure_wf_enable_2fa'] ) );
			update_option( 'conjure_wf_enforce_strong_passwords', ! empty( $_POST['conjure_wf_enforce_strong_passwords'] ) );
			update_option( 'conjure_wf_disable_xml_rpc', ! empty( $_POST['conjure_wf_disable_xml_rpc'] ) );
			update_option( 'conjure_wf_mask_login_errors', ! empty( $_POST['conjure_wf_mask_login_errors'] ) );
		}

		if ( in_array( 'malware_scan', $enabled_keys, true ) ) {
			update_option( 'conjure_wf_enable_auto_scan', ! empty( $_POST['conjure_wf_enable_auto_scan'] ) );
			update_option( 'conjure_wf_scan_core_files', ! empty( $_POST['conjure_wf_scan_core_files'] ) );
			update_option( 'conjure_wf_scan_themes', ! empty( $_POST['conjure_wf_scan_themes'] ) );
			update_option( 'conjure_wf_scan_plugins', ! empty( $_POST['conjure_wf_scan_plugins'] ) );
		}

		if ( in_array( 'email_alerts', $enabled_keys, true ) ) {
			$alert_email = isset( $_POST['conjure_wf_alert_email'] ) ? sanitize_email( wp_unslash( $_POST['conjure_wf_alert_email'] ) ) : '';

			if ( ! empty( $alert_email ) ) {
				update_option( 'conjure_wf_alert_email', $alert_email );
			}

			update_option( 'conjure_wf_alert_on_critical', ! empty( $_POST['conjure_wf_alert_on_critical'] ) );
			update_option( 'conjure_wf_alert_on_warnings', ! empty( $_POST['conjure_wf_alert_on_warnings'] ) );
			update_option( 'conjure_wf_alert_on_block', ! empty( $_POST['conjure_wf_alert_on_block'] ) );
		}

		if ( in_array( 'brute_force_limits', $enabled_keys, true ) ) {
			$max_failures = isset( $_POST['conjure_wf_max_login_failures'] ) ? absint( $_POST['conjure_wf_max_login_failures'] ) : 5;
			$max_failures = max( 1, min( 50, $max_failures ) );

			$lockout_dur = isset( $_POST['conjure_wf_lockout_duration'] ) ? absint( $_POST['conjure_wf_lockout_duration'] ) : 30;
			$lockout_dur = max( 1, min( 1440, $lockout_dur ) );

			$count_period = isset( $_POST['conjure_wf_count_failures_period'] ) ? absint( $_POST['conjure_wf_count_failures_period'] ) : 120;
			$count_period = max( 1, min( 1440, $count_period ) );

			update_option( 'conjure_wf_max_login_failures', $max_failures );
			update_option( 'conjure_wf_lockout_duration', $lockout_dur );
			update_option( 'conjure_wf_count_failures_period', $count_period );
			update_option( 'conjure_wf_immediately_block_invalid_users', ! empty( $_POST['conjure_wf_immediately_block_invalid_users'] ) );
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
