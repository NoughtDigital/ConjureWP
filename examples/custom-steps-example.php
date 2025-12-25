<?php
/**
 * Example: Adding Custom Steps to ConjureWP Wizard
 *
 * This file demonstrates how theme developers can add custom steps to the
 * ConjureWP setup wizard. Custom steps are added in the theme, so they
 * persist when the plugin is updated.
 *
 * IMPORTANT: Add this code to your THEME's functions.php or an includes file,
 * NOT to the plugin directory, as plugin files are overwritten on updates.
 *
 * @package   YourTheme
 * @version   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ========================================================================
 * EXAMPLE 1: Add a Simple Custom Step (View Only)
 * ========================================================================
 *
 * This adds a custom step that displays information but doesn't process
 * any form submissions.
 */

function mytheme_add_custom_info_step( $steps ) {
	// Insert the step after 'welcome' and before 'child'.
	$new_steps = array();
	
	foreach ( $steps as $key => $step ) {
		$new_steps[ $key ] = $step;
		
		// Insert our custom step after 'welcome'.
		if ( 'welcome' === $key ) {
			$new_steps['custom_info'] = array(
				'name' => esc_html__( 'Information', 'your-textdomain' ),
				'view' => 'mytheme_custom_info_step_view',
			);
		}
	}
	
	return $new_steps;
}
add_filter( 'conjure_steps', 'mytheme_add_custom_info_step' );

/**
 * Display the custom info step content.
 */
function mytheme_custom_info_step_view() {
	?>
	<div class="conjure__intro-text">
		<h1><?php esc_html_e( 'Welcome to Our Theme', 'your-textdomain' ); ?></h1>
		<p><?php esc_html_e( 'This is a custom step added by the theme. It provides important information before proceeding.', 'your-textdomain' ); ?></p>
	</div>
	
	<form method="post">
		<?php wp_nonce_field( 'conjure' ); ?>
		<p>
			<label>
				<input type="checkbox" name="acknowledged" value="1" required>
				<?php esc_html_e( 'I have read and understood the information', 'your-textdomain' ); ?>
			</label>
		</p>
		<p class="conjure__buttons">
			<a href="<?php echo esc_url( admin_url( 'themes.php?page=conjure-setup' ) ); ?>" class="button">
				<?php esc_html_e( 'Skip', 'your-textdomain' ); ?>
			</a>
			<button type="submit" class="button button-primary" name="save_step">
				<?php esc_html_e( 'Continue', 'your-textdomain' ); ?>
			</button>
		</p>
	</form>
	<?php
}

/**
 * ========================================================================
 * EXAMPLE 2: Add a Custom Step with Form Handler
 * ========================================================================
 *
 * This adds a custom step that processes form submissions and can
 * redirect to the next step or show different content based on user input.
 */

function mytheme_add_custom_setup_step( $steps ) {
	// Insert the step before 'content'.
	$new_steps = array();
	
	foreach ( $steps as $key => $step ) {
		// Insert our custom step before 'content'.
		if ( 'content' === $key ) {
			$new_steps['theme_setup'] = array(
				'name'    => esc_html__( 'Theme Setup', 'your-textdomain' ),
				'view'    => 'mytheme_theme_setup_step_view',
				'handler' => 'mytheme_theme_setup_step_handler',
			);
		}
		$new_steps[ $key ] = $step;
	}
	
	return $new_steps;
}
add_filter( 'conjure_steps', 'mytheme_add_custom_setup_step' );

/**
 * Display the theme setup step content.
 */
function mytheme_theme_setup_step_view() {
	// Retrieve saved values if available.
	$color_scheme = get_option( 'mytheme_color_scheme', 'light' );
	$layout_style = get_option( 'mytheme_layout_style', 'boxed' );
	?>
	<div class="conjure__intro-text">
		<h1><?php esc_html_e( 'Configure Your Theme', 'your-textdomain' ); ?></h1>
		<p><?php esc_html_e( 'Choose your preferred settings before importing content.', 'your-textdomain' ); ?></p>
	</div>
	
	<form method="post">
		<?php wp_nonce_field( 'conjure' ); ?>
		
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="color_scheme"><?php esc_html_e( 'Color Scheme', 'your-textdomain' ); ?></label>
				</th>
				<td>
					<select name="color_scheme" id="color_scheme">
						<option value="light" <?php selected( $color_scheme, 'light' ); ?>>
							<?php esc_html_e( 'Light', 'your-textdomain' ); ?>
						</option>
						<option value="dark" <?php selected( $color_scheme, 'dark' ); ?>>
							<?php esc_html_e( 'Dark', 'your-textdomain' ); ?>
						</option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="layout_style"><?php esc_html_e( 'Layout Style', 'your-textdomain' ); ?></label>
				</th>
				<td>
					<select name="layout_style" id="layout_style">
						<option value="boxed" <?php selected( $layout_style, 'boxed' ); ?>>
							<?php esc_html_e( 'Boxed', 'your-textdomain' ); ?>
						</option>
						<option value="fullwidth" <?php selected( $layout_style, 'fullwidth' ); ?>>
							<?php esc_html_e( 'Full Width', 'your-textdomain' ); ?>
						</option>
					</select>
				</td>
			</tr>
		</table>
		
		<p class="conjure__buttons">
			<a href="<?php echo esc_url( admin_url( 'themes.php?page=conjure-setup&step=content' ) ); ?>" class="button">
				<?php esc_html_e( 'Skip', 'your-textdomain' ); ?>
			</a>
			<button type="submit" class="button button-primary" name="save_step">
				<?php esc_html_e( 'Save & Continue', 'your-textdomain' ); ?>
			</button>
		</p>
	</form>
	<?php
}

