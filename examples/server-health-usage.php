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

	// Create instance with default requirements (256MB memory, 300s execution).
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
	$server_health = new Conjure_Server_Health( 256, 300 );
	
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

/**
 * Example 11: Disable server health checks entirely
 * 
 * Add this to your theme's functions.php to completely disable server health checks.
 */
function example_disable_health_checks() {
	add_filter( 'conjure_server_health_enabled', '__return_false' );
}
add_action( 'init', 'example_disable_health_checks' );

/**
 * Example 12: Lower memory requirements for shared hosting
 * 
 * Reduce memory requirement to 128MB (suitable for shared hosting).
 */
function example_lower_memory_requirement( $min_memory ) {
	return 128; // 128MB instead of default 256MB.
}
add_filter( 'conjure_server_health_min_memory', 'example_lower_memory_requirement' );

/**
 * Example 13: Reduce execution time requirement
 * 
 * Reduce execution time to 180 seconds (3 minutes).
 */
function example_reduce_execution_time( $min_execution ) {
	return 180; // 180 seconds instead of default 300 seconds.
}
add_filter( 'conjure_server_health_min_execution', 'example_reduce_execution_time' );

/**
 * Example 14: Set realistic requirements for a lightweight theme
 * 
 * Use both filters together for a theme with minimal requirements.
 */
function example_lightweight_theme_requirements() {
	// Set minimum memory to 128MB.
	add_filter( 'conjure_server_health_min_memory', function( $min_memory ) {
		return 128;
	});
	
	// Set minimum execution time to 120 seconds.
	add_filter( 'conjure_server_health_min_execution', function( $min_execution ) {
		return 120;
	});
}
add_action( 'init', 'example_lightweight_theme_requirements' );

/**
 * Example 15: Conditional health checks (disable for specific environments)
 * 
 * Disable health checks on staging/development environments.
 */
function example_conditional_health_checks( $enabled ) {
	// Disable on staging and development.
	if ( defined( 'WP_ENVIRONMENT_TYPE' ) ) {
		$env = WP_ENVIRONMENT_TYPE;
		if ( in_array( $env, array( 'development', 'staging' ), true ) ) {
			return false;
		}
	}
	return $enabled;
}
add_filter( 'conjure_server_health_enabled', 'example_conditional_health_checks' );

/**
 * Example 16: Higher requirements for premium themes with large imports
 * 
 * Increase requirements for themes with heavy content imports.
 */
function example_premium_theme_requirements() {
	// Set minimum memory to 512MB.
	add_filter( 'conjure_server_health_min_memory', function( $min_memory ) {
		return 512;
	});
	
	// Set minimum execution time to 600 seconds (10 minutes).
	add_filter( 'conjure_server_health_min_execution', function( $min_execution ) {
		return 600;
	});
}
add_action( 'init', 'example_premium_theme_requirements' );

/**
 * Example 17: Get minimum requirements to display in custom UI
 * 
 * Display current thresholds to users.
 */
function example_display_requirements() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-conjure-server-health.php';
	
	$server_health = new Conjure_Server_Health();
	
	// Check if enabled.
	if ( ! $server_health->is_enabled() ) {
		echo '<p>Server health checks are disabled.</p>';
		return;
	}
	
	// Get minimum requirements.
	$min_memory = $server_health->get_min_memory_limit();
	$min_execution = $server_health->get_min_execution_time();
	
	// Get current values.
	$current_memory = $server_health->get_memory_limit_value();
	$current_execution = $server_health->get_max_execution_value();
	
	?>
	<div class="requirements-info">
		<h3>Minimum Requirements</h3>
		<ul>
			<li>
				<strong>Memory:</strong> 
				<?php echo esc_html( $min_memory ); ?>MB required 
				(Current: <?php echo esc_html( $current_memory ); ?>MB)
			</li>
			<li>
				<strong>Execution Time:</strong> 
				<?php echo esc_html( $min_execution ); ?>s required 
				(Current: <?php echo esc_html( $current_execution ); ?>s)
			</li>
		</ul>
	</div>
	<?php
}

