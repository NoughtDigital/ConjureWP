<?php
/**
 * LiteSpeed Cache step connector definition.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'id'          => 'litespeed-cache',
	'name'        => __( 'LiteSpeed Cache Connector', 'ConjureWP' ),
	'description' => __( 'Adds a LiteSpeed Cache setup step to the wizard for page caching, QUIC.cloud, image optimisation and CDN.', 'ConjureWP' ),
	'step_key'    => 'litespeed-cache',
	'step_name'   => __( 'LiteSpeed Cache', 'ConjureWP' ),
	'class_file'  => 'class-conjure-step-connector-litespeed-cache.php',
	'class_name'  => 'Conjure_Step_Connector_LiteSpeed_Cache',
	'plugin'      => array(
		'name'             => 'LiteSpeed Cache',
		'slug'             => 'litespeed-cache',
		'file'             => 'litespeed-cache/litespeed-cache.php',
		'active_callback'  => 'conjurewp_is_litespeed_cache_active',
		'version_constant' => 'LSCWP_V',
	),
);
