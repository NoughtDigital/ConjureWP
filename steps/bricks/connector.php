<?php
/**
 * Bricks step connector definition.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'id'          => 'bricks',
	'name'        => __( 'Bricks Connector', 'ConjureWP' ),
	'description' => __( 'Adds a Bricks Builder setup step to the wizard for template-ready onboarding and builder configuration.', 'ConjureWP' ),
	'step_key'    => 'bricks',
	'step_name'   => __( 'Bricks', 'ConjureWP' ),
	'class_file'  => 'class-conjure-step-connector-bricks.php',
	'class_name'  => 'Conjure_Step_Connector_Bricks',
	'plugin'      => array(
		'name'             => 'Bricks',
		'slug'             => 'bricks',
		'file'             => '',
		'active_callback'  => 'conjurewp_is_bricks_active',
		'version_constant' => 'BRICKS_VERSION',
	),
);
