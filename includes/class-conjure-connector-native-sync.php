<?php
/**
 * Push Conjure connector wizard choices into third-party plugin settings.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Native settings synchronisation for step connectors.
 */
class Conjure_Connector_Native_Sync {

	/**
	 * Connectors with a sync implementation.
	 *
	 * @var array<string,string>
	 */
	protected static $handlers = array(
		'yoast-seo'       => 'sync_yoast_seo',
		'rank-math'       => 'sync_rank_math',
		'acf'             => 'sync_acf',
		'jetpack'         => 'sync_jetpack',
		'wp-rocket'       => 'sync_wp_rocket',
		'wordfence'       => 'sync_wordfence',
		'litespeed-cache' => 'sync_litespeed_cache',
		'wpforms'         => 'sync_wpforms',
		'whippet'         => 'sync_whippet',
		'elementor'       => 'sync_elementor',
		'gravity-forms'   => 'sync_gravity_forms',
		'contact-form-7'  => 'sync_contact_form_7',
		'woocommerce'     => 'sync_woocommerce',
		'bricks'          => 'sync_bricks',
		'gutenberg'       => 'sync_gutenberg',
	);

	/**
	 * Whether sync is registered for a connector.
	 *
	 * @param string $connector_id Connector id.
	 * @return bool
	 */
	public static function supports( $connector_id ) {
		return isset( self::$handlers[ sanitize_key( $connector_id ) ] );
	}

	/**
	 * Apply native sync for a connector after wizard submission.
	 *
	 * @param string $connector_id  Connector id.
	 * @param array  $enabled_keys  Enabled feature keys.
	 * @return bool True when a handler ran.
	 */
	public static function apply( $connector_id, $enabled_keys = array() ) {
		$connector_id = sanitize_key( $connector_id );

		if ( ! isset( self::$handlers[ $connector_id ] ) ) {
			return false;
		}

		$method = self::$handlers[ $connector_id ];

		if ( ! method_exists( __CLASS__, $method ) ) {
			return false;
		}

		self::$method( $enabled_keys );

		/**
		 * Fires after Conjure syncs wizard settings to a third-party plugin.
		 *
		 * @param string $connector_id Connector id.
		 * @param array  $enabled_keys Enabled feature keys.
		 */
		do_action( 'conjure_connector_native_synced', $connector_id, $enabled_keys );

		return true;
	}

	/**
	 * Merge values into an option array.
	 *
	 * @param string $option_name Option name.
	 * @param array  $values      Values to merge.
	 * @return void
	 */
	protected static function merge_option_array( $option_name, array $values ) {
		$current = get_option( $option_name, array() );

		if ( ! is_array( $current ) ) {
			$current = array();
		}

		update_option( $option_name, array_merge( $current, $values ) );
	}

	/**
	 * @param array $enabled_keys Enabled features.
	 * @return void
	 */
	protected static function sync_yoast_seo( $enabled_keys ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		if ( ! defined( 'WPSEO_VERSION' ) ) {
			return;
		}

		$titles = get_option( 'wpseo_titles', array() );
		if ( ! is_array( $titles ) ) {
			$titles = array();
		}

		$separator = get_option( 'conjure_yoast_title_separator', '' );
		if ( '' !== $separator ) {
			$titles['separator'] = $separator;
		}

		$post_template = get_option( 'conjure_yoast_post_title_template', '' );
		if ( '' !== $post_template ) {
			$titles['title-post'] = $post_template;
		}

		$page_template = get_option( 'conjure_yoast_page_title_template', '' );
		if ( '' !== $page_template ) {
			$titles['title-page'] = $page_template;
		}

		if ( get_option( 'conjure_yoast_strip_category_base' ) ) {
			$titles['stripcategorybase'] = true;
		}

		$noindex_map = array(
			'conjure_yoast_noindex_author_archives' => 'noindex-author-wpseo',
			'conjure_yoast_noindex_date_archives'   => 'noindex-archive-wpseo',
			'conjure_yoast_noindex_format_archives' => 'noindex-format',
			'conjure_yoast_noindex_tags'            => 'noindex-tax-post_tag',
		);

		foreach ( $noindex_map as $conjure_key => $yoast_key ) {
			if ( get_option( $conjure_key ) ) {
				$titles[ $yoast_key ] = true;
			}
		}

		update_option( 'wpseo_titles', $titles );

		$xml = get_option( 'wpseo_xml', array() );
		if ( ! is_array( $xml ) ) {
			$xml = array();
		}
		$xml['enable_xml_sitemap'] = get_option( 'conjure_yoast_enable_sitemap' ) ? true : false;
		update_option( 'wpseo_xml', $xml );

		$social = get_option( 'wpseo_social', array() );
		if ( ! is_array( $social ) ) {
			$social = array();
		}
		$social['opengraph']      = get_option( 'conjure_yoast_enable_opengraph' ) ? true : false;
		$social['twitter']        = get_option( 'conjure_yoast_enable_twitter_cards' ) ? true : false;
		$social['facebook_site']  = get_option( 'conjure_yoast_facebook_url', '' );
		$social['twitter_site']   = get_option( 'conjure_yoast_twitter_username', '' );
		update_option( 'wpseo_social', $social );

		$intern = get_option( 'wpseo_internallinks', array() );
		if ( ! is_array( $intern ) ) {
			$intern = array();
		}
		$intern['breadcrumbs-enable'] = get_option( 'conjure_yoast_enable_breadcrumbs' ) ? true : false;
		$sep                          = get_option( 'conjure_yoast_breadcrumb_separator', '' );
		if ( '' !== $sep ) {
			$intern['breadcrumbs-sep'] = $sep;
		}
		update_option( 'wpseo_internallinks', $intern );
	}

