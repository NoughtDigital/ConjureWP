<?php
/**
 * Gutenberg step connector definition.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'id'          => 'gutenberg',
	'name'        => __( 'Gutenberg Connector', 'ConjureWP' ),
	'description' => __( 'Adds a block editor setup step to the wizard for pattern guidance, template presets and editor defaults.', 'ConjureWP' ),
	'step_key'    => 'gutenberg',
	'step_name'   => __( 'Gutenberg', 'ConjureWP' ),
	'class_file'  => 'class-conjure-step-connector-gutenberg.php',
	'class_name'  => 'Conjure_Step_Connector_Gutenberg',
	'plugin'      => array(
		'name'             => 'Gutenberg',
		'slug'             => 'gutenberg',
		'file'             => 'gutenberg/gutenberg.php',
		'active_callback'  => 'conjurewp_is_gutenberg_active',
		'version_constant' => '',
	),
);
