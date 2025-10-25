<?php
/**
 * Visual Plugin Test Page
 * 
 * This creates a test admin page where you can see the demo-specific plugin selection UI
 * without going through the entire setup wizard.
 * 
 * USAGE:
 * 1. Copy this file to your theme directory
 * 2. Include it in your theme's functions.php:
 *    require_once get_template_directory() . '/visual-plugin-test-page.php';
 * 3. Go to: WordPress Admin → ConjureWP → Plugin Preview
 * 
 * @package ConjureWP
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add the test page to WordPress admin menu.
 */
function conjure_test_add_plugin_preview_page() {
	add_submenu_page(
		'conjurewp-setup',                           // Parent slug (ConjureWP)
		'Plugin Selection Preview',                  // Page title
		'Plugin Preview',                            // Menu title
		'manage_options',                            // Capability
		'conjure-plugin-preview',                    // Menu slug
		'conjure_test_render_plugin_preview_page'    // Callback
	);
}
add_action( 'admin_menu', 'conjure_test_add_plugin_preview_page', 99 );

/**
 * Render the plugin preview page.
 */
function conjure_test_render_plugin_preview_page() {
	// Check user capability
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.' );
	}

	// Load ConjureWP styles if available
	wp_enqueue_style( 'conjure-style' );
	
	?>
	<div class="wrap">
		<h1>Demo-Specific Plugin Selection Preview</h1>
		<p class="description">This page shows how the plugin selection UI looks with demo-specific plugins enabled.</p>
		
		<div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<?php conjure_test_render_plugin_selection_ui(); ?>
		</div>
		
		<div style="background: #e7f3ff; border-left: 4px solid #2271b1; padding: 15px; margin: 20px 0;">
			<h3 style="margin-top: 0;">How to use this in your theme:</h3>
			<ol>
				<li>Copy the code from <code>examples/simple-demo-plugins-no-tgmpa.php</code> to your theme's <code>functions.php</code></li>
				<li>Define your demos with <code>required_plugins</code> arrays</li>
				<li>Go to the actual setup wizard: <strong>Theme Setup Wizard → Plugins</strong></li>
				<li>If you have multiple demos, select one from the dropdown and watch the plugins change!</li>
			</ol>
		</div>
	</div>
	<?php
}

/**
 * Render the plugin selection UI (mimics the wizard step).
 */
