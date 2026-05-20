<?php

test('conjure gravity forms importer class can be loaded', function () {
	require_once conjurewp_test_get_plugin_path( 'includes/class-conjure-gravity-forms-importer.php' );
	expect( class_exists( 'Conjure_Gravity_Forms_Importer' ) )->toBeTrue();
});

test('conjure gravity forms importer detects entry shaped arrays', function () {
	require_once conjurewp_test_get_plugin_path( 'includes/class-conjure-gravity-forms-importer.php' );

	$reflection = new ReflectionClass( 'Conjure_Gravity_Forms_Importer' );
	$method     = $reflection->getMethod( 'looks_like_entry' );
	$method->setAccessible( true );

	expect( $method->invoke( null, array( 'form_id' => 3, '1' => 'Jane' ) ) )->toBeTrue();
	expect( $method->invoke( null, array( 'title' => 'Not an entry' ) ) )->toBeFalse();
});

test('conjure file upload handler exposes gravity forms sections when gf is active', function () {
	if ( ! class_exists( 'GFAPI' ) && ! class_exists( 'GFForms' ) ) {
		$this->markTestSkipped( 'Gravity Forms is not available in the test environment.' );
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

	$handler  = new Conjure_File_Upload_Handler( $conjure, $wizard_ui );
	$sections = $handler->get_manual_upload_sections( array() );

	expect( $sections )->toHaveKey( 'gf_forms' );
	expect( $sections )->toHaveKey( 'gf_entries' );
	expect( $sections['gf_forms']['accept'] )->toBe( '.json,.xml,.zip' );
	expect( $sections['gf_entries']['accept'] )->toBe( '.json,.csv,.zip' );
});
