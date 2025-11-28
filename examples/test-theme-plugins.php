<?php
/**
 * Theme Plugin Configuration Test Utility
 *
 * Place this file in your theme's root directory and access it via:
 * https://yoursite.com/wp-content/themes/your-theme/test-theme-plugins.php
 *
 * This will test your conjurewp-plugins/plugins.json configuration.
 *
 * @package ConjureWP
 * @version 2.0.0
 */

// Load WordPress.
require_once '../../../../../wp-load.php';

// Security check.
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'You do not have permission to access this page.' );
}

// Check if Conjure_Theme_Plugins class is available.
if ( ! class_exists( 'Conjure_Theme_Plugins' ) ) {
	wp_die( 'ConjureWP plugin is not active. Please activate ConjureWP first.' );
}

// Run tests.
$test_result = Conjure_Theme_Plugins::test_plugin_config();

?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>ConjureWP Theme Plugin Configuration Test</title>
	<style>
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}
		body {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			line-height: 1.6;
			color: #333;
			background: #f5f5f5;
			padding: 40px 20px;
		}
		.container {
			max-width: 900px;
			margin: 0 auto;
		}
		.card {
			background: white;
			border-radius: 8px;
			padding: 30px;
			margin-bottom: 20px;
			box-shadow: 0 2px 4px rgba(0,0,0,0.1);
		}
		h1 {
			color: #1e3a8a;
			margin-bottom: 10px;
			font-size: 28px;
		}
		h2 {
			color: #1e3a8a;
			margin-top: 30px;
			margin-bottom: 15px;
			font-size: 20px;
		}
		.status {
			display: inline-block;
			padding: 8px 16px;
			border-radius: 4px;
			font-weight: 600;
			margin: 20px 0;
		}
		.status.success {
			background: #d1fae5;
			color: #065f46;
		}
		.status.error {
			background: #fee2e2;
			color: #991b1b;
		}
		.status.warning {
			background: #fef3c7;
			color: #92400e;
		}
		.message-list {
			list-style: none;
		}
		.message-list li {
			padding: 12px;
			margin: 8px 0;
			border-radius: 4px;
			border-left: 4px solid;
		}
		.message-list li.error {
			background: #fee2e2;
			border-color: #991b1b;
		}
		.message-list li.warning {
			background: #fef3c7;
			border-color: #92400e;
		}
		.info {
			background: #e0f2fe;
			padding: 16px;
			border-radius: 4px;
			border-left: 4px solid #0284c7;
			margin: 20px 0;
		}
		.code {
			background: #f3f4f6;
			padding: 2px 6px;
			border-radius: 3px;
			font-family: monospace;
			font-size: 14px;
		}
		.button {
			display: inline-block;
			padding: 10px 20px;
			background: #1e3a8a;
			color: white;
			text-decoration: none;
			border-radius: 4px;
			margin-top: 20px;
			transition: background 0.2s;
		}
		.button:hover {
			background: #1e40af;
		}
	</style>
</head>
<body>
	<div class="container">
		<div class="card">
			<h1>ConjureWP Theme Plugin Configuration Test</h1>
			<p>Theme: <strong><?php echo esc_html( wp_get_theme()->get( 'Name' ) ); ?></strong></p>
			
			<?php if ( $test_result['valid'] ) : ?>
				<div class="status success">✓ Configuration Valid</div>
				<p>Found <strong><?php echo esc_html( $test_result['plugins'] ); ?></strong> plugin(s) in your configuration.</p>
			<?php else : ?>
				<div class="status error">✗ Configuration Invalid</div>
			<?php endif; ?>

			<?php if ( ! empty( $test_result['errors'] ) ) : ?>
				<h2>Errors</h2>
				<ul class="message-list">
					<?php foreach ( $test_result['errors'] as $error ) : ?>
						<li class="error"><?php echo esc_html( $error ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( ! empty( $test_result['warnings'] ) ) : ?>
				<h2>Warnings</h2>
				<ul class="message-list">
					<?php foreach ( $test_result['warnings'] as $warning ) : ?>
						<li class="warning"><?php echo esc_html( $warning ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( $test_result['valid'] && empty( $test_result['errors'] ) && empty( $test_result['warnings'] ) ) : ?>
				<div class="info">
					<strong>✓ Perfect!</strong> Your plugin configuration has no issues.
					All bundled files exist and external URLs are accessible.
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $test_result['valid'] && $test_result['plugins'] > 0 ) : ?>
			<div class="card">
				<h2>Plugin Details</h2>
				<?php
				$plugins = Conjure_Theme_Plugins::get_bundled_plugins();
				?>
				<table style="width: 100%; border-collapse: collapse;">
					<thead>
						<tr style="border-bottom: 2px solid #e5e7eb;">
							<th style="text-align: left; padding: 12px;">Name</th>
							<th style="text-align: left; padding: 12px;">Slug</th>
							<th style="text-align: center; padding: 12px;">Required</th>
							<th style="text-align: left; padding: 12px;">Source</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $plugins as $plugin ) : ?>
							<tr style="border-bottom: 1px solid #e5e7eb;">
								<td style="padding: 12px;"><?php echo esc_html( $plugin['name'] ); ?></td>
								<td style="padding: 12px;"><span class="code"><?php echo esc_html( $plugin['slug'] ); ?></span></td>
								<td style="padding: 12px; text-align: center;">
									<?php if ( $plugin['required'] ) : ?>
										<span style="color: #dc2626;">●</span> Yes
									<?php else : ?>
										<span style="color: #9ca3af;">○</span> No
									<?php endif; ?>
								</td>
								<td style="padding: 12px;">
									<?php
									if ( ! empty( $plugin['bundled'] ) ) {
										echo 'Bundled ZIP';
									} elseif ( ! empty( $plugin['external'] ) ) {
										echo 'External URL';
									} else {
										echo 'WordPress.org';
									}
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>

		<div class="card">
			<h2>Next Steps</h2>
			<ul style="list-style: disc; margin-left: 20px;">
				<li>Review any errors or warnings above</li>
				<li>Ensure all bundled plugin ZIP files exist in <span class="code">conjurewp-plugins/</span></li>
				<li>Test external URLs are accessible and return valid ZIP files</li>
				<li>Use the WP-CLI command: <span class="code">wp conjure validate-theme-plugins</span></li>
			</ul>
			
			<a href="<?php echo esc_url( admin_url( 'tools.php?page=conjurewp-logs' ) ); ?>" class="button">
				View ConjureWP Logs
			</a>
		</div>
	</div>
</body>
</html>

