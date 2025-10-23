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

		// Handle clear log action.
		if ( isset( $_GET['action'] ) && 'clear_log' === $_GET['action'] && check_admin_referer( 'conjurewp_clear_log' ) ) {
			$logger   = Conjure_Logger::get_instance();
			$log_path = $logger->get_log_path();

			if ( file_exists( $log_path ) ) {
				file_put_contents( $log_path, '' );
				add_action( 'admin_notices', array( $this, 'clear_log_notice' ) );
			}
		}

		// Handle download log action.
		if ( isset( $_GET['action'] ) && 'download_log' === $_GET['action'] && check_admin_referer( 'conjurewp_download_log' ) ) {
			$logger   = Conjure_Logger::get_instance();
			$log_path = $logger->get_log_path();

			if ( file_exists( $log_path ) ) {
				header( 'Content-Type: text/plain' );
				header( 'Content-Disposition: attachment; filename="conjurewp-' . gmdate( 'Y-m-d-H-i-s' ) . '.log"' );
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
		$logger      = Conjure_Logger::get_instance();
		$log_path    = $logger->get_log_path();
		$log_exists  = file_exists( $log_path );
		$log_content = '';
		$log_size    = 0;

		if ( $log_exists ) {
			$log_size = filesize( $log_path );
			// Read last 500 lines for display.
			if ( $log_size > 0 ) {
				$log_content = $this->tail( $log_path, 500 );
			}
		}

		?>
		<div class="wrap">
			<h1><?php _e( 'ConjureWP Logs', 'conjurewp' ); ?></h1>
			
			<div class="card">
				<h2><?php _e( 'Log File Information', 'conjurewp' ); ?></h2>
				<table class="form-table">
					<tr>
						<th><?php _e( 'Log File Path:', 'conjurewp' ); ?></th>
						<td><code><?php echo esc_html( $log_path ); ?></code></td>
					</tr>
					<tr>
						<th><?php _e( 'File Size:', 'conjurewp' ); ?></th>
						<td><?php echo $log_exists ? size_format( $log_size ) : __( 'File does not exist', 'conjurewp' ); ?></td>
					</tr>
					<tr>
						<th><?php _e( 'Status:', 'conjurewp' ); ?></th>
						<td>
							<?php if ( $log_exists ) : ?>
								<span style="color: green;">✓ <?php _e( 'Log file exists and is writable', 'conjurewp' ); ?></span>
							<?php else : ?>
								<span style="color: orange;">⚠ <?php _e( 'Log file will be created when needed', 'conjurewp' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				</table>

				<p>
					<?php if ( $log_exists && $log_size > 0 ) : ?>
						<a href="<?php echo wp_nonce_url( admin_url( 'tools.php?page=conjurewp-logs&action=download_log' ), 'conjurewp_download_log' ); ?>" class="button button-secondary">
							<?php _e( 'Download Log File', 'conjurewp' ); ?>
						</a>
						<a href="<?php echo wp_nonce_url( admin_url( 'tools.php?page=conjurewp-logs&action=clear_log' ), 'conjurewp_clear_log' ); ?>" class="button button-secondary" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to clear the log file?', 'conjurewp' ); ?>');">
							<?php _e( 'Clear Log File', 'conjurewp' ); ?>
						</a>
					<?php endif; ?>
				</p>
			</div>

			<?php if ( $log_exists && $log_size > 0 ) : ?>
				<div class="card" style="margin-top: 20px;">
					<h2><?php _e( 'Recent Log Entries (Last 500 lines)', 'conjurewp' ); ?></h2>
					<div style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; border-radius: 3px; max-height: 600px; overflow-y: auto;">
						<pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word; font-family: monospace; font-size: 12px;"><?php echo esc_html( $log_content ); ?></pre>
					</div>
				</div>
			<?php endif; ?>

			<div class="card" style="margin-top: 20px;">
				<h2><?php _e( 'About ConjureWP Logging', 'conjurewp' ); ?></h2>
				<p><?php _e( 'ConjureWP uses a logging system to track all import operations and help you debug any issues that may occur during the setup process.', 'conjurewp' ); ?></p>
				<p><?php _e( 'The log file records:', 'conjurewp' ); ?></p>
				<ul style="list-style: disc; margin-left: 20px;">
					<li><?php _e( 'Plugin installations and activations', 'conjurewp' ); ?></li>
					<li><?php _e( 'Content import progress', 'conjurewp' ); ?></li>
					<li><?php _e( 'Child theme generation', 'conjurewp' ); ?></li>
					<li><?php _e( 'License activation attempts', 'conjurewp' ); ?></li>
					<li><?php _e( 'Errors and warnings', 'conjurewp' ); ?></li>
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

