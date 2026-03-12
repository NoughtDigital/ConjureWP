<?php
/**
 * Gravity Forms step connector definition.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'id'          => 'gravity-forms',
	'name'        => __( 'Gravity Forms Connector', 'ConjureWP' ),
	'description' => __( 'Adds a Gravity Forms setup step to the wizard for feed configuration, starter forms and lead capture readiness.', 'ConjureWP' ),
	'step_key'    => 'gravity-forms',
	'step_name'   => __( 'Gravity Forms', 'ConjureWP' ),
	'class_file'  => 'class-conjure-step-connector-gravity-forms.php',
	'class_name'  => 'Conjure_Step_Connector_Gravity_Forms',
	'plugin'      => array(
		'name'             => 'Gravity Forms',
		'slug'             => 'gravityforms',
		'file'             => 'gravityforms/gravityforms.php',
		'active_callback'  => 'conjurewp_is_gravity_forms_active',
		'version_constant' => 'GF_MIN_WP_VERSION',
	),
);
