<?php
/**
 * Yoast SEO step connector.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'conjurewp_is_yoast_seo_active' ) ) {
	/**
	 * Check whether Yoast SEO is active.
	 *
	 * @return bool
	 */
	function conjurewp_is_yoast_seo_active() {
		return defined( 'WPSEO_VERSION' );
	}
}

/**
 * Yoast SEO connector step.
 */
class Conjure_Step_Connector_Yoast_Seo extends Conjure_Step_Connector_Base {

	/**
	 * Feature groups for organising the step render.
	 *
	 * @var array
	 */
	protected static $feature_groups = array(
		'appearance'  => array(
			'label'    => 'Search appearance',
			'features' => array( 'search_appearance', 'indexing_defaults' ),
		),
		'content'     => array(
			'label'    => 'Content and sitemaps',
			'features' => array( 'xml_sitemap', 'social_metadata' ),
		),
		'navigation'  => array(
			'label'    => 'Navigation',
			'features' => array( 'breadcrumbs' ),
		),
	);

	/**
	 * Get available connector features.
	 *
	 * @return array
	 */
	public function get_features() {
		return array(
			'search_appearance' => array(
				'label'           => __( 'Search appearance configuration', 'ConjureWP' ),
				'description'     => __( 'Configure how your site appears in search results with title templates and meta descriptions.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'indexing_defaults'  => array(
				'label'           => __( 'Indexing defaults', 'ConjureWP' ),
				'description'     => __( 'Configure default noindex settings for post types, taxonomies and archives.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'xml_sitemap'        => array(
				'label'           => __( 'XML sitemap', 'ConjureWP' ),
				'description'     => __( 'Enable and configure the Yoast XML sitemap for search engine discovery.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'social_metadata'    => array(
				'label'           => __( 'Social sharing metadata', 'ConjureWP' ),
				'description'     => __( 'Configure Open Graph and Twitter card defaults for social media sharing.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'breadcrumbs'        => array(
				'label'           => __( 'Breadcrumb setup', 'ConjureWP' ),
				'description'     => __( 'Enable and configure Yoast breadcrumbs for improved site navigation.', 'ConjureWP' ),
				'default_enabled' => false,
			),
		);
	}

	/**
	 * Render the Yoast SEO step.
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

				<h1><?php esc_html_e( 'Yoast SEO Setup', 'ConjureWP' ); ?></h1>
				<p><?php esc_html_e( 'Configure your Yoast SEO defaults and search appearance settings below.', 'ConjureWP' ); ?></p>

				<?php $this->render_version_update_toggle(); ?>

				<?php if ( ! $this->can_run() ) : ?>
					<p class="conjure__notice conjure__notice--warning">
						<?php esc_html_e( 'Yoast SEO is not currently active. Please activate it first, then return to this step.', 'ConjureWP' ); ?>
					</p>
				<?php elseif ( empty( $enabled_features ) ) : ?>
					<p><?php esc_html_e( 'No Yoast SEO features are currently enabled for this connector.', 'ConjureWP' ); ?></p>
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
						<?php esc_html_e( 'Apply Yoast SEO Setup', 'ConjureWP' ); ?>
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
			case 'search_appearance':
				$this->render_search_appearance_fields();
				break;

			case 'indexing_defaults':
				$this->render_indexing_defaults_fields();
				break;

			case 'xml_sitemap':
				$this->render_xml_sitemap_fields();
				break;

			case 'social_metadata':
				$this->render_social_metadata_fields();
				break;

			case 'breadcrumbs':
				$this->render_breadcrumbs_fields();
				break;
		}
	}

	/**
	 * Render search appearance fields.
	 *
	 * @return void
	 */
	protected function render_search_appearance_fields() {
		$title_separator = get_option( 'conjure_yoast_title_separator', '-' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_yoast_title_separator" class="conjure__field-label">
				<?php esc_html_e( 'Title separator', 'ConjureWP' ); ?>
			</label>
			<input
				type="text"
				id="conjure_yoast_title_separator"
				name="conjure_yoast_title_separator"
				class="conjure__input"
				value="<?php echo esc_attr( $title_separator ); ?>"
			/>
		</div>
		<?php
		$post_title_template = get_option( 'conjure_yoast_post_title_template', '%%title%% %%sep%% %%sitename%%' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_yoast_post_title_template" class="conjure__field-label">
				<?php esc_html_e( 'Post title template', 'ConjureWP' ); ?>
			</label>
			<input
				type="text"
				id="conjure_yoast_post_title_template"
				name="conjure_yoast_post_title_template"
				class="conjure__input"
				value="<?php echo esc_attr( $post_title_template ); ?>"
			/>
		</div>
		<?php
		$page_title_template = get_option( 'conjure_yoast_page_title_template', '%%title%% %%sep%% %%sitename%%' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_yoast_page_title_template" class="conjure__field-label">
				<?php esc_html_e( 'Page title template', 'ConjureWP' ); ?>
			</label>
			<input
				type="text"
				id="conjure_yoast_page_title_template"
				name="conjure_yoast_page_title_template"
				class="conjure__input"
				value="<?php echo esc_attr( $page_title_template ); ?>"
			/>
		</div>
		<?php
		$this->render_checkbox_field(
			'conjure_yoast_strip_category_base',
			__( 'Remove category base from URLs', 'ConjureWP' ),
			false
		);
	}

	/**
	 * Render indexing defaults fields.
	 *
	 * @return void
	 */
	protected function render_indexing_defaults_fields() {
		$this->render_checkbox_field(
			'conjure_yoast_noindex_author_archives',
			__( 'Set author archives to noindex', 'ConjureWP' ),
			false
		);
		$this->render_checkbox_field(
			'conjure_yoast_noindex_date_archives',
			__( 'Set date archives to noindex', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_yoast_noindex_format_archives',
			__( 'Set format-based archives to noindex', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_yoast_noindex_tags',
			__( 'Set tag archives to noindex', 'ConjureWP' ),
			false
		);
	}

	/**
	 * Render XML sitemap fields.
	 *
	 * @return void
	 */
	protected function render_xml_sitemap_fields() {
		$this->render_checkbox_field(
			'conjure_yoast_enable_sitemap',
			__( 'Enable XML sitemap', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_yoast_sitemap_include_images',
			__( 'Include images in sitemap', 'ConjureWP' ),
			true
		);
		$entries_per_page = get_option( 'conjure_yoast_sitemap_entries_per_page', '1000' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_yoast_sitemap_entries_per_page" class="conjure__field-label">
				<?php esc_html_e( 'Max entries per sitemap page', 'ConjureWP' ); ?>
			</label>
			<input
				type="number"
				id="conjure_yoast_sitemap_entries_per_page"
				name="conjure_yoast_sitemap_entries_per_page"
				class="conjure__input"
				value="<?php echo esc_attr( $entries_per_page ); ?>"
				min="100"
				max="50000"
			/>
		</div>
		<?php
	}

	/**
	 * Render social metadata fields.
	 *
	 * @return void
	 */
	protected function render_social_metadata_fields() {
		$this->render_checkbox_field(
			'conjure_yoast_enable_opengraph',
			__( 'Enable Open Graph meta tags', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_yoast_enable_twitter_cards',
			__( 'Enable Twitter card meta tags', 'ConjureWP' ),
			true
		);
		$facebook_url = get_option( 'conjure_yoast_facebook_url', '' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_yoast_facebook_url" class="conjure__field-label">
				<?php esc_html_e( 'Facebook page URL', 'ConjureWP' ); ?>
			</label>
			<input
				type="text"
				id="conjure_yoast_facebook_url"
				name="conjure_yoast_facebook_url"
				class="conjure__input"
				value="<?php echo esc_attr( $facebook_url ); ?>"
			/>
		</div>
		<?php
		$twitter_username = get_option( 'conjure_yoast_twitter_username', '' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_yoast_twitter_username" class="conjure__field-label">
				<?php esc_html_e( 'Twitter username', 'ConjureWP' ); ?>
			</label>
			<input
				type="text"
				id="conjure_yoast_twitter_username"
				name="conjure_yoast_twitter_username"
				class="conjure__input"
				value="<?php echo esc_attr( $twitter_username ); ?>"
			/>
		</div>
		<?php
	}

	/**
	 * Render breadcrumbs fields.
	 *
	 * @return void
	 */
	protected function render_breadcrumbs_fields() {
		$this->render_checkbox_field(
			'conjure_yoast_enable_breadcrumbs',
			__( 'Enable breadcrumbs', 'ConjureWP' ),
			false
		);
		$breadcrumb_separator = get_option( 'conjure_yoast_breadcrumb_separator', '>' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_yoast_breadcrumb_separator" class="conjure__field-label">
				<?php esc_html_e( 'Breadcrumb separator', 'ConjureWP' ); ?>
			</label>
			<input
				type="text"
				id="conjure_yoast_breadcrumb_separator"
				name="conjure_yoast_breadcrumb_separator"
				class="conjure__input"
				value="<?php echo esc_attr( $breadcrumb_separator ); ?>"
			/>
		</div>
		<?php
		$this->render_checkbox_field(
			'conjure_yoast_breadcrumb_show_home',
			__( 'Show home page in breadcrumbs', 'ConjureWP' ),
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
	 * Handle the Yoast SEO step.
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

		if ( in_array( 'search_appearance', $enabled_keys, true ) ) {
			$title_separator     = isset( $_POST['conjure_yoast_title_separator'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_yoast_title_separator'] ) ) : '-';
			$post_title_template = isset( $_POST['conjure_yoast_post_title_template'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_yoast_post_title_template'] ) ) : '%%title%% %%sep%% %%sitename%%';
			$page_title_template = isset( $_POST['conjure_yoast_page_title_template'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_yoast_page_title_template'] ) ) : '%%title%% %%sep%% %%sitename%%';

			update_option( 'conjure_yoast_title_separator', $title_separator );
			update_option( 'conjure_yoast_post_title_template', $post_title_template );
			update_option( 'conjure_yoast_page_title_template', $page_title_template );
			update_option( 'conjure_yoast_strip_category_base', ! empty( $_POST['conjure_yoast_strip_category_base'] ) );
		}

		if ( in_array( 'indexing_defaults', $enabled_keys, true ) ) {
			update_option( 'conjure_yoast_noindex_author_archives', ! empty( $_POST['conjure_yoast_noindex_author_archives'] ) );
			update_option( 'conjure_yoast_noindex_date_archives', ! empty( $_POST['conjure_yoast_noindex_date_archives'] ) );
			update_option( 'conjure_yoast_noindex_format_archives', ! empty( $_POST['conjure_yoast_noindex_format_archives'] ) );
			update_option( 'conjure_yoast_noindex_tags', ! empty( $_POST['conjure_yoast_noindex_tags'] ) );
		}

		if ( in_array( 'xml_sitemap', $enabled_keys, true ) ) {
			update_option( 'conjure_yoast_enable_sitemap', ! empty( $_POST['conjure_yoast_enable_sitemap'] ) );
			update_option( 'conjure_yoast_sitemap_include_images', ! empty( $_POST['conjure_yoast_sitemap_include_images'] ) );

			$entries_per_page = isset( $_POST['conjure_yoast_sitemap_entries_per_page'] ) ? absint( $_POST['conjure_yoast_sitemap_entries_per_page'] ) : 1000;
			$entries_per_page = max( 100, min( 50000, $entries_per_page ) );
			update_option( 'conjure_yoast_sitemap_entries_per_page', $entries_per_page );
		}

		if ( in_array( 'social_metadata', $enabled_keys, true ) ) {
			update_option( 'conjure_yoast_enable_opengraph', ! empty( $_POST['conjure_yoast_enable_opengraph'] ) );
			update_option( 'conjure_yoast_enable_twitter_cards', ! empty( $_POST['conjure_yoast_enable_twitter_cards'] ) );

			$facebook_url     = isset( $_POST['conjure_yoast_facebook_url'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_yoast_facebook_url'] ) ) : '';
			$twitter_username = isset( $_POST['conjure_yoast_twitter_username'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_yoast_twitter_username'] ) ) : '';

			update_option( 'conjure_yoast_facebook_url', $facebook_url );
			update_option( 'conjure_yoast_twitter_username', $twitter_username );
		}

		if ( in_array( 'breadcrumbs', $enabled_keys, true ) ) {
			update_option( 'conjure_yoast_enable_breadcrumbs', ! empty( $_POST['conjure_yoast_enable_breadcrumbs'] ) );

			$breadcrumb_separator = isset( $_POST['conjure_yoast_breadcrumb_separator'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_yoast_breadcrumb_separator'] ) ) : '>';
			update_option( 'conjure_yoast_breadcrumb_separator', $breadcrumb_separator );
			update_option( 'conjure_yoast_breadcrumb_show_home', ! empty( $_POST['conjure_yoast_breadcrumb_show_home'] ) );
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
