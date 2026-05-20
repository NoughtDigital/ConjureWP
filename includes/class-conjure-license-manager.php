<?php
/**
 * License Manager class
 *
 * Handles license validation and activation for both EDD and Freemius.
 *
 * @package   Conjure WP
 * @version   1.0.0
 * @link      https://ConjureWP.com/
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
					'message' => esc_html__( 'Yikes! The license activation failed. Please try again or contact support.', 'ConjureWP' ),
				)
			);
		}

		if ( empty( $_POST['license_key'] ) ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => esc_html__( 'Please add your license key before attempting to activate one.', 'ConjureWP' ),
				)
			);
		}

		$license_key = sanitize_text_field( wp_unslash( $_POST['license_key'] ) );

		// Ensure Freemius integration file is loaded.
		$freemius_file = function_exists( 'conjurewp_get_runtime_path' )
			? conjurewp_get_runtime_path( 'includes/class-conjure-freemius.php' )
			: '';

		if (
			! function_exists( 'con_fs' )
			&& ! defined( 'CONJUREWP_FREEMIUS_LOADED' )
			&& ! empty( $freemius_file )
			&& file_exists( $freemius_file )
		) {
			require_once $freemius_file;
		}

		// Check if custom filter exists (for theme developers to override).
		if ( has_filter( 'conjure_ajax_activate_license' ) ) {
			$this->logger->debug( 'Using custom license activation filter' );
			$result = apply_filters( 'conjure_ajax_activate_license', $license_key );
		} elseif ( function_exists( 'con_fs' ) ) {
			$fs = con_fs();
			// Check if Freemius SDK is actually available (not just the stub function).
			$fs_available = ( $fs && is_object( $fs ) && method_exists( $fs, 'is_registered' ) );

			if ( $fs_available ) {
				$this->logger->debug( 'Activating license via Freemius SDK' );
				$result = $this->freemius_activate_license( $license_key );
			} else {
				$this->logger->debug( 'Freemius SDK not initialised, falling back to EDD activation' );
				$result = $this->edd_activate_license( $license_key );
			}
		} else {
			$this->logger->debug( 'Freemius not available, falling back to EDD activation' );
			$result = $this->edd_activate_license( $license_key );
		}

		$this->logger->debug( __( 'The license activation was performed with the following results', 'ConjureWP' ), $result );

		// If activation succeeded, re-evaluate steps and provide the correct next step URL.
		// This is needed because activating a license may unlock new steps (e.g. 'plugins').
		if ( ! empty( $result['success'] ) ) {
			$this->conjure->steps();
			$step_keys   = array_keys( $this->conjure->steps );
			$current_idx = array_search( 'license', $step_keys, true );
			if ( false !== $current_idx && isset( $step_keys[ $current_idx + 1 ] ) ) {
				$result['redirect_url'] = $this->conjure->get_wizard_url(
					array(
						'step' => $step_keys[ $current_idx + 1 ],
					)
				);
			}
		}

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
				'message' => esc_html__( 'Freemius SDK is not available.', 'ConjureWP' ),
			);
		}

		$fs = con_fs();
		if ( ! $fs || ! is_object( $fs ) ) {
			return array(
				'success' => false,
				'message' => esc_html__( 'Freemius SDK is not initialised.', 'ConjureWP' ),
			);
		}

		$license_key = trim( $license_key );

		// Use Freemius SDK's activate_license method if available (preferred method).
		if ( method_exists( $fs, 'activate_license' ) ) {
			$result = $fs->activate_license( $license_key );

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
					: esc_html__( 'License activation failed. Please verify your license key and try again.', 'ConjureWP' );
			} else {
				// Sync license and verify activation.
				$this->sync_freemius_license( $fs );
				$success = $this->freemius_has_active_license( $fs );

				if ( $success ) {
					$theme = $this->conjure->theme->get( 'Name' );
					$message = sprintf(
						/* translators: %s: Theme name */
						esc_html__( 'Your ConjureWP license has been activated successfully! You can now use premium features with %s.', 'ConjureWP' ),
						$theme
					);
					$this->step_manager->mark_step_completed( 'license' );
				} else {
					$success = false;
					$message = esc_html__( 'License activation failed. Please verify your license key and try again.', 'ConjureWP' );
				}
			}
		} elseif ( $fs->is_registered() ) {
			// User is registered - activate license via API.
			$api = $fs->get_api_site_scope();
			if ( ! $api ) {
				return array(
					'success' => false,
					'message' => esc_html__( 'Unable to connect to Freemius API.', 'ConjureWP' ),
				);
			}

			// Activate license using the install endpoint.
			$params = array(
				'license_key' => $fs->apply_filters( 'license_key', $license_key ),
			);

			$result = $api->call( $fs->add_show_pending( '/' ), 'put', $params );

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
						esc_html__( 'Your ConjureWP license has been activated successfully! You can now use premium features with %s.', 'ConjureWP' ),
						$theme
					);
					$this->step_manager->mark_step_completed( 'license' );
				} else {
					$success = false;
					$message = esc_html__( 'License activation failed. Please verify your license key and try again.', 'ConjureWP' );
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
					: esc_html__( 'License activation failed. Please verify your license key and try again.', 'ConjureWP' );
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
					: esc_html__( 'License activation failed. Please verify your license key and try again.', 'ConjureWP' );
			} else {
				// opt_in with redirect=false completed. Now check if license was activated successfully.
				$this->sync_freemius_license( $fs );

				// Check if user is now registered and has active license.
				$is_registered = $fs->is_registered();
				$has_license   = $this->freemius_has_active_license( $fs );

				if ( ! $is_registered ) {
					$success = false;
					$message = esc_html__( 'Registration failed. Please verify your license key and try again.', 'ConjureWP' );
					$this->logger->warning( 'User not registered after Freemius opt_in' );
				} elseif ( ! $has_license ) {
					$success = false;
					$message = esc_html__( 'License activation failed. Please verify your license key and try again.', 'ConjureWP' );
					$this->logger->warning( 'License not active after Freemius opt_in' );
				} else {
					// Success - user is now registered and license is activated.
					$theme = $this->conjure->theme->get( 'Name' );
					$message = sprintf(
						/* translators: %s: Theme name */
						esc_html__( 'Your ConjureWP license has been activated successfully! You can now use premium features with %s.', 'ConjureWP' ),
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
		$theme = str_replace( ' Child', '', ucfirst( $this->conjure->theme->name ) );

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
				$this->logger->error(
					'EDD license activation HTTP error',
					array(
						'error_code' => $error_code,
						'error_message' => $error_message,
					)
				);
				$message = sprintf(
					/* translators: %s: Error message */
					esc_html__( 'Connection error: %s', 'ConjureWP' ),
					esc_html( $error_message )
				);
			} else {
				$response_code = wp_remote_retrieve_response_code( $response );
				$response_body = wp_remote_retrieve_body( $response );
				$this->logger->error(
					'EDD license activation failed',
					array(
						'response_code' => $response_code,
						'response_body' => substr( $response_body, 0, 500 ),
					)
				);
				$message = sprintf(
					/* translators: %d: HTTP response code */
					esc_html__( 'Server returned error code %d. Please check your license key and try again.', 'ConjureWP' ),
					$response_code
				);
			}
		} else {

			$body         = wp_remote_retrieve_body( $response );
			$license_data = function_exists( 'conjurewp_json_decode' )
				? conjurewp_json_decode( $body, false )
				: json_decode( $body );

			if ( false === $license_data->success ) {

				switch ( $license_data->error ) {

					case 'expired':
						$message = sprintf(
						/* translators: Expiration date */
							esc_html__( 'Your license key expired on %s.', 'ConjureWP' ),
							date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, time() ) )
						);
						break;

					case 'revoked':
						$message = esc_html__( 'Your license key has been disabled.', 'ConjureWP' );
						break;

					case 'missing':
						$message = esc_html__( 'This appears to be an invalid license key. Please try again or contact support.', 'ConjureWP' );
						break;

					case 'invalid':
					case 'site_inactive':
						$message = esc_html__( 'Your license is not active for this URL.', 'ConjureWP' );
						break;

					case 'item_name_mismatch':
						/* translators: EDD Item Name */
						$message = sprintf( esc_html__( 'This appears to be an invalid license key for %s.', 'ConjureWP' ), $this->conjure->edd_item_name );
						break;

					case 'no_activations_left':
						$message = esc_html__( 'Your license key has reached its activation limit.', 'ConjureWP' );
						break;

					default:
						$message = esc_html__( 'An error occurred, please try again.', 'ConjureWP' );
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

	/**
	 * Resolve the licence step help link URL.
	 *
	 * @return string
	 */
	public function get_license_help_url() {
		$url = $this->conjure->theme_license_help_url;

		if ( ! empty( $url ) ) {
			return apply_filters( 'conjure_license_help_url', $url );
		}

		if ( function_exists( 'con_fs' ) ) {
			$fs = con_fs();
			if ( $fs && is_object( $fs ) && method_exists( $fs, 'is_registered' ) && $fs->is_registered() && method_exists( $fs, 'get_account_url' ) ) {
				$account_url = $fs->get_account_url();
				if ( ! empty( $account_url ) ) {
					return apply_filters( 'conjure_license_help_url', $account_url );
				}
			}
		}

		return apply_filters( 'conjure_license_help_url', 'https://ConjureWP.com/' );
	}

	/**
	 * Render the license activation step.
	 */
	public function render_license_step() {
		$this->logger->debug( __( 'License step view method called', 'ConjureWP' ) );

		$is_theme_registered       = $this->is_theme_registered();
		$action_url                = $this->get_license_help_url();
		$required                  = $this->conjure->license_required;
		$is_theme_registered_class = $is_theme_registered ? ' is-registered' : null;
		$theme                     = str_replace( ' Child', '', ucfirst( $this->conjure->theme->name ) );
		$strings                   = $this->conjure->strings;
		$header                    = ! $is_theme_registered ? $strings['license-header%s'] : $strings['license-header-success%s'];
		$action                    = $strings['license-tooltip'];
		$label                     = $strings['license-label'];
		$skip                      = $strings['btn-license-skip'];
		$next                      = $strings['btn-next'];
		$paragraph                 = ! $is_theme_registered ? $strings['license%s'] : $strings['license-success%s'];
		$install                   = $strings['btn-license-activate'];
		?>

		<div class="conjure__content--transition">

			<?php echo wp_kses( $this->conjure->svg( array( 'icon' => 'license' ) ), $this->conjure->svg_allowed_html() ); ?>

			<svg class="icon icon--checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
				<circle class="icon--checkmark__circle" cx="26" cy="26" r="25" fill="none"/><path class="icon--checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
			</svg>

			<h1><?php echo esc_html( sprintf( $header, $theme ) ); ?></h1>

			<p id="license-text"><?php echo esc_html( sprintf( $paragraph, $theme ) ); ?></p>

			<?php if ( ! $is_theme_registered ) : ?>
				<div class="conjure__content--license-key">
					<label for="license-key"><?php echo esc_html( $label ); ?></label>
					<div class="conjure__content--license-key-wrapper">
						<input type="text" id="license-key" class="js-license-key" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" placeholder="<?php echo esc_attr( __( 'Enter your license key', 'ConjureWP' ) ); ?>">
						<?php if ( ! empty( $action_url ) ) : ?>
							<a href="<?php echo esc_url( $action_url ); ?>" alt="<?php echo esc_attr( $action ); ?>" target="_blank">
								<span class="hint--top" aria-label="<?php echo esc_attr( $action ); ?>">
									<?php echo wp_kses( $this->conjure->svg( array( 'icon' => 'help' ) ), $this->conjure->svg_allowed_html() ); ?>
								</span>
							</a>
						<?php endif ?>
					</div>
				</div>
			<?php endif; ?>

		</div>

		<footer class="conjure__content__footer <?php echo esc_attr( $is_theme_registered_class ); ?>">
			<?php if ( ! $is_theme_registered ) : ?>
				<?php if ( ! $required && ! $this->conjure->license_gate_active ) : ?>
					<a href="<?php echo esc_url( $this->conjure->step_next_link() ); ?>" class="conjure__button conjure__button--skip conjure__button--proceed"><?php echo esc_html( $skip ); ?></a>
				<?php endif ?>
				<a href="<?php echo esc_url( $this->conjure->step_next_link() ); ?>" class="conjure__button conjure__button--next button-next js-conjure-license-activate-button" data-callback="activate_license">
					<span class="conjure__button--loading__text"><?php echo esc_html( $install ); ?></span>
					<?php echo wp_kses( $this->conjure->loading_spinner(), $this->conjure->loading_spinner_allowed_html() ); ?>
				</a>
			<?php else : ?>
				<a href="<?php echo esc_url( $this->conjure->step_next_link() ); ?>" class="conjure__button conjure__button--next conjure__button--proceed conjure__button--colorchange"><?php echo esc_html( $next ); ?></a>
			<?php endif; ?>
			<?php wp_nonce_field( 'conjure' ); ?>
		</footer>
		<?php
		$this->logger->debug( __( 'The license activation step has been displayed', 'ConjureWP' ) );
	}
}
