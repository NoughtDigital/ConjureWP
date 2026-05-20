<?php

test('conjure acf json importer class can be loaded', function () {
	require_once conjurewp_test_get_plugin_path( 'includes/class-conjure-acf-json-importer.php' );
	expect( class_exists( 'Conjure_ACF_JSON_Importer' ) )->toBeTrue();
});

test('conjure acf json importer validates field group structure', function () {
	require_once conjurewp_test_get_plugin_path( 'includes/class-conjure-acf-json-importer.php' );

	$reflection = new ReflectionClass( 'Conjure_ACF_JSON_Importer' );
	$method     = $reflection->getMethod( 'is_valid_field_group_json' );
	$method->setAccessible( true );

	expect( $method->invoke( null, array( 'key' => 'group_test', 'title' => 'Test', 'fields' => array() ) ) )->toBeTrue();
	expect( $method->invoke( null, array( 'title' => 'Missing key' ) ) )->toBeFalse();
});

test('conjure acf json importer builds filename from field group key', function () {
	require_once conjurewp_test_get_plugin_path( 'includes/class-conjure-acf-json-importer.php' );

	$reflection = new ReflectionClass( 'Conjure_ACF_JSON_Importer' );
	$method     = $reflection->getMethod( 'build_filename' );
	$method->setAccessible( true );

	$filename = $method->invoke( null, array( 'key' => 'group_abc123' ), 'upload.json' );

	expect( $filename )->toBe( 'group_abc123.json' );
});

test('conjurewp resolves acf json save path from config default', function () {
	require_once conjurewp_test_get_plugin_path( 'includes/conjurewp-runtime.php' );

	if ( ! defined( 'CONJUREWP_CONFIG_ACF_JSON_SAVE_PATH' ) ) {
		define( 'CONJUREWP_CONFIG_ACF_JSON_SAVE_PATH', 'inc/acf-json' );
	}

	expect( conjurewp_get_acf_json_save_path_config_default() )->toBe( 'inc/acf-json' );
	expect( conjurewp_sanitize_acf_json_save_path( '../unsafe' ) )->toBe( 'unsafe' );
});

test('conjure file upload handler exposes acf_json when acf is active', function () {
	if ( ! class_exists( 'ACF' ) && ! function_exists( 'acf' ) ) {
		$this->markTestSkipped( 'ACF is not available in the test environment.' );
	}

	require_once conjurewp_test_get_plugin_path( 'includes/class-conjure-file-upload-handler.php' );

	$conjure   = new stdClass();
	$conjure->logger = new stdClass();
	$wizard_ui = new stdClass();
	$wizard_ui->svg_allowed_html = static function () {
		return array();
	};
	$wizard_ui->svg = static function () {
		return '';
	};

	$handler = new Conjure_File_Upload_Handler( $conjure, $wizard_ui );
	$sections = $handler->get_manual_upload_sections( array() );

	expect( $sections )->toHaveKey( 'acf_json' );
	expect( $sections['acf_json']['accept'] )->toBe( '.json,.zip' );
});
