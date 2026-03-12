<?php
/**
 * WPForms step connector.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'conjurewp_is_wpforms_active' ) ) {
	/**
	 * Check whether WPForms is active.
	 *
	 * @return bool
	 */
	function conjurewp_is_wpforms_active() {
		return defined( 'WPFORMS_VERSION' );
	}
}

/**
 * WPForms connector step.
 */
class Conjure_Step_Connector_WPForms extends Conjure_Step_Connector_Base {

	/**
	 * Feature groups for organising the step render.
	 *
	 * @var array
	 */
	protected static $feature_groups = array(
		'email' => array(
			'label'    => 'Email configuration',
			'features' => array( 'smtp_setup', 'email_notifications' ),
		),
		'forms' => array(
			'label'    => 'Form creation',
			'features' => array( 'create_contact_form', 'anti_spam' ),
		),
		'experience' => array(
			'label'    => 'User experience',
			'features' => array( 'confirmation_pages' ),
		),
	);

	/**
	 * Get available connector features.
	 *
	 * @return array
	 */
	public function get_features() {
		return array(
			'smtp_setup'          => array(
				'label'           => __( 'SMTP setup', 'ConjureWP' ),
				'description'     => __( 'Configure SMTP email delivery for reliable form notifications.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'create_contact_form' => array(
				'label'           => __( 'Create contact form', 'ConjureWP' ),
				'description'     => __( 'Create a starter contact form during the onboarding process.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'email_notifications' => array(
				'label'           => __( 'Email notifications', 'ConjureWP' ),
				'description'     => __( 'Configure default email notification settings for form submissions.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'anti_spam'           => array(
				'label'           => __( 'Anti-spam', 'ConjureWP' ),
				'description'     => __( 'Enable anti-spam protection for all forms.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'confirmation_pages'  => array(
				'label'           => __( 'Confirmation pages', 'ConjureWP' ),
				'description'     => __( 'Configure default confirmation messages and redirect behaviour after form submission.', 'ConjureWP' ),
				'default_enabled' => true,
			),
		);
	}

	/**
	 * Render the WPForms step.
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

				<h1><?php esc_html_e( 'WPForms Setup', 'ConjureWP' ); ?></h1>
				<p><?php esc_html_e( 'Configure your WPForms defaults and email settings below.', 'ConjureWP' ); ?></p>

				<?php $this->render_version_update_toggle(); ?>

				<?php if ( ! $this->can_run() ) : ?>
					<p class="conjure__notice conjure__notice--warning">
						<?php esc_html_e( 'WPForms is not currently active. Please activate it first, then return to this step.', 'ConjureWP' ); ?>
					</p>
				<?php elseif ( empty( $enabled_features ) ) : ?>
					<p><?php esc_html_e( 'No WPForms features are currently enabled for this connector.', 'ConjureWP' ); ?></p>
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
						<?php esc_html_e( 'Apply WPForms Setup', 'ConjureWP' ); ?>
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
			case 'smtp_setup':
				$this->render_smtp_fields();
				break;

			case 'create_contact_form':
				$this->render_contact_form_fields();
				break;

			case 'email_notifications':
				$this->render_email_notification_fields();
				break;

			case 'anti_spam':
				$this->render_anti_spam_fields();
				break;

			case 'confirmation_pages':
				$this->render_confirmation_fields();
				break;
		}
	}

	/**
	 * Render SMTP setup fields.
	 *
	 * @return void
	 */
	protected function render_smtp_fields() {
		$smtp_host       = get_option( 'conjure_wpforms_smtp_host', '' );
		$smtp_port       = get_option( 'conjure_wpforms_smtp_port', '587' );
		$smtp_encryption = get_option( 'conjure_wpforms_smtp_encryption', 'tls' );
		$smtp_username   = get_option( 'conjure_wpforms_smtp_username', '' );

		$encryption_options = array(
			'none' => __( 'None', 'ConjureWP' ),
			'ssl'  => __( 'SSL', 'ConjureWP' ),
			'tls'  => __( 'TLS', 'ConjureWP' ),
		);
		?>
		<div class="conjure__field-group">
			<label for="conjure_wpforms_smtp_host" class="conjure__field-label">
				<?php esc_html_e( 'SMTP host', 'ConjureWP' ); ?>
			</label>
			<input
				type="text"
				id="conjure_wpforms_smtp_host"
				name="conjure_wpforms_smtp_host"
				class="conjure__input"
				value="<?php echo esc_attr( $smtp_host ); ?>"
			/>
		</div>
		<div class="conjure__field-group">
			<label for="conjure_wpforms_smtp_port" class="conjure__field-label">
				<?php esc_html_e( 'SMTP port', 'ConjureWP' ); ?>
			</label>
			<input
				type="number"
				id="conjure_wpforms_smtp_port"
				name="conjure_wpforms_smtp_port"
				class="conjure__input"
				value="<?php echo esc_attr( $smtp_port ); ?>"
				min="1"
				max="65535"
			/>
		</div>
		<div class="conjure__field-group">
			<label for="conjure_wpforms_smtp_encryption" class="conjure__field-label">
				<?php esc_html_e( 'Encryption', 'ConjureWP' ); ?>
			</label>
			<select id="conjure_wpforms_smtp_encryption" name="conjure_wpforms_smtp_encryption" class="conjure__select">
				<?php foreach ( $encryption_options as $val => $label ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $smtp_encryption, $val ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="conjure__field-group">
			<label for="conjure_wpforms_smtp_username" class="conjure__field-label">
				<?php esc_html_e( 'SMTP username', 'ConjureWP' ); ?>
			</label>
			<input
				type="text"
				id="conjure_wpforms_smtp_username"
				name="conjure_wpforms_smtp_username"
				class="conjure__input"
				value="<?php echo esc_attr( $smtp_username ); ?>"
			/>
		</div>
		<?php
		$this->render_checkbox_field(
			'conjure_wpforms_smtp_auth',
			__( 'Enable SMTP authentication', 'ConjureWP' ),
			true
		);
	}

	/**
	 * Render contact form creation fields.
	 *
	 * @return void
	 */
	protected function render_contact_form_fields() {
		$form_title = get_option( 'conjure_wpforms_contact_form_title', 'Contact Form' );

		$this->render_checkbox_field(
			'conjure_wpforms_create_contact_form',
			__( 'Create a starter contact form', 'ConjureWP' ),
			true
		);
		?>
		<div class="conjure__field-group">
			<label for="conjure_wpforms_contact_form_title" class="conjure__field-label">
				<?php esc_html_e( 'Contact form title', 'ConjureWP' ); ?>
			</label>
			<input
				type="text"
				id="conjure_wpforms_contact_form_title"
				name="conjure_wpforms_contact_form_title"
				class="conjure__input"
				value="<?php echo esc_attr( $form_title ); ?>"
			/>
		</div>
		<?php
	}

	/**
	 * Render email notification fields.
	 *
	 * @return void
	 */
	protected function render_email_notification_fields() {
		$notification_email   = get_option( 'admin_email', '' );
		$notification_from    = get_option( 'blogname', '' );
		$notification_subject = get_option( 'conjure_wpforms_notification_subject', 'New form submission' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_wpforms_notification_email" class="conjure__field-label">
				<?php esc_html_e( 'Notification email address', 'ConjureWP' ); ?>
			</label>
			<input
				type="email"
				id="conjure_wpforms_notification_email"
				name="conjure_wpforms_notification_email"
				class="conjure__input"
				value="<?php echo esc_attr( $notification_email ); ?>"
			/>
		</div>
		<div class="conjure__field-group">
			<label for="conjure_wpforms_notification_from_name" class="conjure__field-label">
				<?php esc_html_e( 'From name', 'ConjureWP' ); ?>
			</label>
			<input
				type="text"
				id="conjure_wpforms_notification_from_name"
				name="conjure_wpforms_notification_from_name"
				class="conjure__input"
				value="<?php echo esc_attr( $notification_from ); ?>"
			/>
		</div>
		<div class="conjure__field-group">
			<label for="conjure_wpforms_notification_subject" class="conjure__field-label">
				<?php esc_html_e( 'Default notification subject', 'ConjureWP' ); ?>
			</label>
			<input
				type="text"
				id="conjure_wpforms_notification_subject"
				name="conjure_wpforms_notification_subject"
				class="conjure__input"
				value="<?php echo esc_attr( $notification_subject ); ?>"
			/>
		</div>
		<?php
	}

	/**
	 * Render anti-spam fields.
	 *
	 * @return void
	 */
	protected function render_anti_spam_fields() {
		$this->render_checkbox_field(
			'conjure_wpforms_enable_honeypot',
			__( 'Enable honeypot anti-spam', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_wpforms_enable_anti_spam',
			__( 'Enable built-in anti-spam protection', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_wpforms_enable_akismet',
			__( 'Enable Akismet integration (if Akismet is active)', 'ConjureWP' ),
			class_exists( 'Akismet' )
		);
		$this->render_checkbox_field(
			'conjure_wpforms_store_spam_entries',
			__( 'Store spam entries for review', 'ConjureWP' ),
			false
		);
	}

	/**
	 * Render confirmation page fields.
	 *
	 * @return void
	 */
	protected function render_confirmation_fields() {
		$confirmation_type    = get_option( 'conjure_wpforms_confirmation_type', 'message' );
		$confirmation_message = get_option( 'conjure_wpforms_confirmation_message', 'Thank you for getting in touch. We will respond as soon as possible.' );

		$type_options = array(
			'message'  => __( 'Display message', 'ConjureWP' ),
			'page'     => __( 'Redirect to page', 'ConjureWP' ),
			'redirect' => __( 'Redirect to URL', 'ConjureWP' ),
		);
		?>
		<div class="conjure__field-group">
			<label for="conjure_wpforms_confirmation_type" class="conjure__field-label">
				<?php esc_html_e( 'Default confirmation type', 'ConjureWP' ); ?>
			</label>
			<select id="conjure_wpforms_confirmation_type" name="conjure_wpforms_confirmation_type" class="conjure__select">
				<?php foreach ( $type_options as $val => $label ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $confirmation_type, $val ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="conjure__field-group">
			<label for="conjure_wpforms_confirmation_message" class="conjure__field-label">
				<?php esc_html_e( 'Default confirmation message', 'ConjureWP' ); ?>
			</label>
			<input
				type="text"
				id="conjure_wpforms_confirmation_message"
				name="conjure_wpforms_confirmation_message"
				class="conjure__input"
				value="<?php echo esc_attr( $confirmation_message ); ?>"
			/>
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
	 * Handle the WPForms step.
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

		if ( in_array( 'smtp_setup', $enabled_keys, true ) ) {
			$smtp_host     = isset( $_POST['conjure_wpforms_smtp_host'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_wpforms_smtp_host'] ) ) : '';
			$smtp_username = isset( $_POST['conjure_wpforms_smtp_username'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_wpforms_smtp_username'] ) ) : '';
			$smtp_port     = isset( $_POST['conjure_wpforms_smtp_port'] ) ? absint( $_POST['conjure_wpforms_smtp_port'] ) : 587;
			$smtp_port     = max( 1, min( 65535, $smtp_port ) );

			$allowed_encryptions = array( 'none', 'ssl', 'tls' );
			$smtp_encryption     = isset( $_POST['conjure_wpforms_smtp_encryption'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_wpforms_smtp_encryption'] ) ) : 'tls';
			if ( ! in_array( $smtp_encryption, $allowed_encryptions, true ) ) {
				$smtp_encryption = 'tls';
			}

			update_option( 'conjure_wpforms_smtp_host', $smtp_host );
			update_option( 'conjure_wpforms_smtp_port', $smtp_port );
			update_option( 'conjure_wpforms_smtp_encryption', $smtp_encryption );
			update_option( 'conjure_wpforms_smtp_username', $smtp_username );
			update_option( 'conjure_wpforms_smtp_auth', ! empty( $_POST['conjure_wpforms_smtp_auth'] ) );
		}

		if ( in_array( 'create_contact_form', $enabled_keys, true ) ) {
			$form_title = isset( $_POST['conjure_wpforms_contact_form_title'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_wpforms_contact_form_title'] ) ) : '';

			update_option( 'conjure_wpforms_create_contact_form', ! empty( $_POST['conjure_wpforms_create_contact_form'] ) );

			if ( ! empty( $form_title ) ) {
				update_option( 'conjure_wpforms_contact_form_title', $form_title );
			}
		}

		if ( in_array( 'email_notifications', $enabled_keys, true ) ) {
			$notification_email   = isset( $_POST['conjure_wpforms_notification_email'] ) ? sanitize_email( wp_unslash( $_POST['conjure_wpforms_notification_email'] ) ) : '';
			$notification_from    = isset( $_POST['conjure_wpforms_notification_from_name'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_wpforms_notification_from_name'] ) ) : '';
			$notification_subject = isset( $_POST['conjure_wpforms_notification_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_wpforms_notification_subject'] ) ) : '';

			if ( ! empty( $notification_email ) ) {
				update_option( 'conjure_wpforms_notification_email', $notification_email );
			}
			if ( ! empty( $notification_from ) ) {
				update_option( 'conjure_wpforms_notification_from_name', $notification_from );
			}
			if ( ! empty( $notification_subject ) ) {
				update_option( 'conjure_wpforms_notification_subject', $notification_subject );
			}
		}

		if ( in_array( 'anti_spam', $enabled_keys, true ) ) {
			update_option( 'conjure_wpforms_enable_honeypot', ! empty( $_POST['conjure_wpforms_enable_honeypot'] ) );
			update_option( 'conjure_wpforms_enable_anti_spam', ! empty( $_POST['conjure_wpforms_enable_anti_spam'] ) );
			update_option( 'conjure_wpforms_enable_akismet', ! empty( $_POST['conjure_wpforms_enable_akismet'] ) );
			update_option( 'conjure_wpforms_store_spam_entries', ! empty( $_POST['conjure_wpforms_store_spam_entries'] ) );
		}

		if ( in_array( 'confirmation_pages', $enabled_keys, true ) ) {
			$allowed_types     = array( 'message', 'page', 'redirect' );
			$confirmation_type = isset( $_POST['conjure_wpforms_confirmation_type'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_wpforms_confirmation_type'] ) ) : 'message';
			if ( ! in_array( $confirmation_type, $allowed_types, true ) ) {
				$confirmation_type = 'message';
			}

			$confirmation_message = isset( $_POST['conjure_wpforms_confirmation_message'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_wpforms_confirmation_message'] ) ) : '';

			update_option( 'conjure_wpforms_confirmation_type', $confirmation_type );

			if ( ! empty( $confirmation_message ) ) {
				update_option( 'conjure_wpforms_confirmation_message', $confirmation_message );
			}
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
