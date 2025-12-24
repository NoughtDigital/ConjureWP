<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * This file performs a complete cleanup of all ConjureWP data:
 * - Database options and transients
 * - Log files and directories
 * - Uploaded files and directories
 * - All plugin-created data
 *
 * @package ConjureWP
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check user capabilities.
if ( ! current_user_can( 'activate_plugins' ) ) {
	return;
}

global $wpdb;

// ============================================================================
// DATABASE CLEANUP
// ============================================================================

// Delete all options that start with 'conjure_'.
// This includes:
// - conjure_{theme_slug}_completed
// - conjure_{theme_slug}_child
// - conjure_{theme_slug}_step_completion
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( 'conjure_' ) . '%'
	)
);

// Delete all transients that start with 'conjure_'.
// This includes:
// - conjure_import_file_base_name
// - conjure_uploaded_files
// - conjure_selected_demo_index
// - conjure_admin_notice
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_conjure_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_conjure_' ) . '%'
	)
);

// Delete all transients that start with 'conjurewp_'.
// This includes:
// - conjurewp_theme_plugins_config
// - conjurewp_theme_plugin_validation
// - conjurewp_downloads_{hash}
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_conjurewp_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_conjurewp_' ) . '%'
	)
);

// Delete transients that contain '_conjure_redirect' (theme-specific redirects).
// Format: _transient_{theme}_conjure_redirect or _transient_timeout_{theme}_conjure_redirect
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_' ) . '%' . $wpdb->esc_like( '_conjure_redirect' ),
		$wpdb->esc_like( '_transient_timeout_' ) . '%' . $wpdb->esc_like( '_conjure_redirect' )
	)
);

// Delete pt_importer_data transient (used by the importer).
delete_transient( 'pt_importer_data' );

// ============================================================================
// FILE SYSTEM CLEANUP
// ============================================================================

// Initialize WP_Filesystem.
require_once ABSPATH . 'wp-admin/includes/file.php';
WP_Filesystem();
global $wp_filesystem;

if ( ! $wp_filesystem ) {
	return; // Cannot proceed without filesystem access.
}

$upload_dir = wp_upload_dir();

// Delete log directory: /wp-content/uploads/conjure-wp/
$log_dir = trailingslashit( $upload_dir['basedir'] ) . 'conjure-wp';
if ( $wp_filesystem->exists( $log_dir ) ) {
	$wp_filesystem->rmdir( $log_dir, true );
}

// Delete uploads directory: /wp-content/uploads/conjure-uploads/
// This contains files uploaded through the wizard (XML, JSON, etc.).
$uploads_dir = trailingslashit( $upload_dir['basedir'] ) . 'conjure-uploads';
if ( $wp_filesystem->exists( $uploads_dir ) ) {
	$wp_filesystem->rmdir( $uploads_dir, true );
}

// Note: We do NOT delete /wp-content/uploads/conjurewp-demos/ as this may
// contain user-provided demo content that should persist even after plugin removal.

// ============================================================================
// CLEAR OBJECT CACHE
// ============================================================================

// Clear any cached data related to ConjureWP.
if ( function_exists( 'wp_cache_flush' ) ) {
	wp_cache_flush();
}

// ============================================================================
// CLEANUP COMPLETE
// ============================================================================

// Log the uninstall (if possible, but don't fail if logger doesn't exist).
if ( class_exists( 'Conjure_Logger' ) ) {
	try {
		$logger = Conjure_Logger::get_instance();
		$logger->info( 'ConjureWP plugin uninstalled - all data cleaned up' );
	} catch ( Exception $e ) {
		// Silently fail - we're uninstalling anyway.
	}
}

