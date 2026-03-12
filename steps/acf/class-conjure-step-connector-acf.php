<?php
/**
 * ACF step connector.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'conjurewp_is_acf_active' ) ) {
	/**
	 * Check whether Advanced Custom Fields is active.
	 *
	 * Supports both free and PRO versions.
	 *
	 * @return bool
	 */
	function conjurewp_is_acf_active() {
		return class_exists( 'ACF' ) || function_exists( 'acf' );
	}
}

/**
 * ACF connector step.
 */
class Conjure_Step_Connector_ACF extends Conjure_Step_Connector_Base {

	/**
	 * Feature groups for organising the step render.
	 *
	 * @var array
	 */
	protected static $feature_groups = array(
		'fields' => array(
			'label'    => 'Field groups',
			'features' => array( 'field_group_config', 'starter_field_groups' ),
		),
		'developer' => array(
			'label'    => 'Developer settings',
			'features' => array( 'json_sync', 'options_pages' ),
		),
		'content' => array(
			'label'    => 'Content structure',
			'features' => array( 'content_structure' ),
		),
	);

	/**
	 * Get available connector features.
	 *
	 * @return array
	 */
	public function get_features() {
		return array(
			'field_group_config'  => array(
				'label'           => __( 'Field group configuration', 'ConjureWP' ),
				'description'     => __( 'Configure default field group settings and editor compatibility options.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'starter_field_groups' => array(
				'label'           => __( 'Starter field groups', 'ConjureWP' ),
				'description'     => __( 'Create commonly used starter field groups during onboarding.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'json_sync'           => array(
				'label'           => __( 'Local JSON sync', 'ConjureWP' ),
				'description'     => __( 'Configure ACF local JSON for version-controlled field group storage.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'options_pages'       => array(
				'label'           => __( 'Options pages', 'ConjureWP' ),
				'description'     => __( 'Register commonly used ACF options pages for site-wide settings.', 'ConjureWP' ),
				'default_enabled' => false,
			),
			'content_structure'   => array(
				'label'           => __( 'Content structure', 'ConjureWP' ),
				'description'     => __( 'Configure custom post type and taxonomy field assignments for structured content.', 'ConjureWP' ),
				'default_enabled' => true,
			),
		);
	}

	/**
	 * Render the ACF step.
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

				<h1><?php esc_html_e( 'ACF Setup', 'ConjureWP' ); ?></h1>
				<p><?php esc_html_e( 'Configure your Advanced Custom Fields defaults and developer settings below.', 'ConjureWP' ); ?></p>

				<?php $this->render_version_update_toggle(); ?>

				<?php if ( ! $this->can_run() ) : ?>
					<p class="conjure__notice conjure__notice--warning">
						<?php esc_html_e( 'Advanced Custom Fields is not currently active. Please activate it first, then return to this step.', 'ConjureWP' ); ?>
					</p>
				<?php elseif ( empty( $enabled_features ) ) : ?>
					<p><?php esc_html_e( 'No ACF features are currently enabled for this connector.', 'ConjureWP' ); ?></p>
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
						<?php esc_html_e( 'Apply ACF Setup', 'ConjureWP' ); ?>
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
			case 'field_group_config':
				$this->render_field_group_config_fields();
				break;

			case 'starter_field_groups':
				$this->render_starter_field_groups_fields();
				break;

			case 'json_sync':
				$this->render_json_sync_fields();
				break;

			case 'options_pages':
				$this->render_options_pages_fields();
				break;

			case 'content_structure':
				$this->render_content_structure_fields();
				break;
		}
	}

	/**
	 * Render field group configuration fields.
	 *
	 * @return void
	 */
	protected function render_field_group_config_fields() {
		$this->render_checkbox_field(
			'conjure_acf_show_in_rest',
			__( 'Expose field groups to the REST API by default', 'ConjureWP' ),
			false
		);
		$this->render_checkbox_field(
			'conjure_acf_gutenberg_compat',
			__( 'Enable block editor (Gutenberg) compatibility mode', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_acf_hide_admin_menu',
			__( 'Hide the ACF admin menu from non-administrators', 'ConjureWP' ),
			false
		);

		$style = get_option( 'conjure_acf_field_group_style', 'default' );
		$styles = array(
			'default'  => __( 'Default (WP meta box)', 'ConjureWP' ),
			'seamless' => __( 'Seamless (no meta box)', 'ConjureWP' ),
		);
		?>
		<div class="conjure__field-group">
			<label for="conjure_acf_field_group_style" class="conjure__field-label">
				<?php esc_html_e( 'Default field group style', 'ConjureWP' ); ?>
			</label>
			<select id="conjure_acf_field_group_style" name="conjure_acf_field_group_style" class="conjure__select">
				<?php foreach ( $styles as $val => $label ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $style, $val ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<?php
		$position = get_option( 'conjure_acf_field_group_position', 'acf_after_title' );
		$positions = array(
			'acf_after_title' => __( 'After title', 'ConjureWP' ),
			'normal'          => __( 'Normal (below content)', 'ConjureWP' ),
			'side'            => __( 'Side', 'ConjureWP' ),
		);
		?>
		<div class="conjure__field-group">
			<label for="conjure_acf_field_group_position" class="conjure__field-label">
				<?php esc_html_e( 'Default field group position', 'ConjureWP' ); ?>
			</label>
			<select id="conjure_acf_field_group_position" name="conjure_acf_field_group_position" class="conjure__select">
				<?php foreach ( $positions as $val => $label ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $position, $val ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	/**
	 * Render starter field groups fields.
	 *
	 * @return void
	 */
	protected function render_starter_field_groups_fields() {
		$this->render_checkbox_field(
			'conjure_acf_create_page_meta',
			__( 'Create a "Page Meta" field group (subtitle, hero image, CTA)', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_acf_create_post_meta',
			__( 'Create a "Post Meta" field group (author bio, read time, featured)', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_acf_create_testimonials',
			__( 'Create a "Testimonials" repeater field group', 'ConjureWP' ),
			false
		);
		$this->render_checkbox_field(
			'conjure_acf_create_flexible_content',
			__( 'Create a "Page Builder" flexible content field group', 'ConjureWP' ),
			false
		);
	}

	/**
	 * Render local JSON sync fields.
	 *
	 * @return void
	 */
	protected function render_json_sync_fields() {
		$this->render_checkbox_field(
			'conjure_acf_enable_local_json',
			__( 'Enable ACF local JSON', 'ConjureWP' ),
			true
		);

		$save_path = get_option( 'conjure_acf_json_save_path', 'acf-json' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_acf_json_save_path" class="conjure__field-label">
				<?php esc_html_e( 'Local JSON save path (relative to active theme)', 'ConjureWP' ); ?>
			</label>
			<input
				type="text"
				id="conjure_acf_json_save_path"
				name="conjure_acf_json_save_path"
				class="conjure__input"
				value="<?php echo esc_attr( $save_path ); ?>"
			/>
		</div>
		<?php
		$this->render_checkbox_field(
			'conjure_acf_create_json_directory',
			__( 'Automatically create the JSON directory if it does not exist', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_acf_auto_sync',
			__( 'Enable automatic sync on field group save', 'ConjureWP' ),
			true
		);
	}

	/**
	 * Render options pages fields.
	 *
	 * @return void
	 */
	protected function render_options_pages_fields() {
		$this->render_checkbox_field(
			'conjure_acf_create_general_options',
			__( 'Register a "Site Options" page', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_acf_create_header_footer_options',
			__( 'Register "Header Settings" and "Footer Settings" sub-pages', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_acf_create_social_options',
			__( 'Register a "Social Media" sub-page', 'ConjureWP' ),
			false
		);
		$this->render_checkbox_field(
			'conjure_acf_create_analytics_options',
			__( 'Register an "Analytics and Tracking" sub-page', 'ConjureWP' ),
			false
		);
	}

	/**
	 * Render content structure fields.
	 *
	 * @return void
	 */
	protected function render_content_structure_fields() {
		$this->render_checkbox_field(
			'conjure_acf_assign_to_posts',
			__( 'Assign default field groups to posts', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_acf_assign_to_pages',
			__( 'Assign default field groups to pages', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_acf_enable_bidirectional',
			__( 'Enable bidirectional relationship fields where applicable', 'ConjureWP' ),
			false
		);
		$this->render_checkbox_field(
			'conjure_acf_preload_blocks',
			__( 'Pre-register ACF blocks for the block editor', 'ConjureWP' ),
			false
		);

		$label_placement = get_option( 'conjure_acf_label_placement', 'top' );
		$placements = array(
			'top'  => __( 'Top aligned', 'ConjureWP' ),
			'left' => __( 'Left aligned', 'ConjureWP' ),
		);
		?>
		<div class="conjure__field-group">
			<label for="conjure_acf_label_placement" class="conjure__field-label">
				<?php esc_html_e( 'Default label placement', 'ConjureWP' ); ?>
			</label>
			<select id="conjure_acf_label_placement" name="conjure_acf_label_placement" class="conjure__select">
				<?php foreach ( $placements as $val => $label ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $label_placement, $val ); ?>>
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
	 * Handle the ACF step.
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

		if ( in_array( 'field_group_config', $enabled_keys, true ) ) {
			update_option( 'conjure_acf_show_in_rest', ! empty( $_POST['conjure_acf_show_in_rest'] ) );
			update_option( 'conjure_acf_gutenberg_compat', ! empty( $_POST['conjure_acf_gutenberg_compat'] ) );
			update_option( 'conjure_acf_hide_admin_menu', ! empty( $_POST['conjure_acf_hide_admin_menu'] ) );

			$style       = isset( $_POST['conjure_acf_field_group_style'] ) ? sanitize_key( wp_unslash( $_POST['conjure_acf_field_group_style'] ) ) : 'default';
			$valid_styles = array( 'default', 'seamless' );
			if ( ! in_array( $style, $valid_styles, true ) ) {
				$style = 'default';
			}
			update_option( 'conjure_acf_field_group_style', $style );

			$position       = isset( $_POST['conjure_acf_field_group_position'] ) ? sanitize_key( wp_unslash( $_POST['conjure_acf_field_group_position'] ) ) : 'acf_after_title';
			$valid_positions = array( 'acf_after_title', 'normal', 'side' );
			if ( ! in_array( $position, $valid_positions, true ) ) {
				$position = 'acf_after_title';
			}
			update_option( 'conjure_acf_field_group_position', $position );
		}

		if ( in_array( 'starter_field_groups', $enabled_keys, true ) ) {
			update_option( 'conjure_acf_create_page_meta', ! empty( $_POST['conjure_acf_create_page_meta'] ) );
			update_option( 'conjure_acf_create_post_meta', ! empty( $_POST['conjure_acf_create_post_meta'] ) );
			update_option( 'conjure_acf_create_testimonials', ! empty( $_POST['conjure_acf_create_testimonials'] ) );
			update_option( 'conjure_acf_create_flexible_content', ! empty( $_POST['conjure_acf_create_flexible_content'] ) );
		}

		if ( in_array( 'json_sync', $enabled_keys, true ) ) {
			update_option( 'conjure_acf_enable_local_json', ! empty( $_POST['conjure_acf_enable_local_json'] ) );

			$save_path = isset( $_POST['conjure_acf_json_save_path'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_acf_json_save_path'] ) ) : 'acf-json';
			if ( empty( $save_path ) ) {
				$save_path = 'acf-json';
			}
			update_option( 'conjure_acf_json_save_path', $save_path );

			update_option( 'conjure_acf_create_json_directory', ! empty( $_POST['conjure_acf_create_json_directory'] ) );
			update_option( 'conjure_acf_auto_sync', ! empty( $_POST['conjure_acf_auto_sync'] ) );

			if ( ! empty( $_POST['conjure_acf_enable_local_json'] ) && ! empty( $_POST['conjure_acf_create_json_directory'] ) ) {
				$this->maybe_create_json_directory( $save_path );
			}
		}

		if ( in_array( 'options_pages', $enabled_keys, true ) ) {
			update_option( 'conjure_acf_create_general_options', ! empty( $_POST['conjure_acf_create_general_options'] ) );
			update_option( 'conjure_acf_create_header_footer_options', ! empty( $_POST['conjure_acf_create_header_footer_options'] ) );
			update_option( 'conjure_acf_create_social_options', ! empty( $_POST['conjure_acf_create_social_options'] ) );
			update_option( 'conjure_acf_create_analytics_options', ! empty( $_POST['conjure_acf_create_analytics_options'] ) );
		}

		if ( in_array( 'content_structure', $enabled_keys, true ) ) {
			update_option( 'conjure_acf_assign_to_posts', ! empty( $_POST['conjure_acf_assign_to_posts'] ) );
			update_option( 'conjure_acf_assign_to_pages', ! empty( $_POST['conjure_acf_assign_to_pages'] ) );
			update_option( 'conjure_acf_enable_bidirectional', ! empty( $_POST['conjure_acf_enable_bidirectional'] ) );
			update_option( 'conjure_acf_preload_blocks', ! empty( $_POST['conjure_acf_preload_blocks'] ) );

			$label_placement  = isset( $_POST['conjure_acf_label_placement'] ) ? sanitize_key( wp_unslash( $_POST['conjure_acf_label_placement'] ) ) : 'top';
			$valid_placements = array( 'top', 'left' );
			if ( ! in_array( $label_placement, $valid_placements, true ) ) {
				$label_placement = 'top';
			}
			update_option( 'conjure_acf_label_placement', $label_placement );
		}

		$this->conjure->mark_step_completed( $this->get_step_key() );
		wp_safe_redirect( $this->conjure->step_next_link() );
		exit;
	}

	/**
	 * Create the ACF local JSON directory inside the active theme if it does not exist.
	 *
	 * @param string $relative_path Directory path relative to the active theme.
	 * @return void
	 */
	protected function maybe_create_json_directory( $relative_path ) {
		$theme_dir = get_stylesheet_directory();
		$json_dir  = trailingslashit( $theme_dir ) . ltrim( $relative_path, '/' );

		if ( ! is_dir( $json_dir ) ) {
			wp_mkdir_p( $json_dir );
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
