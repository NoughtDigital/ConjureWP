<?php
/**
 * Example: Require ConjureWP Plugin in Your Theme
 *
 * This shows how to make your theme require or recommend the ConjureWP plugin
 * using TGM Plugin Activation (TGMPA).
 *
 * @package ConjureWP
 * @subpackage Examples
 */

/**
 * OPTION 1: Using TGM Plugin Activation (TGMPA)
 * =============================================
 * 
 * This is the most popular method for requiring plugins in themes.
 * 
 * Step 1: Download TGMPA
 * Download from: https://github.com/TGMPA/TGM-Plugin-Activation
 * Place in: /your-theme/includes/class-tgm-plugin-activation.php
 * 
 * Step 2: Add this to your theme's functions.php:
 */

// Include TGMPA library.
require_once get_template_directory() . '/includes/class-tgm-plugin-activation.php';

/**
 * Register required plugins.
 */
function mytheme_register_required_plugins() {
	$plugins = array(
		// Require ConjureWP from WordPress.org.
		array(
			'name'     => 'ConjureWP',
			'slug'     => 'conjurewp',
			'required' => true, // Theme requires this plugin.
		),
		
		// Optionally require other plugins too.
		array(
			'name'     => 'Contact Form 7',
			'slug'     => 'contact-form-7',
			'required' => false, // Recommended but not required.
		),
	);

	$config = array(
		'id'           => 'mytheme',
		'default_path' => '',
		'menu'         => 'tgmpa-install-plugins',
		'has_notices'  => true,
		'dismissable'  => false, // Users cannot dismiss if required.
		'dismiss_msg'  => '',
		'is_automatic' => false,
		'message'      => '',
	);

	tgmpa( $plugins, $config );
}
add_action( 'tgmpa_register', 'mytheme_register_required_plugins' );

/**
 * OPTION 2: Simple Admin Notice (No TGMPA)
 * =========================================
 * 
 * A lightweight alternative that just shows an admin notice.
 * Add this to your theme's functions.php:
 */

function mytheme_check_conjurewp() {
	// Check if ConjureWP is active.
	if ( ! class_exists( 'Conjure' ) ) {
		add_action( 'admin_notices', 'mytheme_conjurewp_notice' );
	}
}
add_action( 'admin_init', 'mytheme_check_conjurewp' );

function mytheme_conjurewp_notice() {
	$plugin_slug = 'conjurewp';
	$install_url = wp_nonce_url(
		self_admin_url( 'update.php?action=install-plugin&plugin=' . $plugin_slug ),
		'install-plugin_' . $plugin_slug
	);
	
	?>
	<div class="notice notice-warning is-dismissible">
		<p>
			<strong>Theme Name</strong> requires the <strong>ConjureWP</strong> plugin to import demo content.
		</p>
		<p>
			<a href="<?php echo esc_url( $install_url ); ?>" class="button button-primary">
				Install ConjureWP Now
			</a>
		</p>
	</div>
	<?php
}

/**
 * OPTION 3: Check at Theme Activation (Prevent Activation)
 * ========================================================
 * 
 * This prevents the theme from activating if ConjureWP is not installed.
 * Add this to your theme's functions.php:
 */

function mytheme_activation_check() {
	// Check if ConjureWP is installed and active.
	if ( ! class_exists( 'Conjure' ) ) {
		// Switch back to previous theme.
		switch_theme( get_option( 'theme_switched' ) );
		
		// Show error message.
		add_action( 'admin_notices', 'mytheme_activation_error' );
		
		// Deactivate the theme.
		return false;
	}
}
add_action( 'after_switch_theme', 'mytheme_activation_check' );

function mytheme_activation_error() {
	?>
	<div class="notice notice-error">
		<p>
			<strong>Theme activation failed!</strong>
			This theme requires the <strong>ConjureWP</strong> plugin to be installed and activated.
		</p>
		<p>
			Please install ConjureWP from the WordPress plugin repository, then activate the theme again.
		</p>
	</div>
	<?php
}

/**
 * RECOMMENDED APPROACH
 * ====================
 * 
 * Use OPTION 1 (TGMPA) for the best user experience:
 * 
 * ✅ Professional plugin installer UI
 * ✅ Bulk install/activate
 * ✅ Handles dependencies
 * ✅ Used by most premium themes
 * ✅ Users can install with one click
 * 
 * Complete Setup Instructions:
 * 
 * 1. Download TGMPA:
 *    https://github.com/TGMPA/TGM-Plugin-Activation/blob/develop/class-tgm-plugin-activation.php
 * 
 * 2. Save to your theme:
 *    /your-theme/includes/class-tgm-plugin-activation.php
 * 
 * 3. Add to functions.php:
 *    require_once get_template_directory() . '/includes/class-tgm-plugin-activation.php';
 *    
 *    function mytheme_register_required_plugins() {
 *        $plugins = array(
 *            array(
 *                'name'     => 'ConjureWP',
 *                'slug'     => 'conjurewp',
 *                'required' => true,
 *            ),
 *        );
 *        
 *        $config = array(
 *            'id'           => 'mytheme',
 *            'default_path' => '',
 *            'menu'         => 'tgmpa-install-plugins',
 *            'has_notices'  => true,
 *            'dismissable'  => false,
 *            'is_automatic' => false,
 *            'message'      => '',
 *        );
 *        
 *        tgmpa( $plugins, $config );
 *    }
 *    add_action( 'tgmpa_register', 'mytheme_register_required_plugins' );
 * 
 * 4. Create demo folder:
 *    /your-theme/conjurewp-demos/
 *    ├── content.xml
 *    ├── widgets.json
 *    ├── customizer.dat
 *    ├── redux-options.json
 *    └── slider.zip
 * 
 * Done! When users activate your theme:
 * - They'll see a notice to install ConjureWP
 * - Click "Install" and it installs automatically
 * - ConjureWP auto-discovers demos from your theme
 * - Everything just works!
 */

