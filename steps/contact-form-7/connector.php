<?php
/**
 * Contact Form 7 step connector definition.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'id'          => 'contact-form-7',
	'name'        => __( 'Contact Form 7 Connector', 'ConjureWP' ),
	'description' => __( 'Adds a Contact Form 7 setup step to the wizard for quick form configuration and mail settings guidance.', 'ConjureWP' ),
	'step_key'    => 'contact-form-7',
	'step_name'   => __( 'Contact Form 7', 'ConjureWP' ),
	'class_file'  => 'class-conjure-step-connector-contact-form-7.php',
	'class_name'  => 'Conjure_Step_Connector_Contact_Form_7',
	'plugin'      => array(
		'name'             => 'Contact Form 7',
		'slug'             => 'contact-form-7',
		'file'             => 'contact-form-7/wp-contact-form-7.php',
		'active_callback'  => 'conjurewp_is_cf7_active',
		'version_constant' => 'WPCF7_VERSION',
	),
);
