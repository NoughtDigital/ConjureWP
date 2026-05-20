<?php
/**
 * Connector catalogue: disk reconciliation and integration tiers.
 *
 * Step connectors are included with ConjureWP Pro (plugin licence), not sold separately.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central metadata for step connectors shipped with ConjureWP.
 */
class Conjure_Connector_Catalog {

	/**
	 * ConjureWP Pro plugin licence price label (annual per site).
	 */
	const PRO_PLUGIN_PRICE_LABEL = '£29/year';

	/**
	 * Integration tier: writes to the target plugin's native options/API.
	 */
	const TIER_FULL = 'full';

	/**
	 * Integration tier: mixed native + Conjure preference options.
	 */
	const TIER_PARTIAL = 'partial';

	/**
	 * Integration tier: wizard UI only until native sync ships.
	 */
	const TIER_PREFERENCES = 'preferences';

	/**
	 * Connectors referenced in marketing/UI but not shipped in this repo.
	 *
	 * @var array<string,array>
	 */
	const MARKETING_ONLY = array(
		'pdf'            => array(
			'name' => 'PDF Connector',
		),
		'code-snippets'  => array(
			'name' => 'Code Snippets Connector',
		),
		'custom-posts'   => array(
			'name' => 'Custom Posts Connector',
		),
		'memberpress'    => array(
			'name' => 'MemberPress Connector',
		),
		'shortpixel'     => array(
			'name' => 'ShortPixel Connector',
		),
	);

	/**
	 * Connector metadata keyed by connector id.
	 *
	 * @var array<string,array>
	 */
	const CONNECTORS = array(
		'woocommerce'     => array(
			'integration_tier'  => self::TIER_FULL,
			'smoke_native_keys' => array( 'woocommerce_currency', 'woocommerce_shop_page_id' ),
		),
		'bricks'          => array(
			'integration_tier'  => self::TIER_FULL,
			'smoke_native_keys' => array( 'bricks_global_settings' ),
		),
		'elementor'       => array(
			'integration_tier'  => self::TIER_PARTIAL,
			'smoke_native_keys' => array( 'elementor_container_width', 'elementor_onboarded' ),
		),
		'gravity-forms'   => array(
			'integration_tier'  => self::TIER_PARTIAL,
			'smoke_native_keys' => array( 'gform_enable_noconflict' ),
		),
		'contact-form-7'  => array(
			'integration_tier'  => self::TIER_PARTIAL,
			'smoke_native_keys' => array( 'conjure_cf7_contact_form_id' ),
		),
		'gutenberg'       => array(
			'integration_tier'  => self::TIER_PARTIAL,
			'smoke_native_keys' => array( 'show_on_front', 'page_on_front' ),
		),
		'whippet'         => array(
			'integration_tier'  => self::TIER_PREFERENCES,
			'smoke_native_keys' => array(),
		),
		'yoast-seo'       => array(
			'integration_tier'  => self::TIER_PARTIAL,
			'smoke_native_keys' => array( 'wpseo_titles', 'wpseo_social', 'wpseo_xml' ),
		),
		'rank-math'       => array(
			'integration_tier'  => self::TIER_PARTIAL,
			'smoke_native_keys' => array( 'rank-math-options-titles', 'rank-math-options-sitemap' ),
		),
		'acf'             => array(
			'integration_tier'  => self::TIER_PARTIAL,
			'smoke_native_keys' => array( 'acf_settings' ),
		),
		'jetpack'         => array(
			'integration_tier'  => self::TIER_PARTIAL,
			'smoke_native_keys' => array( 'jetpack_active_modules' ),
		),
		'wp-rocket'       => array(
			'integration_tier'  => self::TIER_PARTIAL,
			'smoke_native_keys' => array( 'wp_rocket_settings' ),
		),
		'litespeed-cache' => array(
			'integration_tier'  => self::TIER_PARTIAL,
			'smoke_native_keys' => array( 'litespeed-cache-conf' ),
		),
		'wpforms'         => array(
			'integration_tier'  => self::TIER_PARTIAL,
			'smoke_native_keys' => array( 'wpforms_settings' ),
		),
		'wordfence'       => array(
			'integration_tier'  => self::TIER_PARTIAL,
			'smoke_native_keys' => array(),
		),
	);

	/**
	 * Whether step connectors require an active ConjureWP Pro plugin licence.
	 *
	 * @return bool
	 */
	public static function connectors_require_pro_plugin() {
		/**
		 * Filter whether step connectors require ConjureWP Pro.
		 *
		 * @param bool $requires_pro Default true.
		 */
		return (bool) apply_filters( 'conjure_connectors_require_pro_plugin', true );
	}

