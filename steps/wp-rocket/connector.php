<?php
/**
 * WP Rocket step connector definition.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'id'          => 'wp-rocket',
	'name'        => __( 'WP Rocket Connector', 'ConjureWP' ),
	'description' => __( 'Adds a WP Rocket performance setup step to the wizard for cache, file optimisation and preloading.', 'ConjureWP' ),
	'step_key'    => 'wp-rocket',
	'step_name'   => __( 'WP Rocket', 'ConjureWP' ),
	'class_file'  => 'class-conjure-step-connector-wp-rocket.php',
	'class_name'  => 'Conjure_Step_Connector_WP_Rocket',
	'plugin'      => array(
		'name'             => 'WP Rocket',
		'slug'             => 'wp-rocket',
		'file'             => 'wp-rocket/wp-rocket.php',
		'active_callback'  => 'conjurewp_is_wp_rocket_active',
		'version_constant' => 'WP_ROCKET_VERSION',
	),
);
