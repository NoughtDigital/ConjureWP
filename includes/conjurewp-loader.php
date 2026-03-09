<?php
/**
 * ConjureWP shared loader.
 *
 * Boots the shared core for both plugin and theme-embedded usage.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/conjurewp-runtime.php';

if ( ! function_exists( 'conjurewp_should_load_freemius' ) ) {
	/**
	 * Determine whether the Freemius integration should be loaded.
	 *
	 * @return bool
	 */
	function conjurewp_should_load_freemius() {
		if ( conjurewp_is_plugin_runtime() ) {
			return true;
		}

		return (bool) apply_filters( 'conjurewp_load_freemius_in_theme_embed', false );
	}
}

if ( ! function_exists( 'conjurewp_merge_theme_bundled_plugins' ) ) {
	/**
	 * Merge theme-bundled plugins with demo-specific plugins.
	 *
	 * @param array $demo_plugins  Demo-specific plugins.
	 * @param int   $demo_index    Demo index.
	 * @param array $selected_demo Demo configuration.
	 * @return array
	 */
	function conjurewp_merge_theme_bundled_plugins( $demo_plugins, $demo_index, $selected_demo ) {
		return Conjure_Theme_Plugins::merge_with_demo_plugins( $demo_plugins );
	}
}

if ( ! function_exists( 'conjurewp_auto_register_demos' ) ) {
	/**
	 * Auto-register demo imports from the active demo directory.
	 *
	 * @param array $import_files Existing import files.
	 * @return array
	 */
	function conjurewp_auto_register_demos( $import_files ) {
		if ( ! Conjure_Demo_Helpers::is_auto_register_enabled() ) {
			return $import_files;
		}

		$auto_demos = Conjure_Demo_Helpers::auto_discover_demos();

		if ( ! empty( $auto_demos ) ) {
			$import_files = array_merge( $auto_demos, $import_files );
		}

		return $import_files;
	}
}

if ( ! function_exists( 'conjurewp_load_textdomains' ) ) {
	/**
	 * Load ConjureWP translations before translated strings are initialised.
	 *
	 * Also registers the legacy mixed-case text domain so existing calls using
	 * `ConjureWP` continue to resolve to the same translation catalogue.
	 *
	 * @return void
	 */
	function conjurewp_load_textdomains() {
		if ( conjurewp_is_plugin_runtime() ) {
			$language_path = dirname( plugin_basename( conjurewp_get_runtime_path( 'conjurewp.php' ) ) ) . '/languages';
			load_plugin_textdomain( 'conjurewp', false, $language_path );
		} else {
			load_theme_textdomain( 'conjurewp', conjurewp_get_runtime_path( 'languages' ) );
		}

		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		$mofile = WP_LANG_DIR . '/plugins/conjurewp-' . $locale . '.mo';

		if ( ! file_exists( $mofile ) ) {
			$mofile = conjurewp_get_runtime_path( 'languages/conjurewp-' . $locale . '.mo' );
		}

		if ( file_exists( $mofile ) ) {
			load_textdomain( 'ConjureWP', $mofile );
		}
	}
}

if ( ! function_exists( 'conjurewp_bootstrap' ) ) {
	/**
	 * Bootstrap the shared ConjureWP runtime.
	 *
	 * @param array $runtime_args Runtime configuration.
	 * @return bool
	 */
	function conjurewp_bootstrap( $runtime_args = array() ) {
		if ( defined( 'CONJUREWP_BOOTSTRAPPED' ) && CONJUREWP_BOOTSTRAPPED ) {
			return true;
		}

		conjurewp_define_runtime( $runtime_args );

		$autoload_file = conjurewp_get_runtime_path( 'vendor/autoload.php' );
		if ( file_exists( $autoload_file ) ) {
			require_once $autoload_file;
		}

		conjurewp_load_textdomains();

		if ( conjurewp_should_load_freemius() ) {
			$freemius_file = conjurewp_get_runtime_path( 'includes/class-conjure-freemius.php' );
			if ( file_exists( $freemius_file ) ) {
				require_once $freemius_file;
			}
		}

		$premium_features_file = conjurewp_get_runtime_path( 'includes/class-conjure-premium-features.php' );
		if ( file_exists( $premium_features_file ) ) {
			require_once $premium_features_file;
		}

		require_once conjurewp_get_runtime_path( 'class-conjure.php' );
		require_once conjurewp_get_runtime_path( 'includes/class-conjure-logger.php' );
		require_once conjurewp_get_runtime_path( 'includes/class-conjure-demo-helpers.php' );
		require_once conjurewp_get_runtime_path( 'includes/class-conjure-theme-plugins.php' );

		add_filter( 'conjure_demo_required_plugins', 'conjurewp_merge_theme_bundled_plugins', 5, 3 );
		add_filter( 'conjure_import_files', 'conjurewp_auto_register_demos', 5 );

		require_once conjurewp_get_runtime_path( 'conjurewp-config.php' );

		if ( function_exists( 'is_admin' ) && is_admin() ) {
			$admin_tools_file = conjurewp_get_runtime_path( 'includes/class-conjure-admin-tools.php' );
			if ( file_exists( $admin_tools_file ) ) {
				require_once $admin_tools_file;
			}
		}

		if ( ! defined( 'CONJUREWP_BOOTSTRAPPED' ) ) {
			define( 'CONJUREWP_BOOTSTRAPPED', true );
		}

		return true;
	}
}
