<?php
/**
 * Gutenberg step connector.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'conjurewp_is_gutenberg_active' ) ) {
	/**
	 * Check whether the block editor is available.
	 *
	 * Returns true when the Classic Editor plugin is not forcing the classic
	 * experience, meaning the block editor is the active editing interface.
	 *
	 * @return bool
	 */
	function conjurewp_is_gutenberg_active() {
		if ( function_exists( 'use_block_editor_for_post_type' ) && use_block_editor_for_post_type( 'page' ) ) {
			return true;
		}

		return ! class_exists( 'Classic_Editor' );
	}
}

/**
 * Gutenberg connector step.
 */
class Conjure_Step_Connector_Gutenberg extends Conjure_Step_Connector_Base {

	/**
	 * Feature groups for organising the step render.
	 *
	 * @var array
	 */
	protected static $feature_groups = array(
		'editor' => array(
			'label'    => 'Editor setup',
			'features' => array( 'block_editor_setup', 'theme_json_alignment' ),
		),
		'content' => array(
			'label'    => 'Content structure',
			'features' => array( 'pattern_template_guidance', 'page_post_presets' ),
		),
		'guidance' => array(
			'label'    => 'Guidance',
			'features' => array( 'training_prompts' ),
		),
	);

	/**
	 * Get available connector features.
	 *
	 * @return array
	 */
	public function get_features() {
		return array(
			'block_editor_setup'        => array(
				'label'           => __( 'Block editor-first setup', 'ConjureWP' ),
				'description'     => __( 'Provide a block editor-first setup experience with sensible editor defaults.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'pattern_template_guidance' => array(
				'label'           => __( 'Pattern and template guidance', 'ConjureWP' ),
				'description'     => __( 'Guide users through available block patterns and templates for new sites.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'page_post_presets'         => array(
				'label'           => __( 'Page and post structure presets', 'ConjureWP' ),
				'description'     => __( 'Configure default page and post structure presets for consistent content.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'theme_json_alignment'      => array(
				'label'           => __( 'Theme.json and editor preferences', 'ConjureWP' ),
				'description'     => __( 'Align theme.json settings with editor preferences for a consistent editing experience.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'training_prompts'          => array(
				'label'           => __( 'Training prompts', 'ConjureWP' ),
				'description'     => __( 'Display training prompts for content teams and clients working with the block editor.', 'ConjureWP' ),
				'default_enabled' => false,
			),
		);
	}

	/**
	 * Render the Gutenberg step.
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

				<h1><?php esc_html_e( 'Gutenberg Setup', 'ConjureWP' ); ?></h1>
				<p><?php esc_html_e( 'Configure block editor defaults and content structure presets below.', 'ConjureWP' ); ?></p>

				<?php $this->render_version_update_toggle(); ?>

				<?php if ( ! $this->can_run() ) : ?>
					<p class="conjure__notice conjure__notice--warning">
						<?php esc_html_e( 'The block editor is not currently available. The Classic Editor plugin may be overriding it.', 'ConjureWP' ); ?>
					</p>
				<?php elseif ( empty( $enabled_features ) ) : ?>
					<p><?php esc_html_e( 'No Gutenberg features are currently enabled for this connector.', 'ConjureWP' ); ?></p>
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
						<?php esc_html_e( 'Apply Gutenberg Setup', 'ConjureWP' ); ?>
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
			case 'block_editor_setup':
				$this->render_editor_setup_fields();
				break;

			case 'pattern_template_guidance':
				$this->render_pattern_guidance_fields();
				break;

			case 'page_post_presets':
				$this->render_preset_fields();
				break;

			case 'theme_json_alignment':
				$this->render_theme_json_fields();
				break;

			case 'training_prompts':
				$this->render_training_fields();
				break;
		}
	}

	/**
	 * Render editor setup fields.
	 *
	 * @return void
	 */
	protected function render_editor_setup_fields() {
		$this->render_checkbox_field(
			'conjure_gutenberg_wide_align',
			__( 'Enable wide and full-width alignment support', 'ConjureWP' ),
			(bool) get_theme_support( 'align-wide' )
		);
		$this->render_checkbox_field(
			'conjure_gutenberg_responsive_embeds',
			__( 'Enable responsive embed support', 'ConjureWP' ),
			(bool) get_theme_support( 'responsive-embeds' )
		);
		$this->render_checkbox_field(
			'conjure_gutenberg_editor_styles',
			__( 'Enable editor styles', 'ConjureWP' ),
			(bool) get_theme_support( 'editor-styles' )
		);
		$this->render_checkbox_field(
			'conjure_gutenberg_appearance_tools',
			__( 'Enable appearance tools (spacing, border, typography controls)', 'ConjureWP' ),
			(bool) get_theme_support( 'appearance-tools' )
		);
	}

	/**
	 * Render page and post preset fields.
	 *
	 * @return void
	 */
	protected function render_preset_fields() {
		$show_on_front = get_option( 'show_on_front', 'posts' );
		$options       = array(
			'posts' => __( 'Latest posts', 'ConjureWP' ),
			'page'  => __( 'Static page', 'ConjureWP' ),
		);
		?>
		<div class="conjure__field-group">
			<label for="conjure_gutenberg_show_on_front" class="conjure__field-label">
				<?php esc_html_e( 'Homepage display', 'ConjureWP' ); ?>
			</label>
			<select id="conjure_gutenberg_show_on_front" name="conjure_gutenberg_show_on_front" class="conjure__select">
				<?php foreach ( $options as $val => $label ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $show_on_front, $val ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
		$posts_per_page = get_option( 'posts_per_page', 10 );
		?>
		<div class="conjure__field-group">
			<label for="conjure_gutenberg_posts_per_page" class="conjure__field-label">
				<?php esc_html_e( 'Posts per page', 'ConjureWP' ); ?>
			</label>
			<input
				type="number"
				id="conjure_gutenberg_posts_per_page"
				name="conjure_gutenberg_posts_per_page"
				class="conjure__input"
				value="<?php echo esc_attr( $posts_per_page ); ?>"
				min="1"
				max="100"
			/>
		</div>
		<?php
		$permalink = get_option( 'permalink_structure', '' );
		$structures = array(
			''            => __( 'Plain', 'ConjureWP' ),
			'/%postname%/' => __( 'Post name', 'ConjureWP' ),
			'/%year%/%monthnum%/%postname%/' => __( 'Day and name', 'ConjureWP' ),
		);
		?>
		<div class="conjure__field-group">
			<label for="conjure_gutenberg_permalink" class="conjure__field-label">
				<?php esc_html_e( 'Permalink structure', 'ConjureWP' ); ?>
			</label>
			<select id="conjure_gutenberg_permalink" name="conjure_gutenberg_permalink" class="conjure__select">
				<?php foreach ( $structures as $val => $label ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $permalink, $val ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	/**
	 * Render pattern and template guidance fields.
	 *
	 * @return void
	 */
	protected function render_pattern_guidance_fields() {
		$this->render_checkbox_field(
			'conjure_gutenberg_register_patterns',
			__( 'Register starter block patterns for common page sections', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_gutenberg_register_templates',
			__( 'Register page templates (About, Contact, Services)', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_gutenberg_enable_pattern_directory',
			__( 'Enable WordPress.org pattern directory access', 'ConjureWP' ),
			true
		);
	}

	/**
	 * Render theme.json alignment fields.
	 *
	 * @return void
	 */
	protected function render_theme_json_fields() {
		$content_width = get_option( 'conjure_gutenberg_content_width', '1200' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_gutenberg_content_width" class="conjure__field-label">
				<?php esc_html_e( 'Content width for theme.json (px)', 'ConjureWP' ); ?>
			</label>
			<input
				type="number"
				id="conjure_gutenberg_content_width"
				name="conjure_gutenberg_content_width"
				class="conjure__input"
				value="<?php echo esc_attr( $content_width ); ?>"
				min="600"
				max="2400"
			/>
		</div>
		<?php
		$wide_width = get_option( 'conjure_gutenberg_wide_width', '1400' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_gutenberg_wide_width" class="conjure__field-label">
				<?php esc_html_e( 'Wide width for theme.json (px)', 'ConjureWP' ); ?>
			</label>
			<input
				type="number"
				id="conjure_gutenberg_wide_width"
				name="conjure_gutenberg_wide_width"
				class="conjure__input"
				value="<?php echo esc_attr( $wide_width ); ?>"
				min="800"
				max="3000"
			/>
		</div>
		<?php
		$this->render_checkbox_field(
			'conjure_gutenberg_enable_custom_spacing',
			__( 'Enable custom spacing scale in editor', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_gutenberg_enable_custom_line_height',
			__( 'Enable custom line height control', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_gutenberg_enable_link_colour',
			__( 'Enable link colour controls', 'ConjureWP' ),
			true
		);
	}

	/**
	 * Render training prompt fields.
	 *
	 * @return void
	 */
	protected function render_training_fields() {
		$this->render_checkbox_field(
			'conjure_gutenberg_show_block_tips',
			__( 'Show block editor tips for new users', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_gutenberg_show_welcome_guide',
			__( 'Show the block editor welcome guide on first visit', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_gutenberg_enable_fullscreen',
			__( 'Default to full-screen editing mode', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_gutenberg_disable_block_directory',
			__( 'Disable block directory (prevent clients installing blocks)', 'ConjureWP' ),
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
	 * Render a feature as a read-only info item.
	 *
	 * @param array $feature Feature data.
	 * @return void
	 */
	protected function render_feature_info( $feature ) {
		?>
		<div class="conjure__field-group conjure__field-group--info">
			<p>
				<strong><?php echo esc_html( $feature['label'] ); ?></strong>
				&mdash;
				<?php echo esc_html( $feature['description'] ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Handle the Gutenberg step.
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

		if ( in_array( 'pattern_template_guidance', $enabled_keys, true ) ) {
			update_option( 'conjure_gutenberg_register_patterns', ! empty( $_POST['conjure_gutenberg_register_patterns'] ) );
			update_option( 'conjure_gutenberg_register_templates', ! empty( $_POST['conjure_gutenberg_register_templates'] ) );
			update_option( 'conjure_gutenberg_enable_pattern_directory', ! empty( $_POST['conjure_gutenberg_enable_pattern_directory'] ) );
		}

		if ( in_array( 'theme_json_alignment', $enabled_keys, true ) ) {
			$content_width = isset( $_POST['conjure_gutenberg_content_width'] ) ? absint( $_POST['conjure_gutenberg_content_width'] ) : 1200;
			$content_width = max( 600, min( 2400, $content_width ) );
			update_option( 'conjure_gutenberg_content_width', $content_width );

			$wide_width = isset( $_POST['conjure_gutenberg_wide_width'] ) ? absint( $_POST['conjure_gutenberg_wide_width'] ) : 1400;
			$wide_width = max( 800, min( 3000, $wide_width ) );
			update_option( 'conjure_gutenberg_wide_width', $wide_width );

			update_option( 'conjure_gutenberg_enable_custom_spacing', ! empty( $_POST['conjure_gutenberg_enable_custom_spacing'] ) );
			update_option( 'conjure_gutenberg_enable_custom_line_height', ! empty( $_POST['conjure_gutenberg_enable_custom_line_height'] ) );
			update_option( 'conjure_gutenberg_enable_link_colour', ! empty( $_POST['conjure_gutenberg_enable_link_colour'] ) );
		}

		if ( in_array( 'training_prompts', $enabled_keys, true ) ) {
			update_option( 'conjure_gutenberg_show_block_tips', ! empty( $_POST['conjure_gutenberg_show_block_tips'] ) );
			update_option( 'conjure_gutenberg_show_welcome_guide', ! empty( $_POST['conjure_gutenberg_show_welcome_guide'] ) );
			update_option( 'conjure_gutenberg_enable_fullscreen', ! empty( $_POST['conjure_gutenberg_enable_fullscreen'] ) );
			update_option( 'conjure_gutenberg_disable_block_directory', ! empty( $_POST['conjure_gutenberg_disable_block_directory'] ) );
		}

		if ( in_array( 'page_post_presets', $enabled_keys, true ) ) {
			$show_on_front = isset( $_POST['conjure_gutenberg_show_on_front'] ) ? sanitize_key( wp_unslash( $_POST['conjure_gutenberg_show_on_front'] ) ) : 'posts';
			if ( in_array( $show_on_front, array( 'posts', 'page' ), true ) ) {
				update_option( 'show_on_front', $show_on_front );
			}

			if ( 'page' === $show_on_front ) {
				$this->ensure_static_front_page();
			}

			$posts_per_page = isset( $_POST['conjure_gutenberg_posts_per_page'] ) ? absint( $_POST['conjure_gutenberg_posts_per_page'] ) : 10;
			if ( $posts_per_page < 1 ) {
				$posts_per_page = 10;
			}
			update_option( 'posts_per_page', $posts_per_page );

			$permalink = isset( $_POST['conjure_gutenberg_permalink'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_gutenberg_permalink'] ) ) : '';
			$valid_structures = array( '', '/%postname%/', '/%year%/%monthnum%/%postname%/' );
			if ( in_array( $permalink, $valid_structures, true ) ) {
				update_option( 'permalink_structure', $permalink );
				flush_rewrite_rules();
			}
		}

		$action_features = array( 'block_editor_setup', 'pattern_template_guidance', 'theme_json_alignment', 'training_prompts' );
		foreach ( $action_features as $feature_id ) {
			if ( in_array( $feature_id, $enabled_keys, true ) ) {
				$this->run_feature( $feature_id );
			}
		}

		$this->conjure->mark_step_completed( $this->get_step_key() );
		wp_safe_redirect( $this->conjure->step_next_link() );
		exit;
	}

	/**
	 * Run an action-based connector feature.
	 *
	 * @param string $feature_id Feature identifier.
	 * @return void
	 */
	protected function run_feature( $feature_id ) {
		switch ( $feature_id ) {
			case 'block_editor_setup':
				$this->apply_editor_defaults();
				break;

			case 'pattern_template_guidance':
				update_option( 'conjure_gutenberg_pattern_guidance_shown', true );
				break;

			case 'theme_json_alignment':
				update_option( 'conjure_gutenberg_theme_json_aligned', true );
				break;

			case 'training_prompts':
				update_option( 'conjure_gutenberg_training_prompts_shown', true );
				break;
		}
	}

	/**
	 * Apply block editor defaults based on submitted settings.
	 *
	 * @return void
	 */
	protected function apply_editor_defaults() {
		$settings = array(
			'conjure_gutenberg_wide_align'       => 'align-wide',
			'conjure_gutenberg_responsive_embeds' => 'responsive-embeds',
			'conjure_gutenberg_editor_styles'     => 'editor-styles',
			'conjure_gutenberg_appearance_tools'  => 'appearance-tools',
		);

		$enabled = array();

		foreach ( $settings as $field_name => $theme_support ) {
			if ( ! empty( $_POST[ $field_name ] ) ) {
				$enabled[] = $theme_support;
			}
		}

		update_option( 'conjure_gutenberg_editor_features', $enabled );
	}

	/**
	 * Create a static front page and blog page if they do not exist.
	 *
	 * @return void
	 */
	protected function ensure_static_front_page() {
		$front_page_id = (int) get_option( 'page_on_front', 0 );

		if ( $front_page_id < 1 || 'publish' !== get_post_status( $front_page_id ) ) {
			$front_page_id = wp_insert_post(
				array(
					'post_title'  => __( 'Home', 'ConjureWP' ),
					'post_name'   => 'home',
					'post_status' => 'publish',
					'post_type'   => 'page',
				)
			);

			if ( $front_page_id > 0 ) {
				update_option( 'page_on_front', $front_page_id );
			}
		}

		$blog_page_id = (int) get_option( 'page_for_posts', 0 );

		if ( $blog_page_id < 1 || 'publish' !== get_post_status( $blog_page_id ) ) {
			$blog_page_id = wp_insert_post(
				array(
					'post_title'  => __( 'Blog', 'ConjureWP' ),
					'post_name'   => 'blog',
					'post_status' => 'publish',
					'post_type'   => 'page',
				)
			);

			if ( $blog_page_id > 0 ) {
				update_option( 'page_for_posts', $blog_page_id );
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
