<?php
/**
 * Jetpack step connector definition.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'id'          => 'jetpack',
	'name'        => __( 'Jetpack Connector', 'ConjureWP' ),
	'description' => __( 'Adds a Jetpack setup step to the wizard for WordPress.com connection, backups, security and analytics.', 'ConjureWP' ),
	'step_key'    => 'jetpack',
	'step_name'   => __( 'Jetpack', 'ConjureWP' ),
	'class_file'  => 'class-conjure-step-connector-jetpack.php',
	'class_name'  => 'Conjure_Step_Connector_Jetpack',
	'plugin'      => array(
		'name'             => 'Jetpack',
		'slug'             => 'jetpack',
		'file'             => 'jetpack/jetpack.php',
		'active_callback'  => 'conjurewp_is_jetpack_active',
		'version_constant' => 'JETPACK__VERSION',
	),
);
