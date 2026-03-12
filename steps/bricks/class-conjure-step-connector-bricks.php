<?php
/**
 * Bricks step connector.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'conjurewp_is_bricks_active' ) ) {
	/**
	 * Check whether Bricks is active.
	 *
	 * @return bool
	 */
	function conjurewp_is_bricks_active() {
		return defined( 'BRICKS_VERSION' ) || class_exists( 'Bricks\Theme' );
	}
}

/**
 * Bricks connector step.
 */
class Conjure_Step_Connector_Bricks extends Conjure_Step_Connector_Base {

	/**
	 * Feature groups for organising the step render.
	 *
	 * @var array
	 */
	protected static $feature_groups = array(
		'onboarding' => array(
			'label'    => 'Onboarding',
			'features' => array( 'template_onboarding', 'builder_defaults' ),
		),
		'design' => array(
			'label'    => 'Design hand-off',
			'features' => array( 'theme_style_handoff', 'conditional_layouts' ),
		),
		'guidance' => array(
			'label'    => 'Guidance',
			'features' => array( 'post_launch_guidance' ),
		),
	);

	/**
	 * Get available connector features.
	 *
	 * @return array
	 */
	public function get_features() {
		return array(
			'template_onboarding'   => array(
				'label'           => __( 'Template-ready onboarding', 'ConjureWP' ),
				'description'     => __( 'Provide a Bricks template-ready onboarding flow so new sites launch with pre-built layouts.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'builder_defaults'      => array(
				'label'           => __( 'Builder defaults', 'ConjureWP' ),
				'description'     => __( 'Apply sensible Bricks builder defaults during setup such as container width and breakpoints.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'theme_style_handoff'   => array(
				'label'           => __( 'Theme style and global class hand-off', 'ConjureWP' ),
				'description'     => __( 'Configure Bricks theme styles, global CSS classes and colour variables for consistent design.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'conditional_layouts'   => array(
				'label'           => __( 'Conditional layout steps', 'ConjureWP' ),
				'description'     => __( 'Add conditional steps for client-specific layouts based on site type or content structure.', 'ConjureWP' ),
				'default_enabled' => false,
			),
			'post_launch_guidance'  => array(
				'label'           => __( 'Post-launch editing guidance', 'ConjureWP' ),
				'description'     => __( 'Provide guidance for editing Bricks pages after launch, including builder access and template management.', 'ConjureWP' ),
				'default_enabled' => true,
			),
		);
	}

	/**
	 * Render the Bricks step.
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

				<h1><?php esc_html_e( 'Bricks Builder Setup', 'ConjureWP' ); ?></h1>
				<p><?php esc_html_e( 'Configure your Bricks Builder settings and onboarding preferences below.', 'ConjureWP' ); ?></p>

				<?php $this->render_version_update_toggle(); ?>

				<?php if ( ! $this->can_run() ) : ?>
					<p class="conjure__notice conjure__notice--warning">
						<?php esc_html_e( 'Bricks is not currently active. Please activate it first, then return to this step.', 'ConjureWP' ); ?>
					</p>
				<?php elseif ( empty( $enabled_features ) ) : ?>
					<p><?php esc_html_e( 'No Bricks features are currently enabled for this connector.', 'ConjureWP' ); ?></p>
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
						<?php esc_html_e( 'Apply Bricks Setup', 'ConjureWP' ); ?>
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
			case 'template_onboarding':
				$this->render_template_onboarding_fields();
				break;

			case 'builder_defaults':
				$this->render_builder_defaults_fields();
				break;

			case 'theme_style_handoff':
				$this->render_theme_style_fields();
				break;

			case 'conditional_layouts':
				$this->render_conditional_layout_fields();
				break;

			case 'post_launch_guidance':
				$this->render_post_launch_fields();
				break;
		}
	}

	/**
	 * Render template onboarding fields.
	 *
	 * @return void
	 */
	protected function render_template_onboarding_fields() {
		$this->render_checkbox_field(
			'conjure_bricks_import_templates',
			__( 'Import starter templates during onboarding', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_bricks_create_header_footer',
			__( 'Create default header and footer templates', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_bricks_set_homepage_template',
			__( 'Assign a homepage template on setup', 'ConjureWP' ),
			true
		);
	}

	/**
	 * Render builder default fields.
	 *
	 * @return void
	 */
	protected function render_builder_defaults_fields() {
		$container_width = $this->get_bricks_option( 'defaultContainerWidth', '1200' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_bricks_container_width" class="conjure__field-label">
				<?php esc_html_e( 'Default container width (px)', 'ConjureWP' ); ?>
			</label>
			<input
				type="number"
				id="conjure_bricks_container_width"
				name="conjure_bricks_container_width"
				class="conjure__input"
				value="<?php echo esc_attr( $container_width ); ?>"
				min="600"
				max="2400"
			/>
		</div>
		<?php
		$breakpoint_tablet = $this->get_bricks_option( 'breakpointTablet', '1024' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_bricks_breakpoint_tablet" class="conjure__field-label">
				<?php esc_html_e( 'Tablet breakpoint (px)', 'ConjureWP' ); ?>
			</label>
			<input
				type="number"
				id="conjure_bricks_breakpoint_tablet"
				name="conjure_bricks_breakpoint_tablet"
				class="conjure__input"
				value="<?php echo esc_attr( $breakpoint_tablet ); ?>"
				min="600"
				max="1400"
			/>
		</div>
		<?php
		$breakpoint_mobile = $this->get_bricks_option( 'breakpointMobile', '768' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_bricks_breakpoint_mobile" class="conjure__field-label">
				<?php esc_html_e( 'Mobile breakpoint (px)', 'ConjureWP' ); ?>
			</label>
			<input
				type="number"
				id="conjure_bricks_breakpoint_mobile"
				name="conjure_bricks_breakpoint_mobile"
				class="conjure__input"
				value="<?php echo esc_attr( $breakpoint_mobile ); ?>"
				min="320"
				max="900"
			/>
		</div>
		<?php
		$this->render_checkbox_field(
			'conjure_bricks_disable_gutenberg',
			__( 'Disable Gutenberg for Bricks post types', 'ConjureWP' ),
			$this->get_bricks_option( 'disableGutenberg', false )
		);
		$this->render_checkbox_field(
			'conjure_bricks_disable_seo_meta',
			__( 'Disable default SEO meta output (use an SEO plugin instead)', 'ConjureWP' ),
			$this->get_bricks_option( 'disableSeoMetaTags', false )
		);
		$this->render_checkbox_field(
			'conjure_bricks_disable_wp_rest_api',
			__( 'Disable WordPress REST API for non-authenticated users', 'ConjureWP' ),
			$this->get_bricks_option( 'disableRestApi', false )
		);
		$this->render_checkbox_field(
			'conjure_bricks_disable_emojis',
			__( 'Disable WordPress emoji scripts', 'ConjureWP' ),
			$this->get_bricks_option( 'disableEmojis', false )
		);
		$this->render_checkbox_field(
			'conjure_bricks_disable_embed',
			__( 'Disable WordPress oEmbed scripts', 'ConjureWP' ),
			$this->get_bricks_option( 'disableEmbed', false )
		);
	}

	/**
	 * Render theme style hand-off fields.
	 *
	 * @return void
	 */
	protected function render_theme_style_fields() {
		$this->render_checkbox_field(
			'conjure_bricks_css_loading',
			__( 'Enable external CSS file loading for better performance', 'ConjureWP' ),
			$this->get_bricks_option( 'cssLoading', false )
		);
		$this->render_checkbox_field(
			'conjure_bricks_disable_class_chaining',
			__( 'Disable global class chaining', 'ConjureWP' ),
			$this->get_bricks_option( 'disableClassChaining', false )
		);
		$this->render_checkbox_field(
			'conjure_bricks_disable_google_fonts',
			__( 'Disable Google Fonts loading', 'ConjureWP' ),
			$this->get_bricks_option( 'disableGoogleFonts', false )
		);
		$this->render_checkbox_field(
			'conjure_bricks_disable_lazy_load',
			__( 'Disable Bricks lazy loading (use a separate performance plugin)', 'ConjureWP' ),
			$this->get_bricks_option( 'disableLazyLoad', false )
		);
	}

	/**
	 * Render conditional layout fields.
	 *
	 * @return void
	 */
	protected function render_conditional_layout_fields() {
		$layout_type = get_option( 'conjure_bricks_layout_type', 'standard' );
		$options     = array(
			'standard'  => __( 'Standard (blog + pages)', 'ConjureWP' ),
			'portfolio' => __( 'Portfolio (project-focused)', 'ConjureWP' ),
			'ecommerce' => __( 'E-commerce (shop-focused)', 'ConjureWP' ),
			'landing'   => __( 'Landing page (single-page)', 'ConjureWP' ),
		);
		?>
		<div class="conjure__field-group">
			<label for="conjure_bricks_layout_type" class="conjure__field-label">
				<?php esc_html_e( 'Site layout type', 'ConjureWP' ); ?>
			</label>
			<select id="conjure_bricks_layout_type" name="conjure_bricks_layout_type" class="conjure__select">
				<?php foreach ( $options as $val => $label ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $layout_type, $val ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
		$this->render_checkbox_field(
			'conjure_bricks_create_archive_template',
			__( 'Create a custom archive template for the selected layout', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_bricks_create_single_template',
			__( 'Create a custom single post template', 'ConjureWP' ),
			true
		);
	}

	/**
	 * Render post-launch guidance fields.
	 *
	 * @return void
	 */
	protected function render_post_launch_fields() {
		$this->render_checkbox_field(
			'conjure_bricks_set_builder_access',
			__( 'Restrict Bricks builder access to administrators only', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_bricks_lock_templates',
			__( 'Lock global templates from editor role changes', 'ConjureWP' ),
			false
		);
		$this->render_checkbox_field(
			'conjure_bricks_flush_rewrite',
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
	 * Handle the Bricks step.
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

		if ( in_array( 'template_onboarding', $enabled_keys, true ) ) {
			update_option( 'conjure_bricks_import_templates', ! empty( $_POST['conjure_bricks_import_templates'] ) );
			update_option( 'conjure_bricks_create_header_footer', ! empty( $_POST['conjure_bricks_create_header_footer'] ) );
			update_option( 'conjure_bricks_set_homepage_template', ! empty( $_POST['conjure_bricks_set_homepage_template'] ) );
		}

		if ( in_array( 'builder_defaults', $enabled_keys, true ) ) {
			$container_width = isset( $_POST['conjure_bricks_container_width'] ) ? absint( $_POST['conjure_bricks_container_width'] ) : 1200;
			$container_width = max( 600, min( 2400, $container_width ) );
			$this->set_bricks_option( 'defaultContainerWidth', (string) $container_width );

			$breakpoint_tablet = isset( $_POST['conjure_bricks_breakpoint_tablet'] ) ? absint( $_POST['conjure_bricks_breakpoint_tablet'] ) : 1024;
			$breakpoint_tablet = max( 600, min( 1400, $breakpoint_tablet ) );
			$this->set_bricks_option( 'breakpointTablet', (string) $breakpoint_tablet );

			$breakpoint_mobile = isset( $_POST['conjure_bricks_breakpoint_mobile'] ) ? absint( $_POST['conjure_bricks_breakpoint_mobile'] ) : 768;
			$breakpoint_mobile = max( 320, min( 900, $breakpoint_mobile ) );
			$this->set_bricks_option( 'breakpointMobile', (string) $breakpoint_mobile );

			$this->set_bricks_option( 'disableGutenberg', ! empty( $_POST['conjure_bricks_disable_gutenberg'] ) );
			$this->set_bricks_option( 'disableSeoMetaTags', ! empty( $_POST['conjure_bricks_disable_seo_meta'] ) );
			$this->set_bricks_option( 'disableRestApi', ! empty( $_POST['conjure_bricks_disable_wp_rest_api'] ) );
			$this->set_bricks_option( 'disableEmojis', ! empty( $_POST['conjure_bricks_disable_emojis'] ) );
			$this->set_bricks_option( 'disableEmbed', ! empty( $_POST['conjure_bricks_disable_embed'] ) );
		}

		if ( in_array( 'theme_style_handoff', $enabled_keys, true ) ) {
			$this->set_bricks_option( 'cssLoading', ! empty( $_POST['conjure_bricks_css_loading'] ) ? 'file' : '' );
			$this->set_bricks_option( 'disableClassChaining', ! empty( $_POST['conjure_bricks_disable_class_chaining'] ) );
			$this->set_bricks_option( 'disableGoogleFonts', ! empty( $_POST['conjure_bricks_disable_google_fonts'] ) );
			$this->set_bricks_option( 'disableLazyLoad', ! empty( $_POST['conjure_bricks_disable_lazy_load'] ) );
		}

		if ( in_array( 'conditional_layouts', $enabled_keys, true ) ) {
			$layout_type = isset( $_POST['conjure_bricks_layout_type'] ) ? sanitize_key( wp_unslash( $_POST['conjure_bricks_layout_type'] ) ) : 'standard';
			$valid       = array( 'standard', 'portfolio', 'ecommerce', 'landing' );
			if ( ! in_array( $layout_type, $valid, true ) ) {
				$layout_type = 'standard';
			}
			update_option( 'conjure_bricks_layout_type', $layout_type );
			update_option( 'conjure_bricks_create_archive_template', ! empty( $_POST['conjure_bricks_create_archive_template'] ) );
			update_option( 'conjure_bricks_create_single_template', ! empty( $_POST['conjure_bricks_create_single_template'] ) );
		}

		if ( in_array( 'post_launch_guidance', $enabled_keys, true ) ) {
			if ( ! empty( $_POST['conjure_bricks_set_builder_access'] ) ) {
				$this->set_bricks_option( 'builderAccess', array( 'administrator' ) );
			}
			update_option( 'conjure_bricks_lock_templates', ! empty( $_POST['conjure_bricks_lock_templates'] ) );

			if ( ! empty( $_POST['conjure_bricks_flush_rewrite'] ) ) {
				flush_rewrite_rules();
			}
		}

		$this->conjure->mark_step_completed( $this->get_step_key() );
		wp_safe_redirect( $this->conjure->step_next_link() );
		exit;
	}

	/**
	 * Get a Bricks global setting.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	protected function get_bricks_option( $key, $default = '' ) {
		$settings = get_option( 'bricks_global_settings', array() );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	/**
	 * Set a Bricks global setting.
	 *
	 * @param string $key   Setting key.
	 * @param mixed  $value Setting value.
	 * @return void
	 */
	protected function set_bricks_option( $key, $value ) {
		$settings = get_option( 'bricks_global_settings', array() );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings[ $key ] = $value;
		update_option( 'bricks_global_settings', $settings );
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
