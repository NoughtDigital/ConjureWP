<?php
/**
 * Example wp-config.php constants for ConjureWP custom demo content
 *
 * Add these constants to your wp-config.php file to use custom demo content
 * that survives plugin updates.
 *
 * Copy the relevant lines below and paste them into your wp-config.php file
 * (before the "That's all, stop editing!" line).
 *
 * @package ConjureWP
 */

// ============================================================================
// ConjureWP Custom Demo Content Setup
// ============================================================================

/**
 * OPTION 1: Absolute Path (Most Common)
 *
 * Use an absolute path to a directory outside the plugin folder.
 * This ensures your demo content survives plugin updates.
 */
define( 'CONJUREWP_DEMO_PATH', '/var/www/html/wp-content/demo-content' );
define( 'CONJUREWP_AUTO_REGISTER_DEMOS', true );

/**
 * OPTION 2: Using WP_CONTENT_DIR
 *
 * Store demos in wp-content directory (survives plugin updates).
 */
// define( 'CONJUREWP_DEMO_PATH', WP_CONTENT_DIR . '/conjurewp-demos' );
// define( 'CONJUREWP_AUTO_REGISTER_DEMOS', true );

/**
 * OPTION 3: Custom Location
 *
 * Point to any directory on your server.
 */
// define( 'CONJUREWP_DEMO_PATH', '/home/username/demo-files' );
// define( 'CONJUREWP_AUTO_REGISTER_DEMOS', true );

// ============================================================================
// File Structure Examples
// ============================================================================

/**
 * Single Demo Example:
 * --------------------
 * /your-custom-path/
 * ├── content.xml
 * ├── widgets.json
 * ├── customizer.dat (customiser settings)
 * ├── redux-options.json (optional)
 * ├── slider.zip (optional)
 * ├── preview.jpg (optional)
 * └── info.txt (optional - becomes import notice)
 *
 * Multiple Demos Example:
 * -----------------------
 * /your-custom-path/
 * ├── demo-main/
 * │   ├── content.xml
 * │   ├── widgets.json
 * │   ├── customizer.dat (customiser settings)
 * │   └── preview.jpg
 * ├── demo-business/
 * │   ├── content.xml
 * │   ├── widgets.json
 * │   └── info.txt
 * └── demo-portfolio/
 *     ├── content.xml
 *     └── customizer.dat
 *
 * Each subdirectory becomes a separate demo that users can choose from.
 */

// ============================================================================
// Notes
// ============================================================================

/**
 * Auto-Discovery:
 * ---------------
 * When CONJUREWP_AUTO_REGISTER_DEMOS is enabled, the plugin automatically:
 * - Scans the directory for demo files
 * - Creates import configurations
 * - Detects all supported file types
 * - Uses folder names as demo names (e.g., "demo-main" becomes "Demo Main")
 *
 * No Manual Configuration Needed:
 * -------------------------------
 * You don't need to add any filters or functions to your theme.
 * Just define these constants and organise your files.
 *
 * Priority Order:
 * ---------------
 * The plugin checks for demos in this order:
 * 1. CONJUREWP_DEMO_PATH (highest priority)
 * 2. Theme directory: /themes/your-theme/conjurewp-demos/
 * 3. Uploads directory: /uploads/conjurewp-demos/
 * 4. Plugin directory: /plugins/ConjureWP/demo/ (examples only)
 *
 * Update Safety:
 * --------------
 * By using CONJUREWP_DEMO_PATH pointing to a location outside the plugin
 * directory, your demo content will NOT be affected by plugin updates.
 */

