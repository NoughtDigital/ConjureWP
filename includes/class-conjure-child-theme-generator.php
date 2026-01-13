<?php
/**
 * Child Theme Generator class
 *
 * Handles creation of child themes.
 *
 * @package   Conjure WP
 * @version   @@pkg.version
 * @link      https://conjurewp.com/
 * @author    Jake Henshall, from Nought.digital
 * @copyright Copyright (c) 2018, Conjure WP of Nought Digital
 * @license   Licensed GPLv3 for Open Source Use
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conjure Child Theme Generator class.
 */
class Conjure_Child_Theme_Generator {

	/**
	 * Reference to main Conjure instance.
	 *
	 * @var Conjure
	 */
	protected $conjure;

	/**
	 * Logger instance.
	 *
	 * @var Conjure_Logger
	 */
	protected $logger;

	/**
	 * Constructor.
	 *
	 * @param Conjure $conjure Main Conjure instance.
	 */
	public function __construct( $conjure ) {
		$this->conjure = $conjure;
		$this->logger  = $conjure->logger;
	}

	/**
	 * Generate the child theme via AJAX.
	 */
	public function generate_child() {

		// Verify nonce for security.
		check_ajax_referer( 'conjure_nonce', 'wpnonce' );

		// Check if user has permission to switch themes.
		if ( ! current_user_can( 'switch_themes' ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'You do not have permission to perform this action.', 'conjurewp' ),
				)
			);
		}

		// Strings passed in from the config file.
		$strings = $this->conjure->strings;

		// Text strings.
		$success = $strings['child-json-success%s'];
		$already = $strings['child-json-already%s'];

		// Get the parent theme (in case we're already on a child theme).
		$parent_theme = wp_get_theme( $this->conjure->theme->template );
		$parent_slug  = $parent_theme->get_stylesheet();
		$name         = $parent_theme->name . ' Child';
		$slug         = sanitize_title( $name );

		$path = get_theme_root() . '/' . $slug;

		if ( ! file_exists( $path ) ) {

			if ( ! WP_Filesystem() ) {
				$error_message = __( 'Unable to initialise the WordPress filesystem. Cannot create child theme.', 'conjurewp' );
				$this->logger->error( $error_message );

				wp_send_json_error(
					array(
						'message' => esc_html( $error_message ),
					)
				);
			}

			global $wp_filesystem;

			if ( ! $wp_filesystem ) {
				$error_message = __( 'WordPress filesystem is not available. Cannot create child theme.', 'conjurewp' );
				$this->logger->error( $error_message );

				wp_send_json_error(
					array(
						'message' => esc_html( $error_message ),
					)
				);
			}

			$mkdir_result = $wp_filesystem->mkdir( $path );
			if ( ! $mkdir_result ) {
				$error_message = sprintf(
					/* translators: %s: directory path */
					__( 'Unable to create child theme directory: %s', 'conjurewp' ),
					$path
				);
				$this->logger->error( $error_message );

				wp_send_json_error(
					array(
						'message' => esc_html( $error_message ),
					)
				);
			}

			$style_result = $wp_filesystem->put_contents( $path . '/style.css', $this->generate_child_style_css( $parent_slug, $parent_theme->name, $parent_theme->author, $parent_theme->version ) );
			if ( ! $style_result ) {
				$error_message = __( 'Unable to create child theme style.css file.', 'conjurewp' );
				$this->logger->error( $error_message );

				wp_send_json_error(
					array(
						'message' => esc_html( $error_message ),
					)
				);
			}

			$functions_result = $wp_filesystem->put_contents( $path . '/functions.php', $this->generate_child_functions_php( $parent_slug ) );
			if ( ! $functions_result ) {
				$error_message = __( 'Unable to create child theme functions.php file.', 'conjurewp' );
				$this->logger->error( $error_message );

				wp_send_json_error(
					array(
						'message' => esc_html( $error_message ),
					)
				);
			}

			$this->generate_child_screenshot( $path );

			$allowed_themes          = get_option( 'allowedthemes' );
			$allowed_themes[ $slug ] = true;
			update_option( 'allowedthemes', $allowed_themes );

		} else {

			if ( $this->conjure->theme->template !== $slug ) :
				update_option( 'conjure_' . $this->conjure->slug . '_child', $name );
				switch_theme( $slug );
			endif;

			$this->logger->debug( __( 'The existing child theme was activated', 'conjurewp' ) );

			wp_send_json(
				array(
					'done'    => 1,
					'message' => sprintf(
						esc_html( $success ),
						$slug
					),
				)
			);
		}

		if ( $this->conjure->theme->template !== $slug ) :
			update_option( 'conjure_' . $this->conjure->slug . '_child', $name );
			switch_theme( $slug );
		endif;

		$this->logger->debug( __( 'A new child theme was created and activated', 'conjurewp' ) );

		wp_send_json(
			array(
				'done'    => 1,
				'message' => sprintf(
					esc_html( $success ),
					$theme = $name
				),
			)
		);
	}

	/**
	 * Content template for the child theme functions.php file.
	 *
	 * @param string $slug Parent theme slug.
	 * @return string
	 */
	public function generate_child_functions_php( $slug ) {

		// Strip any existing '-child' suffix to prevent child_child_child issues.
		$clean_slug = preg_replace( '/-child$/', '', $slug );
		$slug_no_hyphens = strtolower( preg_replace( '#[^a-zA-Z]#', '', $clean_slug ) );

		$output = "
			<?php
			/**
			 * Theme functions and definitions.
			 * This child theme was generated by Conjure WP.
			 *
			 * @link https://developer.wordpress.org/themes/basics/theme-functions/
			 */

			/*
			 * If your child theme has more than one .css file (eg. ie.css, style.css, main.css) then
			 * you will have to make sure to maintain all of the parent theme dependencies.
			 *
			 * Make sure you're using the correct handle for loading the parent theme's styles.
			 * Failure to use the proper tag will result in a CSS file needlessly being loaded twice.
			 * This will usually not affect the site appearance, but it's inefficient and extends your page's loading time.
			 *
			 * @link https://developer.wordpress.org/themes/advanced-topics/child-themes/
			 */
			function {$slug_no_hyphens}_child_enqueue_styles() {
			    wp_enqueue_style( '{$clean_slug}-style' , get_template_directory_uri() . '/style.css' );
			    wp_enqueue_style( '{$clean_slug}-child-style',
			        get_stylesheet_directory_uri() . '/style.css',
			        array( '{$clean_slug}-style' ),
			        wp_get_theme()->get('Version')
			    );
			}

			add_action(  'wp_enqueue_scripts', '{$slug_no_hyphens}_child_enqueue_styles' );\n
		";

		// Let's remove the tabs so that it displays nicely.
		$output = trim( preg_replace( '/\t+/', '', $output ) );

		$this->logger->debug( __( 'The child theme functions.php content was generated', 'conjurewp' ) );

		// Filterable return.
		return apply_filters( 'conjure_generate_child_functions_php', $output, $slug );
	}

	/**
	 * Content template for the child theme style.css file.
	 *
	 * @param string $slug              Parent theme slug.
	 * @param string $parent_theme_name Parent theme name.
	 * @param string $author            Parent theme author.
	 * @param string $version           Parent theme version.
	 * @return string
	 */
	public function generate_child_style_css( $slug, $parent_theme_name, $author, $version ) {

		$output = "
			/**
			* Theme Name: {$parent_theme_name} Child
			* Description: This is a child theme of {$parent_theme_name}, generated by Conjure WP.
			* Author: {$author}
			* Template: {$slug}
			* Version: {$version}
			*/\n
		";

		// Let's remove the tabs so that it displays nicely.
		$output = trim( preg_replace( '/\t+/', '', $output ) );

		$this->logger->debug( __( 'The child theme style.css content was generated', 'conjurewp' ) );

		return apply_filters( 'conjure_generate_child_style_css', $output, $slug, $parent_theme_name, $version );
	}

	/**
	 * Generate child theme screenshot file.
	 *
	 * @param string $path Child theme path.
	 */
	public function generate_child_screenshot( $path ) {

		$screenshot = apply_filters( 'conjure_generate_child_screenshot', '' );

		if ( ! empty( $screenshot ) ) {
			// Get custom screenshot file extension.
			if ( '.png' === substr( $screenshot, -4 ) ) {
				$screenshot_ext = 'png';
			} else {
				$screenshot_ext = 'jpg';
			}
		} else {
			if ( file_exists( $this->conjure->base_path . '/screenshot.png' ) ) {
				$screenshot     = $this->conjure->base_path . '/screenshot.png';
				$screenshot_ext = 'png';
			} elseif ( file_exists( $this->conjure->base_path . '/screenshot.jpg' ) ) {
				$screenshot     = $this->conjure->base_path . '/screenshot.jpg';
				$screenshot_ext = 'jpg';
			}
		}

		if ( ! empty( $screenshot ) && file_exists( $screenshot ) ) {
			$copied = copy( $screenshot, $path . '/screenshot.' . $screenshot_ext );

			$this->logger->debug( __( 'The child theme screenshot was copied to the child theme, with the following result', 'conjurewp' ), array( 'copied' => $copied ) );
		} else {
			$this->logger->debug( __( 'The child theme screenshot was not generated, because of these results', 'conjurewp' ), array( 'screenshot' => $screenshot ) );
		}
	}
}

