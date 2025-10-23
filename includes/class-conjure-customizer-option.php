<?php
/**
 * Customizer Option Class
 *
 * This file contains the customizer option functionality.
 *
 * @package Conjure WP
 */

/**
 * A class that extends WP_Customize_Setting so we can access
 * the protected updated method when importing options.
 *
 * Used in the Customizer importer.
 *
 * @package Conjure WP
 */
final class Conjure_Customizer_Option extends \WP_Customize_Setting {
	/**
	 * Import an option value for this setting.
	 *
	 * @since 1.1.1
	 * @param mixed $value The option value.
	 * @return void
	 */
	public function import( $value ) {
		$this->update( $value );
	}
}
