<?php
/**
 * Premium Features Usage Examples
 *
 * This file shows how to gate premium features in your plugin.
 * Copy these patterns throughout your codebase where needed.
 *
 * @package ConjureWP
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ============================================================================
// EXAMPLE 1: Simple runtime feature check (license-based)
// ============================================================================

/**
 * Enable automatic plugin installation (premium feature).
 * This checks if the user has a valid license at RUNTIME.
 */
function conjurewp_automatic_plugin_installation() {
	// Check if user has premium access.
	if ( ! Conjure_Premium_Features::is_premium() ) {
		return; // Feature not available for free users.
	}

	// Premium feature code here.
	// Install plugins automatically...
}

// ============================================================================
// EXAMPLE 1B: Code stripping using __premium_only() (Freemius will remove this from free version)
// ============================================================================

/**
 * Premium-only function that gets REMOVED from free version by Freemius.
 * 
 * When you upload to Freemius, any code wrapped in is__premium_only() 
 * will be automatically stripped from the free version download.
 */
function conjurewp_premium_batch_import() {
	// This entire function will be removed from the free version.
	if ( Conjure_Premium_Features::is_premium_code() ) {
		// Advanced batch import code here.
		// This code won't even exist in the free version files.
	}
}

// ============================================================================
// EXAMPLE 2: Show upgrade notice when feature is unavailable
// ============================================================================

/**
 * Display advanced demo import options.
 */
function conjurewp_render_advanced_demo_options() {
	if ( ! Conjure_Premium_Features::is_premium() ) {
		// Show upgrade notice.
		Conjure_Premium_Features::show_upgrade_notice( 'Advanced Demo Import' );
		return;
	}

	// Show the advanced options UI.
	?>
	<div class="advanced-demo-options">
		<!-- Premium feature UI here -->
	</div>
	<?php
}

// ============================================================================
// EXAMPLE 3: Gate API endpoint
// ============================================================================

/**
 * REST API endpoint for batch operations (premium).
 */
function conjurewp_register_premium_endpoints() {
	register_rest_route(
		'conjurewp/v1',
		'/batch-import',
		array(
			'methods'             => 'POST',
			'callback'            => 'conjurewp_handle_batch_import',
			'permission_callback' => function() {
				// Require both admin access AND premium license.
				return current_user_can( 'manage_options' ) && Conjure_Premium_Features::is_premium();
			},
		)
	);
}

// ============================================================================
// EXAMPLE 4: Add premium badge to UI
// ============================================================================

/**
 * Add menu item with premium badge.
 */
function conjurewp_add_premium_menu_item() {
	$menu_title = __( 'Batch Import', 'conjurewp' );
	
	// Add premium badge if user doesn't have license.
	if ( ! Conjure_Premium_Features::is_premium() ) {
		$menu_title .= Conjure_Premium_Features::get_premium_badge();
	}

	add_submenu_page(
		'conjurewp-setup',
		__( 'Batch Import', 'conjurewp' ),
		$menu_title,
		'manage_options',
		'conjurewp-batch-import',
		'conjurewp_batch_import_page'
	);
}

// ============================================================================
// EXAMPLE 5: Check specific plan level
// ============================================================================

/**
 * White-label features for Business plan users.
 */
function conjurewp_enable_white_label() {
	// Only available for Business plan.
	if ( ! Conjure_Premium_Features::has_plan( 'business' ) ) {
		return false;
	}

	// Remove ConjureWP branding.
	remove_action( 'conjure_footer', 'conjure_footer_branding' );
	
	return true;
}

// ============================================================================
// EXAMPLE 6: Conditional feature in settings
// ============================================================================

/**
 * Add settings field with upgrade prompt.
 */
