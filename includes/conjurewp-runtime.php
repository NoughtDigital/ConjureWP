<?php
/**
 * ConjureWP runtime helpers.
 *
 * Shared bootstrap utilities used by both plugin and theme-embedded entry points.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'conjurewp_normalize_runtime_path' ) ) {
	/**
	 * Normalise a filesystem path and ensure it has a trailing slash.
	 *
	 * @param string $path Filesystem path.
	 * @return string
	 */
	function conjurewp_normalize_runtime_path( $path ) {
		if ( function_exists( 'wp_normalize_path' ) ) {
			$path = wp_normalize_path( $path );
		} else {
			$path = str_replace( '\\', '/', $path );
		}

		return trailingslashit( rtrim( $path, '/\\' ) );
	}
}

if ( ! function_exists( 'conjurewp_define_runtime' ) ) {
	/**
	 * Define shared runtime constants for the active bootstrap mode.
	 *
	 * @param array $args Runtime configuration.
	 * @return array
	 */
	function conjurewp_define_runtime( $args = array() ) {
		$defaults = array(
			'mode'      => defined( 'CONJUREWP_PLUGIN_DIR' ) ? 'plugin' : 'theme',
			'base_path' => defined( 'CONJUREWP_PLUGIN_DIR' ) ? CONJUREWP_PLUGIN_DIR : dirname( __DIR__ ),
			'base_url'  => defined( 'CONJUREWP_PLUGIN_URL' ) ? CONJUREWP_PLUGIN_URL : '',
			'file'      => defined( 'CONJUREWP_PLUGIN_FILE' ) ? CONJUREWP_PLUGIN_FILE : '',
		);

		$args = wp_parse_args( $args, $defaults );

		$runtime = array(
			'mode'      => 'plugin' === $args['mode'] ? 'plugin' : 'theme',
			'base_path' => conjurewp_normalize_runtime_path( $args['base_path'] ),
			'base_url'  => empty( $args['base_url'] ) ? '' : trailingslashit( $args['base_url'] ),
			'file'      => $args['file'],
		);

		if ( ! defined( 'CONJUREWP_VERSION' ) ) {
			define( 'CONJUREWP_VERSION', '1.0.0' );
		}

		if ( ! defined( 'CONJUREWP_BASE_PATH' ) ) {
			define( 'CONJUREWP_BASE_PATH', $runtime['base_path'] );
		}

		if ( ! defined( 'CONJUREWP_BASE_URL' ) ) {
			define( 'CONJUREWP_BASE_URL', $runtime['base_url'] );
		}

		if ( ! defined( 'CONJUREWP_RUNTIME_MODE' ) ) {
			define( 'CONJUREWP_RUNTIME_MODE', $runtime['mode'] );
		}

		if ( ! empty( $runtime['file'] ) && ! defined( 'CONJUREWP_RUNTIME_FILE' ) ) {
			define( 'CONJUREWP_RUNTIME_FILE', $runtime['file'] );
		}

		if ( 'plugin' === $runtime['mode'] ) {
			if ( ! defined( 'CONJUREWP_PLUGIN_DIR' ) ) {
				define( 'CONJUREWP_PLUGIN_DIR', $runtime['base_path'] );
			}

			if ( ! defined( 'CONJUREWP_PLUGIN_URL' ) ) {
				define( 'CONJUREWP_PLUGIN_URL', $runtime['base_url'] );
			}

			if ( ! empty( $runtime['file'] ) && ! defined( 'CONJUREWP_PLUGIN_FILE' ) ) {
				define( 'CONJUREWP_PLUGIN_FILE', $runtime['file'] );
			}
		}

		return $runtime;
	}
}

if ( ! function_exists( 'conjurewp_get_runtime_mode' ) ) {
	/**
	 * Get the current runtime mode.
	 *
	 * @return string
	 */
	function conjurewp_get_runtime_mode() {
		return defined( 'CONJUREWP_RUNTIME_MODE' ) ? CONJUREWP_RUNTIME_MODE : 'plugin';
	}
}

if ( ! function_exists( 'conjurewp_is_plugin_runtime' ) ) {
	/**
	 * Check whether ConjureWP is running as a plugin.
	 *
	 * @return bool
	 */
	function conjurewp_is_plugin_runtime() {
		return 'plugin' === conjurewp_get_runtime_mode();
	}
}

