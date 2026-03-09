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
