<?php
/**
 * WooCommerce step connector.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'conjurewp_is_woocommerce_active' ) ) {
	/**
	 * Check whether WooCommerce is active.
	 *
	 * @return bool
	 */
	function conjurewp_is_woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}
}

/**
 * WooCommerce connector step.
 */
class Conjure_Step_Connector_WooCommerce extends Conjure_Step_Connector_Base {

	/**
	 * Feature groups for organising the step render.
	 *
	 * @var array
	 */
	protected static $feature_groups = array(
		'store_basics' => array(
			'label'    => 'Store basics',
			'features' => array( 'set_currency', 'set_store_location', 'set_measurement_units' ),
		),
		'store_pages' => array(
			'label'    => 'Store pages',
			'features' => array( 'assign_shop_page', 'assign_cart_page', 'assign_checkout_page', 'assign_myaccount_page' ),
		),
		'commerce' => array(
			'label'    => 'Commerce settings',
			'features' => array( 'configure_tax', 'enable_cod_payment', 'configure_checkout_accounts', 'set_catalog_defaults' ),
		),
		'housekeeping' => array(
			'label'    => 'Housekeeping',
			'features' => array( 'flush_rewrite_rules' ),
		),
	);

	/**
	 * Get available connector features.
	 *
	 * @return array
	 */
	public function get_features() {
		return array(
			'set_currency'                => array(
				'label'           => __( 'Store currency', 'ConjureWP' ),
				'description'     => __( 'Choose the store currency during setup.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'set_store_location'          => array(
				'label'           => __( 'Store location', 'ConjureWP' ),
				'description'     => __( 'Set the default store country and selling locations.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'set_measurement_units'       => array(
				'label'           => __( 'Measurement units', 'ConjureWP' ),
				'description'     => __( 'Configure weight and dimension units for products.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'assign_shop_page'            => array(
				'label'           => __( 'Assign shop page', 'ConjureWP' ),
				'description'     => __( 'Assign or create the main WooCommerce shop page.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'assign_cart_page'            => array(
				'label'           => __( 'Assign cart page', 'ConjureWP' ),
				'description'     => __( 'Assign or create the WooCommerce cart page.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'assign_checkout_page'        => array(
				'label'           => __( 'Assign checkout page', 'ConjureWP' ),
				'description'     => __( 'Assign or create the WooCommerce checkout page.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'assign_myaccount_page'       => array(
				'label'           => __( 'Assign my account page', 'ConjureWP' ),
				'description'     => __( 'Assign or create the WooCommerce my account page.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'configure_tax'               => array(
				'label'           => __( 'Tax defaults', 'ConjureWP' ),
				'description'     => __( 'Enable tax calculation and set sensible tax defaults.', 'ConjureWP' ),
				'default_enabled' => false,
			),
			'enable_cod_payment'          => array(
				'label'           => __( 'Enable Cash on Delivery', 'ConjureWP' ),
				'description'     => __( 'Activate the Cash on Delivery gateway so the store has at least one payment method.', 'ConjureWP' ),
				'default_enabled' => false,
			),
			'configure_checkout_accounts' => array(
				'label'           => __( 'Checkout and account defaults', 'ConjureWP' ),
				'description'     => __( 'Enable guest checkout and allow account creation during checkout.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'set_catalog_defaults'        => array(
				'label'           => __( 'Catalogue defaults', 'ConjureWP' ),
				'description'     => __( 'Set products per page, display columns, and default sorting for the shop catalogue.', 'ConjureWP' ),
				'default_enabled' => true,
			),
			'flush_rewrite_rules'         => array(
				'label'           => __( 'Flush rewrite rules', 'ConjureWP' ),
				'description'     => __( 'Refresh permalinks after WooCommerce pages are configured.', 'ConjureWP' ),
				'default_enabled' => true,
			),
		);
	}

	/**
	 * Render the WooCommerce step.
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

				<h1><?php esc_html_e( 'WooCommerce Setup', 'ConjureWP' ); ?></h1>
				<p><?php esc_html_e( 'Configure your WooCommerce store settings below.', 'ConjureWP' ); ?></p>

				<?php $this->render_version_update_toggle(); ?>

				<?php if ( ! $this->can_run() ) : ?>
					<p class="conjure__notice conjure__notice--warning">
						<?php esc_html_e( 'WooCommerce is not currently active. Please activate it first, then return to this step.', 'ConjureWP' ); ?>
					</p>
				<?php elseif ( empty( $enabled_features ) ) : ?>
					<p><?php esc_html_e( 'No WooCommerce features are currently enabled for this connector.', 'ConjureWP' ); ?></p>
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
						<?php esc_html_e( 'Apply WooCommerce Setup', 'ConjureWP' ); ?>
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
			case 'set_currency':
				$this->render_currency_field();
				break;

			case 'set_store_location':
				$this->render_store_location_field();
				break;

			case 'set_measurement_units':
				$this->render_measurement_fields();
				break;

			case 'configure_checkout_accounts':
				$this->render_checkout_account_fields();
				break;

			case 'set_catalog_defaults':
				$this->render_catalog_fields();
				break;

			default:
				$this->render_feature_info( $feature );
				break;
		}
	}

	/**
	 * Render the currency dropdown.
	 *
	 * @return void
	 */
	protected function render_currency_field() {
		$currencies = $this->get_currency_list();

		if ( empty( $currencies ) ) {
			return;
		}

		$current = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'GBP';
		?>
		<div class="conjure__field-group">
			<label for="conjure_woo_currency" class="conjure__field-label">
				<?php esc_html_e( 'Store currency', 'ConjureWP' ); ?>
			</label>
			<select id="conjure_woo_currency" name="conjure_woo_currency" class="conjure__select">
				<?php foreach ( $currencies as $code => $label ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $current, $code ); ?>>
						<?php echo esc_html( $code . ' — ' . $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<input type="hidden" name="conjure_woo_currency_field" value="1" />
		</div>
		<?php
	}

	/**
	 * Render the store country dropdown.
	 *
	 * @return void
	 */
	protected function render_store_location_field() {
		$countries = $this->get_country_list();
		$current   = get_option( 'woocommerce_default_country', 'GB' );

		$base_country = $current;
		if ( strpos( $current, ':' ) !== false ) {
			list( $base_country ) = explode( ':', $current );
		}
		?>
		<div class="conjure__field-group">
			<label for="conjure_woo_country" class="conjure__field-label">
				<?php esc_html_e( 'Store country', 'ConjureWP' ); ?>
			</label>
			<select id="conjure_woo_country" name="conjure_woo_country" class="conjure__select">
				<?php foreach ( $countries as $code => $name ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $base_country, $code ); ?>>
						<?php echo esc_html( $name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	/**
	 * Render weight and dimension unit dropdowns.
	 *
	 * @return void
	 */
	protected function render_measurement_fields() {
		$weight_units      = $this->get_weight_units();
		$dimension_units   = $this->get_dimension_units();
		$current_weight    = get_option( 'woocommerce_weight_unit', 'kg' );
		$current_dimension = get_option( 'woocommerce_dimension_unit', 'cm' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_woo_weight_unit" class="conjure__field-label">
				<?php esc_html_e( 'Weight unit', 'ConjureWP' ); ?>
			</label>
			<select id="conjure_woo_weight_unit" name="conjure_woo_weight_unit" class="conjure__select">
				<?php foreach ( $weight_units as $val => $label ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $current_weight, $val ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="conjure__field-group">
			<label for="conjure_woo_dimension_unit" class="conjure__field-label">
				<?php esc_html_e( 'Dimension unit', 'ConjureWP' ); ?>
			</label>
			<select id="conjure_woo_dimension_unit" name="conjure_woo_dimension_unit" class="conjure__select">
				<?php foreach ( $dimension_units as $val => $label ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $current_dimension, $val ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	/**
	 * Render checkout and account setting checkboxes.
	 *
	 * @return void
	 */
	protected function render_checkout_account_fields() {
		$fields = array(
			'woocommerce_enable_guest_checkout'                  => __( 'Enable guest checkout', 'ConjureWP' ),
			'woocommerce_enable_checkout_login_reminder'         => __( 'Show login reminder during checkout', 'ConjureWP' ),
			'woocommerce_enable_signup_and_login_from_checkout'  => __( 'Allow account creation during checkout', 'ConjureWP' ),
			'woocommerce_enable_myaccount_registration'          => __( 'Allow registration on My Account page', 'ConjureWP' ),
		);

		foreach ( $fields as $option_name => $label ) :
			$current    = get_option( $option_name, 'no' );
			$field_name = 'conjure_' . str_replace( 'woocommerce_', 'woo_', $option_name );
			?>
			<div class="conjure__field-group conjure__field-group--checkbox">
				<label for="<?php echo esc_attr( $field_name ); ?>">
					<input type="hidden" name="<?php echo esc_attr( $field_name ); ?>" value="no" />
					<input
						type="checkbox"
						id="<?php echo esc_attr( $field_name ); ?>"
						name="<?php echo esc_attr( $field_name ); ?>"
						value="yes"
						<?php checked( $current, 'yes' ); ?>
					/>
					<?php echo esc_html( $label ); ?>
				</label>
			</div>
			<?php
		endforeach;
	}

	/**
	 * Render catalogue default fields.
	 *
	 * @return void
	 */
	protected function render_catalog_fields() {
		$columns  = absint( get_option( 'woocommerce_catalog_columns', 4 ) );
		$rows     = absint( get_option( 'woocommerce_catalog_rows', 3 ) );
		$per_page = $columns * $rows;

		if ( $per_page < 1 ) {
			$per_page = 12;
		}

		$orderby_options = $this->get_catalog_orderby_options();
		$current_orderby = get_option( 'woocommerce_default_catalog_orderby', 'menu_order' );
		?>
		<div class="conjure__field-group">
			<label for="conjure_woo_products_per_page" class="conjure__field-label">
				<?php esc_html_e( 'Products per page', 'ConjureWP' ); ?>
			</label>
			<input
				type="number"
				id="conjure_woo_products_per_page"
				name="conjure_woo_products_per_page"
				class="conjure__input"
				value="<?php echo esc_attr( $per_page ); ?>"
				min="1"
				max="100"
			/>
		</div>
		<div class="conjure__field-group">
			<label for="conjure_woo_catalog_columns" class="conjure__field-label">
				<?php esc_html_e( 'Catalogue columns', 'ConjureWP' ); ?>
			</label>
			<input
				type="number"
				id="conjure_woo_catalog_columns"
				name="conjure_woo_catalog_columns"
				class="conjure__input"
				value="<?php echo esc_attr( $columns ); ?>"
				min="1"
				max="6"
			/>
		</div>
		<div class="conjure__field-group">
			<label for="conjure_woo_catalog_orderby" class="conjure__field-label">
				<?php esc_html_e( 'Default product sorting', 'ConjureWP' ); ?>
			</label>
			<select id="conjure_woo_catalog_orderby" name="conjure_woo_catalog_orderby" class="conjure__select">
				<?php foreach ( $orderby_options as $val => $label ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $current_orderby, $val ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
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
	 * Handle the WooCommerce step.
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

		// Currency.
		if ( in_array( 'set_currency', $enabled_keys, true ) && ! empty( $_POST['conjure_woo_currency_field'] ) && ! empty( $_POST['conjure_woo_currency'] ) ) {
			$currency = sanitize_text_field( wp_unslash( $_POST['conjure_woo_currency'] ) );
			$valid    = array_keys( $this->get_currency_list() );

			if ( in_array( $currency, $valid, true ) ) {
				update_option( 'woocommerce_currency', $currency );
			}
		}

		// Store location from form.
		if ( in_array( 'set_store_location', $enabled_keys, true ) ) {
			$country = isset( $_POST['conjure_woo_country'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_woo_country'] ) ) : '';
			$valid   = array_keys( $this->get_country_list() );

			if ( ! empty( $country ) && in_array( $country, $valid, true ) ) {
				update_option( 'woocommerce_default_country', $country );
			}

			update_option( 'woocommerce_allowed_countries', 'all' );
		}

		// Measurement units from form.
		if ( in_array( 'set_measurement_units', $enabled_keys, true ) ) {
			$weight    = isset( $_POST['conjure_woo_weight_unit'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_woo_weight_unit'] ) ) : '';
			$dimension = isset( $_POST['conjure_woo_dimension_unit'] ) ? sanitize_text_field( wp_unslash( $_POST['conjure_woo_dimension_unit'] ) ) : '';

			if ( ! empty( $weight ) && array_key_exists( $weight, $this->get_weight_units() ) ) {
				update_option( 'woocommerce_weight_unit', $weight );
			}

			if ( ! empty( $dimension ) && array_key_exists( $dimension, $this->get_dimension_units() ) ) {
				update_option( 'woocommerce_dimension_unit', $dimension );
			}
		}

		// Checkout and account settings from form.
		if ( in_array( 'configure_checkout_accounts', $enabled_keys, true ) ) {
			$checkout_map = array(
				'conjure_woo_enable_guest_checkout'                 => 'woocommerce_enable_guest_checkout',
				'conjure_woo_enable_checkout_login_reminder'        => 'woocommerce_enable_checkout_login_reminder',
				'conjure_woo_enable_signup_and_login_from_checkout' => 'woocommerce_enable_signup_and_login_from_checkout',
				'conjure_woo_enable_myaccount_registration'         => 'woocommerce_enable_myaccount_registration',
			);

			foreach ( $checkout_map as $form_name => $option_name ) {
				$value = isset( $_POST[ $form_name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $form_name ] ) ) : 'no';
				update_option( $option_name, in_array( $value, array( 'yes', 'no' ), true ) ? $value : 'no' );
			}
		}

		// Catalogue defaults from form.
		if ( in_array( 'set_catalog_defaults', $enabled_keys, true ) ) {
			$per_page = isset( $_POST['conjure_woo_products_per_page'] ) ? absint( $_POST['conjure_woo_products_per_page'] ) : 12;
			$columns  = isset( $_POST['conjure_woo_catalog_columns'] ) ? absint( $_POST['conjure_woo_catalog_columns'] ) : 4;
			$orderby  = isset( $_POST['conjure_woo_catalog_orderby'] ) ? sanitize_key( wp_unslash( $_POST['conjure_woo_catalog_orderby'] ) ) : 'menu_order';

			if ( $per_page < 1 ) {
				$per_page = 12;
			}
			if ( $columns < 1 ) {
				$columns = 4;
			}
			if ( ! array_key_exists( $orderby, $this->get_catalog_orderby_options() ) ) {
				$orderby = 'menu_order';
			}

			update_option( 'woocommerce_catalog_columns', $columns );
			update_option( 'woocommerce_catalog_rows', absint( ceil( $per_page / max( 1, $columns ) ) ) );
			update_option( 'woocommerce_default_catalog_orderby', $orderby );
		}

		// Action-based features: page assignment, tax, COD, flush.
		$action_features = array(
			'assign_shop_page',
			'assign_cart_page',
			'assign_checkout_page',
			'assign_myaccount_page',
			'configure_tax',
			'enable_cod_payment',
			'flush_rewrite_rules',
		);

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
	 * Features with form fields (currency, location, units, checkout, catalogue)
	 * are saved directly in handle_step(). This method handles features that
	 * perform actions rather than saving form values.
	 *
	 * @param string $feature_id Feature identifier.
	 * @return void
	 */
	protected function run_feature( $feature_id ) {
		switch ( $feature_id ) {
			case 'assign_shop_page':
				$this->assign_woocommerce_page( 'shop', 'woocommerce_shop_page_id', __( 'Shop', 'ConjureWP' ), '' );
				break;

			case 'assign_cart_page':
				$this->assign_woocommerce_page( 'cart', 'woocommerce_cart_page_id', __( 'Cart', 'ConjureWP' ), '[woocommerce_cart]' );
				break;

			case 'assign_checkout_page':
				$this->assign_woocommerce_page( 'checkout', 'woocommerce_checkout_page_id', __( 'Checkout', 'ConjureWP' ), '[woocommerce_checkout]' );
				break;

			case 'assign_myaccount_page':
				$this->assign_woocommerce_page( 'my-account', 'woocommerce_myaccount_page_id', __( 'My account', 'ConjureWP' ), '[woocommerce_my_account]' );
				break;

			case 'configure_tax':
				$this->apply_tax_defaults();
				break;

			case 'enable_cod_payment':
				$this->enable_cod_gateway();
				break;

			case 'flush_rewrite_rules':
				flush_rewrite_rules();
				break;
		}
	}

	/**
	 * Assign or create a WooCommerce page.
	 *
	 * @param string $slug         Page slug.
	 * @param string $option_name  WooCommerce option name.
	 * @param string $page_title   Page title.
	 * @param string $page_content Page content.
	 * @return void
	 */
	protected function assign_woocommerce_page( $slug, $option_name, $page_title, $page_content ) {
		$page_id = 0;

		if ( function_exists( 'wc_get_page_id' ) ) {
			$page_id = absint( wc_get_page_id( str_replace( 'woocommerce_', '', str_replace( '_page_id', '', $option_name ) ) ) );
		}

		if ( $page_id < 1 ) {
			$page_id = $this->find_page_id( $slug, $page_title );
		}

		if ( $page_id < 1 && function_exists( 'wc_create_page' ) ) {
			$page_id = absint( wc_create_page( $slug, $option_name, $page_title, $page_content ) );
		}

		if ( $page_id < 1 ) {
			$page_id = wp_insert_post(
				array(
					'post_title'   => $page_title,
					'post_name'    => $slug,
					'post_content' => $page_content,
					'post_status'  => 'publish',
					'post_type'    => 'page',
				)
			);
		}

		if ( $page_id > 0 ) {
			update_option( $option_name, $page_id );
		}
	}

	/**
	 * Find a page by slug or title.
	 *
	 * @param string $slug       Page slug.
	 * @param string $page_title Page title.
	 * @return int
	 */
	protected function find_page_id( $slug, $page_title ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );

		if ( $page && ! empty( $page->ID ) ) {
			return absint( $page->ID );
		}

		if ( function_exists( 'get_page_by_title' ) ) {
			$page = get_page_by_title( $page_title, OBJECT, 'page' );

			if ( $page && ! empty( $page->ID ) ) {
				return absint( $page->ID );
			}
		}

		return 0;
	}

	/**
	 * Enable tax calculation and set sensible defaults.
	 *
	 * @return void
	 */
	protected function apply_tax_defaults() {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_tax_based_on', 'shipping' );
		update_option( 'woocommerce_tax_display_shop', 'incl' );
		update_option( 'woocommerce_tax_display_cart', 'incl' );
	}

	/**
	 * Enable the Cash on Delivery payment gateway.
	 *
	 * @return void
	 */
	protected function enable_cod_gateway() {
		$cod_settings = get_option( 'woocommerce_cod_settings', array() );

		if ( ! is_array( $cod_settings ) ) {
			$cod_settings = array();
		}

		$cod_settings = wp_parse_args(
			$cod_settings,
			array(
				'enabled'      => 'yes',
				'title'        => __( 'Cash on Delivery', 'ConjureWP' ),
				'description'  => __( 'Pay with cash upon delivery.', 'ConjureWP' ),
				'instructions' => __( 'Pay with cash upon delivery.', 'ConjureWP' ),
			)
		);

		$cod_settings['enabled'] = 'yes';

		update_option( 'woocommerce_cod_settings', $cod_settings );
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

	/**
	 * Get a list of countries.
	 *
	 * Uses WooCommerce data when available, otherwise falls back to a common subset.
	 *
	 * @return array<string,string>
	 */
	protected function get_country_list() {
		if ( function_exists( 'WC' ) && WC()->countries ) {
			return WC()->countries->get_countries();
		}

		return array(
			'AU' => __( 'Australia', 'ConjureWP' ),
			'AT' => __( 'Austria', 'ConjureWP' ),
			'BE' => __( 'Belgium', 'ConjureWP' ),
			'BR' => __( 'Brazil', 'ConjureWP' ),
			'CA' => __( 'Canada', 'ConjureWP' ),
			'CN' => __( 'China', 'ConjureWP' ),
			'CZ' => __( 'Czech Republic', 'ConjureWP' ),
			'DK' => __( 'Denmark', 'ConjureWP' ),
			'FI' => __( 'Finland', 'ConjureWP' ),
			'FR' => __( 'France', 'ConjureWP' ),
			'DE' => __( 'Germany', 'ConjureWP' ),
			'HK' => __( 'Hong Kong', 'ConjureWP' ),
			'HU' => __( 'Hungary', 'ConjureWP' ),
			'IN' => __( 'India', 'ConjureWP' ),
			'ID' => __( 'Indonesia', 'ConjureWP' ),
			'IE' => __( 'Ireland', 'ConjureWP' ),
			'IL' => __( 'Israel', 'ConjureWP' ),
			'IT' => __( 'Italy', 'ConjureWP' ),
			'JP' => __( 'Japan', 'ConjureWP' ),
			'MY' => __( 'Malaysia', 'ConjureWP' ),
			'MX' => __( 'Mexico', 'ConjureWP' ),
			'NL' => __( 'Netherlands', 'ConjureWP' ),
			'NZ' => __( 'New Zealand', 'ConjureWP' ),
			'NO' => __( 'Norway', 'ConjureWP' ),
			'PH' => __( 'Philippines', 'ConjureWP' ),
			'PL' => __( 'Poland', 'ConjureWP' ),
			'PT' => __( 'Portugal', 'ConjureWP' ),
			'RO' => __( 'Romania', 'ConjureWP' ),
			'SA' => __( 'Saudi Arabia', 'ConjureWP' ),
			'SG' => __( 'Singapore', 'ConjureWP' ),
			'ZA' => __( 'South Africa', 'ConjureWP' ),
			'KR' => __( 'South Korea', 'ConjureWP' ),
			'ES' => __( 'Spain', 'ConjureWP' ),
			'SE' => __( 'Sweden', 'ConjureWP' ),
			'CH' => __( 'Switzerland', 'ConjureWP' ),
			'TW' => __( 'Taiwan', 'ConjureWP' ),
			'TH' => __( 'Thailand', 'ConjureWP' ),
			'TR' => __( 'Turkey', 'ConjureWP' ),
			'AE' => __( 'United Arab Emirates', 'ConjureWP' ),
			'GB' => __( 'United Kingdom', 'ConjureWP' ),
			'US' => __( 'United States', 'ConjureWP' ),
		);
	}

	/**
	 * Get available weight units.
	 *
	 * @return array<string,string>
	 */
	protected function get_weight_units() {
		return array(
			'kg'  => __( 'kg', 'ConjureWP' ),
			'g'   => __( 'g', 'ConjureWP' ),
			'lbs' => __( 'lbs', 'ConjureWP' ),
			'oz'  => __( 'oz', 'ConjureWP' ),
		);
	}

	/**
	 * Get available dimension units.
	 *
	 * @return array<string,string>
	 */
	protected function get_dimension_units() {
		return array(
			'm'  => __( 'm', 'ConjureWP' ),
			'cm' => __( 'cm', 'ConjureWP' ),
			'mm' => __( 'mm', 'ConjureWP' ),
			'in' => __( 'in', 'ConjureWP' ),
			'yd' => __( 'yd', 'ConjureWP' ),
		);
	}

	/**
	 * Get catalogue order-by options.
	 *
	 * @return array<string,string>
	 */
	protected function get_catalog_orderby_options() {
		return array(
			'menu_order' => __( 'Default sorting (custom ordering + name)', 'ConjureWP' ),
			'popularity' => __( 'Popularity (sales)', 'ConjureWP' ),
			'rating'     => __( 'Average rating', 'ConjureWP' ),
			'date'       => __( 'Sort by most recent', 'ConjureWP' ),
			'price'      => __( 'Sort by price (asc)', 'ConjureWP' ),
			'price-desc' => __( 'Sort by price (desc)', 'ConjureWP' ),
		);
	}

	/**
	 * Get the list of supported currencies.
	 *
	 * Falls back to a core subset when WooCommerce is not active.
	 *
	 * @return array<string,string>
	 */
	protected function get_currency_list() {
		if ( function_exists( 'get_woocommerce_currencies' ) ) {
			return get_woocommerce_currencies();
		}

		return array(
			'GBP' => __( 'Pound sterling', 'ConjureWP' ),
			'USD' => __( 'United States (US) dollar', 'ConjureWP' ),
			'EUR' => __( 'Euro', 'ConjureWP' ),
			'CAD' => __( 'Canadian dollar', 'ConjureWP' ),
			'AUD' => __( 'Australian dollar', 'ConjureWP' ),
			'NZD' => __( 'New Zealand dollar', 'ConjureWP' ),
			'JPY' => __( 'Japanese yen', 'ConjureWP' ),
			'CHF' => __( 'Swiss franc', 'ConjureWP' ),
			'SEK' => __( 'Swedish krona', 'ConjureWP' ),
			'NOK' => __( 'Norwegian krone', 'ConjureWP' ),
			'DKK' => __( 'Danish krone', 'ConjureWP' ),
			'PLN' => __( 'Polish zloty', 'ConjureWP' ),
			'CZK' => __( 'Czech koruna', 'ConjureWP' ),
			'HUF' => __( 'Hungarian forint', 'ConjureWP' ),
			'RON' => __( 'Romanian leu', 'ConjureWP' ),
			'BGN' => __( 'Bulgarian lev', 'ConjureWP' ),
			'HRK' => __( 'Croatian kuna', 'ConjureWP' ),
			'INR' => __( 'Indian rupee', 'ConjureWP' ),
			'CNY' => __( 'Chinese yuan', 'ConjureWP' ),
			'SGD' => __( 'Singapore dollar', 'ConjureWP' ),
			'HKD' => __( 'Hong Kong dollar', 'ConjureWP' ),
			'MYR' => __( 'Malaysian ringgit', 'ConjureWP' ),
			'BRL' => __( 'Brazilian real', 'ConjureWP' ),
			'MXN' => __( 'Mexican peso', 'ConjureWP' ),
			'ZAR' => __( 'South African rand', 'ConjureWP' ),
			'AED' => __( 'United Arab Emirates dirham', 'ConjureWP' ),
			'SAR' => __( 'Saudi riyal', 'ConjureWP' ),
			'ILS' => __( 'Israeli new shekel', 'ConjureWP' ),
			'TRY' => __( 'Turkish lira', 'ConjureWP' ),
			'THB' => __( 'Thai baht', 'ConjureWP' ),
			'KRW' => __( 'South Korean won', 'ConjureWP' ),
			'TWD' => __( 'New Taiwan dollar', 'ConjureWP' ),
			'PHP' => __( 'Philippine peso', 'ConjureWP' ),
			'IDR' => __( 'Indonesian rupiah', 'ConjureWP' ),
		);
	}
}
