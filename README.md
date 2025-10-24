![ConjureWP](conjurewp.jpg)

# ConjureWP

WordPress theme setup wizard with demo content import.

> **Development Status:** Not production ready. Based on [MerlinWP](https://github.com/richtabor/MerlinWP).

## Features

-   Setup wizard with progress tracking
-   Plugin installation (TGMPA)
-   Child theme generator
-   Demo content, widgets, customiser import
-   Revolution Slider & Redux support
-   Self-contained importer (no external dependencies)
-   **WP-CLI commands for automated imports** (CI/CD, hosting automation)

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

### Auto-Discovery

When `CONJUREWP_AUTO_REGISTER_DEMOS` is enabled:

-   Automatically finds all demos in the directory
-   Detects `content.xml`, `widgets.json`, `customizer.dat`, `redux-options.json`, `slider.zip`
-   Reads `info.txt` for import notices
-   Finds `preview.jpg/png/gif` for preview images

### Key Hooks

-   `conjure_import_files` - Register import configurations
-   `conjure_after_all_import` - Post-import setup
-   `conjure_content_home_page_title` - Homepage title
-   `conjure_content_blog_page_title` - Blog page title

See `examples/conjure-filters-sample.php` and `examples/theme-integration.php` for more examples.

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

## Admin Tools

Admin bar options when active:

-   **Run Setup Wizard** - Launch wizard
-   **Reset Setup Wizard** - Delete child theme, clear progress

## Debugging

Logs: `wp-content/uploads/conjure-wp/main.log`

Common fixes:

-   Increase memory: `define('WP_MEMORY_LIMIT', '256M');`
-   Increase timeout: `define('WP_MAX_EXECUTION_TIME', 300);`

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
npm run build    # Production
npm run dev      # Development with watch
```

## Credits

Based on [MerlinWP](https://github.com/richtabor/MerlinWP). Built with ConjureWP Importer, [Monolog](https://github.com/Seldaek/monolog), and TGMPA.

## Licence

GPLv3 - See [LICENSE](LICENSE)
