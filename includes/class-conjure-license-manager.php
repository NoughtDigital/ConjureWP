<?php
/**
 * License Manager class
 *
 * Handles license validation and activation for both EDD and Freemius.
 *
 * @package   Conjure WP
 * @version   1.0.0
 * @link      https://conjurewp.com/
 * @author    Jake Henshall, from Nought.digital
 * @copyright Copyright (c) 2018, Conjure WP of Nought Digital
 * @license   Licensed GPLv3 for Open Source Use
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conjure License Manager class.
 */
class Conjure_License_Manager {

	/**
	 * Reference to main Conjure instance.
	 *
	 * @var Conjure
	 */
	protected $conjure;

	/**
	 * Logger instance.
	 *
	 * @var Conjure_Logger
	 */
	protected $logger;

	/**
	 * Step manager instance.
	 *
	 * @var Conjure_Step_Manager
	 */
	protected $step_manager;

	/**
	 * Constructor.
	 *
	 * @param Conjure              $conjure Main Conjure instance.
	 * @param Conjure_Step_Manager $step_manager Step manager instance.
	 */
	public function __construct( $conjure, $step_manager ) {
		$this->conjure      = $conjure;
		$this->logger       = $conjure->logger;
		$this->step_manager = $step_manager;
	}

	/**
	 * AJAX handler for license activation.
	 * Supports both Freemius and EDD license activation.
	 */
	public function ajax_activate_license() {

		if ( ! check_ajax_referer( 'conjure_nonce', 'wpnonce' ) ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => esc_html__( 'Yikes! The license activation failed. Please try again or contact support.', 'conjurewp' ),
				)
			);
		}

		if ( empty( $_POST['license_key'] ) ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => esc_html__( 'Please add your license key before attempting to activate one.', 'conjurewp' ),
				)
			);
		}

		$license_key = sanitize_text_field( wp_unslash( $_POST['license_key'] ) );
		
		// Ensure Freemius integration file is loaded.
		if ( ! function_exists( 'con_fs' ) && file_exists( CONJUREWP_PLUGIN_DIR . 'includes/class-conjure-freemius.php' ) ) {
			require_once CONJUREWP_PLUGIN_DIR . 'includes/class-conjure-freemius.php';
		}

		// Check if custom filter exists (for theme developers to override).
		if ( has_filter( 'conjure_ajax_activate_license' ) ) {
			$this->logger->debug( 'Using custom license activation filter' );
			$result = apply_filters( 'conjure_ajax_activate_license', $license_key );
		} elseif ( function_exists( 'con_fs' ) ) {
			$fs = con_fs();
			// Check if Freemius SDK is actually available (not just the stub function).
			$fs_available = ( $fs && is_object( $fs ) && method_exists( $fs, 'is_registered' ) );
			
			$this->logger->debug( 'Freemius SDK check', array( 
				'function_exists' => function_exists( 'con_fs' ),
				'fs_instance' => is_object( $fs ) ? 'object' : ( $fs === false ? 'false' : 'other' ),
				'fs_available' => $fs_available,
				'is_registered' => $fs_available ? $fs->is_registered() : 'unknown',
				'fs_dynamic_init_exists' => function_exists( 'fs_dynamic_init' ),
				'license_key_length' => strlen( $license_key )
			) );
			
			if ( $fs_available ) {
				// Use Freemius license activation.
				$result = $this->freemius_activate_license( $license_key );
			} else {
				$this->logger->warning( 'Freemius SDK function exists but SDK not initialised, falling back to EDD activation', array(
					'fs_value' => $fs,
					'fs_type' => gettype( $fs ),
					'fs_methods' => is_object( $fs ) ? get_class_methods( $fs ) : 'not_object'
				) );
				// Fallback to EDD license activation.
				$result = $this->edd_activate_license( $license_key );
			}
		} else {
			$this->logger->warning( 'Freemius SDK function not available, falling back to EDD activation', array(
				'freemius_file_exists' => file_exists( CONJUREWP_PLUGIN_DIR . 'includes/class-conjure-freemius.php' ),
				'con_fs_defined' => defined( 'CONJUREWP_PLUGIN_DIR' ) ? 'constant_defined' : 'constant_not_defined'
			) );
			// Fallback to EDD license activation.
			$result = $this->edd_activate_license( $license_key );
		}

		$this->logger->debug( __( 'The license activation was performed with the following results', 'conjurewp' ), $result );

		wp_send_json( array_merge( array( 'done' => 1 ), $result ) );
	}

	/**
	 * Activate Freemius license key.
	 *
	 * @param string $license_key The license key to activate.
	 * @return array Activation result with 'success' and 'message' keys.
	 */
	public function freemius_activate_license( $license_key ) {
		$success = false;
		$message = '';

		if ( ! function_exists( 'con_fs' ) ) {
			return array(
				'success' => false,
				'message' => esc_html__( 'Freemius SDK is not available.', 'conjurewp' ),
			);
		}

		$fs = con_fs();
		if ( ! $fs || ! is_object( $fs ) ) {
			return array(
				'success' => false,
				'message' => esc_html__( 'Freemius SDK is not initialised.', 'conjurewp' ),
			);
		}

		$license_key = trim( $license_key );

		// Use Freemius SDK's activate_license method if available (preferred method).
		if ( method_exists( $fs, 'activate_license' ) ) {
			$result = $fs->activate_license( $license_key );
			
			$this->logger->debug( 'Freemius activate_license result', array( 'result' => $result ) );

			if ( is_object( $result ) && isset( $result->error ) ) {
				$success = false;
				$error_message = '';
				if ( is_string( $result->error ) ) {
					$error_message = $result->error;
				} elseif ( is_object( $result->error ) && isset( $result->error->message ) ) {
					$error_message = $result->error->message;
				}
				$message = ! empty( $error_message ) 
					? esc_html( $error_message )
					: esc_html__( 'License activation failed. Please verify your license key and try again.', 'conjurewp' );
			} else {
				// Sync license and verify activation.
				$this->sync_freemius_license( $fs );
				$success = $this->freemius_has_active_license( $fs );

				if ( $success ) {
					$theme = $this->conjure->theme->get( 'Name' );
					$message = sprintf(
						/* translators: %s: Theme name */
						esc_html__( 'Your ConjureWP license has been activated successfully! You can now use premium features with %s.', 'conjurewp' ),
						$theme
					);
					$this->step_manager->mark_step_completed( 'license' );
				} else {
					$success = false;
					$message = esc_html__( 'License activation failed. Please verify your license key and try again.', 'conjurewp' );
				}
			}
		} elseif ( $fs->is_registered() ) {
			// User is registered - activate license via API.
			$api = $fs->get_api_site_scope();
			if ( ! $api ) {
				return array(
					'success' => false,
					'message' => esc_html__( 'Unable to connect to Freemius API.', 'conjurewp' ),
				);
			}

			// Activate license using the install endpoint.
			$params = array(
				'license_key' => $fs->apply_filters( 'license_key', $license_key ),
			);

			$result = $api->call( $fs->add_show_pending( '/' ), 'put', $params );

			$this->logger->debug( 'Freemius API call result', array( 'result' => $result ) );

			// Check if result is an error.
			$is_error = ( is_object( $result ) && isset( $result->error ) ) || false === $result;

			if ( ! $is_error && is_object( $result ) ) {
				// License activated successfully.
				$this->sync_freemius_license( $fs );
				$success = $this->freemius_has_active_license( $fs );

				if ( $success ) {
					$theme = $this->conjure->theme->get( 'Name' );
					$message = sprintf(
						/* translators: %s: Theme name */
						esc_html__( 'Your ConjureWP license has been activated successfully! You can now use premium features with %s.', 'conjurewp' ),
						$theme
					);
					$this->step_manager->mark_step_completed( 'license' );
				} else {
					$success = false;
					$message = esc_html__( 'License activation failed. Please verify your license key and try again.', 'conjurewp' );
				}
			} else {
				// Handle API errors.
				$success = false;
				$error_message = '';
				if ( is_object( $result ) && isset( $result->error ) ) {
					if ( is_string( $result->error ) ) {
						$error_message = $result->error;
					} elseif ( is_object( $result->error ) && isset( $result->error->message ) ) {
						$error_message = $result->error->message;
					}
				}

				$message = ! empty( $error_message ) 
					? esc_html( $error_message )
					: esc_html__( 'License activation failed. Please verify your license key and try again.', 'conjurewp' );
			}
		} else {
			// User is not registered - need to register first via opt_in.
			$next_page = $fs->opt_in(
				false, // email
				false, // first
				false, // last
				$license_key, // license_key
				false, // is_uninstall
				false, // trial_plan_id
				false, // is_disconnected
				null, // is_marketing_allowed
				array(), // sites
				false, // redirect - set to false for AJAX calls to prevent redirect URLs
				null // license_owner_id
			);

			$this->logger->debug( 'Freemius opt_in result', array( 'result' => $next_page, 'type' => gettype( $next_page ), 'is_registered' => $fs->is_registered() ) );

			// Check for errors in the response.
			if ( is_object( $next_page ) && isset( $next_page->error ) ) {
				$success = false;
				$error_message = '';
				if ( is_string( $next_page->error ) ) {
					$error_message = $next_page->error;
				} elseif ( is_object( $next_page->error ) && isset( $next_page->error->message ) ) {
					$error_message = $next_page->error->message;
				}
				$message = ! empty( $error_message ) 
					? esc_html( $error_message )
					: esc_html__( 'License activation failed. Please verify your license key and try again.', 'conjurewp' );
			} else {
				// opt_in with redirect=false completed. Now check if license was activated successfully.
				$this->sync_freemius_license( $fs );
				
				// Check if user is now registered and has active license.
				$is_registered = $fs->is_registered();
				$has_license = $this->freemius_has_active_license( $fs );
				
				$this->logger->debug( 'After opt_in sync check', array( 
					'is_registered' => $is_registered,
					'has_license' => $has_license 
				) );

				if ( ! $is_registered ) {
					$success = false;
					$message = esc_html__( 'Registration failed. Please verify your license key and try again.', 'conjurewp' );
					$this->logger->warning( 'User not registered after opt_in' );
				} elseif ( ! $has_license ) {
					$success = false;
					$message = esc_html__( 'License activation failed. Please verify your license key and try again.', 'conjurewp' );
					$this->logger->warning( 'License activation failed after opt_in', array( 'is_registered' => $is_registered ) );
				} else {
					// Success - user is now registered and license is activated.
					$theme = $this->conjure->theme->get( 'Name' );
					$message = sprintf(
						/* translators: %s: Theme name */
						esc_html__( 'Your ConjureWP license has been activated successfully! You can now use premium features with %s.', 'conjurewp' ),
						$theme
					);
					$success = true;
					$this->step_manager->mark_step_completed( 'license' );
				}
			}
		}

		return compact( 'success', 'message' );
	}

	/**
	 * Sync Freemius license data locally after activation.
	 *
	 * @param object $fs Freemius instance.
	 */
	private function sync_freemius_license( $fs ) {
		if ( $fs && is_object( $fs ) && method_exists( $fs, 'reconnect_locally' ) && method_exists( $fs, '_sync_license' ) ) {
			$fs->reconnect_locally();
			$fs->_sync_license( true );
		}
	}

	/**
	 * Check if Freemius has an active valid license.
	 *
	 * @param object $fs Freemius instance.
	 * @return bool
	 */
	private function freemius_has_active_license( $fs ) {
		if ( $fs && is_object( $fs ) && method_exists( $fs, 'has_active_valid_license' ) ) {
			return (bool) $fs->has_active_valid_license();
		}

		return false;
	}

	/**
	 * Activate the EDD license.
	 *
	 * This code was taken from the EDD licensing addon theme example code
	 * (`activate_license` method of the `EDD_Theme_Updater_Admin` class).
	 *
	 * @param string $license The license key.
	 * @return array
	 */
	public function edd_activate_license( $license ) {
		$success = false;

		// Strings passed in from the config file.
		$strings = $this->conjure->strings;

		// Theme Name.
		$theme = ucfirst( $this->conjure->theme );

		// Remove "Child" from the current theme name, if it's installed.
		$theme = str_replace( ' Child', '', $theme );

		// Text strings.
		$success_message = $strings['license-json-success%s'];

		// Data to send in our API request.
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => rawurlencode( $license ),
			'item_name'  => rawurlencode( $this->conjure->edd_item_name ),
			'url'        => esc_url( home_url( '/' ) ),
		);

		$response = $this->edd_get_api_response( $api_params );

		// Make sure the response came back okay.
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$success = false;
			
			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				$error_code = $response->get_error_code();
				$this->logger->error( 'EDD license activation HTTP error', array(
					'error_code' => $error_code,
					'error_message' => $error_message
				) );
				$message = sprintf(
					/* translators: %s: Error message */
					esc_html__( 'Connection error: %s', 'conjurewp' ),
					esc_html( $error_message )
				);
			} else {
				$response_code = wp_remote_retrieve_response_code( $response );
				$response_body = wp_remote_retrieve_body( $response );
				$this->logger->error( 'EDD license activation failed', array(
					'response_code' => $response_code,
					'response_body' => substr( $response_body, 0, 500 )
				) );
				$message = sprintf(
					/* translators: %d: HTTP response code */
					esc_html__( 'Server returned error code %d. Please check your license key and try again.', 'conjurewp' ),
					$response_code
				);
			}
		} else {

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( false === $license_data->success ) {

				switch ( $license_data->error ) {

					case 'expired':
						$message = sprintf(
						/* translators: Expiration date */
							esc_html__( 'Your license key expired on %s.', 'conjurewp' ),
							date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, time() ) )
						);
						break;

					case 'revoked':
						$message = esc_html__( 'Your license key has been disabled.', 'conjurewp' );
						break;

					case 'missing':
						$message = esc_html__( 'This appears to be an invalid license key. Please try again or contact support.', 'conjurewp' );
						break;

					case 'invalid':
					case 'site_inactive':
						$message = esc_html__( 'Your license is not active for this URL.', 'conjurewp' );
						break;

					case 'item_name_mismatch':
						/* translators: EDD Item Name */
						$message = sprintf( esc_html__( 'This appears to be an invalid license key for %s.', 'conjurewp' ), $this->conjure->edd_item_name );
						break;

					case 'no_activations_left':
						$message = esc_html__( 'Your license key has reached its activation limit.', 'conjurewp' );
						break;

					default:
						$message = esc_html__( 'An error occurred, please try again.', 'conjurewp' );
						break;
				}
			} else {
				if ( 'valid' === $license_data->license ) {
					$message = sprintf( esc_html( $success_message ), $theme );
					$success = true;

					// Removes the default EDD hook for this option, which breaks the AJAX call.
					remove_all_actions( 'update_option_' . $this->conjure->edd_theme_slug . '_license_key', 10 );

					update_option( $this->conjure->edd_theme_slug . '_license_key', $license );
					update_option( $this->conjure->edd_theme_slug . '_license_key_status', $license_data->license );
					
					$this->step_manager->mark_step_completed( 'license' );
				}
			}
		}

		if ( $success ) {
			do_action( 'conjure_license_activated' );
		}

		return compact( 'success', 'message' );
	}

	/**
	 * Makes a call to the API.
	 *
	 * @param array $api_params The params to send to the API.
	 * @return array|WP_Error
	 */
	private function edd_get_api_response( $api_params ) {
		// Call the custom API.
		$response = wp_remote_post(
			$this->conjure->edd_remote_api_url,
			array(
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $api_params,
			)
		);

		return $response;
	}

	/**
	 * Allow Freemius gating to accept a valid EDD license.
	 *
	 * @param bool   $has_access Current access flag from Freemius.
	 * @param string $theme_name Theme name passed through the filter (informational).
	 * @return bool
	 */
	public function grant_access_for_valid_edd_license( $has_access, $theme_name = '' ) {
		if ( $has_access ) {
			return true;
		}

		if ( empty( $this->conjure->edd_theme_slug ) ) {
			return $has_access;
		}

		return $this->is_theme_registered() ? true : $has_access;
	}

	/**
	 * Check, if the theme is currently registered.
	 *
	 * @return boolean
	 */
	public function is_theme_registered() {
		if ( function_exists( 'con_fs' ) ) {
			$fs = con_fs();
			if ( $fs && is_object( $fs ) && method_exists( $fs, 'is_registered' ) && method_exists( $fs, 'has_active_valid_license' ) ) {
				if ( $fs->is_registered() && $fs->has_active_valid_license() ) {
					return true;
				}
			}
		}

		$is_registered = get_option( $this->conjure->edd_theme_slug . '_license_key_status', false ) === 'valid';
		return apply_filters( 'conjure_is_theme_registered', $is_registered );
	}
}

