<?php
/**
 * Wizard UI rendering class
 *
 * Handles all wizard user interface rendering and display logic.
 *
 * @package   Conjure WP
 * @version   1.0.0
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
 * Conjure Wizard UI class.
 */
class Conjure_Wizard_UI {

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
	 * Step manager instance.
	 *
	 * @var Conjure_Step_Manager
	 */
	protected $step_manager;

	/**
	 * Constructor.
	 *
	 * @param Conjure              $conjure Main Conjure instance.
	 * @param Conjure_Step_Manager $step_manager Step manager instance.
	 */
	public function __construct( $conjure, $step_manager ) {
		$this->conjure      = $conjure;
		$this->logger       = $conjure->logger;
		$this->step_manager = $step_manager;
	}

	/**
	 * Output the header.
	 */
	public function header() {
		// Strings passed in from the config file.
		$strings = $this->conjure->strings;

		// Get the current step.
		$current_step = '';
		if ( ! empty( $this->conjure->step ) && isset( $this->conjure->steps[ $this->conjure->step ] ) && isset( $this->conjure->steps[ $this->conjure->step ]['name'] ) ) {
			$current_step = strtolower( $this->conjure->steps[ $this->conjure->step ]['name'] );
		}

		// Set the current screen to prevent "get_current_screen called incorrectly" notices.
		if ( ! empty( $this->conjure->hook_suffix ) ) {
			set_current_screen( $this->conjure->hook_suffix );
		}
		?>

		<!DOCTYPE html>
		<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
		<head>
			<meta name="viewport" content="width=device-width"/>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
			<?php printf( esc_html( $strings['title%s%s%s%s'] ), '<ti', 'tle>', esc_html( $this->conjure->theme->name ), '</title>' ); ?>
			<?php do_action( 'admin_print_styles' ); ?>
			<?php do_action( 'admin_print_scripts' ); ?>
			<?php do_action( 'admin_head' ); ?>
		</head>
		<body class="conjure__body conjure__body--<?php echo esc_attr( $current_step ); ?>">
		<?php
	}

	/**
	 * Output the content for the current step.
	 */
	public function body() {
		if ( empty( $this->conjure->step ) || ! isset( $this->conjure->steps[ $this->conjure->step ] ) ) {
			$this->logger->error( sprintf( __( 'Invalid step requested: %s', 'conjurewp' ), $this->conjure->step ) );
			return;
		}

		if ( ! isset( $this->conjure->steps[ $this->conjure->step ]['view'] ) || ! is_callable( $this->conjure->steps[ $this->conjure->step ]['view'] ) ) {
			$this->logger->error( sprintf( __( 'Step view is not callable: %s', 'conjurewp' ), $this->conjure->step ) );
			return;
		}

		try {
			call_user_func( $this->conjure->steps[ $this->conjure->step ]['view'] );
		} catch ( Exception $e ) {
			$this->logger->error( sprintf( __( 'Error rendering step %s: %s', 'conjurewp' ), $this->conjure->step, $e->getMessage() ) );
			echo '<div class="error"><p>' . esc_html__( 'An error occurred while loading this step. Please check the error logs.', 'conjurewp' ) . '</p></div>';
		}
	}

	/**
	 * Output the footer.
	 */
	public function footer() {
		?>
		</body>
		<?php do_action( 'admin_footer' ); ?>
		<?php do_action( 'admin_print_footer_scripts' ); ?>
		</html>
		<?php
	}

	/**
	 * Output the steps navigation.
	 */
	public function step_output() {
		$ouput_steps  = $this->conjure->steps;
		$array_keys   = array_keys( $this->conjure->steps );
		$current_step = array_search( $this->conjure->step, $array_keys, true );

		array_shift( $ouput_steps );
		?>

		<ol class="dots">

			<?php
			foreach ( $ouput_steps as $step_key => $step ) :

				$class_attr = '';
				$show_link  = false;

				if ( $step_key === $this->conjure->step ) {
					$class_attr = 'active';
				} elseif ( $current_step > array_search( $step_key, $array_keys, true ) ) {
					$class_attr = 'done';
					$show_link  = true;
				}
				?>

				<li class="<?php echo esc_attr( $class_attr ); ?>">
					<a href="<?php echo esc_url( $this->step_link( $step_key ) ); ?>" title="<?php echo esc_attr( $step['name'] ); ?>"></a>
				</li>

			<?php endforeach; ?>

		</ol>

		<?php
	}