if ( ! function_exists( 'conjurewp_get_runtime_path' ) ) {
	/**
	 * Get an absolute runtime path.
	 *
	 * @param string $relative_path Optional relative path.
	 * @return string
	 */
	function conjurewp_get_runtime_path( $relative_path = '' ) {
		$base_path = defined( 'CONJUREWP_BASE_PATH' ) ? CONJUREWP_BASE_PATH : ( defined( 'CONJUREWP_PLUGIN_DIR' ) ? CONJUREWP_PLUGIN_DIR : conjurewp_normalize_runtime_path( dirname( __DIR__ ) ) );

		if ( empty( $relative_path ) ) {
			return $base_path;
		}

		return trailingslashit( $base_path ) . ltrim( $relative_path, '/\\' );
	}
}

if ( ! function_exists( 'conjurewp_get_runtime_url' ) ) {
	/**
	 * Get a runtime URL.
	 *
	 * @param string $relative_path Optional relative path.
	 * @return string
	 */
	function conjurewp_get_runtime_url( $relative_path = '' ) {
		$base_url = defined( 'CONJUREWP_BASE_URL' ) ? CONJUREWP_BASE_URL : ( defined( 'CONJUREWP_PLUGIN_URL' ) ? CONJUREWP_PLUGIN_URL : '' );

		if ( empty( $relative_path ) || empty( $base_url ) ) {
			return $base_url;
		}

		return trailingslashit( $base_url ) . ltrim( $relative_path, '/\\' );
	}
}

if ( ! function_exists( 'conjurewp_get_theme_embed_roots' ) ) {
	/**
	 * Get the active theme root paths and URLs for embed resolution.
	 *
	 * Returns unique stylesheet/template roots so embedded packages can resolve
	 * correctly from either a child theme or a parent theme.
	 *
	 * @return array<int, array{path:string,url:string}>
	 */
	function conjurewp_get_theme_embed_roots() {
		$roots = array();

		$candidates = array(
			array(
				'path' => function_exists( 'get_stylesheet_directory' ) ? get_stylesheet_directory() : '',
				'url'  => function_exists( 'get_stylesheet_directory_uri' ) ? get_stylesheet_directory_uri() : '',
			),
			array(
				'path' => function_exists( 'get_template_directory' ) ? get_template_directory() : ( function_exists( 'get_parent_theme_file_path' ) ? get_parent_theme_file_path() : '' ),
				'url'  => function_exists( 'get_template_directory_uri' ) ? get_template_directory_uri() : ( function_exists( 'get_parent_theme_file_uri' ) ? get_parent_theme_file_uri() : '' ),
			),
		);

		foreach ( $candidates as $candidate ) {
			if ( empty( $candidate['path'] ) ) {
				continue;
			}

			$normalised_path = conjurewp_normalize_runtime_path( $candidate['path'] );

			if ( isset( $roots[ $normalised_path ] ) ) {
				continue;
			}

			$roots[ $normalised_path ] = array(
				'path' => $normalised_path,
				'url'  => empty( $candidate['url'] ) ? '' : trailingslashit( $candidate['url'] ),
			);
		}

		return array_values( $roots );
	}
}

if ( ! function_exists( 'conjurewp_get_theme_embed_root' ) ) {
	/**
	 * Get the owning theme root for an embedded ConjureWP package.
	 *
	 * @param string $base_path Embedded package base path.
	 * @return array{path:string,url:string}
	 */
	function conjurewp_get_theme_embed_root( $base_path = '' ) {
		$roots = conjurewp_get_theme_embed_roots();

		if ( empty( $roots ) ) {
			return array(
				'path' => '',
				'url'  => '',
			);
		}

		$normalised_base_path = empty( $base_path ) ? conjurewp_get_runtime_path() : conjurewp_normalize_runtime_path( $base_path );

		foreach ( $roots as $root ) {
			if ( 0 === strpos( $normalised_base_path, $root['path'] ) ) {
				return $root;
			}
		}

		return $roots[0];
	}
}

if ( ! function_exists( 'conjurewp_get_theme_embed_root_path' ) ) {
	/**
	 * Get the owning theme root path for an embedded ConjureWP package.
	 *
	 * @param string $base_path Embedded package base path.
	 * @return string
	 */
	function conjurewp_get_theme_embed_root_path( $base_path = '' ) {
		$root = conjurewp_get_theme_embed_root( $base_path );

		return empty( $root['path'] ) ? '' : $root['path'];
	}
}

