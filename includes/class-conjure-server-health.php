<?php
/**
 * Server Health Check
 *
 * @package   Conjure
 * @author    ConjureWP
 * @license   GPL-3.0
 * @link      https://conjurewp.com
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Server health check functionality.
 */
class Conjure_Server_Health {

	/**
	 * Minimum required PHP memory limit (in MB).
	 *
	 * @var int
	 */
	private $min_memory_limit = 512;

	/**
	 * Minimum required PHP max execution time (in seconds).
	 *
	 * @var int
	 */
	private $min_execution_time = 40000;

	/**
	 * Constructor.
	 *
	 * @param int $min_memory      Optional. Minimum memory limit in MB.
	 * @param int $min_execution   Optional. Minimum execution time in seconds.
	 */
	public function __construct( $min_memory = 512, $min_execution = 40000 ) {
		// Validate and sanitize inputs.
		$this->min_memory_limit   = absint( $min_memory );
		$this->min_execution_time = absint( $min_execution );

		// Ensure minimum values are set.
		if ( $this->min_memory_limit < 1 ) {
			$this->min_memory_limit = 512;
		}
		if ( $this->min_execution_time < 1 ) {
			$this->min_execution_time = 40000;
		}
	}

	/**
	 * Check if server meets requirements.
	 *
	 * @return bool True if requirements are met, false otherwise.
	 */
	public function meets_requirements() {
		$memory_limit = $this->get_memory_limit_value();
		$max_execution = $this->get_max_execution_value();

		return ( $memory_limit >= $this->min_memory_limit && $max_execution >= $this->min_execution_time );
	}

	/**
	 * Get the PHP memory limit value in MB.
	 *
	 * @return int Memory limit in MB.
	 */
	public function get_memory_limit_value() {
		$memory_limit = ini_get( 'memory_limit' );
		
		if ( ! $memory_limit ) {
			return 0;
		}

		// Convert to MB if needed.
		if ( is_numeric( $memory_limit ) ) {
			return (int) $memory_limit;
		}

		$memory_limit = strtoupper( $memory_limit );
		
		if ( strpos( $memory_limit, 'G' ) !== false ) {
			return (int) $memory_limit * 1024;
		} elseif ( strpos( $memory_limit, 'M' ) !== false ) {
			return (int) $memory_limit;
		} elseif ( strpos( $memory_limit, 'K' ) !== false ) {
			return (int) $memory_limit / 1024;
		}

		return (int) $memory_limit;
	}

	/**
	 * Get the PHP max execution time value in seconds.
	 *
	 * @return int Max execution time in seconds.
	 */
	public function get_max_execution_value() {
		$max_execution = ini_get( 'max_execution_time' );
		
		if ( ! $max_execution ) {
			return 0;
		}

		return (int) $max_execution;
	}

	/**
	 * Get formatted PHP memory limit.
	 *
	 * @return string Formatted memory limit.
	 */
	public function get_memory_limit() {
		$memory_limit = ini_get( 'memory_limit' );

		if ( ! $memory_limit ) {
			return __( 'N/A', 'conjure-wp' );
		}

		return $memory_limit;
	}

	/**
	 * Get formatted PHP memory limit with status.
	 *
	 * @return string Formatted memory limit with HTML.
	 */
	public function get_memory_limit_html() {
		$memory_limit = $this->get_memory_limit();
		$memory_value = $this->get_memory_limit_value();

		if ( $memory_value < $this->min_memory_limit ) {
			return '<span class="below-req">' . esc_html( $memory_limit ) . '</span>';
		}

		return '<span class="meets-req">' . esc_html( $memory_limit ) . '</span>';
	}

	/**
	 * Get formatted PHP max execution time.
	 *
	 * @return string Formatted max execution time.
	 */
	public function get_max_execution_time() {
		$max_execution = ini_get( 'max_execution_time' );

		if ( ! $max_execution ) {
			return __( 'N/A', 'conjure-wp' );
		}

		return $max_execution . 's';
	}

