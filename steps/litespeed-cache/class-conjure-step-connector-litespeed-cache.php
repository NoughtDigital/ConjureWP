<?php
/**
 * LiteSpeed Cache step connector.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'conjurewp_is_litespeed_cache_active' ) ) {
	/**
	 * Check whether LiteSpeed Cache is active.
	 *
	 * @return bool
	 */
	function conjurewp_is_litespeed_cache_active() {
		return defined( 'LSCWP_V' );
	}
}

/**
 * LiteSpeed Cache connector step.
 */
class Conjure_Step_Connector_LiteSpeed_Cache extends Conjure_Step_Connector_Base {

	/**
	 * Feature groups for organising the step render.
	 *
	 * @var array
	 */
	protected static $feature_groups = array(
		'caching' => array(
			'label'    => 'Caching',
			'features' => array( 'page_caching' ),
		),
		'cloud' => array(
			'label'    => 'Cloud services',
			'features' => array( 'quic_cloud', 'image_optimisation' ),
		),
		'advanced' => array(
			'label'    => 'Advanced',
			'features' => array( 'object_cache', 'cdn_setup' ),
		),
	);

	/**
	 * Get available connector features.
	 *
	 * @return array
	 */
	public function get_features() {
		return array(
			'page_caching'        => array(
				'label'           => __( 'Enable page caching', 'ConjureWP' ),
				'description'     => __( 'Activate LiteSpeed page caching for improved server response times.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'quic_cloud'          => array(
				'label'           => __( 'QUIC.cloud integration', 'ConjureWP' ),
				'description'     => __( 'Connect to QUIC.cloud for CDN and optimisation services.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'image_optimisation'  => array(
				'label'           => __( 'Image optimisation', 'ConjureWP' ),
				'description'     => __( 'Configure automatic image compression and WebP conversion.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'object_cache'        => array(
				'label'           => __( 'Object cache configuration', 'ConjureWP' ),
				'description'     => __( 'Enable object caching to reduce database queries.', 'ConjureWP' ),
				'default_enabled' => false,
			),
			'cdn_setup'           => array(
				'label'           => __( 'CDN setup', 'ConjureWP' ),
				'description'     => __( 'Configure CDN settings for static asset delivery.', 'ConjureWP' ),
				'default_enabled' => false,
			),
		);
	}

	/**
	 * Render the LiteSpeed Cache step.
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

				<h1><?php esc_html_e( 'LiteSpeed Cache Setup', 'ConjureWP' ); ?></h1>
				<p><?php esc_html_e( 'Configure your LiteSpeed Cache settings below.', 'ConjureWP' ); ?></p>

				<?php $this->render_version_update_toggle(); ?>

				<?php if ( ! $this->can_run() ) : ?>
					<p class="conjure__notice conjure__notice--warning">
						<?php esc_html_e( 'LiteSpeed Cache is not currently active. Please activate it first, then return to this step.', 'ConjureWP' ); ?>
					</p>
				<?php elseif ( empty( $enabled_features ) ) : ?>
					<p><?php esc_html_e( 'No LiteSpeed Cache features are currently enabled for this connector.', 'ConjureWP' ); ?></p>
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
						<?php esc_html_e( 'Apply LiteSpeed Cache Setup', 'ConjureWP' ); ?>
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
			case 'page_caching':
				$this->render_page_caching_fields();
				break;

			case 'quic_cloud':
				$this->render_quic_cloud_fields();
				break;

			case 'image_optimisation':
				$this->render_image_optimisation_fields();
				break;

			case 'object_cache':
				$this->render_object_cache_fields();
				break;

			case 'cdn_setup':
				$this->render_cdn_setup_fields();
				break;
		}
	}

	/**
	 * Render page caching fields.
	 *
	 * @return void
	 */
	protected function render_page_caching_fields() {
		$this->render_checkbox_field(
			'conjure_lscache_enable_cache',
			__( 'Enable page caching', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_lscache_cache_mobile',
			__( 'Enable separate mobile cache', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_lscache_cache_logged_in',
			__( 'Cache pages for logged-in users', 'ConjureWP' ),
			false
		);
		$this->render_checkbox_field(
			'conjure_lscache_cache_favicon',
			__( 'Cache favicon requests', 'ConjureWP' ),
			true
		);
	}

	/**
	 * Render QUIC.cloud integration fields.
	 *
	 * @return void
	 */
	protected function render_quic_cloud_fields() {
		$this->render_checkbox_field(
			'conjure_lscache_enable_quic_cloud',
			__( 'Enable QUIC.cloud integration', 'ConjureWP' ),
			true
		);
		$quic_cloud_email = get_option( 'conjure_lscache_quic_cloud_email', '' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_lscache_quic_cloud_email" class="conjure__field-label">
				<?php esc_html_e( 'QUIC.cloud account email', 'ConjureWP' ); ?>
			</label>
			<input
				type="text"
				id="conjure_lscache_quic_cloud_email"
				name="conjure_lscache_quic_cloud_email"
				class="conjure__input"
				value="<?php echo esc_attr( $quic_cloud_email ); ?>"
			/>
		</div>
		<?php
		$this->render_checkbox_field(
			'conjure_lscache_quic_cloud_cdn',
			__( 'Enable QUIC.cloud CDN', 'ConjureWP' ),
			true
		);
	}

	/**
	 * Render image optimisation fields.
	 *
	 * @return void
	 */
	protected function render_image_optimisation_fields() {
		$this->render_checkbox_field(
			'conjure_lscache_optimise_images',
			__( 'Enable image optimisation', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_lscache_webp_conversion',
			__( 'Enable WebP conversion', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_lscache_lazy_load_images',
			__( 'Enable lazy loading for images', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_lscache_responsive_placeholders',
			__( 'Generate responsive placeholders', 'ConjureWP' ),
			false
		);
	}

	/**
	 * Render object cache fields.
	 *
	 * @return void
	 */
	protected function render_object_cache_fields() {
		$this->render_checkbox_field(
			'conjure_lscache_enable_object_cache',
			__( 'Enable object cache', 'ConjureWP' ),
			false
		);
		$ttl = get_option( 'conjure_lscache_object_cache_ttl', '3600' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_lscache_object_cache_ttl" class="conjure__field-label">
				<?php esc_html_e( 'Object cache TTL (seconds)', 'ConjureWP' ); ?>
			</label>
			<input
				type="number"
				id="conjure_lscache_object_cache_ttl"
				name="conjure_lscache_object_cache_ttl"
				class="conjure__input"
				value="<?php echo esc_attr( $ttl ); ?>"
				min="60"
				max="86400"
			/>
		</div>
		<?php
	}

	/**
	 * Render CDN setup fields.
	 *
	 * @return void
	 */
	protected function render_cdn_setup_fields() {
		$this->render_checkbox_field(
			'conjure_lscache_enable_cdn',
			__( 'Enable CDN', 'ConjureWP' ),
			false
		);
		$cdn_url = get_option( 'conjure_lscache_cdn_url', '' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_lscache_cdn_url" class="conjure__field-label">
				<?php esc_html_e( 'CDN URL', 'ConjureWP' ); ?>
			</label>
			<input
				type="text"
				id="conjure_lscache_cdn_url"
				name="conjure_lscache_cdn_url"
				class="conjure__input"
				value="<?php echo esc_attr( $cdn_url ); ?>"
			/>
		</div>
		<?php
		$this->render_checkbox_field(
			'conjure_lscache_cdn_include_images',
			__( 'Include images in CDN', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_lscache_cdn_include_css',
			__( 'Include CSS files in CDN', 'ConjureWP' ),
			true
		);
		$this->render_checkbox_field(
			'conjure_lscache_cdn_include_js',
			__( 'Include JavaScript files in CDN', 'ConjureWP' ),
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
	 * Handle the LiteSpeed Cache step.
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

		if ( in_array( 'page_caching', $enabled_keys, true ) ) {
			update_option( 'conjure_lscache_enable_cache', ! empty( $_POST['conjure_lscache_enable_cache'] ) );
			update_option( 'conjure_lscache_cache_mobile', ! empty( $_POST['conjure_lscache_cache_mobile'] ) );
			update_option( 'conjure_lscache_cache_logged_in', ! empty( $_POST['conjure_lscache_cache_logged_in'] ) );
			update_option( 'conjure_lscache_cache_favicon', ! empty( $_POST['conjure_lscache_cache_favicon'] ) );
		}

		if ( in_array( 'quic_cloud', $enabled_keys, true ) ) {
			update_option( 'conjure_lscache_enable_quic_cloud', ! empty( $_POST['conjure_lscache_enable_quic_cloud'] ) );
			$quic_email = isset( $_POST['conjure_lscache_quic_cloud_email'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_lscache_quic_cloud_email'] ) ) : '';
			update_option( 'conjure_lscache_quic_cloud_email', $quic_email );
			update_option( 'conjure_lscache_quic_cloud_cdn', ! empty( $_POST['conjure_lscache_quic_cloud_cdn'] ) );
		}

		if ( in_array( 'image_optimisation', $enabled_keys, true ) ) {
			update_option( 'conjure_lscache_optimise_images', ! empty( $_POST['conjure_lscache_optimise_images'] ) );
			update_option( 'conjure_lscache_webp_conversion', ! empty( $_POST['conjure_lscache_webp_conversion'] ) );
			update_option( 'conjure_lscache_lazy_load_images', ! empty( $_POST['conjure_lscache_lazy_load_images'] ) );
			update_option( 'conjure_lscache_responsive_placeholders', ! empty( $_POST['conjure_lscache_responsive_placeholders'] ) );
		}

		if ( in_array( 'object_cache', $enabled_keys, true ) ) {
			update_option( 'conjure_lscache_enable_object_cache', ! empty( $_POST['conjure_lscache_enable_object_cache'] ) );
			$ttl = isset( $_POST['conjure_lscache_object_cache_ttl'] ) ? absint( $_POST['conjure_lscache_object_cache_ttl'] ) : 3600;
			$ttl = max( 60, min( 86400, $ttl ) );
			update_option( 'conjure_lscache_object_cache_ttl', $ttl );
		}

		if ( in_array( 'cdn_setup', $enabled_keys, true ) ) {
			update_option( 'conjure_lscache_enable_cdn', ! empty( $_POST['conjure_lscache_enable_cdn'] ) );
			$cdn_url = isset( $_POST['conjure_lscache_cdn_url'] ) ? esc_url_raw( wp_unslash( $_POST['conjure_lscache_cdn_url'] ) ) : '';
			update_option( 'conjure_lscache_cdn_url', $cdn_url );
			update_option( 'conjure_lscache_cdn_include_images', ! empty( $_POST['conjure_lscache_cdn_include_images'] ) );
			update_option( 'conjure_lscache_cdn_include_css', ! empty( $_POST['conjure_lscache_cdn_include_css'] ) );
			update_option( 'conjure_lscache_cdn_include_js', ! empty( $_POST['conjure_lscache_cdn_include_js'] ) );
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
