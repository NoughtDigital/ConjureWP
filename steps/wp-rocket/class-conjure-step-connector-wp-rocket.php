<?php
/**
 * WP Rocket step connector.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'conjurewp_is_wp_rocket_active' ) ) {
	/**
	 * Check whether WP Rocket is active.
	 *
	 * @return bool
	 */
	function conjurewp_is_wp_rocket_active() {
		return defined( 'WP_ROCKET_VERSION' );
	}
}

/**
 * WP Rocket connector step.
 */
class Conjure_Step_Connector_WP_Rocket extends Conjure_Step_Connector_Base {

	/**
	 * Feature groups for organising the step render.
	 *
	 * @var array
	 */
	protected static $feature_groups = array(
		'caching' => array(
			'label'    => 'Caching',
			'features' => array( 'cache_activation' ),
		),
		'optimisation' => array(
			'label'    => 'Optimisation',
			'features' => array( 'file_optimisation', 'lazy_loading' ),
		),
		'delivery' => array(
			'label'    => 'Delivery',
			'features' => array( 'preload_configuration', 'cdn_integration' ),
		),
	);

	/**
	 * Get available connector features.
	 *
	 * @return array
	 */
	public function get_features() {
		return array(
			'cache_activation'      => array(
				'label'           => __( 'Cache activation', 'ConjureWP' ),
				'description'     => __( 'Enable page caching for faster load times.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'file_optimisation'     => array(
				'label'           => __( 'File optimisation', 'ConjureWP' ),
				'description'     => __( 'Minify and combine CSS and JavaScript files to reduce page weight.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'lazy_loading'          => array(
				'label'           => __( 'Lazy loading', 'ConjureWP' ),
				'description'     => __( 'Enable lazy loading for images, iframes and videos.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'preload_configuration' => array(
				'label'           => __( 'Preload configuration', 'ConjureWP' ),
				'description'     => __( 'Configure preloading to keep the cache warm and improve performance.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'cdn_integration'       => array(
				'label'           => __( 'CDN integration', 'ConjureWP' ),
				'description'     => __( 'Configure a content delivery network for static asset delivery.', 'ConjureWP' ),
				'default_enabled' => false,
			),
		);
	}

	/**
	 * Render the WP Rocket step.
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

				<h1><?php esc_html_e( 'WP Rocket Setup', 'ConjureWP' ); ?></h1>
				<p><?php esc_html_e( 'Configure your WP Rocket performance settings below.', 'ConjureWP' ); ?></p>

				<?php $this->render_version_update_toggle(); ?>

				<?php if ( ! $this->can_run() ) : ?>
					<p class="conjure__notice conjure__notice--warning">
						<?php esc_html_e( 'WP Rocket is not currently active. Please activate it first, then return to this step.', 'ConjureWP' ); ?>
					</p>
				<?php elseif ( empty( $enabled_features ) ) : ?>
					<p><?php esc_html_e( 'No WP Rocket features are currently enabled for this connector.', 'ConjureWP' ); ?></p>
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
						<?php esc_html_e( 'Apply WP Rocket Setup', 'ConjureWP' ); ?>
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
			case 'cache_activation':
				$this->render_cache_activation_fields();
				break;

			case 'file_optimisation':
				$this->render_file_optimisation_fields();
				break;

			case 'lazy_loading':
				$this->render_lazy_loading_fields();
				break;

			case 'preload_configuration':
				$this->render_preload_fields();
				break;

			case 'cdn_integration':
				$this->render_cdn_fields();
				break;
		}
	}

	/**
	 * Render cache activation fields.
	 *
	 * @return void
	 */
	protected function render_cache_activation_fields() {
		$this->render_checkbox_field(
			'conjure_wprocket_enable_page_cache',
			__( 'Enable page caching', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_wprocket_enable_mobile_cache',
			__( 'Enable separate mobile cache', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_wprocket_cache_logged_in',
			__( 'Cache pages for logged-in users', 'ConjureWP' ),
			false
		);
	}

	/**
	 * Render file optimisation fields.
	 *
	 * @return void
	 */
	protected function render_file_optimisation_fields() {
		$this->render_checkbox_field(
			'conjure_wprocket_minify_css',
			__( 'Minify CSS files', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_wprocket_minify_js',
			__( 'Minify JavaScript files', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_wprocket_combine_css',
			__( 'Combine CSS files', 'ConjureWP' ),
			false
		);
		$this->render_checkbox_field(
			'conjure_wprocket_combine_js',
			__( 'Combine JavaScript files', 'ConjureWP' ),
			false
		);
		$this->render_checkbox_field(
			'conjure_wprocket_defer_js',
			__( 'Defer JavaScript loading', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_wprocket_delay_js',
			__( 'Delay JavaScript execution', 'ConjureWP' ),
			false
		);
	}

	/**
	 * Render lazy loading fields.
	 *
	 * @return void
	 */
	protected function render_lazy_loading_fields() {
		$this->render_checkbox_field(
			'conjure_wprocket_lazy_images',
			__( 'Lazy load images', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_wprocket_lazy_iframes',
			__( 'Lazy load iframes and videos', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_wprocket_lazy_css_bg',
			__( 'Lazy load CSS background images', 'ConjureWP' ),
			false
		);
	}

	/**
	 * Render preload configuration fields.
	 *
	 * @return void
	 */
	protected function render_preload_fields() {
		$this->render_checkbox_field(
			'conjure_wprocket_enable_preload',
			__( 'Enable cache preloading', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_wprocket_preload_links',
			__( 'Enable link preloading on hover', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_wprocket_preload_fonts',
			__( 'Preload web fonts', 'ConjureWP' ),
			false
		);
	}

	/**
	 * Render CDN integration fields.
	 *
	 * @return void
	 */
	protected function render_cdn_fields() {
		$this->render_checkbox_field(
			'conjure_wprocket_enable_cdn',
			__( 'Enable CDN', 'ConjureWP' ),
			false
		);
		$cdn_url = get_option( 'conjure_wprocket_cdn_url', '' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_wprocket_cdn_url" class="conjure__field-label">
				<?php esc_html_e( 'CDN URL', 'ConjureWP' ); ?>
			</label>
			<input
				type="text"
				id="conjure_wprocket_cdn_url"
				name="conjure_wprocket_cdn_url"
				class="conjure__input"
				value="<?php echo esc_attr( $cdn_url ); ?>"
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
	 * Handle the WP Rocket step.
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

		if ( in_array( 'cache_activation', $enabled_keys, true ) ) {
			update_option( 'conjure_wprocket_enable_page_cache', ! empty( $_POST['conjure_wprocket_enable_page_cache'] ) );
			update_option( 'conjure_wprocket_enable_mobile_cache', ! empty( $_POST['conjure_wprocket_enable_mobile_cache'] ) );
			update_option( 'conjure_wprocket_cache_logged_in', ! empty( $_POST['conjure_wprocket_cache_logged_in'] ) );
		}

		if ( in_array( 'file_optimisation', $enabled_keys, true ) ) {
			update_option( 'conjure_wprocket_minify_css', ! empty( $_POST['conjure_wprocket_minify_css'] ) );
			update_option( 'conjure_wprocket_minify_js', ! empty( $_POST['conjure_wprocket_minify_js'] ) );
			update_option( 'conjure_wprocket_combine_css', ! empty( $_POST['conjure_wprocket_combine_css'] ) );
			update_option( 'conjure_wprocket_combine_js', ! empty( $_POST['conjure_wprocket_combine_js'] ) );
			update_option( 'conjure_wprocket_defer_js', ! empty( $_POST['conjure_wprocket_defer_js'] ) );
			update_option( 'conjure_wprocket_delay_js', ! empty( $_POST['conjure_wprocket_delay_js'] ) );
		}

		if ( in_array( 'lazy_loading', $enabled_keys, true ) ) {
			update_option( 'conjure_wprocket_lazy_images', ! empty( $_POST['conjure_wprocket_lazy_images'] ) );
			update_option( 'conjure_wprocket_lazy_iframes', ! empty( $_POST['conjure_wprocket_lazy_iframes'] ) );
			update_option( 'conjure_wprocket_lazy_css_bg', ! empty( $_POST['conjure_wprocket_lazy_css_bg'] ) );
		}

		if ( in_array( 'preload_configuration', $enabled_keys, true ) ) {
			update_option( 'conjure_wprocket_enable_preload', ! empty( $_POST['conjure_wprocket_enable_preload'] ) );
			update_option( 'conjure_wprocket_preload_links', ! empty( $_POST['conjure_wprocket_preload_links'] ) );
			update_option( 'conjure_wprocket_preload_fonts', ! empty( $_POST['conjure_wprocket_preload_fonts'] ) );
		}

		if ( in_array( 'cdn_integration', $enabled_keys, true ) ) {
			update_option( 'conjure_wprocket_enable_cdn', ! empty( $_POST['conjure_wprocket_enable_cdn'] ) );
			$cdn_url = isset( $_POST['conjure_wprocket_cdn_url'] ) ? esc_url_raw( wp_unslash( $_POST['conjure_wprocket_cdn_url'] ) ) : '';
			update_option( 'conjure_wprocket_cdn_url', $cdn_url );
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
