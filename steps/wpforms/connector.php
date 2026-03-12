<?php
/**
 * WPForms step connector definition.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'id'          => 'wpforms',
	'name'        => __( 'WPForms Connector', 'ConjureWP' ),
	'description' => __( 'Adds a WPForms setup step to the wizard for SMTP, contact forms, email notifications and anti-spam.', 'ConjureWP' ),
	'step_key'    => 'wpforms',
	'step_name'   => __( 'WPForms', 'ConjureWP' ),
	'class_file'  => 'class-conjure-step-connector-wpforms.php',
	'class_name'  => 'Conjure_Step_Connector_WPForms',
	'plugin'      => array(
		'name'             => 'WPForms Lite',
		'slug'             => 'wpforms-lite',
		'file'             => 'wpforms-lite/wpforms.php',
		'active_callback'  => 'conjurewp_is_wpforms_active',
		'version_constant' => 'WPFORMS_VERSION',
	),
);
