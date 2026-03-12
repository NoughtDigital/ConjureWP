<?php
/**
 * Contact Form 7 step connector.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'conjurewp_is_cf7_active' ) ) {
	/**
	 * Check whether Contact Form 7 is active.
	 *
	 * @return bool
	 */
	function conjurewp_is_cf7_active() {
		return defined( 'WPCF7_VERSION' ) || class_exists( 'WPCF7' );
	}
}

/**
 * Contact Form 7 connector step.
 */
class Conjure_Step_Connector_Contact_Form_7 extends Conjure_Step_Connector_Base {

	/**
	 * Feature groups for organising the step render.
	 *
	 * @var array
	 */
	protected static $feature_groups = array(
		'setup' => array(
			'label'    => 'Form setup',
			'features' => array( 'onboarding_flow', 'default_form_creation' ),
		),
		'configuration' => array(
			'label'    => 'Configuration',
			'features' => array( 'mail_settings', 'spam_protection' ),
		),
		'handoff' => array(
			'label'    => 'Hand-off',
			'features' => array( 'client_handoff' ),
		),
	);

	/**
	 * Get available connector features.
	 *
	 * @return array
	 */
	public function get_features() {
		return array(
			'onboarding_flow'       => array(
				'label'           => __( 'Quick onboarding flow', 'ConjureWP' ),
				'description'     => __( 'Provide a quick Contact Form 7 onboarding flow within the wizard.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'default_form_creation' => array(
				'label'           => __( 'Default contact form', 'ConjureWP' ),
				'description'     => __( 'Create a default contact form with sensible field defaults during setup.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'mail_settings'         => array(
				'label'           => __( 'Mail settings and recipients', 'ConjureWP' ),
				'description'     => __( 'Configure mail settings and recipient addresses for form submissions.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'spam_protection'       => array(
				'label'           => __( 'Spam protection', 'ConjureWP' ),
				'description'     => __( 'Guidance on spam protection setup and recommended plugin pairings.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'client_handoff'        => array(
				'label'           => __( 'Client hand-off', 'ConjureWP' ),
				'description'     => __( 'Provide a simple hand-off for client form editing and management.', 'ConjureWP' ),
				'default_enabled' => true,
			),
		);
	}

	/**
	 * Render the Contact Form 7 step.
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

				<h1><?php esc_html_e( 'Contact Form 7 Setup', 'ConjureWP' ); ?></h1>
				<p><?php esc_html_e( 'Configure your Contact Form 7 defaults and mail settings below.', 'ConjureWP' ); ?></p>

				<?php $this->render_version_update_toggle(); ?>

				<?php if ( ! $this->can_run() ) : ?>
					<p class="conjure__notice conjure__notice--warning">
						<?php esc_html_e( 'Contact Form 7 is not currently active. Please activate it first, then return to this step.', 'ConjureWP' ); ?>
					</p>
				<?php elseif ( empty( $enabled_features ) ) : ?>
					<p><?php esc_html_e( 'No Contact Form 7 features are currently enabled for this connector.', 'ConjureWP' ); ?></p>
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
						<?php esc_html_e( 'Apply Contact Form 7 Setup', 'ConjureWP' ); ?>
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
			case 'onboarding_flow':
				$this->render_onboarding_fields();
				break;

			case 'default_form_creation':
				$this->render_form_creation_fields();
				break;

			case 'mail_settings':
				$this->render_mail_settings_fields();
				break;

			case 'spam_protection':
				$this->render_spam_protection_fields();
				break;

			case 'client_handoff':
				$this->render_client_handoff_fields();
				break;
		}
	}

	/**
	 * Render mail settings fields.
	 *
	 * @return void
	 */
	protected function render_mail_settings_fields() {
		$admin_email = get_option( 'admin_email', '' );
		$site_name   = get_option( 'blogname', '' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_cf7_recipient" class="conjure__field-label">
				<?php esc_html_e( 'Form recipient email', 'ConjureWP' ); ?>
			</label>
			<input
				type="email"
				id="conjure_cf7_recipient"
				name="conjure_cf7_recipient"
				class="conjure__input"
				value="<?php echo esc_attr( $admin_email ); ?>"
			/>
		</div>
		<div class="conjure__field-group">
			<label for="conjure_cf7_from_name" class="conjure__field-label">
				<?php esc_html_e( 'From name', 'ConjureWP' ); ?>
			</label>
			<input
				type="text"
				id="conjure_cf7_from_name"
				name="conjure_cf7_from_name"
				class="conjure__input"
				value="<?php echo esc_attr( $site_name ); ?>"
			/>
		</div>
		<div class="conjure__field-group">
			<label for="conjure_cf7_subject" class="conjure__field-label">
				<?php esc_html_e( 'Default email subject', 'ConjureWP' ); ?>
			</label>
			<input
				type="text"
				id="conjure_cf7_subject"
				name="conjure_cf7_subject"
				class="conjure__input"
				value="<?php echo esc_attr( sprintf( __( '%s: New contact form submission', 'ConjureWP' ), $site_name ) ); ?>"
			/>
		</div>
		<?php
	}

	/**
	 * Render form creation fields.
	 *
	 * @return void
	 */
	protected function render_form_creation_fields() {
		$this->render_checkbox_field(
			'conjure_cf7_create_contact_form',
			__( 'Create a default contact form', 'ConjureWP' ),
			true
		);
	}

	/**
	 * Render spam protection fields.
	 *
	 * @return void
	 */
	protected function render_spam_protection_fields() {
		$this->render_checkbox_field(
			'conjure_cf7_enable_akismet',
			__( 'Enable Akismet integration (requires Akismet plugin)', 'ConjureWP' ),
			class_exists( 'Akismet' )
		);
		$this->render_checkbox_field(
			'conjure_cf7_enable_honeypot',
			__( 'Add honeypot field to forms for basic spam prevention', 'ConjureWP' ),
			true
		);
	}

	/**
	 * Render onboarding flow fields.
	 *
	 * @return void
	 */
	protected function render_onboarding_fields() {
		$this->render_checkbox_field(
			'conjure_cf7_skip_default_form',
			__( 'Remove the default CF7 sample form created on activation', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_cf7_disable_autop',
			__( 'Disable automatic paragraph insertion in form output', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_cf7_enable_html5_fallback',
			__( 'Enable HTML5 input field fallback', 'ConjureWP' ),
			true
		);
	}

	/**
	 * Render client hand-off fields.
	 *
	 * @return void
	 */
	protected function render_client_handoff_fields() {
		$this->render_checkbox_field(
			'conjure_cf7_restrict_form_editing',
			__( 'Restrict form editing to administrators only', 'ConjureWP' ),
			false
		);
		$this->render_checkbox_field(
			'conjure_cf7_hide_admin_notices',
			__( 'Suppress CF7 admin notices for non-admin users', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_cf7_flush_rewrite',
			__( 'Flush rewrite rules after setup', 'ConjureWP' ),
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
	 * Handle the Contact Form 7 step.
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

		if ( in_array( 'onboarding_flow', $enabled_keys, true ) ) {
			update_option( 'conjure_cf7_skip_default_form', ! empty( $_POST['conjure_cf7_skip_default_form'] ) );
			update_option( 'conjure_cf7_disable_autop', ! empty( $_POST['conjure_cf7_disable_autop'] ) );
			update_option( 'conjure_cf7_enable_html5_fallback', ! empty( $_POST['conjure_cf7_enable_html5_fallback'] ) );

			if ( ! empty( $_POST['conjure_cf7_disable_autop'] ) ) {
				add_filter( 'wpcf7_autop_or_not', '__return_false' );
			}
		}

		$recipient  = '';
		$from_name  = '';
		$subject    = '';

		if ( in_array( 'mail_settings', $enabled_keys, true ) ) {
			$recipient = isset( $_POST['conjure_cf7_recipient'] ) ? sanitize_email( wp_unslash( $_POST['conjure_cf7_recipient'] ) ) : '';
			$from_name = isset( $_POST['conjure_cf7_from_name'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_cf7_from_name'] ) ) : '';
			$subject   = isset( $_POST['conjure_cf7_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_cf7_subject'] ) ) : '';

			if ( ! empty( $recipient ) ) {
				update_option( 'conjure_cf7_recipient_email', $recipient );
			}
			if ( ! empty( $from_name ) ) {
				update_option( 'conjure_cf7_from_name', $from_name );
			}
			if ( ! empty( $subject ) ) {
				update_option( 'conjure_cf7_default_subject', $subject );
			}
		}

		if ( in_array( 'default_form_creation', $enabled_keys, true ) && ! empty( $_POST['conjure_cf7_create_contact_form'] ) ) {
			$this->create_default_contact_form( $recipient, $from_name, $subject );
		}

		if ( in_array( 'spam_protection', $enabled_keys, true ) ) {
			$this->apply_spam_settings();
		}

		if ( in_array( 'client_handoff', $enabled_keys, true ) ) {
			update_option( 'conjure_cf7_restrict_form_editing', ! empty( $_POST['conjure_cf7_restrict_form_editing'] ) );
			update_option( 'conjure_cf7_hide_admin_notices', ! empty( $_POST['conjure_cf7_hide_admin_notices'] ) );

			if ( ! empty( $_POST['conjure_cf7_flush_rewrite'] ) ) {
				flush_rewrite_rules();
			}
		}

		$this->conjure->mark_step_completed( $this->get_step_key() );
		wp_safe_redirect( $this->conjure->step_next_link() );
		exit;
	}

	/**
	 * Apply spam protection settings.
	 *
	 * @return void
	 */
	protected function apply_spam_settings() {
		$enable_akismet  = ! empty( $_POST['conjure_cf7_enable_akismet'] );
		$enable_honeypot = ! empty( $_POST['conjure_cf7_enable_honeypot'] );

		update_option( 'conjure_cf7_akismet_enabled', $enable_akismet );
		update_option( 'conjure_cf7_honeypot_enabled', $enable_honeypot );
	}

	/**
	 * Create a default contact form using the CF7 API.
	 *
	 * @param string $recipient Recipient email.
	 * @param string $from_name From name.
	 * @param string $subject   Email subject.
	 * @return void
	 */
	protected function create_default_contact_form( $recipient, $from_name, $subject ) {
		$existing = get_option( 'conjure_cf7_contact_form_id', 0 );

		if ( $existing > 0 && get_post( $existing ) ) {
			return;
		}

		if ( ! $recipient ) {
			$recipient = get_option( 'admin_email', '' );
		}
		if ( ! $from_name ) {
			$from_name = get_option( 'blogname', '' );
		}
		if ( ! $subject ) {
			$subject = sprintf( __( '%s: New contact form submission', 'ConjureWP' ), get_option( 'blogname', '' ) );
		}

		if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
			return;
		}

		$form_template = implode(
			"\n",
			array(
				'<label>' . __( 'Your name', 'ConjureWP' ) . "\n" . '    [text* your-name]</label>',
				'',
				'<label>' . __( 'Your email', 'ConjureWP' ) . "\n" . '    [email* your-email]</label>',
				'',
				'<label>' . __( 'Subject', 'ConjureWP' ) . "\n" . '    [text* your-subject]</label>',
				'',
				'<label>' . __( 'Your message', 'ConjureWP' ) . "\n" . '    [textarea your-message]</label>',
				'',
				'[submit "' . __( 'Send', 'ConjureWP' ) . '"]',
			)
		);

		$mail_body = implode(
			"\n",
			array(
				__( 'From:', 'ConjureWP' ) . ' [your-name] <[your-email]>',
				__( 'Subject:', 'ConjureWP' ) . ' [your-subject]',
				'',
				__( 'Message body:', 'ConjureWP' ),
				'[your-message]',
				'',
				'--',
				sprintf( __( 'This message was sent from a contact form on %s.', 'ConjureWP' ), get_option( 'blogname', '' ) ),
			)
		);

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'wpcf7_contact_form',
				'post_status'  => 'publish',
				'post_title'   => __( 'Contact Form', 'ConjureWP' ),
				'post_content' => '',
			)
		);

		if ( $post_id < 1 || is_wp_error( $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, '_form', $form_template );

		$mail = array(
			'active'             => true,
			'subject'            => $subject,
			'sender'             => sprintf( '%s <%s>', $from_name, $recipient ),
			'recipient'          => $recipient,
			'body'               => $mail_body,
			'additional_headers' => 'Reply-To: [your-email]',
			'attachments'        => '',
			'use_html'           => false,
		);

		update_post_meta( $post_id, '_mail', $mail );

		$messages = array(
			'mail_sent_ok'             => __( 'Thank you for your message. It has been sent.', 'ConjureWP' ),
			'mail_sent_ng'             => __( 'There was an error trying to send your message. Please try again later.', 'ConjureWP' ),
			'validation_error'         => __( 'One or more fields have an error. Please check and try again.', 'ConjureWP' ),
			'spam'                     => __( 'There was an error trying to send your message. Please try again later.', 'ConjureWP' ),
			'accept_terms'             => __( 'You must accept the terms and conditions before sending your message.', 'ConjureWP' ),
			'invalid_required'         => __( 'Please fill out this field.', 'ConjureWP' ),
			'invalid_too_long'         => __( 'This field has a too long input.', 'ConjureWP' ),
			'invalid_too_short'        => __( 'This field has a too short input.', 'ConjureWP' ),
			'invalid_email'            => __( 'Please enter a valid email address.', 'ConjureWP' ),
		);

		update_post_meta( $post_id, '_messages', $messages );
		update_option( 'conjure_cf7_contact_form_id', $post_id );
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
