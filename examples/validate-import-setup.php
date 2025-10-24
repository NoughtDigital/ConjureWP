<?php
/**
 * Import Setup Validator for ConjureWP
 *
 * This script helps validate your demo content import setup.
 * Add this to your theme temporarily to check if everything is configured correctly.
 *
 * Usage: Add to your theme's functions.php, then check your WordPress debug log.
 *
 * @package ConjureWP
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validate ConjureWP import setup.
 *
 * Checks:
 * 1. If ConjureWP plugin is active
 * 2. If import files filter is registered
 * 3. If import files are properly configured
 * 4. If files exist and are readable
 * 5. If required WordPress functions are available
 */
function conjurewp_validate_import_setup() {
	// Only run in admin.
	if ( ! is_admin() ) {
		return;
	}

	$results  = array();
	$errors   = array();
	$warnings = array();

	// Check 1: Is ConjureWP plugin active?
	if ( ! class_exists( 'Conjure' ) ) {
		$errors[] = 'ConjureWP plugin is not active or not installed.';
		return; // Can't continue without the plugin.
	}
	$results[] = '✓ ConjureWP plugin is active';

	// Check 2: Is the import files filter registered?
	if ( ! has_filter( 'conjure_import_files' ) ) {
		$warnings[] = 'No filter registered for conjure_import_files. You need to add a filter to define your import files.';
	} else {
		$results[] = '✓ conjure_import_files filter is registered';

		// Check 3: Get the import files configuration.
		$import_files = apply_filters( 'conjure_import_files', array() );

		if ( empty( $import_files ) ) {
			$warnings[] = 'conjure_import_files filter returns empty array. Check your filter function.';
		} else {
			$results[] = '✓ Found ' . count( $import_files ) . ' import configuration(s)';

			// Check each import configuration.
			foreach ( $import_files as $index => $import_config ) {
				$config_errors   = array();
				$config_warnings = array();

				// Check required field: import_file_name.
				if ( empty( $import_config['import_file_name'] ) ) {
					$config_errors[] = "Import #{$index}: Missing required 'import_file_name' field";
				} else {
					$results[] = "✓ Import #{$index}: {$import_config['import_file_name']}";
				}

				// Check content file.
				if ( ! empty( $import_config['local_import_file'] ) ) {
					if ( file_exists( $import_config['local_import_file'] ) ) {
						if ( is_readable( $import_config['local_import_file'] ) ) {
							$results[] = '  ✓ Content file exists and is readable';
						} else {
							$config_errors[] = '  ✗ Content file exists but is NOT readable (check permissions)';
						}
					} else {
						$config_errors[] = "  ✗ Content file NOT found: {$import_config['local_import_file']}";
					}
				} elseif ( ! empty( $import_config['import_file_url'] ) ) {
					$results[] = "  ℹ Content file URL provided: {$import_config['import_file_url']}";
				} else {
					$config_warnings[] = '  ! No content file (local or URL) specified';
				}

				// Check widget file.
				if ( ! empty( $import_config['local_import_widget_file'] ) ) {
					if ( file_exists( $import_config['local_import_widget_file'] ) ) {
						if ( is_readable( $import_config['local_import_widget_file'] ) ) {
							$results[] = '  ✓ Widget file exists and is readable';
						} else {
							$config_errors[] = '  ✗ Widget file exists but is NOT readable (check permissions)';
						}
					} else {
						$config_errors[] = "  ✗ Widget file NOT found: {$import_config['local_import_widget_file']}";
					}
				} elseif ( ! empty( $import_config['import_widget_file_url'] ) ) {
					$results[] = "  ℹ Widget file URL provided: {$import_config['import_widget_file_url']}";
				} else {
					$config_warnings[] = '  ! No widget file specified (optional)';
				}

				// Check customizer file.
				if ( ! empty( $import_config['local_import_customizer_file'] ) ) {
					if ( file_exists( $import_config['local_import_customizer_file'] ) ) {
						if ( is_readable( $import_config['local_import_customizer_file'] ) ) {
							$results[] = '  ✓ Customizer file exists and is readable';
						} else {
							$config_errors[] = '  ✗ Customizer file exists but is NOT readable (check permissions)';
						}
					} else {
						$config_errors[] = "  ✗ Customizer file NOT found: {$import_config['local_import_customizer_file']}";
					}
				} elseif ( ! empty( $import_config['import_customizer_file_url'] ) ) {
					$results[] = "  ℹ Customizer file URL provided: {$import_config['import_customizer_file_url']}";
				} else {
					$config_warnings[] = '  ! No customizer file specified (optional)';
				}

				// Check Redux files.
				if ( ! empty( $import_config['local_import_redux'] ) ) {
					if ( is_array( $import_config['local_import_redux'] ) ) {
						foreach ( $import_config['local_import_redux'] as $redux_index => $redux_config ) {
							if ( ! empty( $redux_config['file_path'] ) ) {
								if ( file_exists( $redux_config['file_path'] ) ) {
									if ( is_readable( $redux_config['file_path'] ) ) {
										$results[] = "  ✓ Redux file #{$redux_index} exists and is readable";
									} else {
										$config_errors[] = "  ✗ Redux file #{$redux_index} exists but is NOT readable";
									}
								} else {
									$config_errors[] = "  ✗ Redux file #{$redux_index} NOT found: {$redux_config['file_path']}";
								}
							}

							if ( empty( $redux_config['option_name'] ) ) {
								$config_errors[] = "  ✗ Redux config #{$redux_index} missing 'option_name'";
							}
						}
					} else {
						$config_errors[] = '  ✗ local_import_redux must be an array';
					}
				}

				// Check Revolution Slider file.
				if ( ! empty( $import_config['local_import_rev_slider_file'] ) ) {
					if ( file_exists( $import_config['local_import_rev_slider_file'] ) ) {
						if ( is_readable( $import_config['local_import_rev_slider_file'] ) ) {
							$results[] = '  ✓ Revolution Slider file exists and is readable';
						} else {
							$config_errors[] = '  ✗ Revolution Slider file exists but is NOT readable';
						}
					} else {
						$config_errors[] = "  ✗ Revolution Slider file NOT found: {$import_config['local_import_rev_slider_file']}";
					}
				}

				// Merge config-specific issues.
				$errors   = array_merge( $errors, $config_errors );
				$warnings = array_merge( $warnings, $config_warnings );
			}
		}
	}

	// Check 4: Is after import hook registered?
	if ( has_action( 'conjure_after_all_import' ) ) {
		$results[] = '✓ conjure_after_all_import hook is registered';
	} else {
		$warnings[] = 'No conjure_after_all_import hook registered. Consider adding post-import setup code.';
	}

	// Check 5: Required WordPress functions.
	if ( ! function_exists( 'get_template_directory' ) ) {
		$errors[] = 'get_template_directory() function not available';
	}

	if ( ! function_exists( 'trailingslashit' ) ) {
		$errors[] = 'trailingslashit() function not available';
	}

	// Output results.
	$output   = array();
	$output[] = "\n========================================";
	$output[] = 'ConjureWP Import Setup Validation';
	$output[] = "========================================\n";

	if ( ! empty( $results ) ) {
		$output[] = 'RESULTS:';
		foreach ( $results as $result ) {
			$output[] = $result;
		}
		$output[] = '';
	}

	if ( ! empty( $warnings ) ) {
		$output[] = 'WARNINGS:';
		foreach ( $warnings as $warning ) {
			$output[] = $warning;
		}
		$output[] = '';
	}

	if ( ! empty( $errors ) ) {
		$output[] = 'ERRORS:';
		foreach ( $errors as $error ) {
			$output[] = $error;
		}
		$output[] = '';
	}

	if ( empty( $errors ) && empty( $warnings ) ) {
		$output[] = '✓✓✓ ALL CHECKS PASSED! Your import setup is ready. ✓✓✓';
	} elseif ( empty( $errors ) ) {
		$output[] = 'Your setup is functional but has some warnings to review.';
	} else {
		$output[] = '✗✗✗ ERRORS FOUND! Please fix the errors above. ✗✗✗';
	}

	$output[] = "\n========================================\n";

	// Log to WordPress debug log.
	error_log( implode( "\n", $output ) );

	// Also output to admin notices if in admin.
	if ( current_user_can( 'manage_options' ) ) {
		add_action(
			'admin_notices',
			function () use ( $output, $errors ) {
				$class = empty( $errors ) ? 'notice-success' : 'notice-error';
				echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible">';
				echo '<p><strong>ConjureWP Import Validation:</strong></p>';
				echo '<pre style="background: #f5f5f5; padding: 10px; overflow: auto;">';
				echo esc_html( implode( "\n", $output ) );
				echo '</pre>';
				echo '</div>';
			}
		);
	}
}

// Run validation on admin_init (only once per session to avoid spam).
add_action(
	'admin_init',
	function () {
		// Only run if a specific query parameter is set.
		if ( isset( $_GET['conjurewp_validate'] ) && current_user_can( 'manage_options' ) ) {
			conjurewp_validate_import_setup();
		}
	},
	999
);

/**
 * Add a validation link to the admin menu for easy access.
 */
add_action(
	'admin_menu',
	function () {
		// Only for administrators.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Add a submenu under Tools.
		add_management_page(
			'ConjureWP Validation',
			'ConjureWP Validation',
			'manage_options',
			'conjurewp-validate',
			function () {
				?>
			<div class="wrap">
				<h1>ConjureWP Import Setup Validation</h1>
				<p>Click the button below to validate your import setup configuration.</p>
				<p>
						<a href="<?php echo esc_url( admin_url( 'tools.php?page=conjurewp-validate&conjurewp_validate=1' ) ); ?>" class="button button-primary">
						Run Validation
					</a>
				</p>
				<p><em>Results will be displayed above and also logged to your WordPress debug log.</em></p>
			</div>
				<?php
			}
		);
	}
);

