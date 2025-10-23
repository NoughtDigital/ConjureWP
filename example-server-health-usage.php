<?php
/**
 * Example: How to use the Conjure_Server_Health class
 *
 * @package   Conjure
 * @author    ConjureWP
 * @license   GPL-3.0
 * @link      https://conjurewp.com
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Example 1: Basic usage - Check if server meets requirements
 */
function example_basic_server_check() {
	// Include the class file.
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-conjure-server-health.php';

	// Create instance with default requirements (512MB memory, 40000s execution).
	$server_health = new Conjure_Server_Health();

	// Check if requirements are met.
	if ( $server_health->meets_requirements() ) {
		echo 'Server meets all requirements!';
	} else {
		echo 'Server does not meet requirements.';
	}
}

/**
 * Example 2: Custom requirements
 */
function example_custom_requirements() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-conjure-server-health.php';

	// Create instance with custom requirements (256MB memory, 300s execution).
	$server_health = new Conjure_Server_Health( 256, 300 );

	if ( $server_health->meets_requirements() ) {
		echo 'Server meets custom requirements!';
	}
}

/**
 * Example 3: Get individual values
 */
function example_get_individual_values() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-conjure-server-health.php';

	$server_health = new Conjure_Server_Health();

	// Get memory limit.
	$memory = $server_health->get_memory_limit(); // e.g., "512M".
	$memory_value = $server_health->get_memory_limit_value(); // e.g., 512.

	// Get execution time.
	$execution = $server_health->get_max_execution_time(); // e.g., "120s".
	$execution_value = $server_health->get_max_execution_value(); // e.g., 120.

	// Get MySQL version.
	$mysql = $server_health->get_mysql_version();

	echo "Memory: $memory ($memory_value MB)<br>";
	echo "Execution Time: $execution<br>";
	echo "MySQL: $mysql<br>";
}

/**
 * Example 4: Get all health info as array
 */
function example_get_health_info_array() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-conjure-server-health.php';

	$server_health = new Conjure_Server_Health();
	$info = $server_health->get_health_info();

	/*
	Array will contain:
	- meets_requirements (bool)
	- memory_limit (string)
	- memory_limit_value (int)
	- max_execution (string)
	- max_execution_value (int)
	- mysql_version (string)
	*/
	
	print_r( $info );
}

/**
 * Example 5: Render complete HTML with styles (basic)
 */
function example_render_basic() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-conjure-server-health.php';

	$server_health = new Conjure_Server_Health();
	
	// Render with default settings.
	$server_health->render_complete();
}

/**
 * Example 6: Render HTML with custom settings
 */
function example_render_custom() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-conjure-server-health.php';

	$server_health = new Conjure_Server_Health();
	
	// Render with custom settings.
	$server_health->render_complete(
		array(
			'show_title'       => true,
			'title'            => 'My Theme Server Requirements',
			'requirements_url' => 'https://example.com/docs/requirements',
			'theme_name'       => 'My Awesome Theme',
		)
	);
}

/**
 * Example 7: Get just the HTML without styles
 */
function example_get_html_only() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-conjure-server-health.php';

	$server_health = new Conjure_Server_Health();
	
	// Get HTML output as string.
	$html = $server_health->render_health_check(
		array(
			'show_title'       => true,
			'title'            => 'Server Status',
			'requirements_url' => 'https://example.com/requirements',
		)
	);

	// You can now use $html wherever you need.
	echo $html;
}

/**
 * Example 8: Use in a WordPress admin page
 */
function example_admin_page_integration() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-conjure-server-health.php';

	// In your admin page callback function:
	?>
	<div class="wrap">
		<h1>My Theme Setup</h1>
		
		<?php
		$server_health = new Conjure_Server_Health();
		$server_health->render_complete(
			array(
				'requirements_url' => 'https://mytheme.com/docs/requirements',
				'theme_name'       => 'My Theme',
			)
		);
		?>
		
		<p>Continue with your setup wizard...</p>
	</div>
	<?php
}

/**
 * Example 9: Format file sizes
 */
function example_format_filesize() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-conjure-server-health.php';

	$server_health = new Conjure_Server_Health();
	
	$size_bytes = 1073741824; // 1GB in bytes.
	$formatted = $server_health->format_filesize( $size_bytes ); // "1 GiB".
	
	echo $formatted;
}

/**
 * Example 10: Integration in a setup wizard (like Merlin)
 */
function example_wizard_integration() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-conjure-server-health.php';

	// In your wizard welcome step:
	$server_health = new Conjure_Server_Health( 512, 40000 );
	
	$meets_requirements = $server_health->meets_requirements();
	
	?>
	<div class="wizard-welcome">
		<h1>Welcome to My Theme</h1>
		<p>This wizard will set up your theme, install plugins, and import content.</p>
		
		<?php
		$server_health->render_complete(
			array(
				'requirements_url' => 'https://mytheme.com/docs/requirements',
			)
		);
		?>
		
		<?php if ( $meets_requirements ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=setup&step=2' ) ); ?>" class="button button-primary">
				Start Setup
			</a>
		<?php else : ?>
			<p class="warning">
				Please contact your hosting provider to increase your PHP limits before proceeding.
			</p>
		<?php endif; ?>
	</div>
	<?php
}

