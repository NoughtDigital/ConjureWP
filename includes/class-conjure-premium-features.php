<?php
/**
 * Premium Features Helper Class
 *
 * This class provides helper methods to check if premium features are available.
 * Use these methods throughout your plugin to gate premium functionality.
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
 * ConjureWP Premium Features Helper Class
 * 
 * FREE VERSION: All checks return false (no premium features in WordPress.org version).
 * PREMIUM VERSION: Includes full Freemius integration for licensing.
 */
class Conjure_Premium_Features {

	/**
	 * Check if user has an active premium license.
	 * 
	 * FREE VERSION: Always returns false.
	 *
	 * @return bool Always false in free version.
	 */
	public static function is_premium() {
		// Free version has no premium features.
		return false;
	}

	/**
	 * Check if premium code should be included.
	 * 
	 * FREE VERSION: Always returns false.
	 *
	 * @return bool Always false in free version.
	 */
	public static function is_premium_code() {
		// Free version has no premium code.
		return false;
	}

	/**
	 * Check if user has a specific plan.
	 * 
	 * FREE VERSION: Always returns false.
	 *
	 * @param string $plan_name Plan name to check.
	 * @return bool Always false in free version.
	 */
	public static function has_plan( $plan_name ) {
		// Free version has no plans.
		return false;
	}

	/**
	 * Check if user is on free plan.
	 * 
	 * FREE VERSION: Always returns true.
	 *
	 * @return bool Always true in free version.
	 */
	public static function is_free() {
		// Free version is always free.
		return true;
	}

	/**
	 * Get upgrade URL for premium features.
	 * 
	 * FREE VERSION: Returns empty string (no upgrade URL).
	 *
	 * @return string Empty string in free version.
	 */
	public static function get_upgrade_url() {
		// No upgrade URL in free version.
		return '';
	}

	/**
	 * Display upgrade notice for premium feature.
	 * 
	 * FREE VERSION: Does nothing (no upgrade notices).
	 *
	 * @param string $feature_name Name of the feature.
	 * @param string $context      Context (optional).
	 */
	public static function show_upgrade_notice( $feature_name = '', $context = '' ) {
		// No upgrade notices in free version.
		return;
	}

	/**
	 * Get premium badge HTML.
	 * 
	 * FREE VERSION: Returns empty string (no badges).
	 *
	 * @param string $text Badge text.
	 * @return string Empty string in free version.
	 */
	public static function get_premium_badge( $text = 'Pro' ) {
		// No badges in free version.
		return '';
	}
}

/*
 * NOTE FOR LOCAL DEVELOPMENT:
 * The premium section below may cause class redeclaration errors during local development.
 * During Freemius deployment, this code is automatically managed.
 */

// @freemius:premium-start
// Skip premium code during local dev to prevent redeclaration errors.
if ( false && ! class_exists( 'Conjure_Premium_Features' ) ) :
/**
 * PREMIUM VERSION: Full Freemius integration with licensing checks.
 * This code is automatically stripped from the WordPress.org free version.
 */
class Conjure_Premium_Features {

	/**
	 * Check if user has an active premium license.
	 *
	 * @return bool True if user has active premium license, false otherwise.
	 */
	public static function is_premium() {
		// Check if Freemius is available.
		if ( ! function_exists( 'con_fs' ) ) {
			return false;
		}

		$fs = con_fs();

		// Check if Freemius SDK is loaded and user has valid license.
		if ( $fs && is_object( $fs ) && method_exists( $fs, 'is_paying_or_trial' ) ) {
			// Check if user is paying or has trial.
			return $fs->is_paying_or_trial();
		}

		return false;
	}

	/**
	 * Check if premium code should be included.
	 * 
	 * This method is used by Freemius to strip premium code from the free version.
	 * When you upload to Freemius, it automatically removes code wrapped in 
	 * con_fs()->is__premium_only() checks.
	 *
	 * @return bool True if premium code should be included.
	 */
	public static function is_premium_code() {
		if ( ! function_exists( 'con_fs' ) ) {
			return false;
		}

		$fs = con_fs();

		if ( $fs && is_object( $fs ) && method_exists( $fs, 'is__premium_only' ) ) {
			// This special method tells Freemius to strip this code from free version.
			return $fs->is__premium_only();
		}

		return false;
	}

	/**
	 * Check if user has a specific plan.
	 *
	 * @param string $plan_name Plan name to check (e.g., 'pro', 'business').
	 * @return bool True if user has the specified plan.
	 */
	public static function has_plan( $plan_name ) {
		if ( ! function_exists( 'con_fs' ) ) {
			return false;
		}

		$fs = con_fs();

		if ( $fs && is_object( $fs ) && method_exists( $fs, 'is_paying' ) && method_exists( $fs, 'is_plan' ) ) {
			if ( $fs->is_paying() ) {
				return $fs->is_plan( $plan_name, true );
			}
		}

		return false;
	}

	/**
	 * Check if user is on free plan.
	 *
	 * @return bool True if user is on free plan.
	 */
	public static function is_free() {
		return ! self::is_premium();
	}

	/**
	 * Get upgrade URL for premium features.
	 *
	 * @return string Upgrade URL or empty string if Freemius not available.
	 */
	public static function get_upgrade_url() {
		if ( ! function_exists( 'con_fs' ) ) {
			return '';
		}

		$fs = con_fs();

		if ( $fs && is_object( $fs ) && method_exists( $fs, 'get_upgrade_url' ) ) {
			return $fs->get_upgrade_url();
		}

		return '';
	}

	/**
	 * Display upgrade notice for premium feature.
	 *
	 * @param string $feature_name Name of the feature requiring premium.
	 * @param string $context      Context where the notice is displayed (optional).
	 */
	public static function show_upgrade_notice( $feature_name = '', $context = '' ) {
		if ( self::is_premium() ) {
			return;
		}

		$upgrade_url = self::get_upgrade_url();

		if ( empty( $upgrade_url ) ) {
			return;
		}

		$message = $feature_name 
			? sprintf( 
				/* translators: %s: Feature name */
				__( '%s is a premium feature. Upgrade to unlock it.', 'conjurewp' ), 
				'<strong>' . esc_html( $feature_name ) . '</strong>' 
			)
			: __( 'This is a premium feature. Upgrade to unlock it.', 'conjurewp' );

		?>
		<div class="notice notice-info conjure-premium-notice">
			<p>
				<?php echo wp_kses_post( $message ); ?>
				<a href="<?php echo esc_url( $upgrade_url ); ?>" class="button button-primary" style="margin-left: 10px;">
					<?php esc_html_e( 'Upgrade Now', 'conjurewp' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Get premium badge HTML.
	 *
	 * @param string $text Badge text (default: 'Pro').
	 * @return string HTML for premium badge.
	 */
	public static function get_premium_badge( $text = 'Pro' ) {
		return sprintf(
			'<span class="conjure-premium-badge" style="background: #ff6b6b; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; margin-left: 8px;">%s</span>',
			esc_html( $text )
		);
	}
}

endif; // End class_exists check for premium version.
// @freemius:premium-end

