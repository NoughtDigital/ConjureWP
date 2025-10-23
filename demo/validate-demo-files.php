#!/usr/bin/env php
<?php
/**
 * Validate Demo Files for ConjureWP
 *
 * This script validates that all demo files exist and are properly formatted.
 * Run from command line: php validate-demo-files.php
 *
 * @package ConjureWP
 */

// Colors for terminal output.
define( 'COLOR_GREEN', "\033[32m" );
define( 'COLOR_RED', "\033[31m" );
define( 'COLOR_YELLOW', "\033[33m" );
define( 'COLOR_BLUE', "\033[34m" );
define( 'COLOR_RESET', "\033[0m" );

echo COLOR_BLUE . "\n";
echo "╔═══════════════════════════════════════════════════════╗\n";
echo "║       ConjureWP Demo Files Validation                ║\n";
echo "╚═══════════════════════════════════════════════════════╝\n";
echo COLOR_RESET . "\n";

$demo_dir = __DIR__;
$errors   = array();
$warnings = array();
$success  = array();

// Check if demo directory exists.
if ( ! is_dir( $demo_dir ) ) {
	echo COLOR_RED . "✗ Demo directory not found!\n" . COLOR_RESET;
	exit( 1 );
}

echo "Demo directory: " . COLOR_YELLOW . $demo_dir . COLOR_RESET . "\n\n";

// Files to check.
$required_files = array(
	'content.xml'        => array(
		'name'        => 'Content Export',
		'required'    => true,
		'validate'    => 'validate_xml',
	),
	'widgets.json'       => array(
		'name'        => 'Widget Configuration',
		'required'    => true,
		'validate'    => 'validate_json',
	),
	'customizer.dat'     => array(
		'name'        => 'Customizer Settings',
		'required'    => true,
		'validate'    => 'validate_json',
	),
	'redux-options.json' => array(
		'name'        => 'Redux Options',
		'required'    => false,
		'validate'    => 'validate_json',
	),
	'slider.zip'         => array(
		'name'        => 'Revolution Slider',
		'required'    => false,
		'validate'    => 'validate_zip',
	),
);

echo COLOR_BLUE . "Checking Required Files:\n" . COLOR_RESET;
echo str_repeat( '─', 60 ) . "\n\n";

foreach ( $required_files as $filename => $config ) {
	$file_path = $demo_dir . '/' . $filename;
	$status    = $config['required'] ? 'REQUIRED' : 'OPTIONAL';

	echo sprintf( "%-30s [%s]\n", $config['name'], $status );

	// Check if file exists.
	if ( ! file_exists( $file_path ) ) {
		if ( $config['required'] ) {
			echo COLOR_RED . "  ✗ File not found: {$filename}\n" . COLOR_RESET;
			$errors[] = "{$config['name']}: File not found";
		} else {
			echo COLOR_YELLOW . "  ⚠ Optional file not found: {$filename}\n" . COLOR_RESET;
			$warnings[] = "{$config['name']}: Optional file not found";
		}
		echo "\n";
		continue;
	}

	echo COLOR_GREEN . "  ✓ File exists\n" . COLOR_RESET;

	// Check if file is readable.
	if ( ! is_readable( $file_path ) ) {
		echo COLOR_RED . "  ✗ File is not readable\n" . COLOR_RESET;
		$errors[] = "{$config['name']}: Not readable";
		echo "\n";
		continue;
	}

	echo COLOR_GREEN . "  ✓ File is readable\n" . COLOR_RESET;

	// Check file size.
	$size = filesize( $file_path );
	if ( $size === 0 ) {
		echo COLOR_RED . "  ✗ File is empty\n" . COLOR_RESET;
		$errors[] = "{$config['name']}: File is empty";
		echo "\n";
		continue;
	}

	echo COLOR_GREEN . "  ✓ File size: " . format_bytes( $size ) . "\n" . COLOR_RESET;

	// Validate file format.
	if ( isset( $config['validate'] ) && function_exists( $config['validate'] ) ) {
		$validation = call_user_func( $config['validate'], $file_path );
		if ( $validation === true ) {
			echo COLOR_GREEN . "  ✓ File format valid\n" . COLOR_RESET;
			$success[] = $config['name'];
		} else {
			echo COLOR_RED . "  ✗ Format error: {$validation}\n" . COLOR_RESET;
			$errors[] = "{$config['name']}: {$validation}";
		}
	}

	echo "\n";
}

