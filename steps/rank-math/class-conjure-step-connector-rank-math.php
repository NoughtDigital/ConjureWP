<?php
/**
 * Rank Math step connector.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'conjurewp_is_rank_math_active' ) ) {
	/**
	 * Check whether Rank Math is active.
	 *
	 * @return bool
	 */
	function conjurewp_is_rank_math_active() {
		return class_exists( 'RankMath' );
	}
}

/**
 * Rank Math connector step.
 */
class Conjure_Step_Connector_Rank_Math extends Conjure_Step_Connector_Base {

	/**
	 * Feature groups for organising the step render.
	 *
	 * @var array
	 */
	protected static $feature_groups = array(
		'search'   => array(
			'label'    => 'Search integration',
			'features' => array( 'search_console', 'sitemap' ),
		),
		'defaults' => array(
			'label'    => 'SEO defaults',
			'features' => array( 'schema_defaults', 'titles_meta', 'social_previews' ),
		),
	);

	/**
	 * Get available connector features.
	 *
	 * @return array
	 */
	public function get_features() {
		return array(
			'search_console'  => array(
				'label'           => __( 'Connect Google Search Console', 'ConjureWP' ),
				'description'     => __( 'Connect your Google Search Console account for search analytics integration.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'sitemap'         => array(
				'label'           => __( 'Configure sitemap', 'ConjureWP' ),
				'description'     => __( 'Configure XML sitemap settings and post type inclusion.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'schema_defaults' => array(
				'label'           => __( 'Schema defaults', 'ConjureWP' ),
				'description'     => __( 'Configure default structured data schema type for posts and pages.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'titles_meta'     => array(
				'label'           => __( 'SEO titles and meta patterns', 'ConjureWP' ),
				'description'     => __( 'Set default SEO title and meta description patterns for posts, pages and archives.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'social_previews' => array(
				'label'           => __( 'Social preview defaults', 'ConjureWP' ),
				'description'     => __( 'Configure default Open Graph and Twitter card settings for social sharing.', 'ConjureWP' ),
				'default_enabled' => true,
			),
		);
	}

	/**
	 * Render the Rank Math step.
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

				<h1><?php esc_html_e( 'Rank Math Setup', 'ConjureWP' ); ?></h1>
				<p><?php esc_html_e( 'Configure your Rank Math SEO defaults and search integration below.', 'ConjureWP' ); ?></p>

				<?php $this->render_version_update_toggle(); ?>

				<?php if ( ! $this->can_run() ) : ?>
					<p class="conjure__notice conjure__notice--warning">
						<?php esc_html_e( 'Rank Math is not currently active. Please activate it first, then return to this step.', 'ConjureWP' ); ?>
					</p>
				<?php elseif ( empty( $enabled_features ) ) : ?>
					<p><?php esc_html_e( 'No Rank Math features are currently enabled for this connector.', 'ConjureWP' ); ?></p>
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
						<?php esc_html_e( 'Apply Rank Math Setup', 'ConjureWP' ); ?>
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
			case 'search_console':
				$this->render_search_console_fields();
				break;

			case 'sitemap':
				$this->render_sitemap_fields();
				break;

			case 'schema_defaults':
				$this->render_schema_defaults_fields();
				break;

			case 'titles_meta':
				$this->render_titles_meta_fields();
				break;

			case 'social_previews':
				$this->render_social_previews_fields();
				break;
		}
	}

	/**
	 * Render search console fields.
	 *
	 * @return void
	 */
	protected function render_search_console_fields() {
		$this->render_checkbox_field(
			'conjure_rm_connect_search_console',
			__( 'Enable Google Search Console connection prompt', 'ConjureWP' ),
			true
		);
		$property_url = get_option( 'conjure_rm_search_console_property', '' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_rm_search_console_property" class="conjure__field-label">
				<?php esc_html_e( 'Search Console property URL', 'ConjureWP' ); ?>
			</label>
			<input
				type="text"
				id="conjure_rm_search_console_property"
				name="conjure_rm_search_console_property"
				class="conjure__input"
				value="<?php echo esc_attr( $property_url ); ?>"
			/>
		</div>
		<?php
	}

	/**
	 * Render sitemap fields.
	 *
	 * @return void
	 */
	protected function render_sitemap_fields() {
		$this->render_checkbox_field(
			'conjure_rm_enable_sitemap',
			__( 'Enable XML sitemap', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_rm_sitemap_include_images',
			__( 'Include images in sitemap', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_rm_sitemap_ping_search_engines',
			__( 'Ping search engines on sitemap update', 'ConjureWP' ),
			true
		);
	}

	/**
	 * Render schema defaults fields.
	 *
	 * @return void
	 */
	protected function render_schema_defaults_fields() {
		$current_type = get_option( 'conjure_rm_default_schema_type', 'Article' );
		$schema_types = array(
			'Article'      => __( 'Article', 'ConjureWP' ),
			'WebPage'      => __( 'WebPage', 'ConjureWP' ),
			'NewsArticle'  => __( 'NewsArticle', 'ConjureWP' ),
			'BlogPosting'  => __( 'BlogPosting', 'ConjureWP' ),
		);
		?>
		<div class="conjure__field-group">
			<label for="conjure_rm_default_schema_type" class="conjure__field-label">
				<?php esc_html_e( 'Default schema type', 'ConjureWP' ); ?>
			</label>
			<select id="conjure_rm_default_schema_type" name="conjure_rm_default_schema_type" class="conjure__select">
				<?php foreach ( $schema_types as $val => $label ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $current_type, $val ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
		$this->render_checkbox_field(
			'conjure_rm_enable_schema_for_pages',
			__( 'Enable schema markup for pages', 'ConjureWP' ),
			true
		);
	}

	/**
	 * Render titles and meta fields.
	 *
	 * @return void
	 */
	protected function render_titles_meta_fields() {
		$title_separator = get_option( 'conjure_rm_title_separator', '-' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_rm_title_separator" class="conjure__field-label">
				<?php esc_html_e( 'Title separator', 'ConjureWP' ); ?>
			</label>
			<input
				type="text"
				id="conjure_rm_title_separator"
				name="conjure_rm_title_separator"
				class="conjure__input"
				value="<?php echo esc_attr( $title_separator ); ?>"
			/>
		</div>
		<?php
		$post_title_pattern = get_option( 'conjure_rm_post_title_pattern', '%title% %sep% %sitename%' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_rm_post_title_pattern" class="conjure__field-label">
				<?php esc_html_e( 'Post title pattern', 'ConjureWP' ); ?>
			</label>
			<input
				type="text"
				id="conjure_rm_post_title_pattern"
				name="conjure_rm_post_title_pattern"
				class="conjure__input"
				value="<?php echo esc_attr( $post_title_pattern ); ?>"
			/>
		</div>
		<?php
		$page_title_pattern = get_option( 'conjure_rm_page_title_pattern', '%title% %sep% %sitename%' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_rm_page_title_pattern" class="conjure__field-label">
				<?php esc_html_e( 'Page title pattern', 'ConjureWP' ); ?>
			</label>
			<input
				type="text"
				id="conjure_rm_page_title_pattern"
				name="conjure_rm_page_title_pattern"
				class="conjure__input"
				value="<?php echo esc_attr( $page_title_pattern ); ?>"
			/>
		</div>
		<?php
		$this->render_checkbox_field(
			'conjure_rm_noindex_empty_taxonomies',
			__( 'Set empty taxonomies to noindex', 'ConjureWP' ),
			true
		);
	}

	/**
	 * Render social previews fields.
	 *
	 * @return void
	 */
	protected function render_social_previews_fields() {
		$this->render_checkbox_field(
			'conjure_rm_enable_opengraph',
			__( 'Enable Open Graph meta tags', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_rm_enable_twitter_cards',
			__( 'Enable Twitter card meta tags', 'ConjureWP' ),
			true
		);
		$current_card_type = get_option( 'conjure_rm_twitter_card_type', 'summary_large_image' );
		$card_types        = array(
			'summary'             => __( 'summary', 'ConjureWP' ),
			'summary_large_image' => __( 'summary_large_image', 'ConjureWP' ),
		);
		?>
		<div class="conjure__field-group">
			<label for="conjure_rm_twitter_card_type" class="conjure__field-label">
				<?php esc_html_e( 'Default Twitter card type', 'ConjureWP' ); ?>
			</label>
			<select id="conjure_rm_twitter_card_type" name="conjure_rm_twitter_card_type" class="conjure__select">
				<?php foreach ( $card_types as $val => $label ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $current_card_type, $val ); ?>>
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
	 * Handle the Rank Math step.
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

		if ( in_array( 'search_console', $enabled_keys, true ) ) {
			update_option( 'conjure_rm_connect_search_console', ! empty( $_POST['conjure_rm_connect_search_console'] ) );
			$property_url = isset( $_POST['conjure_rm_search_console_property'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_rm_search_console_property'] ) ) : '';
			update_option( 'conjure_rm_search_console_property', $property_url );
		}

		if ( in_array( 'sitemap', $enabled_keys, true ) ) {
			update_option( 'conjure_rm_enable_sitemap', ! empty( $_POST['conjure_rm_enable_sitemap'] ) );
			update_option( 'conjure_rm_sitemap_include_images', ! empty( $_POST['conjure_rm_sitemap_include_images'] ) );
			update_option( 'conjure_rm_sitemap_ping_search_engines', ! empty( $_POST['conjure_rm_sitemap_ping_search_engines'] ) );
		}

		if ( in_array( 'schema_defaults', $enabled_keys, true ) ) {
			$allowed_schema_types = array( 'Article', 'WebPage', 'NewsArticle', 'BlogPosting' );
			$schema_type          = isset( $_POST['conjure_rm_default_schema_type'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_rm_default_schema_type'] ) ) : 'Article';
			if ( ! in_array( $schema_type, $allowed_schema_types, true ) ) {
				$schema_type = 'Article';
			}
			update_option( 'conjure_rm_default_schema_type', $schema_type );
			update_option( 'conjure_rm_enable_schema_for_pages', ! empty( $_POST['conjure_rm_enable_schema_for_pages'] ) );
		}

		if ( in_array( 'titles_meta', $enabled_keys, true ) ) {
			$title_separator    = isset( $_POST['conjure_rm_title_separator'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_rm_title_separator'] ) ) : '-';
			$post_title_pattern = isset( $_POST['conjure_rm_post_title_pattern'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_rm_post_title_pattern'] ) ) : '%title% %sep% %sitename%';
			$page_title_pattern = isset( $_POST['conjure_rm_page_title_pattern'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_rm_page_title_pattern'] ) ) : '%title% %sep% %sitename%';

			update_option( 'conjure_rm_title_separator', $title_separator );
			update_option( 'conjure_rm_post_title_pattern', $post_title_pattern );
			update_option( 'conjure_rm_page_title_pattern', $page_title_pattern );
			update_option( 'conjure_rm_noindex_empty_taxonomies', ! empty( $_POST['conjure_rm_noindex_empty_taxonomies'] ) );
		}

		if ( in_array( 'social_previews', $enabled_keys, true ) ) {
			update_option( 'conjure_rm_enable_opengraph', ! empty( $_POST['conjure_rm_enable_opengraph'] ) );
			update_option( 'conjure_rm_enable_twitter_cards', ! empty( $_POST['conjure_rm_enable_twitter_cards'] ) );

			$allowed_card_types = array( 'summary', 'summary_large_image' );
			$card_type          = isset( $_POST['conjure_rm_twitter_card_type'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_rm_twitter_card_type'] ) ) : 'summary_large_image';
			if ( ! in_array( $card_type, $allowed_card_types, true ) ) {
				$card_type = 'summary_large_image';
			}
			update_option( 'conjure_rm_twitter_card_type', $card_type );
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
