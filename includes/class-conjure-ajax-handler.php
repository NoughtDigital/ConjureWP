<?php
/**
 * AJAX Handler class
 *
 * Coordinates all AJAX requests and delegates to appropriate classes.
 *
 * @package   Conjure WP
 * @version   @@pkg.version
 * @link      https://conjurewp.com/
 * @author    Jake Henshall, from Nought.digital
 * @copyright Copyright (c) 2018, Conjure WP of Nought Digital
 * @license   Licensed GPLv3 for Open Source Use
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conjure AJAX Handler class.
 */
class Conjure_Ajax_Handler {

	/**
	 * Reference to main Conjure instance.
	 *
	 * @var Conjure
	 */
	protected $conjure;

	/**
	 * Logger instance.
	 *
	 * @var Conjure_Logger
	 */
	protected $logger;

	/**
	 * Child theme generator instance.
	 *
	 * @var Conjure_Child_Theme_Generator
	 */
	protected $child_theme_generator;

	/**
	 * License manager instance.
	 *
	 * @var Conjure_License_Manager
	 */
	protected $license_manager;

	/**
	 * File upload handler instance.
	 *
	 * @var Conjure_File_Upload_Handler
	 */
	protected $file_upload_handler;

	/**
	 * Constructor.
	 *
	 * @param Conjure                       $conjure Main Conjure instance.
	 * @param Conjure_Child_Theme_Generator $child_theme_generator Child theme generator instance.
	 * @param Conjure_License_Manager       $license_manager License manager instance.
	 * @param Conjure_File_Upload_Handler   $file_upload_handler File upload handler instance.
	 */
	public function __construct( $conjure, $child_theme_generator, $license_manager, $file_upload_handler ) {
		$this->conjure               = $conjure;
		$this->logger                = $conjure->logger;
		$this->child_theme_generator = $child_theme_generator;
		$this->license_manager       = $license_manager;
		$this->file_upload_handler   = $file_upload_handler;
	}

	/**
	 * Register all AJAX handlers.
	 */
	public function register_ajax_handlers() {
		// Child theme generation.
		add_action( 'wp_ajax_conjure_child_theme', array( $this->child_theme_generator, 'generate_child' ) );
		add_action( 'wp_ajax_conjure_generate_child', array( $this->child_theme_generator, 'generate_child' ) );

		// License activation.
		add_action( 'wp_ajax_conjure_activate_license', array( $this->license_manager, 'ajax_activate_license' ) );

		// Plugin installation.
		add_action( 'wp_ajax_conjure_install_plugin', array( $this->conjure, '_ajax_install_plugin' ) );

		// Content import.
		add_action( 'wp_ajax_conjure_content', array( $this->conjure, '_ajax_content' ) );
		add_action( 'wp_ajax_conjure_get_total_content_import_items', array( $this->conjure, '_ajax_get_total_content_import_items' ) );
		add_action( 'wp_ajax_conjure_import_finished', array( $this->conjure, 'import_finished' ) );

		// Server health check.
		add_action( 'wp_ajax_conjure_get_health_metrics', array( $this->conjure, '_ajax_get_health_metrics' ) );

		// File uploads.
		add_action( 'wp_ajax_conjure_upload_file', array( $this->file_upload_handler, 'ajax_upload_file' ) );
		add_action( 'wp_ajax_conjure_upload_from_media', array( $this->file_upload_handler, 'ajax_upload_from_media' ) );
		add_action( 'wp_ajax_conjure_delete_uploaded_file', array( $this->file_upload_handler, 'ajax_delete_uploaded_file' ) );

		// Demo selection.
		add_action( 'wp_ajax_conjure_update_selected_import_data_info', array( $this->conjure, 'update_selected_import_data_info' ) );
	}
}

