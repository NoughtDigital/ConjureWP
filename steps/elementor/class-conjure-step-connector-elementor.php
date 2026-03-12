<?php
/**
 * Elementor step connector.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'conjurewp_is_elementor_active' ) ) {
	/**
	 * Check whether Elementor is active.
	 *
	 * @return bool
	 */
	function conjurewp_is_elementor_active() {
		return did_action( 'elementor/loaded' ) || class_exists( '\Elementor\Plugin' );
	}
}

/**
 * Elementor connector step.
 */
class Conjure_Step_Connector_Elementor extends Conjure_Step_Connector_Base {

	/**
	 * Feature groups for organising the step render.
	 *
	 * @var array
	 */
	protected static $feature_groups = array(
		'setup' => array(
			'label'    => 'Setup',
			'features' => array( 'welcome_screens', 'template_import' ),
		),
		'design' => array(
			'label'    => 'Design defaults',
			'features' => array( 'global_colours_typography', 'addon_activation' ),
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
			'welcome_screens'          => array(
				'label'           => __( 'Welcome and setup screens', 'ConjureWP' ),
				'description'     => __( 'Provide Elementor-specific welcome and setup screens during the wizard flow.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'template_import'          => array(
				'label'           => __( 'Template import guidance', 'ConjureWP' ),
				'description'     => __( 'Guide users through importing Elementor templates and kits inside the wizard.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'global_colours_typography' => array(
				'label'           => __( 'Global colours and typography', 'ConjureWP' ),
				'description'     => __( 'Set default global colours and typography settings for Elementor.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'addon_activation'         => array(
				'label'           => __( 'Add-on and kit activation', 'ConjureWP' ),
				'description'     => __( 'Prompt activation of optional Elementor add-ons and starter kits during setup.', 'ConjureWP' ),
				'default_enabled' => false,
			),
			'client_handoff'           => array(
				'label'           => __( 'Client editing hand-off', 'ConjureWP' ),
				'description'     => __( 'Provide a clean hand-off workflow so clients can confidently edit Elementor pages.', 'ConjureWP' ),
				'default_enabled' => true,
			),
		);
	}

	/**
	 * Render the Elementor step.
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

				<h1><?php esc_html_e( 'Elementor Setup', 'ConjureWP' ); ?></h1>
				<p><?php esc_html_e( 'Configure your Elementor builder settings and design defaults below.', 'ConjureWP' ); ?></p>

				<?php $this->render_version_update_toggle(); ?>

				<?php if ( ! $this->can_run() ) : ?>
					<p class="conjure__notice conjure__notice--warning">
						<?php esc_html_e( 'Elementor is not currently active. Please activate it first, then return to this step.', 'ConjureWP' ); ?>
					</p>
				<?php elseif ( empty( $enabled_features ) ) : ?>
					<p><?php esc_html_e( 'No Elementor features are currently enabled for this connector.', 'ConjureWP' ); ?></p>
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
						<?php esc_html_e( 'Apply Elementor Setup', 'ConjureWP' ); ?>
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
			case 'welcome_screens':
				$this->render_welcome_fields();
				break;

			case 'template_import':
				$this->render_template_import_fields();
				break;

			case 'global_colours_typography':
				$this->render_design_defaults_fields();
				break;

			case 'addon_activation':
				$this->render_addon_fields();
				break;

			case 'client_handoff':
				$this->render_handoff_fields();
				break;
		}
	}

	/**
	 * Render welcome screen fields.
	 *
	 * @return void
	 */
	protected function render_welcome_fields() {
		$this->render_checkbox_field(
			'conjure_elementor_disable_onboarding',
			__( 'Disable default Elementor onboarding wizard (use ConjureWP instead)', 'ConjureWP' ),
			(bool) get_option( 'elementor_onboarded', false )
		);
		$this->render_checkbox_field(
			'conjure_elementor_disable_getting_started',
			__( 'Hide Elementor "Getting Started" admin notice', 'ConjureWP' ),
			true
		);
	}

	/**
	 * Render template import fields.
	 *
	 * @return void
	 */
	protected function render_template_import_fields() {
		$this->render_checkbox_field(
			'conjure_elementor_enable_template_library',
			__( 'Enable Elementor template library access', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_elementor_import_starter_kit',
			__( 'Import starter kit templates during setup', 'ConjureWP' ),
			false
		);
		$this->render_checkbox_field(
			'conjure_elementor_create_landing_page',
			__( 'Create a sample landing page template', 'ConjureWP' ),
			true
		);
	}

	/**
	 * Render design default fields.
	 *
	 * @return void
	 */
	protected function render_design_defaults_fields() {
		$container_width = get_option( 'elementor_container_width', 1140 );
		?>
		<div class="conjure__field-group">
			<label for="conjure_elementor_container_width" class="conjure__field-label">
				<?php esc_html_e( 'Content width (px)', 'ConjureWP' ); ?>
			</label>
			<input
				type="number"
				id="conjure_elementor_container_width"
				name="conjure_elementor_container_width"
				class="conjure__input"
				value="<?php echo esc_attr( $container_width ); ?>"
				min="600"
				max="2400"
			/>
		</div>
		<?php
		$space_between = get_option( 'elementor_space_between_widgets', 20 );
		?>
		<div class="conjure__field-group">
			<label for="conjure_elementor_space_between" class="conjure__field-label">
				<?php esc_html_e( 'Space between widgets (px)', 'ConjureWP' ); ?>
			</label>
			<input
				type="number"
				id="conjure_elementor_space_between"
				name="conjure_elementor_space_between"
				class="conjure__input"
				value="<?php echo esc_attr( $space_between ); ?>"
				min="0"
				max="100"
			/>
		</div>
		<?php
		$stretched = get_option( 'elementor_stretched_section_container', '' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_elementor_stretched_selector" class="conjure__field-label">
				<?php esc_html_e( 'Stretched section fit to selector (CSS selector)', 'ConjureWP' ); ?>
			</label>
			<input
				type="text"
				id="conjure_elementor_stretched_selector"
				name="conjure_elementor_stretched_selector"
				class="conjure__input"
				value="<?php echo esc_attr( $stretched ); ?>"
				placeholder="body"
			/>
		</div>
		<?php
		$page_title = get_option( 'elementor_page_title_selector', 'h1.entry-title' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_elementor_page_title_selector" class="conjure__field-label">
				<?php esc_html_e( 'Page title selector (CSS selector to hide)', 'ConjureWP' ); ?>
			</label>
			<input
				type="text"
				id="conjure_elementor_page_title_selector"
				name="conjure_elementor_page_title_selector"
				class="conjure__input"
				value="<?php echo esc_attr( $page_title ); ?>"
				placeholder="h1.entry-title"
			/>
		</div>
		<?php
		$this->render_checkbox_field(
			'conjure_elementor_disable_colours',
			__( 'Disable default Elementor colour palette', 'ConjureWP' ),
			(bool) get_option( 'elementor_disable_color_schemes', false )
		);
		$this->render_checkbox_field(
			'conjure_elementor_disable_typography',
			__( 'Disable default Elementor typography schemes', 'ConjureWP' ),
			(bool) get_option( 'elementor_disable_typography_schemes', false )
		);
		$this->render_checkbox_field(
			'conjure_elementor_enable_unfiltered_uploads',
			__( 'Enable unfiltered file uploads (SVG support)', 'ConjureWP' ),
			(bool) get_option( 'elementor_unfiltered_files_upload', false )
		);
	}

	/**
	 * Render add-on activation fields.
	 *
	 * @return void
	 */
	protected function render_addon_fields() {
		$this->render_checkbox_field(
			'conjure_elementor_activate_pro_widgets',
			__( 'Activate Elementor Pro widgets (if Pro is installed)', 'ConjureWP' ),
			false
		);
		$this->render_checkbox_field(
			'conjure_elementor_enable_custom_fonts',
			__( 'Enable custom font uploads', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_elementor_enable_custom_icons',
			__( 'Enable custom icon library uploads', 'ConjureWP' ),
			true
		);
	}

	/**
	 * Render client hand-off fields.
	 *
	 * @return void
	 */
	protected function render_handoff_fields() {
		$editor_role = get_option( 'conjure_elementor_editor_role', 'editor' );
		$roles       = array(
			'administrator' => __( 'Administrator only', 'ConjureWP' ),
			'editor'        => __( 'Editor and above', 'ConjureWP' ),
			'author'        => __( 'Author and above', 'ConjureWP' ),
		);
		?>
		<div class="conjure__field-group">
			<label for="conjure_elementor_editor_role" class="conjure__field-label">
				<?php esc_html_e( 'Minimum role for Elementor editor access', 'ConjureWP' ); ?>
			</label>
			<select id="conjure_elementor_editor_role" name="conjure_elementor_editor_role" class="conjure__select">
				<?php foreach ( $roles as $val => $label ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $editor_role, $val ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
		$this->render_checkbox_field(
			'conjure_elementor_hide_admin_bar_for_clients',
			__( 'Hide WordPress admin bar for non-admin users on the front end', 'ConjureWP' ),
			false
		);
		$this->render_checkbox_field(
			'conjure_elementor_disable_dashboard_widget',
			__( 'Disable Elementor dashboard overview widget', 'ConjureWP' ),
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
	 * Handle the Elementor step.
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

		if ( in_array( 'welcome_screens', $enabled_keys, true ) ) {
			if ( ! empty( $_POST['conjure_elementor_disable_onboarding'] ) ) {
				update_option( 'elementor_onboarded', true );
			}
			update_option( 'conjure_elementor_disable_getting_started', ! empty( $_POST['conjure_elementor_disable_getting_started'] ) );
		}

		if ( in_array( 'template_import', $enabled_keys, true ) ) {
			update_option( 'conjure_elementor_enable_template_library', ! empty( $_POST['conjure_elementor_enable_template_library'] ) );
			update_option( 'conjure_elementor_import_starter_kit', ! empty( $_POST['conjure_elementor_import_starter_kit'] ) );
			update_option( 'conjure_elementor_create_landing_page', ! empty( $_POST['conjure_elementor_create_landing_page'] ) );
		}

		if ( in_array( 'global_colours_typography', $enabled_keys, true ) ) {
			$container_width = isset( $_POST['conjure_elementor_container_width'] ) ? absint( $_POST['conjure_elementor_container_width'] ) : 1140;
			$container_width = max( 600, min( 2400, $container_width ) );
			update_option( 'elementor_container_width', $container_width );

			$space_between = isset( $_POST['conjure_elementor_space_between'] ) ? absint( $_POST['conjure_elementor_space_between'] ) : 20;
			$space_between = min( 100, $space_between );
			update_option( 'elementor_space_between_widgets', $space_between );

			$stretched = isset( $_POST['conjure_elementor_stretched_selector'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_elementor_stretched_selector'] ) ) : '';
			update_option( 'elementor_stretched_section_container', $stretched );

			$page_title = isset( $_POST['conjure_elementor_page_title_selector'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_elementor_page_title_selector'] ) ) : '';
			update_option( 'elementor_page_title_selector', $page_title );

			update_option( 'elementor_disable_color_schemes', ! empty( $_POST['conjure_elementor_disable_colours'] ) ? 'yes' : '' );
			update_option( 'elementor_disable_typography_schemes', ! empty( $_POST['conjure_elementor_disable_typography'] ) ? 'yes' : '' );
			update_option( 'elementor_unfiltered_files_upload', ! empty( $_POST['conjure_elementor_enable_unfiltered_uploads'] ) ? '1' : '' );
		}

		if ( in_array( 'addon_activation', $enabled_keys, true ) ) {
			update_option( 'conjure_elementor_activate_pro_widgets', ! empty( $_POST['conjure_elementor_activate_pro_widgets'] ) );
			update_option( 'conjure_elementor_enable_custom_fonts', ! empty( $_POST['conjure_elementor_enable_custom_fonts'] ) );
			update_option( 'conjure_elementor_enable_custom_icons', ! empty( $_POST['conjure_elementor_enable_custom_icons'] ) );
		}

		if ( in_array( 'client_handoff', $enabled_keys, true ) ) {
			$editor_role = isset( $_POST['conjure_elementor_editor_role'] ) ? sanitize_key( wp_unslash( $_POST['conjure_elementor_editor_role'] ) ) : 'editor';
			$valid_roles = array( 'administrator', 'editor', 'author' );
			if ( ! in_array( $editor_role, $valid_roles, true ) ) {
				$editor_role = 'editor';
			}
			update_option( 'conjure_elementor_editor_role', $editor_role );
			update_option( 'conjure_elementor_hide_admin_bar_for_clients', ! empty( $_POST['conjure_elementor_hide_admin_bar_for_clients'] ) );
			update_option( 'conjure_elementor_disable_dashboard_widget', ! empty( $_POST['conjure_elementor_disable_dashboard_widget'] ) );
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
