<?php
/**
 * Whippet Performance step connector definition.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'id'          => 'whippet',
	'name'        => __( 'Whippet Performance Connector', 'ConjureWP' ),
	'description' => __( 'Adds a Whippet Performance setup step to the wizard for asset unloading, script optimisation and disabling unnecessary features.', 'ConjureWP' ),
	'step_key'    => 'whippet',
	'step_name'   => __( 'Whippet', 'ConjureWP' ),
	'class_file'  => 'class-conjure-step-connector-whippet.php',
	'class_name'  => 'Conjure_Step_Connector_Whippet',
	'plugin'      => array(
		'name'             => 'Whippet',
		'slug'             => 'whippet',
		'file'             => 'whippet/whippet.php',
		'active_callback'  => 'conjurewp_is_whippet_active',
		'version_constant' => 'WHIPPET_VERSION',
	),
);