	/**
	 * Merge catalogue defaults into a connector definition array.
	 *
	 * @param array $definition Connector definition from disk.
	 * @return array
	 */
	public static function enrich_definition( $definition ) {
		if ( ! is_array( $definition ) ) {
			return array();
		}

		$id   = isset( $definition['id'] ) ? sanitize_key( $definition['id'] ) : '';
		$meta = self::get( $id );

		$definition['integration_tier']  = $meta['integration_tier'];
		$definition['smoke_native_keys'] = $meta['smoke_native_keys'];
		$definition['native_sync_ready'] = self::has_native_sync( $id );

		return $definition;
	}

	/**
	 * Get metadata for a connector id.
	 *
	 * @param string $connector_id Connector id.
	 * @return array
	 */
	public static function get( $connector_id ) {
		$connector_id = sanitize_key( $connector_id );

		if ( isset( self::CONNECTORS[ $connector_id ] ) ) {
			return wp_parse_args(
				self::CONNECTORS[ $connector_id ],
				array(
					'integration_tier'  => self::TIER_PREFERENCES,
					'smoke_native_keys' => array(),
				)
			);
		}

		return array(
			'integration_tier'  => self::TIER_PREFERENCES,
			'smoke_native_keys' => array(),
		);
	}

	/**
	 * Whether the site has an active ConjureWP Pro plugin licence.
	 *
	 * @return bool
	 */
	public static function has_pro_plugin_access() {
		$has_access = false;

		if ( class_exists( 'Conjure_Premium_Features' ) ) {
			$has_access = Conjure_Premium_Features::is_premium();
		}

		/**
		 * Filter ConjureWP Pro plugin licence access (connectors are included in Pro).
		 *
		 * @param bool $has_access Whether the Pro plugin licence is active.
		 */
		return (bool) apply_filters( 'conjure_connector_has_pro_license', $has_access, '' );
	}

	/**
	 * Whether a connector may be toggled on/off in the admin (always true).
	 *
	 * Per-connector activation controls which steps are injected into the wizard.
	 *
	 * @param string $connector_id Connector id.
	 * @return bool
	 */
	public static function can_configure_connector( $connector_id ) {
		/**
		 * Filter whether a connector can be activated/deactivated in the admin.
		 *
		 * @param bool   $can_configure  Default true.
		 * @param string $connector_id   Connector id.
		 */
		return (bool) apply_filters( 'conjure_can_configure_connector', true, $connector_id );
	}

	/**
	 * Whether an enabled connector may appear as a step in the wizard.
	 *
	 * Requires ConjureWP Pro when connectors_require_pro_plugin() is true.
	 *
	 * @param string $connector_id Connector id.
	 * @return bool
	 */
	public static function can_show_connector_in_wizard( $connector_id ) {
		if ( ! self::connectors_require_pro_plugin() ) {
			return true;
		}

		return self::has_pro_plugin_access();
	}

	/**
	 * Whether native sync is implemented for this connector.
	 *
	 * @param string $connector_id Connector id.
	 * @return bool
	 */
	public static function has_native_sync( $connector_id ) {
		return class_exists( 'Conjure_Connector_Native_Sync' )
			&& Conjure_Connector_Native_Sync::supports( $connector_id );
	}

	/**
	 * Human label for an integration tier.
	 *
	 * @param string $tier Tier constant.
	 * @return string
	 */
	public static function get_tier_label( $tier ) {
		switch ( $tier ) {
			case self::TIER_FULL:
				return __( 'Full native setup', 'ConjureWP' );
			case self::TIER_PARTIAL:
				return __( 'Partial native setup', 'ConjureWP' );
			default:
				return __( 'Preferences + native sync', 'ConjureWP' );
		}
	}

	/**
	 * Reconcile connectors on disk vs catalogue vs marketing-only list.
	 *
	 * @param array<string,Conjure_Step_Connector_Base> $loaded_connectors Loaded connectors.
	 * @return array
	 */
	public static function reconcile( $loaded_connectors ) {
		$on_disk = array_keys( $loaded_connectors );
		$known   = array_keys( self::CONNECTORS );
		$missing = array_diff( $known, $on_disk );
		$extra   = array_diff( $on_disk, $known );

		return array(
			'on_disk'                 => $on_disk,
			'catalogue'               => $known,
			'missing_on_disk'         => array_values( $missing ),
			'unknown_on_disk'         => array_values( $extra ),
			'marketing_only'          => array_keys( self::MARKETING_ONLY ),
			'pro_plugin_price_label'  => self::PRO_PLUGIN_PRICE_LABEL,
			'has_pro_plugin_access'   => self::has_pro_plugin_access(),
			'connectors_require_pro'  => self::connectors_require_pro_plugin(),
		);
	}
}
