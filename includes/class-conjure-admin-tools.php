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
	}

	/**
	 * Add tools submenu page.
	 */
	public function add_tools_page() {
		add_submenu_page(
			'tools.php',
			__( 'ConjureWP Logs', 'conjurewp' ),
			__( 'ConjureWP Logs', 'conjurewp' ),
			'manage_options',
			'conjurewp-logs',
			array( $this, 'render_logs_page' )
		);
	}

	/**
	 * Handle log file actions (clear, download).
	 */
	public function handle_log_actions() {
		if ( ! isset( $_GET['page'] ) || 'conjurewp-logs' !== $_GET['page'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$logger = Conjure_Logger::get_instance();

		// Handle clear all logs action.
		if ( isset( $_GET['action'] ) && 'clear_all_logs' === $_GET['action'] && check_admin_referer( 'conjurewp_clear_all_logs' ) ) {
			$log_files = $logger->get_all_log_files();
			foreach ( $log_files as $log_file ) {
				if ( file_exists( $log_file ) ) {
					unlink( $log_file );
				}
			}
			add_action( 'admin_notices', array( $this, 'clear_log_notice' ) );
		}

		// Handle clear single log action.
		if ( isset( $_GET['action'] ) && 'clear_log' === $_GET['action'] && check_admin_referer( 'conjurewp_clear_log' ) ) {
			$log_path = $logger->get_log_path();

			if ( file_exists( $log_path ) ) {
				file_put_contents( $log_path, '' );
				add_action( 'admin_notices', array( $this, 'clear_log_notice' ) );
			}
		}

		// Handle download log action.
		if ( isset( $_GET['action'] ) && 'download_log' === $_GET['action'] && check_admin_referer( 'conjurewp_download_log' ) ) {
			$log_file = isset( $_GET['file'] ) ? sanitize_text_field( $_GET['file'] ) : '';
			
			if ( empty( $log_file ) ) {
				$log_path = $logger->get_log_path();
			} else {
				// Validate file is in the log directory.
				$log_dir  = dirname( $logger->get_log_path() );
				$log_path = $log_dir . '/' . basename( $log_file );
			}

			if ( file_exists( $log_path ) ) {
				header( 'Content-Type: text/plain' );
				header( 'Content-Disposition: attachment; filename="conjurewp-' . basename( $log_path ) . '"' );
				header( 'Content-Length: ' . filesize( $log_path ) );
				readfile( $log_path );
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
			<p><?php _e( 'Log file has been cleared successfully.', 'conjurewp' ); ?></p>
		</div>
		<?php
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
			<h1><?php _e( 'ConjureWP Logs', 'conjurewp' ); ?></h1>

			<div class="card">
				<h2><?php _e( 'Logger Configuration', 'conjurewp' ); ?></h2>
				<table class="form-table">
					<tr>
						<th><?php _e( 'Log Directory:', 'conjurewp' ); ?></th>
						<td><code><?php echo esc_html( dirname( $log_path ) ); ?></code></td>
					</tr>
					<tr>
						<th><?php _e( 'Log Rotation:', 'conjurewp' ); ?></th>
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
					<th><?php _e( 'Minimum Log Level:', 'conjurewp' ); ?></th>
					<td><strong><?php echo esc_html( $min_level_name ); ?></strong></td>
				</tr>
				<tr>
					<th><?php _e( 'Total Log Files:', 'conjurewp' ); ?></th>
					<td>
						<strong><?php echo count( $log_files ); ?></strong>
						<span style="color: #666;"> (<?php echo size_format( $total_size ); ?> total)</span>
					</td>
				</tr>
				</table>
			</div>

			<?php if ( ! empty( $log_files ) ) : ?>
				<div class="card" style="margin-top: 20px;">
					<h2><?php _e( 'Log Files', 'conjurewp' ); ?></h2>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php _e( 'File Name', 'conjurewp' ); ?></th>
								<th><?php _e( 'Size', 'conjurewp' ); ?></th>
								<th><?php _e( 'Modified', 'conjurewp' ); ?></th>
								<th><?php _e( 'Actions', 'conjurewp' ); ?></th>
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
										<td><?php echo size_format( $file_size ); ?></td>
										<td><?php echo esc_html( human_time_diff( $file_modified, current_time( 'timestamp' ) ) ); ?> ago</td>
										<td>
											<a href="<?php echo wp_nonce_url( admin_url( 'tools.php?page=conjurewp-logs&action=download_log&file=' . urlencode( $file_name ) ), 'conjurewp_download_log' ); ?>" class="button button-small">
												<?php _e( 'Download', 'conjurewp' ); ?>
											</a>
										</td>
									</tr>
								<?php endif; ?>
							<?php endforeach; ?>
						</tbody>
					</table>

					<p style="margin-top: 15px;">
						<?php if ( count( $log_files ) > 0 ) : ?>
							<a href="<?php echo wp_nonce_url( admin_url( 'tools.php?page=conjurewp-logs&action=clear_all_logs' ), 'conjurewp_clear_all_logs' ); ?>" class="button button-secondary" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete ALL log files?', 'conjurewp' ); ?>');">
								<?php _e( 'Delete All Logs', 'conjurewp' ); ?>
							</a>
						<?php endif; ?>
					</p>
				</div>
			<?php endif; ?>

			<?php if ( $main_log_exists && $main_log_size > 0 ) : ?>
				<div class="card" style="margin-top: 20px;">
					<h2><?php _e( 'Recent Log Entries (Last 500 lines from current log)', 'conjurewp' ); ?></h2>
					<div style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; border-radius: 3px; max-height: 600px; overflow-y: auto;">
						<pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word; font-family: monospace; font-size: 12px;"><?php echo esc_html( $main_log_content ); ?></pre>
					</div>
				</div>
			<?php endif; ?>

			<div class="card" style="margin-top: 20px;">
				<h2><?php _e( 'About ConjureWP Logging', 'conjurewp' ); ?></h2>
				<p><?php _e( 'ConjureWP uses an advanced logging system with rotation and severity filtering to prevent large log files and reduce noise.', 'conjurewp' ); ?></p>
				
				<h3 style="margin-top: 20px;"><?php _e( 'Features', 'conjurewp' ); ?></h3>
				<ul style="list-style: disc; margin-left: 20px;">
					<li><strong><?php _e( 'Log Rotation:', 'conjurewp' ); ?></strong> <?php _e( 'Automatically rotates logs to prevent large files. Configure in conjurewp-config.php.', 'conjurewp' ); ?></li>
					<li><strong><?php _e( 'Severity Filtering:', 'conjurewp' ); ?></strong> <?php _e( 'Filter logs by severity (DEBUG, INFO, WARNING, ERROR, etc.) to reduce noise.', 'conjurewp' ); ?></li>
					<li><strong><?php _e( 'Multi-file Management:', 'conjurewp' ); ?></strong> <?php _e( 'View, download, and delete all rotated log files from this admin page.', 'conjurewp' ); ?></li>
				</ul>

				<h3 style="margin-top: 20px;"><?php _e( 'What Gets Logged', 'conjurewp' ); ?></h3>
				<ul style="list-style: disc; margin-left: 20px;">
					<li><?php _e( 'Plugin installations and activations', 'conjurewp' ); ?></li>
					<li><?php _e( 'Content import progress', 'conjurewp' ); ?></li>
					<li><?php _e( 'Child theme generation', 'conjurewp' ); ?></li>
					<li><?php _e( 'License activation attempts', 'conjurewp' ); ?></li>
					<li><?php _e( 'Errors, warnings, and critical issues', 'conjurewp' ); ?></li>
				</ul>

				<h3 style="margin-top: 20px;"><?php _e( 'Configuration', 'conjurewp' ); ?></h3>
				<p><?php _e( 'To customize logging behavior, edit the <code>logging</code> section in <code>conjurewp-config.php</code>. Available options:', 'conjurewp' ); ?></p>
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
		$handle = fopen( $file, 'r' );
		if ( ! $handle ) {
			return '';
		}

		$linecounter = $lines;
		$pos         = -2;
		$beginning   = false;
		$text        = array();

		while ( $linecounter > 0 ) {
			$t = ' ';
			while ( "\n" !== $t ) {
				if ( fseek( $handle, $pos, SEEK_END ) === -1 ) {
					$beginning = true;
					break;
				}
				$t = fgetc( $handle );
				--$pos;
			}
			--$linecounter;
			if ( $beginning ) {
				rewind( $handle );
			}
			$text[ $lines - $linecounter - 1 ] = fgets( $handle );
			if ( $beginning ) {
				break;
			}
		}
		fclose( $handle );

		return implode( '', array_reverse( $text ) );
	}
}

// Initialize the admin tools.
new Conjure_Admin_Tools();