	/**
	 * Get the step URL.
	 *
	 * @param string $step Name of the step, appended to the URL.
	 * @return string
	 */
	public function step_link( $step ) {
		return add_query_arg( 'step', $step );
	}

	/**
	 * Get the next step link.
	 *
	 * @return string
	 */
	public function step_next_link() {
		$keys = array_keys( $this->conjure->steps );
		$step = array_search( $this->conjure->step, $keys, true ) + 1;

		return add_query_arg( 'step', $keys[ $step ] );
	}

	/**
	 * SVG sprite.
	 */
	public function svg_sprite() {
		// Define SVG sprite file.
		$svg = trailingslashit( $this->conjure->base_path ) . $this->conjure->directory . '/assets/images/sprite.svg';

		// If it exists, include it.
		if ( file_exists( $svg ) ) {
			require_once apply_filters( 'conjure_svg_sprite', $svg );
		}
	}

	/**
	 * Return SVG markup.
	 *
	 * @param array $args {
	 *     Parameters needed to display an SVG.
	 *
	 *     @type string $icon  Required SVG icon filename.
	 *     @type string $title Optional SVG title.
	 *     @type string $desc  Optional SVG description.
	 * }
	 * @return string SVG markup.
	 */
	public function svg( $args = array() ) {

		// Make sure $args are an array.
		if ( empty( $args ) ) {
			return __( 'Please define default parameters in the form of an array.', 'conjurewp' );
		}

		// Define an icon.
		if ( false === array_key_exists( 'icon', $args ) ) {
			return __( 'Please define an SVG icon filename.', 'conjurewp' );
		}

		// Set defaults.
		$defaults = array(
			'icon'        => '',
			'title'       => '',
			'desc'        => '',
			'aria_hidden' => true, // Hide from screen readers.
			'fallback'    => false,
		);

		// Parse args.
		$args = wp_parse_args( $args, $defaults );

		// Set aria hidden.
		$aria_hidden = '';

		if ( true === $args['aria_hidden'] ) {
			$aria_hidden = ' aria-hidden="true"';
		}

		// Set ARIA.
		$aria_labelledby = '';

		if ( $args['title'] && $args['desc'] ) {
			$aria_labelledby = ' aria-labelledby="title desc"';
		}

		// Begin SVG markup.
		$svg = '<svg class="icon icon--' . esc_attr( $args['icon'] ) . '"' . $aria_hidden . $aria_labelledby . ' role="img">';

		// If there is a title, display it.
		if ( $args['title'] ) {
			$svg .= '<title>' . esc_html( $args['title'] ) . '</title>';
		}

		// If there is a description, display it.
		if ( $args['desc'] ) {
			$svg .= '<desc>' . esc_html( $args['desc'] ) . '</desc>';
		}

		$svg .= '<use xlink:href="#icon-' . esc_html( $args['icon'] ) . '"></use>';

		// Add some markup to use as a fallback for browsers that do not support SVGs.
		if ( $args['fallback'] ) {
			$svg .= '<span class="svg-fallback icon--' . esc_attr( $args['icon'] ) . '"></span>';
		}

		$svg .= '</svg>';

		return $svg;
	}

	/**
	 * Allowed HTML for sprites.
	 *
	 * @return array
	 */
	public function svg_allowed_html() {

		$array = array(
			'svg' => array(
				'class'       => array(),
				'aria-hidden' => array(),
				'role'        => array(),
			),
			'use' => array(
				'xlink:href' => array(),
			),
		);

		return apply_filters( 'conjure_svg_allowed_html', $array );
	}

	/**
	 * Loading spinner.
	 */
	public function loading_spinner() {

		// Define the spinner file.
		$spinner = trailingslashit( $this->conjure->base_path ) . $this->conjure->directory . '/assets/images/spinner.php';

		// Retrieve the spinner.
		$spinner = apply_filters( 'conjure_loading_spinner', $spinner );

		if ( file_exists( $spinner ) ) {
			include $spinner;
		}
	}

	/**
	 * Allowed HTML for the loading spinner.
	 *
	 * @return array
	 */
	public function loading_spinner_allowed_html() {

		$array = array(
			'span' => array(
				'class' => array(),
			),
			'cite' => array(
				'class' => array(),
			),
		);

		return apply_filters( 'conjure_loading_spinner_allowed_html', $array );
	}
}

