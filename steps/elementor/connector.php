<?php
/**
 * Elementor step connector definition.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'id'          => 'elementor',
	'name'        => __( 'Elementor Connector', 'ConjureWP' ),
	'description' => __( 'Adds an Elementor setup step to the wizard for guided template imports and builder-specific hand-off screens.', 'ConjureWP' ),
	'step_key'    => 'elementor',
	'step_name'   => __( 'Elementor', 'ConjureWP' ),
	'class_file'  => 'class-conjure-step-connector-elementor.php',
	'class_name'  => 'Conjure_Step_Connector_Elementor',
	'plugin'      => array(
		'name'             => 'Elementor',
		'slug'             => 'elementor',
		'file'             => 'elementor/elementor.php',
		'active_callback'  => 'conjurewp_is_elementor_active',
		'version_constant' => 'ELEMENTOR_VERSION',
	),
);
