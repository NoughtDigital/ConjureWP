<?php
/**
 * ConjureWP Logging Configuration Sample
 *
 * This file shows all available logging configuration options.
 * Copy the 'logging' array to your conjurewp-config.php file and adjust values as needed.
 *
 * @package   ConjureWP
 * @version   1.0.0
 * @link      https://conjurewp.com/
 * @author    Jake Henshall, from nought.digital
 * @copyright Copyright (c) 2018, Conjure WP of Inventionn LLC
 * @license   Licensed GPLv3 for Open Source Use
 */

// Example configuration - add this to your $config array in conjurewp-config.php:

'logging' => array(
	/**
	 * Enable Log Rotation
	 * 
	 * When enabled, logs will automatically rotate to prevent large files.
	 * This helps manage disk space and keeps logs manageable.
	 * 
	 * Type: boolean
	 * Default: true
	 */
	'enable_rotation' => true,

	/**
	 * Maximum Number of Log Files
	 * 
	 * The maximum number of rotated log files to keep.
	 * Older files beyond this limit will be automatically deleted.
	 * 
	 * Type: integer
	 * Default: 5
	 */
	'max_files' => 5,

	/**
	 * Maximum Log File Size (MB)
	 * 
	 * When a log file reaches this size, it will be rotated.
	 * Set to 0 for no size limit (rotation happens daily instead).
	 * 
	 * Type: integer (megabytes)
	 * Default: 10
	 */
	'max_file_size_mb' => 10,

	/**
	 * Minimum Log Level
	 * 
	 * Only log messages at or above this severity level.
	 * This helps reduce noise and keep logs focused on important events.
	 * 
	 * Available levels (from lowest to highest severity):
	 * - DEBUG: Detailed debug information
	 * - INFO: Informational messages (recommended for production)
	 * - NOTICE: Normal but significant events
	 * - WARNING: Warning messages
	 * - ERROR: Error messages
	 * - CRITICAL: Critical conditions
	 * - ALERT: Action must be taken immediately
	 * - EMERGENCY: System is unusable
	 * 
	 * Type: string
	 * Default: 'INFO'
	 */
	'min_log_level' => 'INFO',
),

/*
 * EXAMPLE CONFIGURATIONS
 * =====================
 */

// Example 1: Development Environment (Verbose Logging)
// -----------------------------------------------------
// 'logging' => array(
//     'enable_rotation'  => true,
//     'max_files'        => 10,
//     'max_file_size_mb' => 5,
//     'min_log_level'    => 'DEBUG',  // Log everything
// ),

// Example 2: Production Environment (Errors Only)
// ------------------------------------------------
// 'logging' => array(
//     'enable_rotation'  => true,
//     'max_files'        => 5,
//     'max_file_size_mb' => 10,
//     'min_log_level'    => 'WARNING', // Only warnings and above
// ),

// Example 3: Minimal Logging (Critical Only)
// -------------------------------------------
// 'logging' => array(
//     'enable_rotation'  => true,
//     'max_files'        => 3,
//     'max_file_size_mb' => 5,
//     'min_log_level'    => 'ERROR',   // Errors and critical only
// ),

// Example 4: No Rotation (Single File)
// -------------------------------------
// 'logging' => array(
//     'enable_rotation'  => false,
//     'min_log_level'    => 'INFO',
// ),

/*
 * ACCESSING LOGS
 * ==============
 * 
 * View and manage logs in WordPress admin:
 * Tools → ConjureWP Logs
 * 
 * The admin page shows:
 * - Current configuration
 * - All log files (main + rotated)
 * - File sizes and modification times
 * - Recent log entries
 * - Download and delete options
 */

/*
 * LOG FILE LOCATION
 * =================
 * 
 * Logs are stored in: wp-content/uploads/conjure-wp/
 * 
 * Files:
 * - main.log (current log file)
 * - main-2024-01-15.log (rotated log from Jan 15, 2024)
 * - main-2024-01-14.log (rotated log from Jan 14, 2024)
 * etc.
 * 
 * The directory is protected with .htaccess and index.php files.
 */

/*
 * HOW LOG ROTATION WORKS
 * =======================
 * 
 * When a log file reaches the max_file_size_mb limit:
 * 1. Current main.log is renamed to main-YYYY-MM-DD.log
 * 2. A new main.log is created
 * 3. If more than max_files exist, oldest ones are deleted
 * 
 * This prevents any single log file from growing too large
 * during imports or other heavy operations.
 */

/*
 * SEVERITY FILTERING TIPS
 * ========================
 * 
 * Choose your log level based on environment:
 * 
 * - DEBUG: Use during development to see everything
 * - INFO: Good for production, captures all operations
 * - WARNING: Production, only log potential issues
 * - ERROR: Production, only log actual errors
 * 
 * Lower severity = more log entries = larger files
 * Higher severity = fewer log entries = smaller files
 */

