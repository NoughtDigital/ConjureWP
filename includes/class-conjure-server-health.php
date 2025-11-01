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
	private $min_memory_limit = 256;

	/**
	 * Minimum required PHP max execution time (in seconds).
	 *
	 * @var int
	 */
	private $min_execution_time = 300;

	/**
	 * Whether health checks are enabled.
	 *
	 * @var bool
	 */
	private $enabled = true;

	/**
	 * Constructor.
	 *
	 * @param int $min_memory      Optional. Minimum memory limit in MB.
	 * @param int $min_execution   Optional. Minimum execution time in seconds.
	 */
	public function __construct( $min_memory = 256, $min_execution = 300 ) {
		// Check if health checks are disabled via filter.
		$this->enabled = apply_filters( 'conjure_server_health_enabled', true );

		// Allow themes/plugins to override default requirements.
		$min_memory = apply_filters( 'conjure_server_health_min_memory', $min_memory );
		$min_execution = apply_filters( 'conjure_server_health_min_execution', $min_execution );

		// Validate and sanitize inputs.
		$this->min_memory_limit   = absint( $min_memory );
		$this->min_execution_time = absint( $min_execution );

		// Ensure minimum values are set (lower defaults for more realistic thresholds).
		if ( $this->min_memory_limit < 1 ) {
			$this->min_memory_limit = 256;
		}
		if ( $this->min_execution_time < 1 ) {
			$this->min_execution_time = 300;
		}
	}

	/**
	 * Check if health checks are enabled.
	 *
	 * @return bool True if enabled, false otherwise.
	 */
	public function is_enabled() {
		return $this->enabled;
	}

	/**
	 * Get minimum required memory limit.
	 *
	 * @return int Minimum memory limit in MB.
	 */
	public function get_min_memory_limit() {
		return $this->min_memory_limit;
	}

	/**
	 * Get minimum required execution time.
	 *
	 * @return int Minimum execution time in seconds.
	 */
	public function get_min_execution_time() {
		return $this->min_execution_time;
	}

	/**
	 * Check if server meets requirements.
	 *
	 * @return bool True if requirements are met, false otherwise.
	 */
	public function meets_requirements() {
		// If health checks are disabled, always return true.
		if ( ! $this->enabled ) {
			return true;
		}

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
			return __( 'N/A', 'conjurewp' );
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
			return __( 'N/A', 'conjurewp' );
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
			return '<span class="below-req">' . esc_html__( 'N/A', 'conjurewp' ) . '</span>';
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
		$version = $wpdb->get_var( 'SELECT VERSION()' );

		if ( empty( $version ) ) {
			// Fallback to WordPress database version.
			$version = $wpdb->db_version();
		}

		// Sanitize output.
		return $version ? sanitize_text_field( $version ) : __( 'N/A', 'conjurewp' );
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
			return __( 'unknown', 'conjurewp' );
		}

		$raw_size = absint( $raw_size );

		if ( $raw_size / 1099511627776 > 1 ) {
			return number_format_i18n( $raw_size / 1099511627776, 1 ) . ' ' . __( 'TiB', 'conjurewp' );
		} elseif ( $raw_size / 1073741824 > 1 ) {
			return number_format_i18n( $raw_size / 1073741824, 1 ) . ' ' . __( 'GiB', 'conjurewp' );
		} elseif ( $raw_size / 1048576 > 1 ) {
			return number_format_i18n( $raw_size / 1048576, 1 ) . ' ' . __( 'MiB', 'conjurewp' );
		} elseif ( $raw_size / 1024 > 1 ) {
			return number_format_i18n( $raw_size / 1024, 1 ) . ' ' . __( 'KiB', 'conjurewp' );
		} elseif ( $raw_size > 1 ) {
			return number_format_i18n( $raw_size, 0 ) . ' ' . __( 'bytes', 'conjurewp' );
		}

		return __( 'unknown', 'conjurewp' );
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
		// If health checks are disabled, return empty string.
		if ( ! $this->enabled ) {
			return '';
		}

		$defaults = array(
			'show_title'       => true,
			'title'            => __( 'Server Health Check', 'conjurewp' ),
			'requirements_url' => '',
			'theme_name'       => '',
		);

		$args = wp_parse_args( $args, $defaults );
		$meets_requirements = $this->meets_requirements();

		ob_start();
		?>
		<div id="server-health-info">
			<?php if ( $args['show_title'] ) : ?>
				<div class="server-health-header" id="server-health-header">
					<h3><?php echo esc_html( $args['title'] ); ?></h3>
					<span id="health-meter" class="<?php echo $meets_requirements ? 'meets-requirements' : 'does-not-meet-requirements'; ?>"></span>
					<span class="chevron"></span>
				</div>
			<?php endif; ?>

			<div class="server-health-content" id="server-health-content">
				<?php if ( $meets_requirements ) : ?>
					<p id="check-req">
						<strong><?php esc_html_e( 'Meets Requirements', 'conjurewp' ); ?></strong>, 
						<?php esc_html_e( 'setup & import functions will operate smoothly.', 'conjurewp' ); ?>
					</p>
			<?php else : ?>
				<p id="check-req">
					<strong><?php esc_html_e( 'Server Resources Low', 'conjurewp' ); ?></strong><br />
					<?php
					if ( ! empty( $args['requirements_url'] ) && filter_var( $args['requirements_url'], FILTER_VALIDATE_URL ) ) {
						echo wp_kses(
							sprintf(
								/* translators: %s: link to theme requirements documentation */
								__( 'Your server may experience timeout issues during import. Please review the %s to ensure a smooth setup.', 'conjurewp' ),
								'<a href="' . esc_url( $args['requirements_url'] ) . '" target="_blank" rel="noopener noreferrer"><em>' . esc_html__( 'recommended requirements', 'conjurewp' ) . '</em></a>'
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
						esc_html_e( 'Your server may experience timeout issues during import. Consider increasing PHP memory and execution time limits.', 'conjurewp' );
					}
					?>
				</p>
			<?php endif; ?>

				<?php
				$memory_limit = $this->get_memory_limit_value();
				$max_execution = $this->get_max_execution_value();
				$mysql_version = $this->get_mysql_version();
				?>
				<ul class="server-info">
					<li>
						<span class="server-feature"><?php esc_html_e( 'PHP Memory Limit', 'conjurewp' ); ?></span>
						<span class="server-value health-metric-value-memory" data-current="<?php echo esc_attr( $memory_limit ); ?>" data-min="<?php echo esc_attr( $this->min_memory_limit ); ?>">
							<?php echo wp_kses_post( $this->get_memory_limit_html() ); ?>
						</span>
					</li>
					<li>
						<span class="server-feature"><?php esc_html_e( 'PHP Max Execution Time', 'conjurewp' ); ?></span>
						<span class="server-value health-metric-value-execution" data-current="<?php echo esc_attr( $max_execution ); ?>" data-min="<?php echo esc_attr( $this->min_execution_time ); ?>">
							<?php echo wp_kses_post( $this->get_max_execution_time_html() ); ?>
						</span>
					</li>
					<li>
						<span class="server-feature"><?php esc_html_e( 'MySQL Version', 'conjurewp' ); ?></span>
						<span class="server-value health-metric-value-mysql">
							<span class="meets-req"><?php echo esc_html( $mysql_version ); ?></span>
						</span>
					</li>
				</ul>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get inline CSS for server health display.
	 *
	 * @deprecated Styles have been moved to SCSS.
	 * @return string Empty string.
	 */
	public function get_health_check_styles() {
		// Styles are now in assets/scss/modules/_server-health.scss
		return '';
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

	/**
	 * Get detected bottlenecks.
	 *
	 * @return array Array of bottleneck identifiers.
	 */
	public function get_bottlenecks() {
		$bottlenecks = array();
		
		if ( ! $this->enabled ) {
			return $bottlenecks;
		}

		$memory_limit = $this->get_memory_limit_value();
		$max_execution = $this->get_max_execution_value();

		if ( $memory_limit < $this->min_memory_limit ) {
			$bottlenecks[] = 'memory';
		}

		if ( $max_execution < $this->min_execution_time ) {
			$bottlenecks[] = 'execution_time';
		}

		return apply_filters( 'conjure_server_health_bottlenecks', $bottlenecks, $this );
	}

	/**
	 * Get remediation links based on detected bottlenecks.
	 *
	 * @return array Array of remediation links with title and URL.
	 */
	public function get_remediation_links() {
		$bottlenecks = $this->get_bottlenecks();
		$links = array();

		if ( empty( $bottlenecks ) ) {
			return $links;
		}

		foreach ( $bottlenecks as $bottleneck ) {
			switch ( $bottleneck ) {
				case 'memory':
					$links[] = array(
						'title' => __( 'How to increase PHP memory limit', 'conjurewp' ),
						'url'   => 'https://wordpress.org/documentation/article/editing-wp-config-php/#increase-memory-allocated-to-php',
						'type'  => 'memory',
					);
					break;

				case 'execution_time':
					$links[] = array(
						'title' => __( 'How to increase PHP max execution time', 'conjurewp' ),
						'url'   => 'https://wordpress.org/support/article/common-wordpress-errors/#maximum-execution-time-exceeded',
						'type'  => 'execution_time',
					);
					break;
			}
		}

		return apply_filters( 'conjure_server_health_remediation_links', $links, $bottlenecks, $this );
	}

	/**
	 * Get health metrics for telemetry display.
	 *
	 * @return array Health metrics data.
	 */
	public function get_telemetry_metrics() {
		if ( ! $this->enabled ) {
			return array(
				'enabled' => false,
			);
		}

		$memory_limit = $this->get_memory_limit_value();
		$max_execution = $this->get_max_execution_value();
		$bottlenecks = $this->get_bottlenecks();
		$remediation_links = $this->get_remediation_links();

		return array(
			'enabled'            => true,
			'meets_requirements' => $this->meets_requirements(),
			'memory_limit'       => array(
				'value'      => $memory_limit,
				'formatted'  => $this->get_memory_limit(),
				'meets_req'  => $memory_limit >= $this->min_memory_limit,
				'min_required' => $this->min_memory_limit,
			),
			'max_execution'      => array(
				'value'      => $max_execution,
				'formatted'  => $this->get_max_execution_time(),
				'meets_req'  => $max_execution >= $this->min_execution_time,
				'min_required' => $this->min_execution_time,
			),
			'mysql_version'      => $this->get_mysql_version(),
			'bottlenecks'        => $bottlenecks,
			'remediation_links'  => $remediation_links,
		);
	}

	/**
	 * Render simple server info for drawer display.
	 *
	 * @return string HTML output for drawer.
	 */
	public function render_drawer_telemetry() {
		// Removed - server info is now only displayed in the server-health-info section.
		return '';
	}
}