function conjurewp_add_premium_setting() {
	?>
	<tr>
		<th scope="row">
			<?php esc_html_e( 'Auto Install Plugins', 'conjurewp' ); ?>
			<?php if ( ! Conjure_Premium_Features::is_premium() ) : ?>
				<?php echo Conjure_Premium_Features::get_premium_badge(); ?>
			<?php endif; ?>
		</th>
		<td>
			<?php if ( Conjure_Premium_Features::is_premium() ) : ?>
				<label>
					<input 
						type="checkbox" 
						name="conjurewp_auto_install_plugins" 
						value="1" 
						<?php checked( get_option( 'conjurewp_auto_install_plugins' ), 1 ); ?>
					/>
					<?php esc_html_e( 'Automatically install required plugins', 'conjurewp' ); ?>
				</label>
			<?php else : ?>
				<p class="description">
					<?php esc_html_e( 'This feature requires a premium license.', 'conjurewp' ); ?>
					<a href="<?php echo esc_url( Conjure_Premium_Features::get_upgrade_url() ); ?>">
						<?php esc_html_e( 'Upgrade now', 'conjurewp' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

// ============================================================================
// EXAMPLE 7: Filter-based feature gating
// ============================================================================

/**
 * Limit number of demos for free users.
 */
add_filter( 'conjure_import_files', function( $demos ) {
	// Free users can only import 1 demo.
	if ( ! Conjure_Premium_Features::is_premium() && count( $demos ) > 1 ) {
		// Keep only the first demo.
		$demos = array( $demos[0] );
		
		// Add upgrade notice.
		add_action( 'conjure_before_demo_list', function() {
			?>
			<div class="notice notice-warning">
				<p>
					<strong><?php esc_html_e( 'Want more demos?', 'conjurewp' ); ?></strong>
					<?php esc_html_e( 'Upgrade to premium to unlock all demo imports.', 'conjurewp' ); ?>
					<a href="<?php echo esc_url( Conjure_Premium_Features::get_upgrade_url() ); ?>" class="button button-primary">
						<?php esc_html_e( 'Upgrade Now', 'conjurewp' ); ?>
					</a>
				</p>
			</div>
			<?php
		});
	}
	
	return $demos;
}, 999 );

// ============================================================================
// EXAMPLE 8: Shortcode with premium check
// ============================================================================

/**
 * Premium shortcode for advanced layouts.
 */
add_shortcode( 'conjure_advanced_layout', function( $atts ) {
	if ( ! Conjure_Premium_Features::is_premium() ) {
		return sprintf(
			'<div class="conjure-premium-notice" style="padding: 20px; background: #f0f0f1; border-left: 4px solid #ff6b6b;">
				<p><strong>%s</strong></p>
				<p>%s <a href="%s">%s</a></p>
			</div>',
			esc_html__( 'Premium Feature', 'conjurewp' ),
			esc_html__( 'This shortcode requires a premium license.', 'conjurewp' ),
			esc_url( Conjure_Premium_Features::get_upgrade_url() ),
			esc_html__( 'Upgrade Now', 'conjurewp' )
		);
	}

	// Premium shortcode functionality here.
	return '<div class="advanced-layout">...</div>';
});

// ============================================================================
// EXAMPLE 9: AJAX handler with premium check
// ============================================================================

/**
 * Handle premium AJAX request.
 */
add_action( 'wp_ajax_conjurewp_batch_process', function() {
	// Security check.
	check_ajax_referer( 'conjurewp_nonce', 'nonce' );

	// Premium check.
	if ( ! Conjure_Premium_Features::is_premium() ) {
		wp_send_json_error( array(
			'message' => __( 'This feature requires a premium license.', 'conjurewp' ),
			'upgrade_url' => Conjure_Premium_Features::get_upgrade_url(),
		) );
	}

	// Process premium feature.
	wp_send_json_success( array(
		'message' => __( 'Batch processing completed!', 'conjurewp' ),
	) );
});

// ============================================================================
// EXAMPLE 10: Alternative approach using Freemius directly
// ============================================================================

/**
 * Direct Freemius check (alternative method).
 */
function conjurewp_premium_feature_direct() {
	// Check if Freemius is loaded.
	if ( function_exists( 'con_fs' ) ) {
		$fs = con_fs();
		
		// Check various license states.
		if ( $fs->is_paying() ) {
			// User has paid license.
		} elseif ( $fs->is_trial() ) {
			// User is on trial.
		} elseif ( $fs->is_free_plan() ) {
			// User is on free plan.
		}
		
		// Check license expiration.
		if ( $fs->is_paying() && ! $fs->is_paying_or_trial() ) {
			// License expired.
		}
	}
}

// ============================================================================
// IMPORTANT: Understanding Runtime Checks vs Code Stripping
// ============================================================================

/**
 * TWO WAYS TO GATE PREMIUM FEATURES:
 * 
 * 1. RUNTIME LICENSE CHECK (is_premium())
 *    - Code exists in BOTH free and premium versions
 *    - Feature unlocks when user enters valid license
 *    - Use for: Settings, UI elements, features that should be "discoverable"
 * 
 * 2. CODE STRIPPING (is_premium_code() / is__premium_only())
 *    - Code is REMOVED from free version by Freemius during deployment
 *    - Code doesn't exist at all in free version files
 *    - Use for: Security-sensitive code, large premium-only classes/files
 * 
 * WHICH SHOULD YOU USE?
 * 
 * Use RUNTIME CHECKS (is_premium()) for:
 * - UI elements with upgrade prompts
 * - Settings that should show "upgrade to unlock"
 * - Features users should know exist
 * 
 * Use CODE STRIPPING (is_premium_code()) for:
 * - Large premium-only classes/files
 * - Security-sensitive premium features
 * - Code you don't want in free version at all
 * - Reducing free version file size
 */

// Example combining both approaches:

/**
 * Premium feature with code stripping.
 */
function conjurewp_register_premium_features() {
	// Show UI for premium feature (visible to all, with upgrade prompt).
	add_action( 'conjurewp_settings_page', function() {
		?>
		<h2>
			<?php esc_html_e( 'Batch Import', 'conjurewp' ); ?>
			<?php if ( ! Conjure_Premium_Features::is_premium() ) : ?>
				<?php echo Conjure_Premium_Features::get_premium_badge(); ?>
			<?php endif; ?>
		</h2>
		
		<?php if ( ! Conjure_Premium_Features::is_premium() ) : ?>
			<p><?php esc_html_e( 'Batch import multiple demos at once.', 'conjurewp' ); ?></p>
			<a href="<?php echo esc_url( Conjure_Premium_Features::get_upgrade_url() ); ?>" class="button button-primary">
				<?php esc_html_e( 'Upgrade to Unlock', 'conjurewp' ); ?>
			</a>
		<?php else : ?>
			<!-- Show actual batch import UI -->
			<div class="batch-import-ui">
				<!-- This UI code exists in both versions -->
			</div>
		<?php endif; ?>
		<?php
	});
	
	// Register the actual premium functionality (code stripping).
	// This code will be REMOVED from free version by Freemius.
	if ( Conjure_Premium_Features::is_premium_code() ) {
		// Load premium-only class.
		require_once CONJUREWP_PLUGIN_DIR . 'includes/premium/class-batch-importer.php';
		
		// Register premium endpoints.
		add_action( 'rest_api_init', function() {
			// This entire REST API won't exist in free version.
			register_rest_route( 'conjurewp/v1', '/batch-import', array(
				'methods'  => 'POST',
				'callback' => array( 'Conjure_Batch_Importer', 'process' ),
			));
		});
	}
}
add_action( 'init', 'conjurewp_register_premium_features' );