	/**
	 * @param array $enabled_keys Enabled features.
	 * @return void
	 */
	protected static function sync_rank_math( $enabled_keys ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		if ( ! class_exists( 'RankMath' ) ) {
			return;
		}

		$titles = get_option( 'rank-math-options-titles', array() );
		if ( ! is_array( $titles ) ) {
			$titles = array();
		}

		$separator = get_option( 'conjure_rm_title_separator', '' );
		if ( '' !== $separator ) {
			$titles['title_separator'] = $separator;
		}

		$post_pattern = get_option( 'conjure_rm_post_title_pattern', '' );
		if ( '' !== $post_pattern ) {
			$titles['title_post'] = $post_pattern;
		}

		$page_pattern = get_option( 'conjure_rm_page_title_pattern', '' );
		if ( '' !== $page_pattern ) {
			$titles['title_page'] = $page_pattern;
		}

		update_option( 'rank-math-options-titles', $titles );

		$sitemap = get_option( 'rank-math-options-sitemap', array() );
		if ( ! is_array( $sitemap ) ) {
			$sitemap = array();
		}
		$sitemap['items_per_page']   = 200;
		$sitemap['include_images']   = get_option( 'conjure_rm_sitemap_include_images' ) ? 'on' : 'off';
		$sitemap['ping_search_engines'] = get_option( 'conjure_rm_sitemap_ping_search_engines' ) ? 1 : 0;
		update_option( 'rank-math-options-sitemap', $sitemap );

		$general = get_option( 'rank-math-options-general', array() );
		if ( ! is_array( $general ) ) {
			$general = array();
		}
		$general['opengraph'] = get_option( 'conjure_rm_enable_opengraph' ) ? true : false;
		$general['twitter']   = get_option( 'conjure_rm_enable_twitter_cards' ) ? true : false;
		update_option( 'rank-math-options-general', $general );
	}

