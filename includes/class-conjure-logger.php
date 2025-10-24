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
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

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
	 * Logger configuration options.
	 *
	 * @var array
	 */
	private $config;


	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @param array $config Optional configuration array (only used on first instantiation).
	 *
	 * @return object EasyDigitalDownloadsFastspring *Singleton* instance.
	 *
	 * @codeCoverageIgnore Nothing to test, default PHP singleton functionality.
	 */
	public static function get_instance( $config = array() ) {
		if ( null === static::$instance ) {
			// Check for global config.
			if ( empty( $config ) && defined( 'CONJUREWP_LOGGER_CONFIG' ) ) {
				$config = CONJUREWP_LOGGER_CONFIG;
			}
			static::$instance = new static( null, 'conjure-logger', $config );
		} elseif ( ! empty( $config ) ) {
			// Allow updating config after instantiation.
			static::$instance->update_config( $config );
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
	 * @param array  $config   Configuration options.
	 */
	protected function __construct( $log_path = null, $name = 'conjure-logger', $config = array() ) {
		$this->log_path    = $log_path;
		$this->logger_name = $name;

		// Default configuration.
		$default_config = array(
			'enable_rotation'  => true,
			'max_files'        => 5,
			'max_file_size_mb' => 10,
			'min_log_level'    => MonologLogger::INFO,
		);

		$this->config = wp_parse_args( $config, $default_config );

		// Convert string log levels to Monolog constants.
		$this->config['min_log_level'] = $this->parse_log_level( $this->config['min_log_level'] );

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

			// Add main file handler with rotation if enabled.
			if ( $this->config['enable_rotation'] ) {
				$max_bytes = $this->config['max_file_size_mb'] * 1024 * 1024;
				$handler   = new RotatingFileHandler(
					$this->log_path,
					$this->config['max_files'],
					$this->config['min_log_level']
				);
			} else {
				$handler = new StreamHandler(
					$this->log_path,
					$this->config['min_log_level']
				);
			}

			// Use a clean formatter.
			$formatter = new LineFormatter(
				"[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
				'Y-m-d H:i:s',
				true,
				true
			);
			$handler->setFormatter( $formatter );
			$this->log->pushHandler( $handler );
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
	 * Get all log files (including rotated ones).
	 *
	 * @return array Array of log file paths sorted by modification time (newest first).
	 */
	public function get_all_log_files() {
		$log_dir   = dirname( $this->log_path );
		$log_files = array();

		if ( ! is_dir( $log_dir ) ) {
			return $log_files;
		}

		$files = glob( $log_dir . '/main*.log' );
		if ( ! empty( $files ) ) {
			// Sort by modification time, newest first.
			usort(
				$files,
				function ( $a, $b ) {
					return filemtime( $b ) - filemtime( $a );
				}
			);
			$log_files = $files;
		}

		return $log_files;
	}


	/**
	 * Get logger configuration.
	 *
	 * @return array Logger configuration.
	 */
	public function get_config() {
		return $this->config;
	}


	/**
	 * Update logger configuration.
	 *
	 * @param array $config New configuration values.
	 */
	public function update_config( $config ) {
		$this->config = wp_parse_args( $config, $this->config );

		// Convert string log levels to Monolog constants.
		if ( isset( $config['min_log_level'] ) ) {
			$this->config['min_log_level'] = $this->parse_log_level( $this->config['min_log_level'] );
		}

		// Re-initialize logger with new config.
		$this->initialize_logger();
	}


	/**
	 * Parse log level string to Monolog constant.
	 *
	 * @param string|int $level Log level string or constant.
	 * @return int Monolog log level constant.
	 */
	private function parse_log_level( $level ) {
		// If already an integer, return it.
		if ( is_int( $level ) ) {
			return $level;
		}

		// Map string levels to Monolog constants.
		$level_map = array(
			'DEBUG'     => MonologLogger::DEBUG,
			'INFO'      => MonologLogger::INFO,
			'NOTICE'    => MonologLogger::NOTICE,
			'WARNING'   => MonologLogger::WARNING,
			'ERROR'     => MonologLogger::ERROR,
			'CRITICAL'  => MonologLogger::CRITICAL,
			'ALERT'     => MonologLogger::ALERT,
			'EMERGENCY' => MonologLogger::EMERGENCY,
		);

		$level = strtoupper( $level );
		return isset( $level_map[ $level ] ) ? $level_map[ $level ] : MonologLogger::INFO;
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
