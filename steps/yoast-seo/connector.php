<?php
/**
 * Yoast SEO step connector definition.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'id'          => 'yoast-seo',
	'name'        => __( 'Yoast SEO Connector', 'ConjureWP' ),
	'description' => __( 'Adds a Yoast SEO setup step to the wizard for search appearance, indexing, sitemaps and social metadata.', 'ConjureWP' ),
	'step_key'    => 'yoast-seo',
	'step_name'   => __( 'Yoast SEO', 'ConjureWP' ),
	'class_file'  => 'class-conjure-step-connector-yoast-seo.php',
	'class_name'  => 'Conjure_Step_Connector_Yoast_Seo',
	'plugin'      => array(
		'name'             => 'Yoast SEO',
		'slug'             => 'wordpress-seo',
		'file'             => 'wordpress-seo/wp-seo.php',
		'active_callback'  => 'conjurewp_is_yoast_seo_active',
		'version_constant' => 'WPSEO_VERSION',
	),
);
