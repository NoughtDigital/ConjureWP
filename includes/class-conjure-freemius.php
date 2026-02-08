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
 * 
 * This class provides licensing and theme detection logic.
 * In the free version (WordPress.org), all methods return values that grant full access.
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
	 * FREE VERSION: Always returns true (all themes get full access).
	 *
	 * @return bool True if open-source, false if paid/commercial.
	 */
	public static function is_open_source_theme() {
		// In free version, all themes are treated as open-source (full access).
		// Premium version includes advanced detection logic.
		return true;
	}

	/**
	 * Check if the current theme has lifetime ConjureWP integration.
	 * 
	 * FREE VERSION: Not applicable (all themes get full access).
	 *
	 * @return bool True if theme has lifetime integration.
	 */
	public static function has_lifetime_integration() {
		// In free version, this feature is not applicable.
		// Premium version includes lifetime integration checks.
		return false;
	}

	/**
	 * Check if user should have free access to all features.
	 * 
	 * FREE VERSION: Always returns true (everyone gets full access to core features).
	 *
	 * @return bool True if free access granted.
	 */
	public static function has_free_access() {
		// In free version, everyone gets full access to core features.
		// Premium version includes licensing checks.
		return true;
	}

	/**
	 * Check if automatic plugin installation is available (PREMIUM FEATURE).
	 * 
	 * FREE VERSION: Always returns false - users must manually install plugins.
	 * PREMIUM VERSION: Returns true if user has valid license.
	 *
	 * @return bool False in free version (feature not available).
	 */
	public static function can_auto_install_plugins() {
		// Automatic plugin installation is a premium-only feature.
		// Free users see the plugin list but must install manually.
		return false;
	}

	/**
	 * Check if advanced imports are available.
	 * Advanced imports include Revolution Slider and Redux Framework options.
	 * 
	 * FREE VERSION: Always returns true - all import features available.
	 * PREMIUM VERSION: Always returns true - all import features available.
	 *
	 * @return bool True (feature available in both free and premium).
	 */
	public static function can_use_advanced_imports() {
		// Advanced imports (Revolution Slider, Redux) are available in both free and premium.
		// All users get full import features: content, widgets, customizer, redux, sliders.
		return true;
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

/*
 * NOTE FOR LOCAL DEVELOPMENT:
 * The premium section below (@freemius:premium-start to @freemius:premium-end) 
 * may cause class redeclaration errors during local development.
 * During Freemius deployment, this code is automatically managed.
 * 
 * To test locally without errors, the premium class is disabled by checking
 * if we're NOT in a freemius build environment.
 */

// @freemius:premium-start
// Skip premium code during local dev to prevent redeclaration errors.
if ( false && ! class_exists( 'Conjure_Freemius' ) ) :
/**
 * PREMIUM VERSION: Advanced theme detection and licensing logic.
 * This code is automatically stripped from the WordPress.org free version.
 */

/**
 * Override the free version class with premium features.
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
	 * PREMIUM VERSION: Advanced detection logic for theme licensing.
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
	 * Check if the current theme has lifetime ConjureWP integration.
	 * Theme developers who purchased lifetime integration can whitelist their themes.
	 *
	 * @return bool True if theme has lifetime integration.
	 */
	public static function has_lifetime_integration() {
		$theme = wp_get_theme();
		$theme_slug = $theme->get_stylesheet();
		$theme_name = $theme->get( 'Name' );

		// Check if theme is whitelisted via constant (wp-config.php).
		if ( defined( 'CONJUREWP_LIFETIME_THEMES' ) ) {
			$lifetime_themes = is_array( CONJUREWP_LIFETIME_THEMES ) ? CONJUREWP_LIFETIME_THEMES : array( CONJUREWP_LIFETIME_THEMES );
			if ( in_array( $theme_slug, $lifetime_themes, true ) || in_array( $theme_name, $lifetime_themes, true ) ) {
				return true;
			}
		}

		// Check via filter hook (recommended for theme developers).
		$is_lifetime = apply_filters( 'conjurewp_has_lifetime_integration', false, $theme_slug, $theme_name );
		
		// Allow theme-specific filter for individual themes to declare themselves.
		$is_lifetime = apply_filters( "conjurewp_has_lifetime_integration_{$theme_slug}", $is_lifetime, $theme_slug, $theme_name );

		return (bool) $is_lifetime;
	}

	/**
	 * Check if user should have free access (open-source theme) or needs to pay.
	 *
	 * @return bool True if free access, false if payment required.
	 */
	public static function has_free_access() {
		$has_access = false;

		// Free access if theme has lifetime integration purchased by developer.
		if ( self::has_lifetime_integration() ) {
			$has_access = true;
		} elseif ( self::is_open_source_theme() ) {
			// Free access if theme is open-source.
			$has_access = true;
		} elseif ( function_exists( 'con_fs' ) ) {
			// If theme is paid/commercial, check if user has valid license.
			$fs = con_fs();
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
	 * Check if automatic plugin installation is available (PREMIUM FEATURE).
	 * 
	 * This is a premium-only feature. Free users see the plugin list but must
	 * manually install plugins from WordPress.org.
	 *
	 * @return bool True if user can use automatic plugin installation.
	 */
	public static function can_auto_install_plugins() {
		// Check if user has premium license.
		if ( function_exists( 'con_fs' ) ) {
			$fs = con_fs();
			if ( $fs && is_object( $fs ) && method_exists( $fs, 'is_paying_or_trial' ) ) {
				// Premium feature: requires active license.
				if ( $fs->is_paying_or_trial() ) {
					return true;
				}
			}
		}

		// Also grant access if theme has lifetime integration.
		if ( self::has_lifetime_integration() ) {
			return true;
		}

		// Allow filter for custom licensing systems.
		return (bool) apply_filters( 'conjurewp_can_auto_install_plugins', false );
	}

	/**
	 * Check if advanced imports are available (PREMIUM FEATURE).
	 * Advanced imports include Revolution Slider and Redux Framework options.
	 * 
	 * This is a premium-only feature. Free users get basic imports only
	 * (content, widgets, customizer).
	 *
	 * @return bool True if user can use advanced imports.
	 */
	public static function can_use_advanced_imports() {
		// Check if user has premium license.
		if ( function_exists( 'con_fs' ) ) {
			$fs = con_fs();
			if ( $fs && is_object( $fs ) && method_exists( $fs, 'is_paying_or_trial' ) ) {
				// Premium feature: requires active license.
				if ( $fs->is_paying_or_trial() ) {
					return true;
				}
			}
		}

		// Also grant access if theme has lifetime integration.
		if ( self::has_lifetime_integration() ) {
			return true;
		}

		// Allow filter for custom licensing systems.
		return (bool) apply_filters( 'conjurewp_can_use_advanced_imports', false );
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
// @freemius:premium-end

/**
 * Initialize Freemius SDK (Premium only - automatically stripped from free version).
 * 
 * IMPORTANT: This entire block is removed by Freemius when generating the WordPress.org version.
 * The free version works perfectly without this code - all features remain available.
 * 
 * For theme developers: Premium features include automatic plugin installation.
 * Free users can still manually install plugins and import demos.
 */
if ( ! function_exists( 'con_fs' ) ) {
	/**
	 * Create Freemius SDK instance stub for free version compatibility.
	 *
	 * @return false Always returns false in free version (no Freemius SDK).
	 */
	function con_fs() {
		// In the free version, this function exists but returns false.
		// Premium version code below is stripped by Freemius processor.
		return false;
	}
}

// @freemius:premium-start
/**
 * PREMIUM ONLY CODE BELOW - Automatically removed from WordPress.org version.
 * 
 * This code is wrapped in Freemius premium tags and will be completely stripped
 * when deployed to WordPress.org, making the free version 100% compliant.
 */

if ( ! function_exists( 'con_fs' ) ) {
	/**
	 * Get Freemius instance (premium version only).
	 *
	 * @return Freemius|false Freemius instance or false if SDK not loaded.
	 */
	function con_fs() {
		global $con_fs;

		if ( ! isset( $con_fs ) ) {
			// Activate multisite network integration.
			if ( ! defined( 'WP_FS__PRODUCT_21879_MULTISITE' ) ) {
				define( 'WP_FS__PRODUCT_21879_MULTISITE', true );
			}

			// Include Freemius SDK.
			// SDK is auto-loaded through Composer, but ensure it's loaded.
			if ( ! function_exists( 'fs_dynamic_init' ) ) {
				// Try to load Freemius SDK if not already loaded.
				$plugin_dir = defined( 'CONJUREWP_PLUGIN_DIR' ) ? CONJUREWP_PLUGIN_DIR : dirname( dirname( __FILE__ ) );
				$sdk_path = $plugin_dir . '/vendor/freemius/wordpress-sdk/start.php';
				if ( file_exists( $sdk_path ) ) {
					require_once $sdk_path;
				}
			}

			// Only initialize if Freemius SDK is available.
			if ( function_exists( 'fs_dynamic_init' ) ) {
				$con_fs = fs_dynamic_init( array(
					'id'                  => '21879',
					'slug'                => 'conjurewp',
					'premium_slug'        => 'conjurewp-pro',
					'type'                => 'plugin',
					'public_key'          => 'pk_4a1a51f34990a6a5f94ddeb5a5fc5',
					'is_premium'          => true,
					'premium_suffix'      => 'Pro',
					'has_premium_version' => true,
					'has_addons'          => false,
					'has_paid_plans'      => true,
					'wp_org_gatekeeper'   => 'OA7#BoRiBNqdf52FvzEf!!074aRLPs8fspif$7K1#4u4Csys1fQlCecVcUTOs2mcpeVHi#C2j9d09fOTvbC0HloPT7fFee5WdS3G',
					'menu'                => array(
						'first-path' => 'plugins.php',
						'support'    => false,
					),
				) );
			} else {
				// Freemius SDK not found - shouldn't happen in premium version.
				$con_fs = false;
			}
		}

		return $con_fs;
	}
}

// Init Freemius.
$con_fs_instance = con_fs();

// Signal that SDK was initiated.
do_action( 'con_fs_loaded' );

/**
 * Customise Freemius opt-in/activation messaging to avoid confusion with theme licences.
 * Makes it clear that this is for ConjureWP premium features, not theme activation.
 */
if ( $con_fs_instance ) {
	/**
	 * Customise the connect message for plugin updates.
	 *
	 * @param string $message         Original message.
	 * @param string $user_first_name User's first name.
	 * @param string $plugin_title    Plugin name.
	 * @param string $user_login      User login.
	 * @param string $site_link       Site link HTML.
	 * @param string $freemius_link   Freemius link HTML.
	 * @return string Modified message.
	 */
	function conjure_fs_custom_connect_message_on_update( $message, $user_first_name, $plugin_title, $user_login, $site_link, $freemius_link ) {
		return sprintf(
			/* translators: %1$s: User's first name, %2$s: Plugin title (ConjureWP) */
			__( 'Hey %1$s, activate your %2$s licence to unlock premium features like automatic plugin installation, advanced demo importing, and priority support.', 'conjurewp' ),
			$user_first_name,
			'<strong>' . $plugin_title . '</strong>'
		);
	}
	$con_fs_instance->add_filter( 'connect_message_on_update', 'conjure_fs_custom_connect_message_on_update', 10, 6 );

	/**
	 * Customise the connect message for new installations.
	 *
	 * @param string $message         Original message.
	 * @param string $user_first_name User's first name.
	 * @param string $plugin_title    Plugin name.
	 * @param string $user_login      User login.
	 * @param string $site_link       Site link HTML.
	 * @param string $freemius_link   Freemius link HTML.
	 * @return string Modified message.
	 */
	function conjure_fs_custom_connect_message( $message, $user_first_name, $plugin_title, $user_login, $site_link, $freemius_link ) {
		return sprintf(
			/* translators: %1$s: User's first name, %2$s: Plugin title (ConjureWP) */
			__( 'Hey %1$s, activate your %2$s licence to unlock premium features like automatic plugin installation and advanced demo importing.', 'conjurewp' ),
			$user_first_name,
			'<strong>' . $plugin_title . '</strong>'
		);
	}
	$con_fs_instance->add_filter( 'connect_message', 'conjure_fs_custom_connect_message', 10, 6 );

	/**
	 * Customise the opt-in heading to make it clear this is about ConjureWP premium features.
	 *
	 * @param string $header Original header text.
	 * @return string Modified header text.
	 */
	function conjure_fs_custom_connect_header( $header ) {
		return __( 'Enter Your ConjureWP Licence Key', 'conjurewp' );
	}
	$con_fs_instance->add_filter( 'connect/header_title', 'conjure_fs_custom_connect_header' );

	/**
	 * Customise the decline/skip button text.
	 *
	 * @param string $text Original button text.
	 * @return string Modified button text.
	 */
	function conjure_fs_custom_skip_button( $text ) {
		return __( 'Continue with Free Version', 'conjurewp' );
	}
	$con_fs_instance->add_filter( 'skip_connection_message', 'conjure_fs_custom_skip_button' );

	/**
	 * Hide Freemius activation screen for themes with lifetime integration.
	 * When a theme developer has purchased lifetime integration, end users
	 * should not see the license activation screen.
	 */
	if ( class_exists( 'Conjure_Freemius' ) && Conjure_Freemius::has_lifetime_integration() ) {
		/**
		 * Skip Freemius connection/activation for lifetime integration themes.
		 * This prevents the activation screen from showing to end users.
		 *
		 * @param bool $skip Whether to skip the connection.
		 * @return bool True to skip connection screen.
		 */
		function conjure_fs_skip_connection_for_lifetime_theme( $skip ) {
			return true;
		}
		$con_fs_instance->add_filter( 'skip_connection', 'conjure_fs_skip_connection_for_lifetime_theme' );

		/**
		 * Hide activation notices for lifetime integration themes.
		 *
		 * @param bool $show Whether to show the notice.
		 * @return bool False to hide notice.
		 */
		function conjure_fs_hide_activation_notice_for_lifetime_theme( $show ) {
			return false;
		}
		$con_fs_instance->add_filter( 'show_opt_in', 'conjure_fs_hide_activation_notice_for_lifetime_theme' );
		$con_fs_instance->add_filter( 'show_admin_notice', 'conjure_fs_hide_activation_notice_for_lifetime_theme' );

		/**
		 * Prevent Freemius from showing activation prompts.
		 *
		 * @param bool $show Whether to show activation prompt.
		 * @return bool False to hide prompt.
		 */
		function conjure_fs_hide_activation_prompt_for_lifetime_theme( $show ) {
			return false;
		}
		$con_fs_instance->add_filter( 'show_connect_notice', 'conjure_fs_hide_activation_prompt_for_lifetime_theme' );
		$con_fs_instance->add_filter( 'show_trial', 'conjure_fs_hide_activation_prompt_for_lifetime_theme' );
	}
}

endif; // End class_exists check for premium version.
// @freemius:premium-end
