<?php
/**
 * Admin tools for ConjureWP - allows viewing and managing log files.
 *
 * @package ConjureWP
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin tools class for ConjureWP.
 */
class Conjure_Admin_Tools {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_tools_page' ), 99 );
		add_action( 'admin_init', array( $this, 'handle_log_actions' ) );
		add_action( 'admin_notices', array( $this, 'show_theme_plugin_validation_notice' ) );
	}

	/**
	 * Add tools submenu page.
	 */
	public function add_tools_page() {
		add_submenu_page(
			'tools.php',
			__( 'ConjureWP Logs', 'ConjureWP' ),
			__( 'ConjureWP Logs', 'ConjureWP' ),
			'manage_options',
			'ConjureWP-logs',
			array( $this, 'render_logs_page' )
		);
	}

	/**
	 * Handle log file actions (clear, download).
	 */
	public function handle_log_actions() {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'ConjureWP-logs' !== $page ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$logger = Conjure_Logger::get_instance();
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';

		// Handle clear all logs action.
		if ( 'clear_all_logs' === $action && check_admin_referer( 'conjurewp_clear_all_logs' ) ) {
			$log_files = $logger->get_all_log_files();
			foreach ( $log_files as $log_file ) {
				if ( file_exists( $log_file ) ) {
					wp_delete_file( $log_file );
				}
			}
			add_action( 'admin_notices', array( $this, 'clear_log_notice' ) );
		}

		// Handle clear single log action.
		if ( 'clear_log' === $action && check_admin_referer( 'conjurewp_clear_log' ) ) {
			$log_path = $logger->get_log_path();

			if ( file_exists( $log_path ) ) {
				global $wp_filesystem;
				if ( ! function_exists( 'WP_Filesystem' ) ) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
				}
				WP_Filesystem();

				$wp_filesystem->put_contents( $log_path, '', FS_CHMOD_FILE );
				add_action( 'admin_notices', array( $this, 'clear_log_notice' ) );
			}
		}

		// Handle download log action.
		if ( 'download_log' === $action && check_admin_referer( 'conjurewp_download_log' ) ) {
			$log_file = isset( $_GET['file'] ) ? sanitize_text_field( wp_unslash( $_GET['file'] ) ) : '';

			if ( empty( $log_file ) ) {
				$log_path = $logger->get_log_path();
			} else {
				// Validate file is in the log directory - prevent traversal attacks.
				$log_dir  = dirname( $logger->get_log_path() );
				$log_path = $log_dir . '/' . basename( $log_file );

				// Use realpath to resolve any path traversal attempts and verify prefix.
				$real_log_path = realpath( $log_path );
				$real_log_dir  = realpath( $log_dir );

				if ( ! $real_log_path || ! $real_log_dir || 0 !== strpos( $real_log_path, $real_log_dir ) ) {
					wp_die( esc_html__( 'Invalid log file path.', 'ConjureWP' ) );
				}

				$log_path = $real_log_path;
			}

			if ( file_exists( $log_path ) ) {
				$filesystem = $this->get_filesystem();
				if ( ! $filesystem ) {
					wp_die( esc_html__( 'Unable to access filesystem.', 'ConjureWP' ) );
				}

				$contents = $filesystem->get_contents( $log_path );
				if ( false === $contents ) {
					wp_die( esc_html__( 'Unable to read log file.', 'ConjureWP' ) );
				}

				$safe_contents = wp_kses( $contents, array() );
				header( 'Content-Type: text/plain' );
				header( 'Content-Disposition: attachment; filename="ConjureWP-' . esc_attr( basename( $log_path ) ) . '"' );
				header( 'Content-Length: ' . (string) strlen( $safe_contents ) );
				echo $safe_contents;
				exit;
			}
		}
	}

	/**
	 * Display clear log notice.
	 */
	public function clear_log_notice() {
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Log file has been cleared successfully.', 'ConjureWP' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Show theme plugin validation notice on Conjure pages.
	 */
	public function show_theme_plugin_validation_notice() {
		// Only show on Conjure-related pages.
		$screen = get_current_screen();
		if ( ! $screen || ( strpos( $screen->id, 'conjure' ) === false && $screen->id !== 'tools_page_ConjureWP-logs' ) ) {
			return;
		}

		// Check if theme has bundled plugins.
		$plugin_dir = Conjure_Theme_Plugins::get_theme_plugin_dir();
		if ( ! $plugin_dir ) {
			return;
		}

		// Get config and validate.
		$config_file = trailingslashit( $plugin_dir ) . 'plugins.json';
		if ( ! file_exists( $config_file ) ) {
			return;
		}

		// Only validate once per day to avoid performance impact.
		$transient_key = 'conjurewp_theme_plugin_validation';
		$cached_result = get_transient( $transient_key );

		if ( false === $cached_result ) {
			$validation_result = Conjure_Theme_Plugins::validate_config( $config_file );
			set_transient( $transient_key, $validation_result, DAY_IN_SECONDS );
			$cached_result = $validation_result;
		}

		// Show validation result.
		if ( is_wp_error( $cached_result ) ) {
			?>
			<div class="notice notice-error">
				<p>
					<strong><?php esc_html_e( 'ConjureWP Theme Plugin Configuration Error:', 'ConjureWP' ); ?></strong>
					<?php echo esc_html( $cached_result->get_error_message() ); ?>
				</p>
			</div>
			<?php
		} else {
			// Get plugins and show summary.
			$plugins = Conjure_Theme_Plugins::get_bundled_plugins();
			$invalid_count = 0;

			foreach ( $plugins as $plugin ) {
				// Check if bundled file exists.
				if ( ! empty( $plugin['bundled'] ) && ! file_exists( $plugin['source'] ) ) {
					$invalid_count++;
				}
			}

			if ( $invalid_count > 0 ) {
				?>
				<div class="notice notice-warning">
					<p>
						<strong><?php esc_html_e( 'ConjureWP Theme Plugins Warning:', 'ConjureWP' ); ?></strong>
						<?php
						printf(
							esc_html(
								/* translators: %d: number of plugins with issues */
								_n(
									'%d plugin has invalid configuration or missing files.',
									'%d plugins have invalid configuration or missing files.',
									$invalid_count,
									'ConjureWP'
								)
							),
							esc_html( number_format_i18n( $invalid_count ) )
						);
						?>
					</p>
				</div>
				<?php
			}
		}
	}

	/**
	 * Render the logs page.
	 */
	public function render_logs_page() {
		$logger     = Conjure_Logger::get_instance();
		$log_path   = $logger->get_log_path();
		$log_files  = $logger->get_all_log_files();
		$config     = $logger->get_config();
		$total_size = 0;

		foreach ( $log_files as $file ) {
			if ( file_exists( $file ) ) {
				$total_size += filesize( $file );
			}
		}

		// Get main log file for display.
		$main_log         = ! empty( $log_files ) ? $log_files[0] : $log_path;
		$main_log_exists  = file_exists( $main_log );
		$main_log_size    = $main_log_exists ? filesize( $main_log ) : 0;
		$main_log_content = '';

		if ( $main_log_exists && $main_log_size > 0 ) {
			$main_log_content = $this->tail( $main_log, 500 );
		}

		// Map log level constants to readable names.
		$level_names = array(
			100 => 'DEBUG',
			200 => 'INFO',
			250 => 'NOTICE',
			300 => 'WARNING',
			400 => 'ERROR',
			500 => 'CRITICAL',
			550 => 'ALERT',
			600 => 'EMERGENCY',
		);

		$min_level_name = isset( $level_names[ $config['min_log_level'] ] ) ? $level_names[ $config['min_log_level'] ] : 'INFO';

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'ConjureWP Logs', 'ConjureWP' ); ?></h1>

			<div class="card">
				<h2><?php esc_html_e( 'Logger Configuration', 'ConjureWP' ); ?></h2>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Log Directory:', 'ConjureWP' ); ?></th>
						<td><code><?php echo esc_html( dirname( $log_path ) ); ?></code></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Log Rotation:', 'ConjureWP' ); ?></th>
						<td>
							<?php if ( $config['enable_rotation'] ) : ?>
								<span style="color: green;">✓ Enabled</span>
								<span style="color: #666;"> (Max <?php echo esc_html( $config['max_files'] ); ?> files, <?php echo esc_html( $config['max_file_size_mb'] ); ?> MB each)</span>
							<?php else : ?>
								<span style="color: orange;">Disabled</span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Minimum Log Level:', 'ConjureWP' ); ?></th>
						<td><strong><?php echo esc_html( $min_level_name ); ?></strong></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Total Log Files:', 'ConjureWP' ); ?></th>
						<td>
							<strong><?php echo count( $log_files ); ?></strong>
							<span style="color: #666;"> (<?php echo esc_html( size_format( $total_size ) ); ?> total)</span>
						</td>
					</tr>
				</table>
			</div>

			<?php if ( ! empty( $log_files ) ) : ?>
				<div class="card" style="margin-top: 20px;">
					<h2><?php esc_html_e( 'Log Files', 'ConjureWP' ); ?></h2>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'File Name', 'ConjureWP' ); ?></th>
								<th><?php esc_html_e( 'Size', 'ConjureWP' ); ?></th>
								<th><?php esc_html_e( 'Modified', 'ConjureWP' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'ConjureWP' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $log_files as $log_file ) : ?>
								<?php if ( file_exists( $log_file ) ) : ?>
									<?php
									$file_size     = filesize( $log_file );
									$file_modified = filemtime( $log_file );
									$file_name     = basename( $log_file );
									?>
									<tr>
										<td><code><?php echo esc_html( $file_name ); ?></code></td>
										<td><?php echo esc_html( size_format( $file_size ) ); ?></td>
										<td><?php echo esc_html( human_time_diff( $file_modified, current_time( 'timestamp' ) ) ); ?> ago</td>
										<td>
											<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'tools.php?page=ConjureWP-logs&action=download_log&file=' . urlencode( $file_name ) ), 'conjurewp_download_log' ) ); ?>" class="button button-small">
												<?php esc_html_e( 'Download', 'ConjureWP' ); ?>
											</a>
										</td>
									</tr>
								<?php endif; ?>
							<?php endforeach; ?>
						</tbody>
					</table>

					<p style="margin-top: 15px;">
						<?php if ( count( $log_files ) > 0 ) : ?>
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'tools.php?page=ConjureWP-logs&action=clear_all_logs' ), 'conjurewp_clear_all_logs' ) ); ?>" class="button button-secondary" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete ALL log files?', 'ConjureWP' ); ?>');">
								<?php esc_html_e( 'Delete All Logs', 'ConjureWP' ); ?>
							</a>
						<?php endif; ?>
					</p>
				</div>
			<?php endif; ?>

			<?php if ( $main_log_exists && $main_log_size > 0 ) : ?>
				<div class="card" style="margin-top: 20px;">
					<h2><?php esc_html_e( 'Recent Log Entries (Last 500 lines from current log)', 'ConjureWP' ); ?></h2>
					<div style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; border-radius: 3px; max-height: 600px; overflow-y: auto;">
						<pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word; font-family: monospace; font-size: 12px;"><?php echo esc_html( $main_log_content ); ?></pre>
					</div>
				</div>
			<?php endif; ?>

			<div class="card" style="margin-top: 20px;">
				<h2><?php esc_html_e( 'About ConjureWP Logging', 'ConjureWP' ); ?></h2>
				<p><?php esc_html_e( 'ConjureWP uses an advanced logging system with rotation and severity filtering to prevent large log files and reduce noise.', 'ConjureWP' ); ?></p>
				
				<h3 style="margin-top: 20px;"><?php esc_html_e( 'Features', 'ConjureWP' ); ?></h3>
				<ul style="list-style: disc; margin-left: 20px;">
					<li><strong><?php esc_html_e( 'Log Rotation:', 'ConjureWP' ); ?></strong> <?php esc_html_e( 'Automatically rotates logs to prevent large files. Configure in ConjureWP-config.php.', 'ConjureWP' ); ?></li>
					<li><strong><?php esc_html_e( 'Severity Filtering:', 'ConjureWP' ); ?></strong> <?php esc_html_e( 'Filter logs by severity (DEBUG, INFO, WARNING, ERROR, etc.) to reduce noise.', 'ConjureWP' ); ?></li>
					<li><strong><?php esc_html_e( 'Multi-file Management:', 'ConjureWP' ); ?></strong> <?php esc_html_e( 'View, download, and delete all rotated log files from this admin page.', 'ConjureWP' ); ?></li>
				</ul>

				<h3 style="margin-top: 20px;"><?php esc_html_e( 'What Gets Logged', 'ConjureWP' ); ?></h3>
				<ul style="list-style: disc; margin-left: 20px;">
					<li><?php esc_html_e( 'Plugin installations and activations', 'ConjureWP' ); ?></li>
					<li><?php esc_html_e( 'Content import progress', 'ConjureWP' ); ?></li>
					<li><?php esc_html_e( 'Child theme generation', 'ConjureWP' ); ?></li>
					<li><?php esc_html_e( 'License activation attempts', 'ConjureWP' ); ?></li>
					<li><?php esc_html_e( 'Errors, warnings, and critical issues', 'ConjureWP' ); ?></li>
				</ul>

				<h3 style="margin-top: 20px;"><?php esc_html_e( 'Configuration', 'ConjureWP' ); ?></h3>
				<p><?php echo wp_kses_post( __( 'To customize logging behavior, edit the <code>logging</code> section in <code>ConjureWP-config.php</code>. Available options:', 'ConjureWP' ) ); ?></p>
				<ul style="list-style: disc; margin-left: 20px;">
					<li><code>enable_rotation</code> - Enable/disable log rotation (default: true)</li>
					<li><code>max_files</code> - Maximum number of rotated files to keep (default: 5)</li>
					<li><code>max_file_size_mb</code> - Maximum file size in MB before rotation (default: 10)</li>
					<li><code>min_log_level</code> - Minimum severity to log: DEBUG, INFO, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY (default: INFO)</li>
				</ul>
			</div>
		</div>
		<?php
	}

	/**
	 * Read the last N lines from a file.
	 *
	 * @param string $file File path.
	 * @param int    $lines Number of lines to read.
	 * @return string File content.
	 */
	private function tail( $file, $lines = 100 ) {
		$filesystem = $this->get_filesystem();
		if ( ! $filesystem ) {
			return '';
		}

		$contents = $filesystem->get_contents( $file );
		if ( false === $contents ) {
			return '';
		}

		$line_count = absint( $lines );
		if ( 0 === $line_count ) {
			return '';
		}

		$all_lines = preg_split( "/\r\n|\r|\n/", $contents );
		$tail      = array_slice( $all_lines, -$line_count );

		return implode( "\n", $tail );
	}

	/**
	 * Get a WP_Filesystem instance.
	 *
	 * @return WP_Filesystem_Base|null Filesystem instance or null on failure.
	 */
	private function get_filesystem() {
		global $wp_filesystem;

		if ( $wp_filesystem instanceof WP_Filesystem_Base ) {
			return $wp_filesystem;
		}

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		return $wp_filesystem instanceof WP_Filesystem_Base ? $wp_filesystem : null;
	}
}

// Initialize the admin tools.
new Conjure_Admin_Tools();