if ( ! function_exists( 'conjurewp_get_theme_embed_base_url' ) ) {
	/**
	 * Derive the URL for a ConjureWP copy embedded inside a theme.
	 *
	 * @param string $base_path Embedded package base path.
	 * @return string
	 */
	function conjurewp_get_theme_embed_base_url( $base_path ) {
		$theme_root = conjurewp_get_theme_embed_root( $base_path );

		if ( empty( $theme_root['path'] ) || empty( $theme_root['url'] ) ) {
			return '';
		}

		$normalised_base_path = conjurewp_normalize_runtime_path( $base_path );
		$normalised_theme_dir = $theme_root['path'];

		if ( 0 !== strpos( $normalised_base_path, $normalised_theme_dir ) ) {
			return $theme_root['url'];
		}

		$relative_path = trim( substr( $normalised_base_path, strlen( $normalised_theme_dir ) ), '/' );
		$theme_root_url = rtrim( $theme_root['url'], '/\\' );

		return '' === $relative_path
			? $theme_root['url']
			: trailingslashit( $theme_root_url . '/' . $relative_path );
	}
}

if ( ! function_exists( 'conjurewp_should_expose_error_details' ) ) {
	/**
	 * Whether detailed error messages may be returned to clients.
	 *
	 * @return bool
	 */
	function conjurewp_should_expose_error_details() {
		if ( defined( 'CONJUREWP_DEBUG' ) && CONJUREWP_DEBUG ) {
			return true;
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && ( ! defined( 'WP_DEBUG_DISPLAY' ) || WP_DEBUG_DISPLAY ) ) {
			return true;
		}

		return (bool) apply_filters( 'conjurewp_expose_error_details', false );
	}
}

if ( ! function_exists( 'conjurewp_safe_error_message' ) ) {
	/**
	 * Return a client-safe error message for production responses.
	 *
	 * Full details are still written to logs by callers; this helper only
	 * gates what is exposed in AJAX/REST payloads.
	 *
	 * @param Throwable|string $error    Throwable or raw message.
	 * @param string           $fallback Optional fallback when details are hidden.
	 * @return string
	 */
	function conjurewp_safe_error_message( $error, $fallback = '' ) {
		$message = '';

		if ( $error instanceof Throwable ) {
			$message = $error->getMessage();
		} elseif ( is_string( $error ) ) {
			$message = $error;
		}

		if ( conjurewp_should_expose_error_details() && '' !== $message ) {
			return $message;
		}

		if ( '' !== $fallback ) {
			return $fallback;
		}

		return __( 'An unexpected error occurred. Please try again or contact support.', 'ConjureWP' );
	}
}

if ( ! function_exists( 'conjurewp_safe_import_message' ) ) {
	/**
	 * Client-safe message for import failures (paths and WP_Error details gated in production).
	 *
	 * @param Throwable|string|WP_Error $detail   Error source.
	 * @param string                    $fallback Message when details are hidden.
	 * @return string
	 */
	function conjurewp_safe_import_message( $detail, $fallback = '' ) {
		if ( is_wp_error( $detail ) ) {
			$detail = $detail->get_error_message();
		}

		return conjurewp_safe_error_message( $detail, $fallback );
	}
}

if ( ! function_exists( 'conjurewp_json_decode' ) ) {
	/**
	 * Decode JSON with a bounded maximum depth to reduce memory exhaustion risk.
	 *
	 * @param string $json       JSON string.
	 * @param bool   $assoc      Return associative array when true.
	 * @param int    $max_depth  Maximum nesting depth (filterable).
	 * @return mixed|null Decoded value, or null on failure / excessive depth.
	 */
	function conjurewp_json_decode( $json, $assoc = false, $max_depth = 0 ) {
		if ( '' === $json || ! is_string( $json ) ) {
			return null;
		}

		if ( $max_depth <= 0 ) {
			$max_depth = (int) apply_filters( 'conjurewp_json_decode_max_depth', 32 );
		}

		$decoded = json_decode( $json, $assoc, max( 1, $max_depth ) );

		if ( JSON_ERROR_DEPTH === json_last_error() ) {
			return null;
		}

		return $decoded;
	}
}

if ( ! function_exists( 'conjurewp_sanitize_acf_json_save_path' ) ) {
	/**
	 * Sanitise a relative ACF JSON directory path for use inside the active theme.
	 *
	 * @param string $relative_path Path relative to the theme root.
	 * @return string
	 */
	function conjurewp_sanitize_acf_json_save_path( $relative_path ) {
		$relative_path = is_string( $relative_path ) ? trim( $relative_path ) : 'acf-json';

		if ( '' === $relative_path ) {
			return 'acf-json';
		}

		$relative_path = ltrim( $relative_path, '/' );
		$relative_path = str_replace( array( '..', '\\' ), '', $relative_path );

		return '' === $relative_path ? 'acf-json' : $relative_path;
	}
}

