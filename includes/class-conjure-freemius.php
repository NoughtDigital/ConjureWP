<?php
/**
 * Freemius Integration for ConjureWP
 *
 * Handles Freemius licensing and theme detection logic.
 * Free for open-source themes, paid for commercial themes.
 *
 * @package   ConjureWP
 * @version   1.0.0
 * @link      https://conjurewp.com/
 * @author    Jake Henshall, from nought.digital
 * @copyright Copyright (c) 2018, Conjure WP of Inventionn LLC
 * @license   GPLv3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ConjureWP Freemius Integration Class
 */
class Conjure_Freemius {

	/**
	 * Theme detection cache.
	 *
	 * @var null|bool
	 */
	private static $is_open_source_theme = null;

	/**
	 * Check if the current theme is open-source (free).
	 *
	 * @return bool True if open-source, false if paid/commercial.
	 */
	public static function is_open_source_theme() {
		// Use cached result if available.
		if ( null !== self::$is_open_source_theme ) {
			return self::$is_open_source_theme;
		}

		$theme = wp_get_theme();
		
		// Check if theme is from WordPress.org.
		// Themes from WordPress.org are always open-source.
		if ( self::is_wordpress_org_theme( $theme ) ) {
			self::$is_open_source_theme = true;
			return true;
		}

		// Check theme metadata for license information.
		$license = $theme->get( 'License' );
		$license_uri = $theme->get( 'License URI' );
		
		// GPL-compatible licenses indicate open-source.
		$open_source_licenses = array(
			'GPL',
			'GPLv2',
			'GPLv2 or later',
			'GPLv3',
			'GPLv3 or later',
			'LGPL',
			'MIT',
			'BSD',
			'Apache',
		);

		if ( ! empty( $license ) ) {
			$license_upper = strtoupper( $license );
			foreach ( $open_source_licenses as $os_license ) {
				if ( false !== strpos( $license_upper, strtoupper( $os_license ) ) ) {
					// Also check if License URI points to WordPress.org or GNU.org.
					if ( empty( $license_uri ) || 
						 false !== strpos( $license_uri, 'wordpress.org' ) || 
						 false !== strpos( $license_uri, 'gnu.org' ) ) {
						self::$is_open_source_theme = true;
						return true;
					}
				}
			}
		}

		// Allow filter for theme developers to mark their theme as open-source.
		$is_open_source = apply_filters( 'conjurewp_is_open_source_theme', false, $theme );
		
		if ( $is_open_source ) {
			self::$is_open_source_theme = true;
			return true;
		}

		// Default to false (paid/commercial) if we can't determine otherwise.
		self::$is_open_source_theme = false;
		return false;
	}

	/**
	 * Check if theme is from WordPress.org repository.
	 *
	 * @param WP_Theme $theme Theme object.
	 * @return bool True if from WordPress.org.
	 */
	private static function is_wordpress_org_theme( $theme ) {
		// Themes from WordPress.org typically have a stylesheet directory in wp-content/themes.
		// And are not premium/commercial themes.
		
		// Check if theme has WordPress.org compatible structure.
		$theme_dir = get_stylesheet_directory();
		$theme_root = get_theme_root();
		
		// If theme is in standard themes directory and doesn't appear to be premium.
		if ( 0 === strpos( $theme_dir, $theme_root ) ) {
			// Check for common premium theme indicators.
			$premium_indicators = array(
				'theme_options',
				'options_panel',
				'envato',
				'theme_framework',
				'premium',
				'pro',
			);
			
			// Read theme stylesheet for clues.
			$stylesheet_file = $theme->get_stylesheet_directory() . '/style.css';
			if ( file_exists( $stylesheet_file ) ) {
				$stylesheet_content = file_get_contents( $stylesheet_file );
				
				// If theme explicitly declares it's from WordPress.org or is GPL.
				if ( false !== strpos( $stylesheet_content, 'WordPress.org' ) ||
					 false !== strpos( $stylesheet_content, 'URI: https://wordpress.org/themes' ) ) {
					return true;
				}
				
				// If theme doesn't have premium indicators, might be open-source.
				foreach ( $premium_indicators as $indicator ) {
					if ( false !== strpos( strtolower( $stylesheet_content ), $indicator ) ) {
						return false;
					}
				}
			}
		}
		
		// Allow filter to override detection.
		return apply_filters( 'conjurewp_is_wordpress_org_theme', false, $theme );
	}

