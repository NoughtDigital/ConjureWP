<?php
/**
 * Gravity Forms step connector.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'conjurewp_is_gravity_forms_active' ) ) {
	/**
	 * Check whether Gravity Forms is active.
	 *
	 * @return bool
	 */
	function conjurewp_is_gravity_forms_active() {
		return class_exists( 'GFForms' ) || class_exists( 'GFAPI' );
	}
}

/**
 * Gravity Forms connector step.
 */
class Conjure_Step_Connector_Gravity_Forms extends Conjure_Step_Connector_Base {

	/**
	 * Feature groups for organising the step render.
	 *
	 * @var array
	 */
	protected static $feature_groups = array(
		'setup' => array(
			'label'    => 'Form setup',
			'features' => array( 'feed_notification_setup', 'starter_forms' ),
		),
		'configuration' => array(
			'label'    => 'Configuration',
			'features' => array( 'conditional_logic', 'email_routing' ),
		),
		'launch' => array(
			'label'    => 'Launch readiness',
			'features' => array( 'launch_checklist' ),
		),
	);

	/**
	 * Get available connector features.
	 *
	 * @return array
	 */
	public function get_features() {
		return array(
			'feed_notification_setup' => array(
				'label'           => __( 'Feed and notification setup', 'ConjureWP' ),
				'description'     => __( 'Configure Gravity Forms feeds and notification defaults during onboarding.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'starter_forms'           => array(
				'label'           => __( 'Starter forms', 'ConjureWP' ),
				'description'     => __( 'Create starter forms such as a contact form during the onboarding process.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'conditional_logic'       => array(
				'label'           => __( 'Conditional logic guidance', 'ConjureWP' ),
				'description'     => __( 'Provide guidance on setting up conditional logic for key forms.', 'ConjureWP' ),
				'default_enabled' => false,
			),
			'email_routing'           => array(
				'label'           => __( 'Admin email and routing', 'ConjureWP' ),
				'description'     => __( 'Configure admin email recipients and notification routing for form submissions.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'launch_checklist'        => array(
				'label'           => __( 'Launch checklist', 'ConjureWP' ),
				'description'     => __( 'Display a launch checklist for lead capture readiness and form testing.', 'ConjureWP' ),
				'default_enabled' => true,
			),
		);
	}

	/**
	 * Render the Gravity Forms step.
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

				<h1><?php esc_html_e( 'Gravity Forms Setup', 'ConjureWP' ); ?></h1>
				<p><?php esc_html_e( 'Configure your Gravity Forms defaults and create starter forms below.', 'ConjureWP' ); ?></p>

				<?php $this->render_version_update_toggle(); ?>

				<?php if ( ! $this->can_run() ) : ?>
					<p class="conjure__notice conjure__notice--warning">
						<?php esc_html_e( 'Gravity Forms is not currently active. Please activate it first, then return to this step.', 'ConjureWP' ); ?>
					</p>
				<?php elseif ( empty( $enabled_features ) ) : ?>
					<p><?php esc_html_e( 'No Gravity Forms features are currently enabled for this connector.', 'ConjureWP' ); ?></p>
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
						<?php esc_html_e( 'Apply Gravity Forms Setup', 'ConjureWP' ); ?>
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
			case 'feed_notification_setup':
				$this->render_notification_fields();
				break;

			case 'starter_forms':
				$this->render_starter_forms_fields();
				break;

			case 'conditional_logic':
				$this->render_conditional_logic_fields();
				break;

			case 'email_routing':
				$this->render_email_routing_fields();
				break;

			case 'launch_checklist':
				$this->render_launch_checklist_fields();
				break;
		}
	}

	/**
	 * Render notification setup fields.
	 *
	 * @return void
	 */
	protected function render_notification_fields() {
		$this->render_checkbox_field(
			'conjure_gf_enable_notifications',
			__( 'Enable admin notifications on all new forms', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_gf_enable_confirmations',
			__( 'Enable default confirmation messages', 'ConjureWP' ),
			true
		);
	}

	/**
	 * Render starter forms fields.
	 *
	 * @return void
	 */
	protected function render_starter_forms_fields() {
		$this->render_checkbox_field(
			'conjure_gf_create_contact_form',
			__( 'Create a starter contact form', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_gf_create_newsletter_form',
			__( 'Create a starter newsletter sign-up form', 'ConjureWP' ),
			false
		);
	}

	/**
	 * Render email routing fields.
	 *
	 * @return void
	 */
	protected function render_email_routing_fields() {
		$admin_email = get_option( 'admin_email', '' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_gf_admin_email" class="conjure__field-label">
				<?php esc_html_e( 'Form notification recipient', 'ConjureWP' ); ?>
			</label>
			<input
				type="email"
				id="conjure_gf_admin_email"
				name="conjure_gf_admin_email"
				class="conjure__input"
				value="<?php echo esc_attr( $admin_email ); ?>"
			/>
		</div>
		<?php
		$from_name = get_option( 'blogname', '' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_gf_from_name" class="conjure__field-label">
				<?php esc_html_e( 'Notification from name', 'ConjureWP' ); ?>
			</label>
			<input
				type="text"
				id="conjure_gf_from_name"
				name="conjure_gf_from_name"
				class="conjure__input"
				value="<?php echo esc_attr( $from_name ); ?>"
			/>
		</div>
		<?php
	}

	/**
	 * Render conditional logic guidance fields.
	 *
	 * @return void
	 */
	protected function render_conditional_logic_fields() {
		$this->render_checkbox_field(
			'conjure_gf_enable_conditional_logic',
			__( 'Enable conditional logic on starter forms', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_gf_enable_save_continue',
			__( 'Enable save and continue for multi-page forms', 'ConjureWP' ),
			false
		);
		$this->render_checkbox_field(
			'conjure_gf_enable_entry_limits',
			__( 'Enable entry limit configuration', 'ConjureWP' ),
			false
		);
		$this->render_checkbox_field(
			'conjure_gf_enable_scheduling',
			__( 'Enable form scheduling (open/close dates)', 'ConjureWP' ),
			false
		);
	}

	/**
	 * Render launch checklist fields.
	 *
	 * @return void
	 */
	protected function render_launch_checklist_fields() {
		$this->render_checkbox_field(
			'conjure_gf_test_notifications',
			__( 'Send a test notification after setup', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_gf_enable_honeypot',
			__( 'Enable honeypot anti-spam on all forms', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_gf_enable_akismet',
			__( 'Enable Akismet integration (if Akismet is active)', 'ConjureWP' ),
			class_exists( 'Akismet' )
		);
		$this->render_checkbox_field(
			'conjure_gf_disable_ip_collection',
			__( 'Disable IP address collection for GDPR compliance', 'ConjureWP' ),
			false
		);
		$this->render_checkbox_field(
			'conjure_gf_enable_no_conflict',
			__( 'Enable no-conflict mode (prevent plugin/theme conflicts)', 'ConjureWP' ),
			false
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
	 * Handle the Gravity Forms step.
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

		if ( in_array( 'email_routing', $enabled_keys, true ) ) {
			$admin_email = isset( $_POST['conjure_gf_admin_email'] ) ? sanitize_email( wp_unslash( $_POST['conjure_gf_admin_email'] ) ) : '';
			$from_name   = isset( $_POST['conjure_gf_from_name'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_gf_from_name'] ) ) : '';

			if ( ! empty( $admin_email ) ) {
				update_option( 'conjure_gf_notification_email', $admin_email );
			}
			if ( ! empty( $from_name ) ) {
				update_option( 'conjure_gf_notification_from_name', $from_name );
			}
		}

		if ( in_array( 'starter_forms', $enabled_keys, true ) ) {
			$this->create_starter_forms();
		}

		if ( in_array( 'feed_notification_setup', $enabled_keys, true ) ) {
			update_option( 'conjure_gf_enable_notifications', ! empty( $_POST['conjure_gf_enable_notifications'] ) );
			update_option( 'conjure_gf_enable_confirmations', ! empty( $_POST['conjure_gf_enable_confirmations'] ) );
		}

		if ( in_array( 'conditional_logic', $enabled_keys, true ) ) {
			update_option( 'conjure_gf_enable_conditional_logic', ! empty( $_POST['conjure_gf_enable_conditional_logic'] ) );
			update_option( 'conjure_gf_enable_save_continue', ! empty( $_POST['conjure_gf_enable_save_continue'] ) );
			update_option( 'conjure_gf_enable_entry_limits', ! empty( $_POST['conjure_gf_enable_entry_limits'] ) );
			update_option( 'conjure_gf_enable_scheduling', ! empty( $_POST['conjure_gf_enable_scheduling'] ) );
		}

		if ( in_array( 'launch_checklist', $enabled_keys, true ) ) {
			$this->apply_launch_checklist_settings();
		}

		$this->conjure->mark_step_completed( $this->get_step_key() );
		wp_safe_redirect( $this->conjure->step_next_link() );
		exit;
	}

	/**
	 * Apply launch checklist settings to Gravity Forms.
	 *
	 * @return void
	 */
	protected function apply_launch_checklist_settings() {
		$enable_honeypot = ! empty( $_POST['conjure_gf_enable_honeypot'] );
		$enable_akismet  = ! empty( $_POST['conjure_gf_enable_akismet'] );
		$disable_ip      = ! empty( $_POST['conjure_gf_disable_ip_collection'] );
		$no_conflict     = ! empty( $_POST['conjure_gf_enable_no_conflict'] );

		update_option( 'conjure_gf_honeypot_enabled', $enable_honeypot );
		update_option( 'conjure_gf_akismet_enabled', $enable_akismet );
		update_option( 'conjure_gf_disable_ip_collection', $disable_ip );

		if ( $no_conflict && class_exists( 'GFForms' ) ) {
			update_option( 'gform_enable_noconflict', true );
		}

		if ( ! empty( $_POST['conjure_gf_test_notifications'] ) ) {
			update_option( 'conjure_gf_test_notification_sent', true );
		}

		update_option( 'conjure_gf_launch_checklist_completed', true );
	}

	/**
	 * Create starter forms via the Gravity Forms API.
	 *
	 * @return void
	 */
	protected function create_starter_forms() {
		if ( ! class_exists( 'GFAPI' ) ) {
			return;
		}

		$admin_email = get_option( 'conjure_gf_notification_email', get_option( 'admin_email', '' ) );
		$from_name   = get_option( 'conjure_gf_notification_from_name', get_option( 'blogname', '' ) );

		if ( ! empty( $_POST['conjure_gf_create_contact_form'] ) ) {
			$existing = get_option( 'conjure_gf_contact_form_id', 0 );
			if ( $existing < 1 ) {
				$form = array(
					'title'         => __( 'Contact Form', 'ConjureWP' ),
					'fields'        => array(
						new \GF_Field_Name(
							array(
								'id'       => 1,
								'label'    => __( 'Name', 'ConjureWP' ),
								'type'     => 'name',
								'isRequired' => true,
								'inputs'   => array(
									array( 'id' => '1.3', 'label' => __( 'First', 'ConjureWP' ) ),
									array( 'id' => '1.6', 'label' => __( 'Last', 'ConjureWP' ) ),
								),
							)
						),
						new \GF_Field_Email(
							array(
								'id'         => 2,
								'label'      => __( 'Email', 'ConjureWP' ),
								'type'       => 'email',
								'isRequired' => true,
							)
						),
						new \GF_Field_Textarea(
							array(
								'id'         => 3,
								'label'      => __( 'Message', 'ConjureWP' ),
								'type'       => 'textarea',
								'isRequired' => true,
							)
						),
					),
					'notifications' => array(
						'admin' => array(
							'id'       => 'admin',
							'name'     => __( 'Admin notification', 'ConjureWP' ),
							'event'    => 'form_submission',
							'toType'   => 'email',
							'to'       => $admin_email,
							'from'     => $admin_email,
							'fromName' => $from_name,
							'subject'  => __( 'New contact form submission', 'ConjureWP' ),
							'message'  => '{all_fields}',
							'isActive' => true,
						),
					),
					'confirmations' => array(
						'default' => array(
							'id'      => 'default',
							'name'    => __( 'Default confirmation', 'ConjureWP' ),
							'type'    => 'message',
							'message' => __( 'Thank you for getting in touch. We will respond as soon as possible.', 'ConjureWP' ),
							'isDefault' => true,
						),
					),
				);

				$form_id = \GFAPI::add_form( $form );

				if ( ! is_wp_error( $form_id ) ) {
					update_option( 'conjure_gf_contact_form_id', $form_id );
				}
			}
		}

		if ( ! empty( $_POST['conjure_gf_create_newsletter_form'] ) ) {
			$existing = get_option( 'conjure_gf_newsletter_form_id', 0 );
			if ( $existing < 1 ) {
				$form = array(
					'title'         => __( 'Newsletter Sign-up', 'ConjureWP' ),
					'fields'        => array(
						new \GF_Field_Email(
							array(
								'id'         => 1,
								'label'      => __( 'Email address', 'ConjureWP' ),
								'type'       => 'email',
								'isRequired' => true,
							)
						),
					),
					'notifications' => array(
						'admin' => array(
							'id'       => 'admin',
							'name'     => __( 'Admin notification', 'ConjureWP' ),
							'event'    => 'form_submission',
							'toType'   => 'email',
							'to'       => $admin_email,
							'from'     => $admin_email,
							'fromName' => $from_name,
							'subject'  => __( 'New newsletter sign-up', 'ConjureWP' ),
							'message'  => '{all_fields}',
							'isActive' => true,
						),
					),
					'confirmations' => array(
						'default' => array(
							'id'      => 'default',
							'name'    => __( 'Default confirmation', 'ConjureWP' ),
							'type'    => 'message',
							'message' => __( 'Thank you for subscribing to our newsletter.', 'ConjureWP' ),
							'isDefault' => true,
						),
					),
				);

				$form_id = \GFAPI::add_form( $form );

				if ( ! is_wp_error( $form_id ) ) {
					update_option( 'conjure_gf_newsletter_form_id', $form_id );
				}
			}
		}
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
