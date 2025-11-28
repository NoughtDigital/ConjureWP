# ConjureWP

WordPress theme setup wizard with demo content import.

> **Free on WordPress.org** | Premium features available via Freemius. Based on [MerlinWP](https://github.com/richtabor/MerlinWP).

## Features

### Core Features (Free)

-   **Setup wizard** - Step-by-step guided installation with progress tracking
-   **Child theme generator** - Create safe child themes automatically
-   **Built-in plugin installer** - Shows required/recommended plugins from WordPress.org and custom sources
-   **Demo content import** - Posts, pages, media, categories, tags
-   **Widget importer** - Automatically configure widgets and sidebars
-   **Customiser importer** - Theme settings and customisations
-   **Demo-specific plugin dependencies** - Show only relevant plugins per demo
-   **Theme bundled plugins** - Auto-merge plugins from theme's `/conjurewp-plugins/` folder
-   **Server health check** - PHP memory limit, execution time, MySQL version monitoring
-   **Comprehensive logging** - Detailed logs with rotation, filtering, and admin viewer
-   **Admin tools** - Log viewer, clear/download logs, wizard reset controls
-   **WP-CLI commands** - Full command-line support for automated deployments
-   **REST API** - HTTP endpoints for hosting dashboards and automation
-   **Auto-discovery** - Automatically detects demo content in theme directory
-   **Update-safe storage** - Store demos outside plugin directory
-   **Security hardened** - `.htaccess` protection, nonce verification, capability checks

### Premium Features ⭐

-   **Automatic plugin installation** - One-click installation of all required plugins (no manual downloads)
-   **Revolution Slider import** - Import slider configurations for advanced demo replication
-   **Redux Framework import** - Import theme option panel settings
-   **Priority support** - Get help when you need it
-   **Lifetime integration** - For theme developers to bundle with themes

### Free vs Premium

| Feature | Free | Premium |
|---------|------|---------|
| Setup wizard & progress tracking | ✓ | ✓ |
| Child theme generator | ✓ | ✓ |
| Content/widget/customiser import | ✓ | ✓ |
| Plugin installer (manual) | ✓ | ✓ |
| Plugin installer (automatic) | ✗ | ✓ |
| Revolution Slider import | ✗ | ✓ |
| Redux Framework import | ✗ | ✓ |
| WP-CLI commands | ✓ | ✓ |
| REST API | ✓ | ✓ |
| Server health monitoring | ✓ | ✓ |
| Comprehensive logging | ✓ | ✓ |
| Support | Community | Priority |

## Requirements

-   WordPress 6.0+ (tested up to 6.6)
-   PHP 7.4+ (tested up to 8.4)
-   256MB memory limit, 60s execution time recommended

**Dev:** Node.js 18.0+, npm 9.0+

## Installation

```bash
composer install
npm install && npm run build
```

Activate via WordPress admin, then configure imports using filters.

## Quick Start

1. Enable demo in `examples/demo-theme-integration.php`
2. Run wizard from theme setup page
3. See `examples/theme-integration.php` for integration

## Configuration

### Production Defaults

**Important:** For production builds, `dev_mode` and verbose logging are **disabled by default** to prevent accidental exposure of reset tools or DEBUG-level log noise.

In `conjurewp-config.php`:

-   `dev_mode`: Defaults to `false` - prevents wizard from being rerun after completion (hides reset tools)
-   `logging['min_log_level']`: Defaults to `'INFO'` - suppresses DEBUG-level verbose logging

To enable these features during development:

```php
// In conjurewp-config.php
'dev_mode' => true, // Enable development mode
'logging' => array(
    'min_log_level' => 'DEBUG', // Enable verbose logging
),
```

**Note:** Only enable `dev_mode` and DEBUG logging in development environments. In production, keep these disabled to avoid exposing internal tools or generating excessive log noise.

### Custom Demo Content (Update-Safe)

The plugin's `/demo/` folder is for examples only and gets overwritten during updates. For production, use update-safe locations:

#### Option 1: Auto-Discovery (Easiest)

Add to your `wp-config.php`:

```php
// Point to your custom demo folder
define( 'CONJUREWP_DEMO_PATH', '/full/path/to/demo/files' );

// Enable auto-discovery
define( 'CONJUREWP_AUTO_REGISTER_DEMOS', true );
```

Organise your demo files:

```
/your-custom-path/
├── content.xml          # Single demo
├── widgets.json
└── customizer.dat

OR multiple demos:
/your-custom-path/
├── main/
│   ├── content.xml
│   └── widgets.json
└── portfolio/
    └── content.xml
```

Demos are automatically discovered and registered.

#### Option 2: Theme Directory

Store in: `wp-content/themes/your-theme/conjurewp-demos/`

```php
function my_theme_import_files() {
    return array(
        array(
            'import_file_name'             => 'Demo Import',
            'local_import_file'            => get_template_directory() . '/conjurewp-demos/content.xml',
            'local_import_widget_file'     => get_template_directory() . '/conjurewp-demos/widgets.json',
            'local_import_customizer_file' => get_template_directory() . '/conjurewp-demos/customizer.dat',
        ),
    );
}
add_filter( 'conjure_import_files', 'my_theme_import_files' );
```

### Post-Import Setup

```php
add_action( 'conjure_after_all_import', 'my_theme_after_import_setup' );
function my_theme_after_import_setup( $selected_import ) {
    // Set menus
    $main_menu = get_term_by( 'name', 'Main Menu', 'nav_menu' );
    set_theme_mod( 'nav_menu_locations', array(
        'primary' => $main_menu->term_id,
    ) );

    // Set front page
    $front_page_id = get_page_by_title( 'Home' );
    update_option( 'show_on_front', 'page' );
    update_option( 'page_on_front', $front_page_id->ID );
}
```

### Demo Location Priority

1. `CONJUREWP_DEMO_PATH` constant (wp-config.php)
2. Theme directory `/conjurewp-demos/`
3. Uploads directory `/conjurewp-demos/`
4. Plugin directory `/demo/` (examples only)

### Securing Demo Directories

Demo directories in the uploads folder are automatically protected with `.htaccess` (Apache/LiteSpeed) and `index.php` placeholders. For nginx, add this to your server configuration:

```nginx
location ~* /wp-content/uploads/conjurewp-demos/ {
    deny all;
    return 403;
}
```

### Auto-Discovery

When `CONJUREWP_AUTO_REGISTER_DEMOS` is enabled:

-   Automatically finds all demos in the directory
-   Detects `content.xml`, `widgets.json`, `customizer.dat`, `redux-options.json`, `slider.zip`
-   Reads `info.txt` for import notices
-   Finds `preview.jpg/png/gif/webp` for preview images

### Preview Images

Add preview images to display demos in a visual grid (similar to "One Click Demo Import"):

```
/themes/your-theme/conjurewp-demos/
├── main-demo/
│   ├── content.xml
│   ├── widgets.json
│   └── preview.jpg      ← Auto-detected
└── portfolio-demo/
    ├── content.xml
    └── preview.png      ← Auto-detected
```

**Supported formats:** `preview.jpg`, `preview.jpeg`, `preview.png`, `preview.gif`, `preview.webp`

**Manual configuration:** If not using auto-discovery, add `import_preview_image_url`:

```php
array(
    'import_file_name'           => 'Business Demo',
    'local_import_file'          => get_template_directory() . '/demo/content.xml',
    'import_preview_image_url'   => get_template_directory_uri() . '/demo/preview.jpg',
),
```

### Key Hooks

-   `conjure_import_files` - Register import configurations
-   `conjure_after_all_import` - Post-import setup
-   `conjure_content_home_page_title` - Homepage title
-   `conjure_content_blog_page_title` - Blog page title

See `examples/conjure-filters-sample.php` and `examples/theme-integration.php` for more examples.

### Plugin Installation (v2.0.0)

**Zero dependencies!** ConjureWP includes a built-in custom plugin installer supporting:

-   ✅ WordPress.org plugins (free from repository)
-   ✅ Custom/Premium plugins (bundled with theme or via URL)
-   ✅ Demo-specific plugin filtering
-   ✅ Required vs recommended plugins
-   ✅ Automatic installation and activation (Premium feature)

**WordPress.org Plugin:**

```php
'required_plugins' => array(
    array(
        'name'     => 'Contact Form 7',
        'slug'     => 'contact-form-7',
        'required' => true, // REQUIRED - users must install
    ),
    array(
        'name'     => 'Yoast SEO',
        'slug'     => 'wordpress-seo',
        'required' => false, // RECOMMENDED - optional
    ),
),
```

**Custom/Premium Plugin:**

```php
'required_plugins' => array(
    array(
        'name'     => 'Elementor Pro',
        'slug'     => 'elementor-pro',
        'source'   => get_template_directory() . '/plugins/elementor-pro.zip', // Local path
        'required' => true,
    ),
    array(
        'name'     => 'Premium Slider',
        'slug'     => 'premium-slider',
        'source'   => 'https://yoursite.com/downloads/slider.zip', // External URL
        'required' => false,
    ),
),
```

**📖 Complete Guide:** See [docs/PLUGIN-CONFIGURATION.md](/docs/PLUGIN-CONFIGURATION.md) for:

-   WordPress.org plugin configuration
-   Bundling custom/premium plugins
-   External plugin URLs and authentication
-   Demo-specific plugin dependencies
-   Advanced filtering and troubleshooting

### Demo-Specific Plugin Dependencies

Define different plugins for each demo, improving UX and reducing installation time:

```php
function mytheme_import_files() {
    return array(
        array(
            'import_file_name'     => 'Business Demo',
            'local_import_file'    => get_template_directory() . '/demos/business/content.xml',

            // Only Contact Form 7 needed for this demo
            'required_plugins'     => array(
                'contact-form-7',
            ),
        ),
        array(
            'import_file_name'     => 'E-commerce Demo',
            'local_import_file'    => get_template_directory() . '/demos/shop/content.xml',

            // WooCommerce required, Contact Form 7 optional
            'required_plugins'     => array(
                'woocommerce' => array( 'required' => true ),
                'contact-form-7',
            ),
        ),
        array(
            'import_file_name'     => 'Minimal Blog',
            'local_import_file'    => get_template_directory() . '/demos/minimal/content.xml',

            // No plugins needed!
            'required_plugins'     => array(),
        ),
    );
}
add_filter( 'conjure_import_files', 'mytheme_import_files' );
```

**Benefits:**

-   Users see only plugins relevant to their chosen demo
-   Faster installation (no unnecessary plugins)
-   Better UX for multi-demo themes
-   Full backward compatibility

**Learn more:**

-   **New system:** See `examples/simple-demo-plugins-no-tgmpa.php`
-   **Advanced:** Demo-specific filtering examples in `/examples/` directory

### Theme Bundled Plugins

Automatically bundle plugins with your theme for seamless distribution.

**Setup:**

1. Create `/conjurewp-plugins/` folder in your theme
2. Add `plugins.json` configuration:

```json
{
    "plugins": [
        {
            "name": "My Premium Plugin",
            "slug": "my-premium-plugin",
            "source": "my-premium-plugin.zip",
            "required": true,
            "version": "1.0.0"
        }
    ]
}
```

3. Place plugin ZIP files in the same folder

**Features:**

-   Automatic merging with demo-specific plugins
-   Support for WordPress.org and custom plugins
-   Version detection and update prompts
-   Required vs recommended designation

See `examples/theme-bundled-plugins/README-THEME-PLUGINS.txt` for complete documentation.

## WP-CLI Commands

ConjureWP supports WP-CLI for automated imports, perfect for CI/CD pipelines and hosting automation.

### List Available Demos

```bash
wp conjure list
```

### Import Demo Content

```bash
# Import by slug
wp conjure import --demo=demo-content

# Import by index
wp conjure import --demo=0

# Skip specific imports
wp conjure import --demo=0 --skip-widgets --skip-sliders
```

**Available Options:**

-   `--skip-content` - Skip content import
-   `--skip-widgets` - Skip widgets import
-   `--skip-options` - Skip customizer options
-   `--skip-sliders` - Skip Revolution Sliders
-   `--skip-redux` - Skip Redux options

### Example: CI/CD Pipeline

```bash
#!/bin/bash
# Automated site setup
wp core install --url=example.com --title="My Site" \
    --admin_user=admin --admin_email=admin@example.com
wp theme activate my-theme
wp plugin install contact-form-7 --activate
wp conjure import --demo=0
```

For complete documentation, see [WP-CLI.md](WP-CLI.md).

## REST API

ConjureWP exposes REST API endpoints for hosting dashboards and automation tools to trigger imports without shell access.

### Available Endpoints

**List Demos:**

```bash
GET /wp-json/conjurewp/v1/demos
```

**Import Demo:**

```bash
POST /wp-json/conjurewp/v1/import
{
    "demo": "demo-slug-or-index",
    "skip_content": false,
    "skip_widgets": false,
    "skip_options": false,
    "skip_sliders": false,
    "skip_redux": false
}
```

**Authentication:** Requires `manage_options` capability (administrator).

**Use Cases:**

-   Hosting control panels triggering demo imports
-   CI/CD pipelines without WP-CLI access
-   Custom admin dashboards
-   Remote site management tools

## Admin Tools

### Admin Bar Menu

When logged in as administrator, access ConjureWP tools via the admin bar:

-   **Run Setup Wizard** - Launch the setup wizard
-   **Reset Setup Wizard** - Delete child theme and clear all progress

### Log Viewer (Tools → ConjureWP Logs)

-   View all log files in browser
-   Filter by log level (DEBUG, INFO, WARNING, ERROR)
-   Download logs for support
-   Clear individual or all logs
-   Automatic log rotation (configurable)

### Developer Tools

Enable advanced reset controls in `wp-config.php`:

```php
define( 'CONJURE_TOOLS_ENABLED', true );
```

Provides granular reset options for individual steps (child theme, licence, plugins, content).

## Server Health Monitoring

ConjureWP includes built-in server health checks to detect potential import issues before they occur.

### Monitored Metrics

-   **PHP Memory Limit** - Recommends 256MB minimum
-   **PHP Max Execution Time** - Recommends 300 seconds minimum
-   **MySQL Version** - Displays current version

### Customisation

```php
// Adjust requirements in your theme
add_filter( 'conjure_server_health_min_memory', function() {
    return 512; // MB
});

add_filter( 'conjure_server_health_min_execution', function() {
    return 600; // seconds
});

// Disable health checks
add_filter( 'conjure_server_health_enabled', '__return_false' );
```

See `examples/server-health-usage.php` for more examples.

## Debugging & Logging

### Log Files

Logs are stored in: `wp-content/uploads/conjure-wp/main.log`

Features:

-   Automatic rotation when files exceed 10MB
-   Keeps 5 most recent log files
-   Configurable log levels (DEBUG, INFO, WARNING, ERROR, etc.)
-   Protected by `.htaccess` (Apache) and `index.php`

### Viewing Logs

1. **Admin viewer:** Tools → ConjureWP Logs
2. **Direct access:** SSH/FTP to log directory
3. **Download:** Use admin viewer's download button

### Log Configuration

In `conjurewp-config.php`:

```php
'logging' => array(
    'enable_rotation'  => true,
    'max_files'        => 5,
    'max_file_size_mb' => 10,
    'min_log_level'    => 'INFO', // DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY
),
```

### Common Fixes

-   **Memory issues:** `define('WP_MEMORY_LIMIT', '256M');` in `wp-config.php`
-   **Timeout issues:** `define('WP_MAX_EXECUTION_TIME', 300);` in `wp-config.php`
-   **Import stalls:** Check logs for specific errors
-   **Plugin conflicts:** Disable other plugins temporarily

## Rerunning Individual Steps (Power Users)

After onboarding is complete, you can enable developer tools to rerun individual steps without resetting the entire onboarding process.

### Enable Rerun Tools

Add this constant to your `wp-config.php`:

```php
define( 'CONJURE_TOOLS_ENABLED', true );
```

### Using Rerun Tools

Once enabled, administrators will see a **"Conjure WP"** menu in the WordPress admin bar with:

-   Individual step status (✓ completed, ○ not completed)
-   Reset individual steps: Child Theme, Licence, Plugins, Content
-   Reset all steps to restart complete onboarding
-   Quick access to open the wizard

**Use cases:**

-   Re-import demo content with different settings
-   Reinstall/update plugins after changes
-   Regenerate child theme
-   Test onboarding flow during development

**Note:** Only visible to users with `manage_options` capability (administrators).

## Build Commands

```bash
npm run build       # Build assets (CSS/JS)
npm run build:wp    # Build WordPress.org distribution zip
npm run dev         # Development with watch
```

## Deployment

### WordPress.org Deployment

Build a WordPress.org compatible distribution:

```bash
npm run build:wp
```

This creates `dist/conjurewp.zip` with:
- Production-optimised assets
- Required vendor dependencies (Freemius SDK, Monolog)
- No development files (tests, examples, build configs)
- WordPress.org ready structure

Upload the generated zip to WordPress.org SVN repository.

### Freemius Deployment

This plugin is also configured for Freemius deployment, which automatically generates:

-   **Free version**: WordPress.org compatible (Freemius SDK stripped)
-   **Premium version**: Includes Freemius SDK and licensing

**Quick Deploy:**

1. Update version in `conjurewp.php`
2. Create git tag: `git tag v1.0.0 && git push origin v1.0.0`
3. Upload ZIP to Freemius dashboard (or use GitHub Actions)
4. Freemius processes and creates both versions
5. Set release status to "Released"

**Free Version:**

-   All core features included
-   No licensing restrictions
-   100% WordPress.org compatible
-   No external API calls

**Premium Version:**

-   Same features as free
-   Freemius SDK for license management
-   Optional: can gate additional features if needed

## Documentation

### User Documentation

-   **[Licence Activation Guide](/docs/LICENCE-ACTIVATION.md)** - Understanding ConjureWP licensing, free vs premium features, and how to activate your licence. Clears up confusion between ConjureWP and theme licensing.
-   **[WP-CLI Documentation](WP-CLI.md)** - Complete WP-CLI command reference for automated imports

### Developer Documentation

-   **[Plugin Configuration Guide](/docs/PLUGIN-CONFIGURATION.md)** - Complete guide to configuring WordPress.org and custom/premium plugins for your theme demos. Includes demo-specific dependencies, required vs recommended plugins, and troubleshooting.
-   **[Lifetime Integration Guide](/docs/LIFETIME-INTEGRATION.md)** - For theme developers who purchased lifetime ConjureWP integration. Allows your users to access premium features without needing their own licence.

### Code Examples (`/examples/` directory)

**Theme Integration:**

-   `theme-integration.php` - Basic theme integration
-   `demo-theme-integration.php` - Complete demo setup
-   `theme-config-options.php` - Configuration examples
-   `theme-redirect-integration.php` - Custom redirect handling

**Plugin Configuration:**

-   `simple-demo-plugins-no-tgmpa.php` - Plugin installer examples
-   `theme-bundled-plugins/` - Bundle plugins with theme

**Advanced Features:**

-   `server-health-usage.php` - Server health monitoring
-   `premium-features-usage.php` - Premium feature detection
-   `redirect-control-examples.php` - Control wizard redirects
-   `conjure-logging-config-sample.php` - Logging configuration

**Automation & Testing:**

-   `cli-integration.sh` - WP-CLI automation scripts
-   `test-cli-import.sh` - Test CLI imports
-   `test-import-setup.php` - Validate import configuration
-   `verify-setup.sh` - Setup verification

See [examples/README.md](examples/README.md) for the complete list with descriptions.

### Additional Resources

-   **[Code of Conduct](CODE_OF_CONDUCT.md)** - Community guidelines
-   **GitHub Repository** - [github.com/NoughtDigital/ConjureWP](https://github.com/NoughtDigital/ConjureWP)
-   **Support** - [conjurewp.com](https://conjurewp.com/)

## Credits

Based on [MerlinWP](https://github.com/richtabor/MerlinWP). Built with ConjureWP Importer, [Monolog](https://github.com/Seldaek/monolog), and TGMPA.

## Licence

GPLv3 - See [LICENSE](LICENSE)