	/**
	 * Get formatted PHP max execution time with status.
	 *
	 * @return string Formatted max execution time with HTML.
	 */
	public function get_max_execution_time_html() {
		$max_execution = ini_get( 'max_execution_time' );

		if ( ! $max_execution ) {
			return '<span class="below-req">' . esc_html__( 'N/A', 'conjure-wp' ) . '</span>';
		}

		if ( $max_execution < $this->min_execution_time ) {
			return '<span class="below-req">' . esc_html( $max_execution ) . 's</span>';
		}

		return '<span class="meets-req">' . esc_html( $max_execution ) . 's</span>';
	}

	/**
	 * Get MySQL version.
	 *
	 * @return string MySQL version.
	 */
	public function get_mysql_version() {
		global $wpdb;

		// Suppress errors and use WordPress DB version as fallback.
		$version = $wpdb->get_var( $wpdb->prepare( 'SELECT VERSION()' ) );

		if ( empty( $version ) ) {
			// Fallback to WordPress database version.
			$version = $wpdb->db_version();
		}

		// Sanitize output.
		return $version ? sanitize_text_field( $version ) : __( 'N/A', 'conjure-wp' );
	}

	/**
	 * Format bytes into human-readable format.
	 *
	 * @param int|string $raw_size Size in bytes.
	 * @return string Formatted size.
	 */
	public function format_filesize( $raw_size ) {
		// Validate input.
		if ( ! is_numeric( $raw_size ) || $raw_size < 0 ) {
			return __( 'unknown', 'conjure-wp' );
		}

		$raw_size = absint( $raw_size );

		if ( $raw_size / 1099511627776 > 1 ) {
			return number_format_i18n( $raw_size / 1099511627776, 1 ) . ' ' . __( 'TiB', 'conjure-wp' );
		} elseif ( $raw_size / 1073741824 > 1 ) {
			return number_format_i18n( $raw_size / 1073741824, 1 ) . ' ' . __( 'GiB', 'conjure-wp' );
		} elseif ( $raw_size / 1048576 > 1 ) {
			return number_format_i18n( $raw_size / 1048576, 1 ) . ' ' . __( 'MiB', 'conjure-wp' );
		} elseif ( $raw_size / 1024 > 1 ) {
			return number_format_i18n( $raw_size / 1024, 1 ) . ' ' . __( 'KiB', 'conjure-wp' );
		} elseif ( $raw_size > 1 ) {
			return number_format_i18n( $raw_size, 0 ) . ' ' . __( 'bytes', 'conjure-wp' );
		}

		return __( 'unknown', 'conjure-wp' );
	}

	/**
	 * Get server health information array.
	 *
	 * @return array Server health data.
	 */
	public function get_health_info() {
		return array(
			'meets_requirements' => $this->meets_requirements(),
			'memory_limit'       => $this->get_memory_limit(),
			'memory_limit_value' => $this->get_memory_limit_value(),
			'max_execution'      => $this->get_max_execution_time(),
			'max_execution_value' => $this->get_max_execution_value(),
			'mysql_version'      => $this->get_mysql_version(),
		);
	}