	/**
	 * Check if user should have free access (open-source theme) or needs to pay.
	 *
	 * @return bool True if free access, false if payment required.
	 */
	public static function has_free_access() {
		$has_access = false;

		// Free access if theme is open-source.
		if ( self::is_open_source_theme() ) {
			$has_access = true;
		} elseif ( function_exists( 'conjure_wp' ) ) {
			// If theme is paid/commercial, check if user has valid license.
			$fs = conjure_wp();
			if ( $fs && is_object( $fs ) && method_exists( $fs, 'is_registered' ) && method_exists( $fs, 'has_active_valid_license' ) ) {
				if ( $fs->is_registered() && $fs->has_active_valid_license() ) {
					$has_access = true;
				}
			}
		}

		// Allow other licensing systems (e.g. EDD) to grant access.
		return (bool) apply_filters( 'conjurewp_has_free_access', $has_access, self::get_current_theme_name() );
	}

	/**
	 * Get the current theme name.
	 *
	 * @return string Theme name.
	 */
	public static function get_current_theme_name() {
		$theme = wp_get_theme();
		return $theme->get( 'Name' );
	}

	/**
	 * Get the current theme author.
	 *
	 * @return string Theme author.
	 */
	public static function get_current_theme_author() {
		$theme = wp_get_theme();
		return $theme->get( 'Author' );
	}
}

/**
 * Initialize Freemius SDK
 * WordPress.org compliant - free version can be hosted on WordPress.org
 */
if ( ! function_exists( 'conjure_wp' ) ) {
	/**
	 * Get Freemius instance.
	 *
	 * @return Freemius|false Freemius instance or false if SDK not loaded.
	 */
	function conjure_wp() {
		global $conjure_wp;

		if ( ! isset( $conjure_wp ) ) {
			// Activate multisite network integration.
			if ( ! defined( 'WP_FS__PRODUCT_21546_MULTISITE' ) ) {
				define( 'WP_FS__PRODUCT_21546_MULTISITE', true );
			}

			// Check if Freemius SDK is available.
			// SDK is auto-loaded through Composer, but check for manual installation too.
			$sdk_paths = array(
				CONJUREWP_PLUGIN_DIR . 'vendor/freemius/wordpress-sdk/start.php',
				CONJUREWP_PLUGIN_DIR . 'freemius/start.php',
				CONJUREWP_PLUGIN_DIR . 'includes/freemius/start.php',
			);

			$sdk_loaded = false;
			foreach ( $sdk_paths as $sdk_path ) {
				if ( file_exists( $sdk_path ) ) {
					require_once $sdk_path;
					$sdk_loaded = true;
					break;
				}
			}

			// Only initialize if Freemius SDK is available.
			if ( $sdk_loaded && function_exists( 'fs_dynamic_init' ) ) {
				$conjure_wp = fs_dynamic_init( array(
					'id'                  => '21546',
					'slug'                => 'conjurewp',
					'type'                => 'plugin',
					'public_key'          => 'pk_cf9facc7360558ffbb5a814a4b34c',
					'is_premium'          => false,
					'has_addons'          => false,
					'has_paid_plans'      => true, // Enable paid plans for commercial themes.
					'has_premium_version' => false, // WordPress.org compliant - no premium version on .org.
					'menu'                => array(
						'first-path'    => 'admin.php?page=conjurewp-setup',
						'account'       => true, // Enable account for license management.
						'pricing'       => true, // Enable pricing for paid themes.
						'contact'       => true, // Enable contact.
						'support'       => true, // Enable support.
					),
					// Hook for theme detection - show pricing only for paid themes.
					'permissions' => array(
						'newsletter' => true,
						'admin'     => true,
					),
				) );
			} else {
				// Freemius SDK not found - plugin will work in free mode for all themes.
				$conjure_wp = false;
			}
		}

		return $conjure_wp;
	}

	// Init Freemius.
	conjure_wp();

	// Signal that SDK was initiated.
	do_action( 'conjure_wp_loaded' );
}
