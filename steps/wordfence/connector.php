<?php
/**
 * Wordfence step connector definition.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'id'          => 'wordfence',
	'name'        => __( 'Wordfence Connector', 'ConjureWP' ),
	'description' => __( 'Adds a Wordfence security setup step to the wizard for firewall, login protection, malware scanning and alerts.', 'ConjureWP' ),
	'step_key'    => 'wordfence',
	'step_name'   => __( 'Wordfence', 'ConjureWP' ),
	'class_file'  => 'class-conjure-step-connector-wordfence.php',
	'class_name'  => 'Conjure_Step_Connector_Wordfence',
	'plugin'      => array(
		'name'             => 'Wordfence Security',
		'slug'             => 'wordfence',
		'file'             => 'wordfence/wordfence.php',
		'active_callback'  => 'conjurewp_is_wordfence_active',
		'version_constant' => 'WORDFENCE_VERSION',
	),
);
