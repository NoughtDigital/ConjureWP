<?php
/**
 * ACF step connector definition.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'id'          => 'acf',
	'name'        => __( 'ACF Connector', 'ConjureWP' ),
	'description' => __( 'Adds an Advanced Custom Fields setup step to the wizard for field group configuration, options pages, JSON sync and content structure.', 'ConjureWP' ),
	'step_key'    => 'acf',
	'step_name'   => __( 'ACF', 'ConjureWP' ),
	'class_file'  => 'class-conjure-step-connector-acf.php',
	'class_name'  => 'Conjure_Step_Connector_ACF',
	'plugin'      => array(
		'name'             => 'Advanced Custom Fields',
		'slug'             => 'advanced-custom-fields',
		'file'             => 'advanced-custom-fields/acf.php',
		'active_callback'  => 'conjurewp_is_acf_active',
		'version_constant' => '',
	),
);
