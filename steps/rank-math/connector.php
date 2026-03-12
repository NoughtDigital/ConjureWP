<?php
/**
 * Rank Math step connector definition.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'id'          => 'rank-math',
	'name'        => __( 'Rank Math Connector', 'ConjureWP' ),
	'description' => __( 'Adds a Rank Math SEO setup step to the wizard for search console, sitemap, schema and SEO title configuration.', 'ConjureWP' ),
	'step_key'    => 'rank-math',
	'step_name'   => __( 'Rank Math', 'ConjureWP' ),
	'class_file'  => 'class-conjure-step-connector-rank-math.php',
	'class_name'  => 'Conjure_Step_Connector_Rank_Math',
	'plugin'      => array(
		'name'             => 'Rank Math SEO',
		'slug'             => 'seo-by-rank-math',
		'file'             => 'seo-by-rank-math/rank-math.php',
		'active_callback'  => 'conjurewp_is_rank_math_active',
		'version_constant' => 'RANK_MATH_VERSION',
	),
);