if ( ! function_exists( 'conjurewp_get_acf_json_save_path_config_default' ) ) {
	/**
	 * Default ACF JSON path from conjurewp-config.php (developer preset).
	 *
	 * @return string
	 */
	function conjurewp_get_acf_json_save_path_config_default() {
		if ( defined( 'CONJUREWP_CONFIG_ACF_JSON_SAVE_PATH' ) ) {
			return conjurewp_sanitize_acf_json_save_path( CONJUREWP_CONFIG_ACF_JSON_SAVE_PATH );
		}

		global $conjurewp;

		if ( isset( $conjurewp ) && $conjurewp instanceof Conjure && ! empty( $conjurewp->acf_json_save_path ) ) {
			return conjurewp_sanitize_acf_json_save_path( $conjurewp->acf_json_save_path );
		}

		return 'acf-json';
	}
}

if ( ! function_exists( 'conjurewp_get_acf_json_save_path' ) ) {
	/**
	 * Resolve the ACF local JSON folder path (relative to the active theme).
	 *
	 * Priority: filter → wp-config constant → saved wizard option → conjurewp-config preset → acf-json.
	 *
	 * @return string
	 */
	function conjurewp_get_acf_json_save_path() {
		$filtered = apply_filters( 'conjurewp_acf_json_save_path', null );

		if ( null !== $filtered && '' !== $filtered ) {
			return conjurewp_sanitize_acf_json_save_path( $filtered );
		}

		if ( defined( 'CONJUREWP_ACF_JSON_SAVE_PATH' ) ) {
			return conjurewp_sanitize_acf_json_save_path( CONJUREWP_ACF_JSON_SAVE_PATH );
		}

		$option = get_option( 'conjure_acf_json_save_path', '' );

		if ( is_string( $option ) && '' !== $option ) {
			return conjurewp_sanitize_acf_json_save_path( $option );
		}

		return conjurewp_get_acf_json_save_path_config_default();
	}
}

if ( ! function_exists( 'conjurewp_rest_import_rate_limit' ) ) {
	/**
	 * Enforce per-user cooldown between REST demo imports.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return true|WP_Error True when allowed; WP_Error with 429 when rate limited.
	 */
	function conjurewp_rest_import_rate_limit( $user_id ) {
		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return new WP_Error(
				'rest_import_unauthorized',
				__( 'You must be logged in to run imports.', 'ConjureWP' ),
				array( 'status' => 401 )
			);
		}

		$key      = 'conjurewp_rest_import_rl_' . $user_id;
		$cooldown = max( 1, (int) apply_filters( 'conjurewp_rest_import_cooldown', 120 ) );
		$last_run = get_transient( $key );

		if ( false !== $last_run ) {
			$elapsed    = time() - (int) $last_run;
			$retry_after = max( 1, $cooldown - $elapsed );

			return new WP_Error(
				'rest_import_rate_limited',
				__( 'Please wait before starting another import.', 'ConjureWP' ),
				array(
					'status'      => 429,
					'retry_after' => $retry_after,
				)
			);
		}

		set_transient( $key, time(), $cooldown );

		return true;
	}
}

if ( ! function_exists( 'conjurewp_require_once' ) ) {
	/**
	 * Require a file once per request.
	 *
	 * @param string $file Absolute path to a PHP file.
	 * @return bool True when the file exists and is loaded.
	 */
	function conjurewp_require_once( $file ) {
		static $loaded = array();

		if ( empty( $file ) || isset( $loaded[ $file ] ) ) {
			return isset( $loaded[ $file ] );
		}

		if ( ! file_exists( $file ) ) {
			return false;
		}

		require_once $file;
		$loaded[ $file ] = true;

		return true;
	}
}

if ( ! function_exists( 'conjurewp_require_runtime_include' ) ) {
	/**
	 * Require a runtime include file once per request.
	 *
	 * @param string $relative_path Path relative to the ConjureWP base directory.
	 * @return bool
	 */
	function conjurewp_require_runtime_include( $relative_path ) {
		return conjurewp_require_once( conjurewp_get_runtime_path( $relative_path ) );
	}
}

if ( ! function_exists( 'conjurewp_get_conjure' ) ) {
	/**
	 * Get the bootstrapped Conjure instance when available.
	 *
	 * @return Conjure|null
	 */
	function conjurewp_get_conjure() {
		return ( isset( $GLOBALS['conjurewp'] ) && $GLOBALS['conjurewp'] instanceof Conjure )
			? $GLOBALS['conjurewp']
			: null;
	}
}