	/**
	 * Render server health check HTML.
	 *
	 * @param array $args Optional. Arguments to customize output.
	 * @return string HTML output.
	 */
	public function render_health_check( $args = array() ) {
		$defaults = array(
			'show_title'       => true,
			'title'            => __( 'Server Health', 'conjure-wp' ),
			'requirements_url' => '',
			'theme_name'       => '',
		);

		$args = wp_parse_args( $args, $defaults );
		$meets_requirements = $this->meets_requirements();

		ob_start();
		?>
		<div id="server-health-info">
			<?php if ( $args['show_title'] ) : ?>
				<h3><?php echo esc_html( $args['title'] ); ?></h3>
				<span id="health-meter" class="<?php echo $meets_requirements ? 'meets-requirements' : 'does-not-meet-requirements'; ?>"></span>
			<?php endif; ?>

			<?php if ( $meets_requirements ) : ?>
				<p id="check-req">
					<strong><?php esc_html_e( 'Meets Requirements', 'conjure-wp' ); ?></strong>, 
					<?php esc_html_e( 'setup & import functions will operate smoothly.', 'conjure-wp' ); ?>
				</p>
			<?php else : ?>
				<p id="check-req">
					<strong><?php esc_html_e( 'Does not meet requirements', 'conjure-wp' ); ?></strong><br />
					<?php
					if ( ! empty( $args['requirements_url'] ) && filter_var( $args['requirements_url'], FILTER_VALIDATE_URL ) ) {
						echo wp_kses(
							sprintf(
								/* translators: %s: link to theme requirements documentation */
								__( 'You may see timeout issues resulting in a broken demo import. Before you proceed please check the %s.', 'conjure-wp' ),
								'<a href="' . esc_url( $args['requirements_url'] ) . '" target="_blank" rel="noopener noreferrer"><em>' . esc_html__( 'theme requirements', 'conjure-wp' ) . '</em></a>'
							),
							array(
								'a' => array(
									'href'   => array(),
									'target' => array(),
									'rel'    => array(),
								),
								'em' => array(),
							)
						);
					} else {
						esc_html_e( 'You may see timeout issues resulting in a broken demo import.', 'conjure-wp' );
					}
					?>
				</p>
			<?php endif; ?>

			<ul class="server-info">
				<li>
					<span class="server-feature"><?php esc_html_e( 'PHP Memory Limit', 'conjure-wp' ); ?></span>
					<span class="server-value"><?php echo wp_kses_post( $this->get_memory_limit_html() ); ?></span>
				</li>
				<li>
					<span class="server-feature"><?php esc_html_e( 'PHP Max Execution Time', 'conjure-wp' ); ?></span>
					<span class="server-value"><?php echo wp_kses_post( $this->get_max_execution_time_html() ); ?></span>
				</li>
			</ul>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get inline CSS for server health display.
	 *
	 * @return string CSS styles.
	 */
	public function get_health_check_styles() {
		return '
		<style>
			#server-health-info {
				background: #f8f9fd;
				padding: 12px 15px !important;
				border-radius: 3px;
				margin-bottom: 15px !important;
				font-size: 11px !important;
			}
			#server-health-info h3 {
				display: inline-block;
				margin: 0 0 6px 0 !important;
				font-weight: 500 !important;
				font-size: 11px !important;
				text-transform: uppercase;
				letter-spacing: 0.5px;
				color: #555 !important;
			}
			#server-health-info p {
				font-size: 10px !important;
				line-height: 1.4 !important;
				margin: 6px 0 !important;
			}
			#health-meter {
				display: inline-block;
				position: relative;
				top: 0px;
				height: 5px;
				width: 5px;
				margin: 0 0 0 5px;
				border-radius: 5px;
			}
			#health-meter.does-not-meet-requirements {
				background-color: #bc0000;
			}
			#health-meter.meets-requirements {
				background-color: #7faf1b;
			}
			.below-req {
				color: #bc0000 !important;
				font-size: 10px !important;
			}
			.meets-req {
				color: #7faf1b !important;
				font-size: 10px !important;
			}
			.server-info {
				margin-bottom: 0 !important;
				list-style: none;
				padding: 0 !important;
				font-size: 10px !important;
			}
			.server-info li {
				clear: both;
				overflow: hidden;
				margin-bottom: 3px !important;
				font-size: 10px !important;
			}
			.server-info li::after {
				content: "";
				display: table;
				clear: both;
			}
			.server-feature {
				float: left;
				font-size: 10px !important;
			}
			.server-value {
				float: right;
				font-size: 10px !important;
				font-weight: 600;
			}
		</style>
		';
	}

	/**
	 * Render complete server health check with styles.
	 *
	 * @param array $args Optional. Arguments to customize output.
	 */
	public function render_complete( $args = array() ) {
		echo $this->get_health_check_styles(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->render_health_check( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

