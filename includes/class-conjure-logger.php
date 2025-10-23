<?php
/**
 * Logger Class
 *
 * The logger class, which will abstract the use of the monolog library.
 * More about monolog: https://github.com/Seldaek/monolog
 *
 * @package Conjure WP
 */

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;

/**
 * Logger class for handling log operations.
 */
class Conjure_Logger {
	/**
	 * Instance of the monolog logger class.
	 *
	 * @var object
	 */
	private $log;


	/**
	 * The absolute path to the log file.
	 *
	 * @var string
	 */
	private $log_path;


	/**
	 * The name of the logger instance.
	 *
	 * @var string
	 */
	private $logger_name;


	/**
	 * The instance *Singleton* of this class
	 *
	 * @var object
	 */
	private static $instance;


	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return object EasyDigitalDownloadsFastspring *Singleton* instance.
	 *
	 * @codeCoverageIgnore Nothing to test, default PHP singleton functionality.
	 */
	public static function get_instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}


	/**
	 * Logger constructor.
	 *
	 * Protected constructor to prevent creating a new instance of the
	 * *Singleton* via the `new` operator from outside of this class.
	 *
	 * @param string $log_path Path to the log file.
	 * @param string $name     Name of the logger instance.
	 */
	protected function __construct( $log_path = null, $name = 'conjure-logger' ) {
		$this->log_path    = $log_path;
		$this->logger_name = $name;

		if ( empty( $this->log_path ) ) {
			$upload_dir = wp_upload_dir();
			$logger_dir = $upload_dir['basedir'] . '/conjure-wp';

			if ( ! file_exists( $logger_dir ) ) {
				wp_mkdir_p( $logger_dir );

				// Add index.php to prevent directory listing.
				$index_file = $logger_dir . '/index.php';
				if ( ! file_exists( $index_file ) ) {
					file_put_contents( $index_file, '<?php // Silence is golden' );
				}

				// Add .htaccess to protect log files.
				$htaccess_file = $logger_dir . '/.htaccess';
				if ( ! file_exists( $htaccess_file ) ) {
					$htaccess_content  = "# Protect log files\n";
					$htaccess_content .= "<Files *.log>\n";
					$htaccess_content .= "Order allow,deny\n";
					$htaccess_content .= "Deny from all\n";
					$htaccess_content .= "</Files>\n";
					file_put_contents( $htaccess_file, $htaccess_content );
				}
			}

			$this->log_path = $logger_dir . '/main.log';
		}

		$this->initialize_logger();
	}


	/**
	 * Initialize the monolog logger class.
	 */
	private function initialize_logger() {
		if ( empty( $this->log_path ) || empty( $this->logger_name ) ) {
			return false;
		}

		try {
			$this->log = new MonologLogger( $this->logger_name );
			$this->log->pushHandler( new StreamHandler( $this->log_path, MonologLogger::DEBUG ) );
		} catch ( \Exception $e ) {
			// Fallback: if logger fails, we'll just continue without logging.
			error_log( 'ConjureWP Logger failed to initialize: ' . $e->getMessage() );
			return false;
		}
	}


	/**
	 * Get the log file path.
	 *
	 * @return string The absolute path to the log file.
	 */
	public function get_log_path() {
		return $this->log_path;
	}


	/**
	 * Log message for log level: debug.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 *
	 * @return boolean Whether the record has been processed.
	 */
	public function debug( $message, $context = array() ) {
		if ( ! $this->log ) {
			return false;
		}
		return $this->log->debug( $message, $context );
	}

	/**
	 * Log message for log level: info.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 *
	 * @return boolean Whether the record has been processed.
	 */
	public function info( $message, $context = array() ) {
		if ( ! $this->log ) {
			return false;
		}
		return $this->log->info( $message, $context );
	}


	/**
	 * Log message for log level: notice.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 *
	 * @return boolean Whether the record has been processed.
	 */
	public function notice( $message, $context = array() ) {
		if ( ! $this->log ) {
			return false;
		}
		return $this->log->notice( $message, $context );
	}


	/**
	 * Log message for log level: warning.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 *
	 * @return boolean Whether the record has been processed.
	 */
	public function warning( $message, $context = array() ) {
		if ( ! $this->log ) {
			return false;
		}
		return $this->log->warning( $message, $context );
	}


	/**
	 * Log message for log level: error.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 *
	 * @return boolean Whether the record has been processed.
	 */
	public function error( $message, $context = array() ) {
		if ( ! $this->log ) {
			return false;
		}
		return $this->log->error( $message, $context );
	}


	/**
	 * Log message for log level: alert.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 *
	 * @return boolean Whether the record has been processed.
	 */
	public function alert( $message, $context = array() ) {
		if ( ! $this->log ) {
			return false;
		}
		return $this->log->alert( $message, $context );
	}


	/**
	 * Log message for log level: emergency.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 *
	 * @return boolean Whether the record has been processed.
	 */
	public function emergency( $message, $context = array() ) {
		if ( ! $this->log ) {
			return false;
		}
		return $this->log->emergency( $message, $context );
	}


	/**
	 * Private clone method to prevent cloning of the instance of the *Singleton* instance.
	 *
	 * @return void
	 */
	private function __clone() {}


	/**
	 * Private unserialize method to prevent unserializing of the *Singleton* instance.
	 *
	 * @return void
	 */
	private function __wakeup() {}
}
