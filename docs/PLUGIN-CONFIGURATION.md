# Plugin Configuration Guide for Theme Developers

Complete guide to configuring plugins for your theme's demo import with ConjureWP.

---

## Overview

ConjureWP includes a built-in plugin installer that allows you to define which plugins are required or recommended for your theme demos. This works with:

- ✅ **WordPress.org plugins** - Free plugins from the WordPress repository
- ✅ **Custom/Premium plugins** - Paid plugins bundled with your theme or hosted externally
- ✅ **Demo-specific plugins** - Different plugins for different demo variations
- ✅ **Required vs recommended** - Force certain plugins or make them optional

---

## Quick Start

### Basic Example - WordPress.org Plugins

Add this to your theme's `functions.php`:

```php
/**
 * Register demo imports with required plugins.
 */
function mytheme_import_files() {
    return array(
        array(
            'import_file_name' => 'Main Demo',
            'local_import_file' => get_template_directory() . '/demo/content.xml',
            'local_widget_file' => get_template_directory() . '/demo/widgets.json',
            'local_customizer_file' => get_template_directory() . '/demo/customizer.dat',
            
            // Define required plugins
            'required_plugins' => array(
                array(
                    'name'     => 'Contact Form 7',
                    'slug'     => 'contact-form-7',
                    'required' => true, // Users must install this
                ),
                array(
                    'name'     => 'Yoast SEO',
                    'slug'     => 'wordpress-seo',
                    'required' => false, // Optional/recommended
                ),
            ),
        ),
    );
}
add_filter( 'conjure_import_files', 'mytheme_import_files' );
```

---

## WordPress.org Plugins

For free plugins from the WordPress plugin directory.

### Full Configuration

```php
array(
    'name'        => 'Plugin Name',           // Display name
    'slug'        => 'plugin-slug',           // WordPress.org slug (required)
    'required'    => true,                    // true = required, false = recommended
    'description' => 'Plugin description',    // Optional description shown to users
)
```

### Minimal Configuration

ConjureWP will auto-detect plugin information from WordPress.org if you only provide the slug:

```php
array(
    'slug'     => 'contact-form-7',
    'required' => true,
)
```

Or even simpler for recommended plugins:

```php
'required_plugins' => array(
    'contact-form-7',        // Slug only - will be marked as recommended
    'wordpress-seo',
),
```

### Common WordPress.org Plugins

```php
'required_plugins' => array(
    // Page Builders
    array( 'slug' => 'elementor', 'required' => true ),
    array( 'slug' => 'beaver-builder-lite-version', 'required' => false ),
    
    // E-commerce
    array( 'slug' => 'woocommerce', 'required' => true ),
    
    // Forms
    array( 'slug' => 'contact-form-7', 'required' => false ),
    array( 'slug' => 'wpforms-lite', 'required' => false ),
    
    // SEO
    array( 'slug' => 'wordpress-seo', 'required' => false ),
    array( 'slug' => 'all-in-one-seo-pack', 'required' => false ),
    
    // Caching
    array( 'slug' => 'wp-super-cache', 'required' => false ),
    array( 'slug' => 'w3-total-cache', 'required' => false ),
),
```

---

## Custom/Premium Plugins

For paid plugins or custom plugins bundled with your theme.

### Method 1: Bundled with Theme (Recommended)

Store plugin ZIP files in your theme:

```
/themes/your-theme/
├── functions.php
└── plugins/
    ├── elementor-pro.zip
    ├── wpforms-pro.zip
    └── your-custom-plugin.zip
```

**Configuration:**

```php
'required_plugins' => array(
    array(
        'name'     => 'Elementor Pro',
        'slug'     => 'elementor-pro',
        'source'   => get_template_directory() . '/plugins/elementor-pro.zip',
        'required' => true,
    ),
    array(
        'name'     => 'WPForms Pro',
        'slug'     => 'wpforms',
        'source'   => get_template_directory() . '/plugins/wpforms-pro.zip',
        'required' => false,
    ),
),
```

### Method 2: External URL

Host plugins on your server or CDN:

```php
'required_plugins' => array(
    array(
        'name'     => 'Premium Slider',
        'slug'     => 'premium-slider',
        'source'   => 'https://yoursite.com/downloads/premium-slider.zip',
        'required' => true,
    ),
    array(
        'name'     => 'Theme Extensions',
        'slug'     => 'theme-extensions',
        'source'   => 'https://cdn.yoursite.com/plugins/theme-extensions-v1.2.0.zip',
        'required' => false,
    ),
),
```

### Method 3: Protected Downloads

For plugins requiring authentication:

```php
array(
    'name'     => 'Premium Plugin',
    'slug'     => 'premium-plugin',
    'source'   => 'https://api.yoursite.com/download/plugin',
    'required' => true,
    'external_url' => 'https://yoursite.com/plugins/premium-plugin', // Plugin homepage
)
```

### Method 4: Conditional URL Based on Licence

```php
/**
 * Provide plugin download URL based on user's theme licence.
 */
function mytheme_get_plugin_url( $plugins ) {
    // Check if user has valid theme licence
    $licence_key = get_option( 'mytheme_licence_key' );
    
    if ( ! empty( $licence_key ) ) {
        $plugins[] = array(
            'name'     => 'Premium Features',
            'slug'     => 'mytheme-premium',
            'source'   => 'https://api.yoursite.com/download?key=' . $licence_key,
            'required' => false,
        );
    }
    
    return $plugins;
}
add_filter( 'conjure_demo_required_plugins', 'mytheme_get_plugin_url', 10, 3 );
```

---

## Demo-Specific Plugins

Configure different plugins for different demos to improve user experience.

### Example: Multiple Demos with Different Plugins

```php
function mytheme_import_files() {
    return array(
        // Business Demo - Contact forms only
        array(
            'import_file_name' => 'Business Demo',
            'local_import_file' => get_template_directory() . '/demos/business/content.xml',
            'required_plugins' => array(
                array( 'slug' => 'contact-form-7', 'required' => true ),
                array( 'slug' => 'wordpress-seo', 'required' => false ),
            ),
        ),
        
        // E-commerce Demo - WooCommerce stack
        array(
            'import_file_name' => 'Shop Demo',
            'local_import_file' => get_template_directory() . '/demos/shop/content.xml',
            'required_plugins' => array(
                array( 'slug' => 'woocommerce', 'required' => true ),
                array( 'slug' => 'woocommerce-gateway-stripe', 'required' => false ),
                array( 'slug' => 'woo-variation-swatches', 'required' => false ),
            ),
        ),
        
        // Elementor Demo - Page builder plugins
        array(
            'import_file_name' => 'Elementor Demo',
            'local_import_file' => get_template_directory() . '/demos/elementor/content.xml',
            'required_plugins' => array(
                array( 'slug' => 'elementor', 'required' => true ),
                array(
                    'name'     => 'Elementor Pro',
                    'slug'     => 'elementor-pro',
                    'source'   => get_template_directory() . '/plugins/elementor-pro.zip',
                    'required' => true,
                ),
            ),
        ),
        
        // Minimal Demo - No plugins needed
        array(
            'import_file_name' => 'Minimal Blog',
            'local_import_file' => get_template_directory() . '/demos/blog/content.xml',
            'required_plugins' => array(), // Empty - no plugins required
        ),
    );
}
add_filter( 'conjure_import_files', 'mytheme_import_files' );
```

### Benefits

- ✅ Users only see relevant plugins for their chosen demo
- ✅ Faster setup - fewer plugins to install
- ✅ Better UX - no confusion about which plugins are needed
- ✅ Reduced support - clear plugin requirements per demo

---

## Advanced Configuration

### Using Filters for Dynamic Plugin Lists

Use the `conjure_demo_required_plugins` filter for advanced logic:

```php
/**
 * Dynamically modify required plugins based on demo.
 *
 * @param array $demo_plugins Current list of plugins
 * @param int   $demo_index   Index of selected demo
 * @param array $selected_demo Full demo configuration
 * @return array Modified plugin list
 */
function mytheme_modify_demo_plugins( $demo_plugins, $demo_index, $selected_demo ) {
    // Add a plugin to all demos
    $demo_plugins[] = array(
        'slug'     => 'wordpress-seo',
        'required' => false,
    );
    
    // Add WooCommerce only to shop demos
    if ( str_contains( $selected_demo['import_file_name'], 'Shop' ) ) {
        $demo_plugins[] = array(
            'slug'     => 'woocommerce',
            'required' => true,
        );
    }
    
    // Add premium plugins only for licensed users
    if ( get_option( 'mytheme_licence_status' ) === 'valid' ) {
        $demo_plugins[] = array(
            'name'     => 'Theme Premium Extensions',
            'slug'     => 'mytheme-extensions',
            'source'   => get_template_directory() . '/plugins/extensions.zip',
            'required' => false,
        );
    }
    
    return $demo_plugins;
}
add_filter( 'conjure_demo_required_plugins', 'mytheme_modify_demo_plugins', 10, 3 );
```

### Programmatic Plugin Registration

Register plugins without using the demo configuration:

```php
/**
 * Register plugins globally for all demos.
 */
function mytheme_register_global_plugins() {
    global $conjure_demo_plugin_manager;
    
    if ( ! $conjure_demo_plugin_manager ) {
        return;
    }
    
    $plugins = array(
        'contact-form-7' => array(
            'name' => 'Contact Form 7',
            'slug' => 'contact-form-7',
        ),
        'elementor-pro' => array(
            'name'   => 'Elementor Pro',
            'slug'   => 'elementor-pro',
            'source' => get_template_directory() . '/plugins/elementor-pro.zip',
        ),
    );
    
    $conjure_demo_plugin_manager->register_plugins( $plugins );
}
add_action( 'init', 'mytheme_register_global_plugins' );
```

---

## Plugin Configuration Reference

### Complete Plugin Array Options

```php
array(
    // REQUIRED FIELDS
    'slug' => 'plugin-slug',                    // Plugin folder name (required)
    
    // BASIC OPTIONS
    'name' => 'Plugin Display Name',            // Shown to users (auto-detected if missing)
    'required' => true,                         // true = required, false = recommended
    
    // CUSTOM/PREMIUM PLUGINS
    'source' => '/path/to/plugin.zip',          // Local path or external URL
    'external_url' => 'https://plugin-site.com', // Plugin homepage/documentation
    
    // OPTIONAL METADATA
    'description' => 'Plugin description',      // Shown in plugin list
    'version' => '1.2.3',                       // Plugin version (for display)
    'author' => 'Plugin Author',                // Plugin author name
    
    // ADVANCED OPTIONS
    'force_activation' => true,                 // Activate even if already installed
    'force_deactivation' => false,              // Deactivate after import (rare)
)
```

### Field Descriptions

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `slug` | `string` | **Yes** | Plugin directory slug. Must match plugin folder name |
| `name` | `string` | No | Display name shown to users. Auto-detected from WordPress.org if missing |
| `required` | `boolean` | No | `true` = users must install, `false` = optional. Default: `false` |
| `source` | `string` | No | Path to ZIP file (local or URL). Omit for WordPress.org plugins |
| `external_url` | `string` | No | Link to plugin homepage or documentation |
| `description` | `string` | No | Description shown in plugin list |
| `version` | `string` | No | Version number for display purposes |
| `author` | `string` | No | Plugin author name |
| `force_activation` | `boolean` | No | Force activation even if already installed. Default: `false` |
| `force_deactivation` | `boolean` | No | Deactivate after import. Rarely used. Default: `false` |

---

## Best Practices

### ✅ DO

1. **Mark critical plugins as required**
   ```php
   array( 'slug' => 'woocommerce', 'required' => true ) // Shop won't work without it
   ```

2. **Make enhancement plugins recommended**
   ```php
   array( 'slug' => 'wordpress-seo', 'required' => false ) // Nice to have
   ```

3. **Use demo-specific plugins**
   ```php
   // Only show WooCommerce for shop demos, not blog demos
   ```

4. **Provide descriptions for premium plugins**
   ```php
   array(
       'slug' => 'my-premium-plugin',
       'description' => 'Adds advanced portfolio features',
   )
   ```

5. **Test plugin installations**
   - Test with fresh WordPress install
   - Verify download URLs work
   - Check plugin activation succeeds

6. **Keep plugin ZIPs updated**
   ```php
   // Include version in path for cache busting
   'source' => get_template_directory() . '/plugins/elementor-pro-v3.1.0.zip'
   ```

### ❌ DON'T

1. **Don't bundle pirated plugins**
   - Only include plugins you have rights to distribute
   - Follow plugin license terms

2. **Don't mark too many plugins as required**
   - Users may be overwhelmed
   - Keep required list minimal

3. **Don't use hardcoded URLs**
   ```php
   // BAD
   'source' => '/home/user/themes/my-theme/plugins/plugin.zip'
   
   // GOOD
   'source' => get_template_directory() . '/plugins/plugin.zip'
   ```

4. **Don't forget plugin dependencies**
   ```php
   // If you require Elementor Pro, also require Elementor
   array( 'slug' => 'elementor', 'required' => true ),
   array( 'slug' => 'elementor-pro', 'required' => true ),
   ```

5. **Don't include plugins with security issues**
   - Keep bundled plugins updated
   - Remove abandoned plugins

---

## Troubleshooting

### Issue: Plugin Not Installing

**Check:**
1. Plugin slug is correct (matches WordPress.org slug)
2. For custom plugins, ZIP file exists at specified path
3. User has ConjureWP Premium (auto install is premium feature)
4. Check logs: `wp-content/uploads/conjure-wp/main.log`