	/**
	 * @param array $enabled_keys Enabled features.
	 * @return void
	 */
	protected static function sync_acf( $enabled_keys ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		if ( ! class_exists( 'ACF' ) && ! function_exists( 'acf_get_setting' ) ) {
			return;
		}

		$settings = get_option( 'acf_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings['show_admin']         = get_option( 'conjure_acf_hide_admin_menu' ) ? false : true;
		$settings['enable_post_types']  = true;
		$settings['enable_options_pages'] = get_option( 'conjure_acf_create_general_options' ) ? true : false;
		$settings['json_save_path']     = function_exists( 'conjurewp_get_acf_json_save_path' )
			? conjurewp_get_acf_json_save_path()
			: 'acf-json';
		$settings['json_load_paths']    = array( $settings['json_save_path'] );

		update_option( 'acf_settings', $settings );

		if ( function_exists( 'acf_update_setting' ) ) {
			if ( get_option( 'conjure_acf_show_in_rest' ) ) {
				acf_update_setting( 'show_in_rest', true );
			}
		}
	}

	/**
	 * @param array $enabled_keys Enabled features.
	 * @return void
	 */
	protected static function sync_jetpack( $enabled_keys ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		if ( ! class_exists( 'Jetpack' ) ) {
			return;
		}

		$modules = array();

		if ( get_option( 'conjure_jetpack_enable_stats' ) ) {
			$modules[] = 'stats';
		}
		if ( get_option( 'conjure_jetpack_enable_related_posts' ) ) {
			$modules[] = 'related-posts';
		}
		if ( get_option( 'conjure_jetpack_brute_force_protection' ) ) {
			$modules[] = 'protect';
		}
		if ( get_option( 'conjure_jetpack_enable_photon' ) && class_exists( 'Jetpack_Photon' ) ) {
			$modules[] = 'photon';
		}

		$active = get_option( 'jetpack_active_modules', array() );
		if ( ! is_array( $active ) ) {
			$active = array();
		}

		foreach ( $modules as $module ) {
			if ( ! in_array( $module, $active, true ) && method_exists( 'Jetpack', 'activate_module' ) ) {
				Jetpack::activate_module( $module, false, false );
			}
		}
	}

	/**
	 * @param array $enabled_keys Enabled features.
	 * @return void
	 */
	protected static function sync_wp_rocket( $enabled_keys ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		if ( ! function_exists( 'conjurewp_is_wp_rocket_active' ) || ! conjurewp_is_wp_rocket_active() ) {
			return;
		}

		$settings = get_option( 'wp_rocket_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$map = array(
			'conjure_wprocket_enable_page_cache'  => 'cache_logged_user',
			'conjure_wprocket_enable_mobile_cache' => 'cache_mobile',
			'conjure_wprocket_minify_css'         => 'minify_css',
			'conjure_wprocket_minify_js'          => 'minify_js',
			'conjure_wprocket_combine_css'        => 'minify_concatenate_css',
			'conjure_wprocket_combine_js'         => 'minify_concatenate_js',
			'conjure_wprocket_defer_js'           => 'defer_all_js',
			'conjure_wprocket_delay_js'           => 'delay_js',
			'conjure_wprocket_lazy_images'        => 'lazyload',
			'conjure_wprocket_lazy_iframes'       => 'lazyload_iframes',
			'conjure_wprocket_enable_preload'     => 'manual_preload',
			'conjure_wprocket_preload_links'      => 'preload_links',
			'conjure_wprocket_enable_cdn'         => 'cdn',
		);

		foreach ( $map as $conjure_key => $rocket_key ) {
			if ( get_option( $conjure_key ) ) {
				$settings[ $rocket_key ] = 1;
			}
		}

		$cdn_url = get_option( 'conjure_wprocket_cdn_url', '' );
		if ( $cdn_url ) {
			$settings['cdn_cnames'] = array( $cdn_url );
		}

		update_option( 'wp_rocket_settings', $settings );

		if ( function_exists( 'rocket_generate_config_file' ) ) {
			rocket_generate_config_file();
		}
	}

	/**
	 * @param array $enabled_keys Enabled features.
	 * @return void
	 */
	protected static function sync_wordfence( $enabled_keys ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		if ( ! class_exists( 'wfConfig' ) ) {
			return;
		}

		if ( get_option( 'conjure_wf_enable_firewall' ) && method_exists( 'wfConfig', 'set' ) ) {
			wfConfig::set( 'firewallEnabled', 1 );
		}
		if ( get_option( 'conjure_wf_enable_auto_scan' ) && method_exists( 'wfConfig', 'set' ) ) {
			wfConfig::set( 'scansEnabled', 1 );
		}
		if ( get_option( 'conjure_wf_disable_xml_rpc' ) && method_exists( 'wfConfig', 'set' ) ) {
			wfConfig::set( 'disableXMLRPC', 1 );
		}

		$email = get_option( 'conjure_wf_alert_email', '' );
		if ( $email && method_exists( 'wfConfig', 'set' ) ) {
			wfConfig::set( 'alertEmails', $email );
		}
	}

	/**
	 * @param array $enabled_keys Enabled features.
	 * @return void
	 */
	protected static function sync_litespeed_cache( $enabled_keys ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		if ( ! class_exists( 'LiteSpeed_Cache' ) && ! defined( 'LSCWP_V' ) ) {
			return;
		}

		if ( class_exists( 'LiteSpeed_Cache_API' ) ) {
			if ( get_option( 'conjure_lscache_enable_cache' ) ) {
				LiteSpeed_Cache_API::set( 'cache', true );
			}
			if ( get_option( 'conjure_lscache_optimise_images' ) ) {
				LiteSpeed_Cache_API::set( 'img_optm', true );
			}
			return;
		}

		self::merge_option_array(
			'litespeed-cache-conf',
			array(
				'cache'         => get_option( 'conjure_lscache_enable_cache' ) ? 1 : 0,
				'cache-mobile'  => get_option( 'conjure_lscache_cache_mobile' ) ? 1 : 0,
				'img_optm'      => get_option( 'conjure_lscache_optimise_images' ) ? 1 : 0,
			)
		);
	}

	/**
	 * @param array $enabled_keys Enabled features.
	 * @return void
	 */
	protected static function sync_wpforms( $enabled_keys ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		if ( ! function_exists( 'conjurewp_is_wpforms_active' ) || ! conjurewp_is_wpforms_active() ) {
			return;
		}

		$settings = get_option( 'wpforms_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$email = get_option( 'conjure_wpforms_notification_email', '' );
		if ( $email ) {
			$settings['email'] = $email;
		}

		$settings['disable-css'] = 0;
		$settings['gdpr']        = get_option( 'conjure_wpforms_enable_anti_spam' ) ? 1 : 0;

		update_option( 'wpforms_settings', $settings );
	}

	/**
	 * @param array $enabled_keys Enabled features.
	 * @return void
	 */
	protected static function sync_whippet( $enabled_keys ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		$flags = array(
			'conjure_whippet_disable_emoji_scripts' => 'disable_emojis',
			'conjure_whippet_disable_embeds'          => 'disable_embeds',
			'conjure_whippet_disable_xmlrpc'          => 'disable_xmlrpc',
			'conjure_whippet_remove_query_strings'    => 'remove_query_strings',
		);

		$active = array();
		foreach ( $flags as $conjure_key => $flag ) {
			if ( get_option( $conjure_key ) ) {
				$active[ $flag ] = 1;
			}
		}

		update_option( 'whippet_optimisations', $active );
	}

	/**
	 * @param array $enabled_keys Enabled features.
	 * @return void
	 */
	protected static function sync_elementor( $enabled_keys ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		if ( ! function_exists( 'conjurewp_is_elementor_active' ) || ! conjurewp_is_elementor_active() ) {
			return;
		}

		if ( get_option( 'conjure_elementor_disable_dashboard_widget' ) ) {
			update_option( 'elementor_disable_dashboard_widgets', array( 'overview' => true ) );
		}
	}

	/**
	 * @param array $enabled_keys Enabled features.
	 * @return void
	 */
	protected static function sync_gravity_forms( $enabled_keys ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		if ( ! class_exists( 'GFAPI' ) ) {
			return;
		}

		$email = get_option( 'conjure_gf_notification_email', '' );
		if ( $email ) {
			update_option( 'admin_email', $email );
		}
	}

	/**
	 * @param array $enabled_keys Enabled features.
	 * @return void
	 */
	protected static function sync_contact_form_7( $enabled_keys ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		// Form creation already writes post meta; ensure autop filter when requested.
		if ( get_option( 'conjure_cf7_disable_autop' ) ) {
			add_filter( 'wpcf7_autop_or_not', '__return_false' );
		}
	}

	/**
	 * @param array $enabled_keys Enabled features.
	 * @return void
	 */
	protected static function sync_woocommerce( $enabled_keys ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		// WooCommerce connector already writes native options during handle_step.
	}

	/**
	 * @param array $enabled_keys Enabled features.
	 * @return void
	 */
	protected static function sync_bricks( $enabled_keys ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		// Bricks connector writes bricks_global_settings during handle_step.
	}

	/**
	 * @param array $enabled_keys Enabled features.
	 * @return void
	 */
	protected static function sync_gutenberg( $enabled_keys ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		$features = get_option( 'conjure_gutenberg_editor_features', array() );
		if ( is_array( $features ) && ! empty( $features ) ) {
			update_option( 'core-block-editor-features', $features );
		}
	}

	/**
	 * Run smoke checks: verify expected native option keys exist after sync.
	 *
	 * @param string $connector_id Connector id.
	 * @return array{pass:bool,messages:array}
	 */
	public static function smoke_test( $connector_id ) {
		$connector_id = sanitize_key( $connector_id );
		$meta         = Conjure_Connector_Catalog::get( $connector_id );
		$messages     = array();
		$pass         = true;

		foreach ( $meta['smoke_native_keys'] as $key ) {
			$value = get_option( $key, null );
			if ( null === $value ) {
				$pass       = false;
				$messages[] = sprintf(
					/* translators: %s: option name */
					__( 'Native option "%s" is not set.', 'ConjureWP' ),
					$key
				);
			}
		}

		return array(
			'pass'     => $pass,
			'messages' => $messages,
		);
	}
}
