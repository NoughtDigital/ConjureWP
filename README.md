![ConjureWP](conjurewp.jpg)

# ConjureWP

WordPress theme setup wizard with demo content import.

> **Development Status:** Not production ready. Based on [MerlinWP](https://github.com/richtabor/MerlinWP).

## Features

-   Setup wizard with progress tracking
-   Plugin installation (TGMPA)
-   Child theme generator
-   Demo content, widgets, customizer import
-   Revolution Slider & Redux support
-   Self-contained importer (no external dependencies)

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

1. Enable demo in `demo-theme-integration.php`
2. Run wizard from theme setup page
3. See `example-theme-integration.php` for integration

## Configuration

```php
function my_theme_import_files() {
    return array(
        array(
            'import_file_name'           => 'Demo Import',
            'import_file_url'            => 'https://example.com/demo/content.xml',
            'import_widget_file_url'     => 'https://example.com/demo/widgets.json',
            'import_customizer_file_url' => 'https://example.com/demo/customizer.dat',
            'import_preview_image_url'   => 'https://example.com/demo/preview.jpg',
        ),
    );
}
add_filter( 'conjure_import_files', 'my_theme_import_files' );
```

Use `local_import_file`, `local_import_widget_file`, `local_import_customizer_file` for local files.

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

### Key Hooks

-   `conjure_import_files` - Define imports
-   `conjure_content_home_page_title` - Home page title
-   `conjure_content_blog_page_title` - Blog page title
-   `conjure_after_all_import` - Post-import actions

See `conjure-filters-sample.php` for examples.

## Admin Tools

Admin bar options when active:

-   **Run Setup Wizard** - Launch wizard
-   **Reset Setup Wizard** - Delete child theme, clear progress

## Debugging

Logs: `wp-content/uploads/conjure-wp/main.log`

Common fixes:

-   Increase memory: `define('WP_MEMORY_LIMIT', '256M');`
-   Increase timeout: `define('WP_MAX_EXECUTION_TIME', 300);`

## Build Commands

```bash
npm run build    # Production
npm run dev      # Development with watch
```

## Credits

Based on [MerlinWP](https://github.com/richtabor/MerlinWP). Built with ConjureWP Importer, [Monolog](https://github.com/Seldaek/monolog), and TGMPA.

## License

GPLv3 - See [LICENSE](LICENSE)
