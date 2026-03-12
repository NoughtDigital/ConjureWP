<?php
/**
 * WooCommerce step connector definition.
 *
 * @package ConjureWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'id'          => 'woocommerce',
	'name'        => __( 'WooCommerce Connector', 'ConjureWP' ),
	'description' => __( 'Adds a WooCommerce setup step to the wizard when enabled in the admin page.', 'ConjureWP' ),
	'step_key'    => 'woocommerce',
	'step_name'   => __( 'WooCommerce', 'ConjureWP' ),
	'class_file'  => 'class-conjure-step-connector-woocommerce.php',
	'class_name'  => 'Conjure_Step_Connector_WooCommerce',
	'plugin'      => array(
		'name'             => 'WooCommerce',
		'slug'             => 'woocommerce',
		'file'             => 'woocommerce/woocommerce.php',
		'active_callback'  => 'conjurewp_is_woocommerce_active',
		'version_constant' => 'WC_VERSION',
	),
);