/**
 * Handle the theme setup step form submission.
 *
 * @return bool|void Return false to prevent showing the step view again,
 *                   or return nothing/true to show the view after processing.
 */
function mytheme_theme_setup_step_handler() {
	check_admin_referer( 'conjure' );
	
	// Save the selected options.
	if ( isset( $_POST['color_scheme'] ) ) {
		$color_scheme = sanitize_text_field( $_POST['color_scheme'] );
		update_option( 'mytheme_color_scheme', $color_scheme );
	}
	
	if ( isset( $_POST['layout_style'] ) ) {
		$layout_style = sanitize_text_field( $_POST['layout_style'] );
		update_option( 'mytheme_layout_style', $layout_style );
	}
	
	// Apply the settings immediately.
	// You could also use these settings during the import process.
	
	// Return false to proceed to next step, or return true to show the view again.
	return false;
}

/**
 * ========================================================================
 * EXAMPLE 3: Add a Custom Step Using a Class Method
 * ========================================================================
 *
 * For more complex steps, you can use a class to organise your code.
 */

class MyTheme_Conjure_Steps {
	
	/**
	 * Register custom steps.
	 */
	public static function add_steps( $steps ) {
		// Add a step after 'ready' (at the end).
		$steps['final_config'] = array(
			'name'    => esc_html__( 'Final Configuration', 'your-textdomain' ),
			'view'    => array( __CLASS__, 'final_config_view' ),
			'handler' => array( __CLASS__, 'final_config_handler' ),
		);
		
		return $steps;
	}
	
	/**
	 * Display the final configuration step.
	 */
	public static function final_config_view() {
		?>
		<div class="conjure__intro-text">
			<h1><?php esc_html_e( 'Final Configuration', 'your-textdomain' ); ?></h1>
			<p><?php esc_html_e( 'Complete the final setup steps.', 'your-textdomain' ); ?></p>
		</div>
		
		<form method="post">
			<?php wp_nonce_field( 'conjure' ); ?>
			<p>
				<label>
					<input type="checkbox" name="setup_complete" value="1">
					<?php esc_html_e( 'Mark setup as complete', 'your-textdomain' ); ?>
				</label>
			</p>
			<p class="conjure__buttons">
				<button type="submit" class="button button-primary" name="save_step">
					<?php esc_html_e( 'Finish', 'your-textdomain' ); ?>
				</button>
			</p>
		</form>
		<?php
	}
	
	/**
	 * Handle the final configuration step.
	 */
	public static function final_config_handler() {
		check_admin_referer( 'conjure' );
		
		if ( isset( $_POST['setup_complete'] ) ) {
			update_option( 'mytheme_setup_complete', true );
		}
		
		return false;
	}
}

add_filter( 'conjure_steps', array( 'MyTheme_Conjure_Steps', 'add_steps' ) );

/**
 * ========================================================================
 * EXAMPLE 4: Modify Existing Steps
 * ========================================================================
 *
 * You can also modify existing steps, such as changing their order,
 * removing steps, or changing their display names.
 */

function mytheme_modify_steps( $steps ) {
	// Change the name of the 'ready' step.
	if ( isset( $steps['ready'] ) ) {
		$steps['ready']['name'] = esc_html__( 'All Done!', 'your-textdomain' );
	}
	
	// Remove the 'child' step if not needed.
	// unset( $steps['child'] );
	
	// Reorder steps by rebuilding the array in desired order.
	$ordered_steps = array();
	$step_order = array( 'welcome', 'license', 'plugins', 'content', 'ready' );
	
	foreach ( $step_order as $key ) {
		if ( isset( $steps[ $key ] ) ) {
			$ordered_steps[ $key ] = $steps[ $key ];
		}
	}
	
	// Add any remaining steps that weren't in our order array.
	foreach ( $steps as $key => $step ) {
		if ( ! isset( $ordered_steps[ $key ] ) ) {
			$ordered_steps[ $key ] = $step;
		}
	}
	
	return $ordered_steps;
}
// Uncomment to enable:
// add_filter( 'conjure_steps', 'mytheme_modify_steps', 20 );

/**
 * ========================================================================
 * NOTES FOR THEME DEVELOPERS
 * ========================================================================
 *
 * 1. Step Structure:
 *    Each step should be an array with:
 *    - 'name' (required): Display name for the step
 *    - 'view' (required): Callback function/method to display step content
 *    - 'handler' (optional): Callback function/method to process form submissions
 *
 * 2. View Callback:
 *    - Should output HTML for the step
 *    - Can include forms that submit to the same step
 *    - Use wp_nonce_field( 'conjure' ) for security
 *    - Use check_admin_referer( 'conjure' ) in handlers
 *
 * 3. Handler Callback:
 *    - Processes form submissions when 'save_step' is posted
 *    - Return false to proceed to next step
 *    - Return true (or nothing) to show the view again
 *
 * 4. Filter Priority:
 *    - Default priority is 10
 *    - Use higher priority (20+) to modify steps added by other code
 *    - Use lower priority (5) to add steps before others modify them
 *
 * 5. Step Keys:
 *    - Use unique, lowercase keys (e.g., 'my_custom_step')
 *    - Avoid conflicts with default step keys: welcome, child, license, plugins, content, ready
 *
 * 6. Navigation:
 *    - Steps are automatically added to the wizard navigation
 *    - Users can navigate between completed steps
 *    - Use admin_url( 'themes.php?page=conjure-setup&step=STEP_KEY' ) for links
 *
 * ========================================================================
 */