function conjure_test_render_plugin_selection_ui() {
	// Get import files
	$import_files = apply_filters( 'conjure_import_files', array() );
	
	if ( empty( $import_files ) ) {
		?>
		<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
			<h3 style="margin-top: 0;">⚠️ No demos configured</h3>
			<p>You need to add demo configurations first. Copy the code from <code>examples/test-demo-plugins.php</code> to your theme's <code>functions.php</code>.</p>
		</div>
		<?php
		return;
	}

	// Check for demo plugin manager
	if ( ! class_exists( 'Conjure_Demo_Plugin_Manager' ) ) {
		echo '<p style="color: red;">Conjure_Demo_Plugin_Manager class not found. Make sure ConjureWP plugin is active.</p>';
		return;
	}

	$demo_plugin_manager = new Conjure_Demo_Plugin_Manager();
	
	// Get selected demo index from GET parameter (for testing)
	$selected_demo_index = isset( $_GET['demo'] ) ? intval( $_GET['demo'] ) : null;
	
	?>
	<style>
		/* Plugin Selection Styles */
		.conjure__demo-selector {
			margin: 2em 0;
			padding: 1.5em;
			background: #f9f9f9;
			border-radius: 8px;
			border: 2px solid #e0e0e0;
		}
		.plugin-section-header {
			background: #fff3cd;
			padding: 0.75em 1em;
			margin-bottom: 0.5em;
			border-left: 4px solid #ffc107;
			font-weight: 600;
			list-style: none;
		}
		.plugin-section-header.recommended {
			background: #d1ecf1;
			border-left-color: #17a2b8;
		}
		.plugin-list {
			list-style: none;
			padding: 0;
			margin: 0;
		}
		.plugin-list li {
			padding: 10px;
			border-bottom: 1px solid #e0e0e0;
			display: flex;
			align-items: center;
		}
		.plugin-list li:last-child {
			border-bottom: none;
		}
		.plugin-badge {
			padding: 2px 8px;
			border-radius: 3px;
			font-size: 0.75em;
			margin-left: 0.5em;
			font-weight: 600;
		}
		.badge-required {
			background: #ffc107;
			color: #856404;
		}
		.badge-optional {
			background: #17a2b8;
			color: white;
		}
		.no-plugins-message {
			background: #d4edda;
			border-left: 4px solid #28a745;
			padding: 15px;
			margin: 20px 0;
		}
	</style>

	<!-- Demo Selector -->
	<div class="conjure__demo-selector">
		<label for="demo-select" style="display: block; margin-bottom: 0.5em; font-weight: 600; font-size: 1.1em;">
			Select your demo:
		</label>
		<select id="demo-select" style="width: 100%; max-width: 400px; padding: 0.75em; font-size: 1em; border: 1px solid #ccc; border-radius: 4px;" onchange="conjure_test_change_demo(this.value)">
			<option value="">-- Choose a demo --</option>
			<?php foreach ( $import_files as $index => $import_file ) : ?>
				<option value="<?php echo esc_attr( $index ); ?>" <?php selected( $selected_demo_index, $index ); ?>>
					<?php echo esc_html( $import_file['import_file_name'] ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description" style="margin-top: 0.75em; font-size: 0.95em; color: #666;">
			Each demo has different plugin requirements. Choose your demo to see required and recommended plugins.
		</p>
		<?php if ( null !== $selected_demo_index ) : ?>
			<p style="margin-top: 0.75em; padding: 0.5em 0.75em; background: #d4edda; color: #155724; border-radius: 4px; font-size: 0.9em;">
				✓ Demo selected! Plugins below are filtered for this demo.
			</p>
		<?php endif; ?>
	</div>

	<script>
		function conjure_test_change_demo(demoIndex) {
			if (demoIndex) {
				window.location.href = '<?php echo admin_url( 'admin.php?page=conjure-plugin-preview' ); ?>&demo=' + demoIndex;
			} else {
				window.location.href = '<?php echo admin_url( 'admin.php?page=conjure-plugin-preview' ); ?>';
			}
		}
	</script>

	<?php
	// Get plugins for selected demo
	if ( null !== $selected_demo_index ) {
		$plugins = $demo_plugin_manager->get_demo_plugins_with_status( $selected_demo_index, $import_files );
		
		$required_plugins = array();
		$recommended_plugins = array();
		
		// Split into required and recommended
		foreach ( $plugins['all'] as $slug => $plugin ) {
			if ( ! empty( $plugin['required'] ) ) {
				$required_plugins[ $slug ] = $plugin;
			} else {
				$recommended_plugins[ $slug ] = $plugin;
			}
		}
		
		$count = count( $plugins['all'] );
		
		if ( $count === 0 ) {
			?>
			<div class="no-plugins-message">
				<h3 style="margin-top: 0;">✓ No plugins required!</h3>
				<p>This demo doesn't need any additional plugins. You can proceed directly to importing the demo content.</p>
			</div>
			<?php
		} else {
			?>
			<div style="margin-top: 2em;">
				<h2>Plugins for this demo (<?php echo esc_html( $count ); ?> total)</h2>
				
				<ul class="plugin-list">
					<?php if ( ! empty( $required_plugins ) ) : ?>
						<li class="plugin-section-header">
							Required Plugins
							<span style="font-weight: normal; color: #856404; font-size: 0.9em; display: block; margin-top: 0.25em;">
								These plugins are essential for the demo to work correctly
							</span>
						</li>
						<?php foreach ( $required_plugins as $slug => $plugin ) : ?>
							<li>
								<input type="checkbox" checked disabled style="margin-right: 10px;">
								<span style="flex: 1;"><?php echo esc_html( $plugin['name'] ); ?></span>
								<span class="plugin-badge badge-required">REQUIRED</span>
							</li>
						<?php endforeach; ?>
					<?php endif; ?>
					
					<?php if ( ! empty( $recommended_plugins ) ) : ?>
						<li class="plugin-section-header recommended" style="margin-top: 1em;">
							Recommended Plugins
							<span style="font-weight: normal; color: #0c5460; font-size: 0.9em; display: block; margin-top: 0.25em;">
								Optional plugins that enhance the demo (can be unchecked)
							</span>
						</li>
						<?php foreach ( $recommended_plugins as $slug => $plugin ) : ?>
							<li>
								<input type="checkbox" checked style="margin-right: 10px;">
								<span style="flex: 1;"><?php echo esc_html( $plugin['name'] ); ?></span>
								<span class="plugin-badge badge-optional">optional</span>
							</li>
						<?php endforeach; ?>
					<?php endif; ?>
				</ul>
			</div>
			<?php
		}
		
		// Show demo info
		$selected_demo = $import_files[ $selected_demo_index ];
		?>
		<div style="background: #f0f0f1; padding: 15px; margin-top: 20px; border-radius: 4px;">
			<h3 style="margin-top: 0;">Demo Information</h3>
			<table style="width: 100%;">
				<tr>
					<td style="font-weight: 600; width: 150px;">Demo Name:</td>
					<td><?php echo esc_html( $selected_demo['import_file_name'] ); ?></td>
				</tr>
				<tr>
					<td style="font-weight: 600;">Demo Slug:</td>
					<td><code><?php echo esc_html( $selected_demo['import_file_slug'] ?? 'N/A' ); ?></code></td>
				</tr>
				<tr>
					<td style="font-weight: 600;">Required Plugins:</td>
					<td><?php echo count( $required_plugins ); ?></td>
				</tr>
				<tr>
					<td style="font-weight: 600;">Recommended Plugins:</td>
					<td><?php echo count( $recommended_plugins ); ?></td>
				</tr>
				<?php if ( ! empty( $selected_demo['import_notice'] ) ) : ?>
				<tr>
					<td style="font-weight: 600; vertical-align: top;">Notice:</td>
					<td><?php echo esc_html( $selected_demo['import_notice'] ); ?></td>
				</tr>
				<?php endif; ?>
			</table>
		</div>
		<?php
	} else {
		?>
		<div style="background: #f0f0f1; padding: 20px; margin-top: 20px; border-radius: 4px; text-align: center;">
			<p style="font-size: 1.1em; color: #666;">
				👆 Select a demo from the dropdown above to see its plugin requirements
			</p>
		</div>
		<?php
	}
}

/**
 * Show admin notice with link to preview page.
 */
function conjure_test_plugin_preview_notice() {
	$screen = get_current_screen();
	if ( $screen && $screen->id === 'toplevel_page_conjurewp-setup' ) {
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<strong>Testing Demo-Specific Plugins?</strong> 
				<a href="<?php echo admin_url( 'admin.php?page=conjure-plugin-preview' ); ?>">
					View Plugin Selection Preview →
				</a>
			</p>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'conjure_test_plugin_preview_notice' );