// Summary.
echo COLOR_BLUE . "\nValidation Summary:\n" . COLOR_RESET;
echo str_repeat( '─', 60 ) . "\n\n";

if ( ! empty( $success ) ) {
	echo COLOR_GREEN . "✓ Valid Files: " . count( $success ) . "\n" . COLOR_RESET;
	foreach ( $success as $item ) {
		echo "  • {$item}\n";
	}
	echo "\n";
}

if ( ! empty( $warnings ) ) {
	echo COLOR_YELLOW . "⚠ Warnings: " . count( $warnings ) . "\n" . COLOR_RESET;
	foreach ( $warnings as $warning ) {
		echo "  • {$warning}\n";
	}
	echo "\n";
}

if ( ! empty( $errors ) ) {
	echo COLOR_RED . "✗ Errors: " . count( $errors ) . "\n" . COLOR_RESET;
	foreach ( $errors as $error ) {
		echo "  • {$error}\n";
	}
	echo "\n";
	echo COLOR_RED . "Validation FAILED!\n" . COLOR_RESET;
	exit( 1 );
}

echo COLOR_GREEN . "All validations passed!\n" . COLOR_RESET;
echo "\nYou can now use these demo files for testing.\n";
echo "See " . COLOR_YELLOW . "DEMO-TESTING-GUIDE.md" . COLOR_RESET . " for instructions.\n\n";

exit( 0 );

/**
 * Validate XML file format.
 *
 * @param string $file_path Path to XML file.
 * @return bool|string True if valid, error message otherwise.
 */
function validate_xml( $file_path ) {
	libxml_use_internal_errors( true );
	$xml = simplexml_load_file( $file_path );

	if ( $xml === false ) {
		$errors = libxml_get_errors();
		libxml_clear_errors();
		return 'Invalid XML: ' . $errors[0]->message;
	}

	// Check for WordPress export format.
	if ( ! isset( $xml->channel ) ) {
		return 'Not a WordPress export file (missing channel element)';
	}

	return true;
}

/**
 * Validate JSON file format.
 *
 * @param string $file_path Path to JSON file.
 * @return bool|string True if valid, error message otherwise.
 */
function validate_json( $file_path ) {
	$content = file_get_contents( $file_path );
	json_decode( $content );

	if ( json_last_error() !== JSON_ERROR_NONE ) {
		return 'Invalid JSON: ' . json_last_error_msg();
	}

	return true;
}

/**
 * Validate ZIP file format.
 *
 * @param string $file_path Path to ZIP file.
 * @return bool|string True if valid, error message otherwise.
 */
function validate_zip( $file_path ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		return 'ZipArchive class not available';
	}

	$zip = new ZipArchive();
	$res = $zip->open( $file_path, ZipArchive::CHECKCONS );

	if ( $res !== true ) {
		return 'Invalid ZIP file';
	}

	$zip->close();
	return true;
}

/**
 * Format bytes to human readable format.
 *
 * @param int $bytes File size in bytes.
 * @return string Formatted file size.
 */
function format_bytes( $bytes ) {
	$units = array( 'B', 'KB', 'MB', 'GB' );
	$bytes = max( $bytes, 0 );
	$pow   = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
	$pow   = min( $pow, count( $units ) - 1 );
	$bytes /= pow( 1024, $pow );

	return round( $bytes, 2 ) . ' ' . $units[ $pow ];
}