### Issue: Wrong Plugin Installed

**Cause:** Slug mismatch

```php
// WRONG - slug doesn't match actual plugin directory
array( 'slug' => 'yoast', 'required' => true )

// CORRECT - matches plugin directory name
array( 'slug' => 'wordpress-seo', 'required' => true )
```

**How to find correct slug:**
- Install plugin manually
- Check `/wp-content/plugins/` directory
- Use the folder name as slug

### Issue: Custom Plugin Download Fails

**Solutions:**

1. **Check file exists:**
   ```php
   $path = get_template_directory() . '/plugins/plugin.zip';
   if ( ! file_exists( $path ) ) {
       error_log( 'Plugin not found: ' . $path );
   }
   ```

2. **Verify URL is accessible:**
   ```php
   $response = wp_remote_get( 'https://yoursite.com/plugins/plugin.zip' );
   if ( is_wp_error( $response ) ) {
       error_log( 'Download failed: ' . $response->get_error_message() );
   }
   ```

3. **Check file permissions:**
   ```bash
   chmod 644 /path/to/plugin.zip
   ```

### Issue: Plugins Show for Wrong Demo

**Solution:** Ensure demo selection is working:

```php
// Add debug logging
function debug_selected_demo( $demo_plugins, $demo_index, $selected_demo ) {
    error_log( 'Selected demo: ' . $selected_demo['import_file_name'] );
    error_log( 'Plugin count: ' . count( $demo_plugins ) );
    return $demo_plugins;
}
add_filter( 'conjure_demo_required_plugins', 'debug_selected_demo', 10, 3 );
```

---

## Examples

### Example 1: Simple Blog Theme

```php
function blogtheme_import_files() {
    return array(
        array(
            'import_file_name' => 'Blog Demo',
            'local_import_file' => get_template_directory() . '/demo/content.xml',
            'required_plugins' => array(
                'contact-form-7',     // Simple: slug only
                'wordpress-seo',      // Recommended by default
            ),
        ),
    );
}
add_filter( 'conjure_import_files', 'blogtheme_import_files' );
```

### Example 2: E-commerce Theme

```php
function shoptheme_import_files() {
    return array(
        array(
            'import_file_name' => 'Full Shop',
            'local_import_file' => get_template_directory() . '/demo/content.xml',
            'required_plugins' => array(
                // WooCommerce - absolutely required
                array(
                    'slug'     => 'woocommerce',
                    'required' => true,
                ),
                // Payment gateways - recommended
                array(
                    'slug'     => 'woocommerce-gateway-stripe',
                    'required' => false,
                ),
                // Enhancements - optional
                array(
                    'slug'     => 'woo-variation-swatches',
                    'required' => false,
                ),
            ),
        ),
    );
}
add_filter( 'conjure_import_files', 'shoptheme_import_files' );
```

### Example 3: Premium Theme with Bundled Plugins

```php
function premiumtheme_import_files() {
    return array(
        array(
            'import_file_name' => 'Premium Demo',
            'local_import_file' => get_template_directory() . '/demo/content.xml',
            'required_plugins' => array(
                // Free plugin from WordPress.org
                array(
                    'slug'     => 'elementor',
                    'required' => true,
                ),
                // Premium plugin bundled with theme
                array(
                    'name'     => 'Elementor Pro',
                    'slug'     => 'elementor-pro',
                    'source'   => get_template_directory() . '/plugins/elementor-pro.zip',
                    'required' => true,
                ),
                // Custom theme plugin
                array(
                    'name'        => 'Theme Extensions',
                    'slug'        => 'mytheme-extensions',
                    'source'      => get_template_directory() . '/plugins/extensions.zip',
                    'description' => 'Custom widgets and features for this theme',
                    'required'    => false,
                ),
            ),
        ),
    );
}
add_filter( 'conjure_import_files', 'premiumtheme_import_files' );
```

---

## Support

### Getting Help

**Documentation:** [conjurewp.com/docs](https://conjurewp.com/docs)  
**GitHub Issues:** [github.com/NoughtDigital/ConjureWP/issues](https://github.com/NoughtDigital/ConjureWP/issues)  
**Support:** support@conjurewp.com

### Before Requesting Support

1. Enable debug logging in `conjurewp-config.php`:
   ```php
   'logging' => array(
       'min_log_level' => 'DEBUG',
   ),
   ```

2. Check logs at: `wp-content/uploads/conjure-wp/main.log`

3. Verify plugin slugs match actual plugin directory names

4. Test with a single simple plugin first

---

**Last Updated:** November 2024  
**ConjureWP Version:** 2.0.0+


