![ConjureWP](conjurewp.jpg)

# ConjureWP - WordPress Setup Wizard Plugin

> **IMPORTANT: NOT PRODUCTION READY**  
> This plugin is currently in development and requires extensive testing before use in production environments. Code is based on [MerlinWP](https://github.com/richtabor/MerlinWP).

ConjureWP is a WordPress plugin that provides an onboarding wizard for theme setup. It streamlines the installation of demo content, plugins, widgets, and customizer settings through an intuitive interface.

## Features

-   Theme setup wizard with progress tracking
-   Plugin installation (TGMPA compatible)
-   Child theme generator
-   Demo content, widgets, and customizer import using ConjureWP Importer (internal)
-   Revolution Slider support
-   Redux Framework support
-   Optional EDD license activation
-   No external dependencies for content import

## Requirements

-   **WordPress:** 6.0+ (tested up to 6.6)
-   **PHP:** 7.4+ (tested up to 8.4)
-   **MySQL:** 5.6+ or MariaDB 10.1+
-   **Memory:** 256MB PHP memory limit recommended
-   **Execution Time:** 60s+ PHP max execution time

### Development Requirements

-   **Node.js:** 18.0+
-   **npm:** 9.0+

## Installation

1. Upload the `ConjureWP` folder to `/wp-content/plugins/`
2. Run `composer install` (if vendor directory is missing)
3. Run `npm install && npm run build` (to compile assets)
4. Activate the plugin through the 'Plugins' menu in WordPress
5. Configure demo imports using filters (see Configuration section)

## Quick Start

### Testing with Demo Files

ConjureWP includes ready-to-use demo files for immediate testing:

1. **Enable demo import** - Open `demo-theme-integration.php` and uncomment:

    ```php
    add_filter( 'conjure_import_files', 'conjurewp_demo_basic_import' );
    add_action( 'conjure_after_all_import', 'conjurewp_demo_after_import_setup' );
    ```

2. **Run the wizard** - Navigate to your theme setup page and import the demo

3. **Verify** - Check that pages, posts, widgets, and settings were imported

Demo files include sample content, widgets, customizer settings, and Redux options.

### Integration Example

See `example-theme-integration.php` for a complete working example. Use `validate-import-setup.php` to verify your setup.

## Configuration

Add a filter in your theme to define demo imports:

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

For local files, use `local_import_file`, `local_import_widget_file`, and `local_import_customizer_file` instead.

### Required Files

-   **content.xml** - WordPress export (Tools > Export)
-   **widgets.json** - Widget export ([Widget Importer & Exporter](https://wordpress.org/plugins/widget-importer-exporter/))
-   **customizer.dat** - Customizer export ([Customizer Export/Import](https://wordpress.org/plugins/customizer-export-import/))

## Post-Import Setup

Execute custom code after import completes:

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

### Key Filters & Hooks

-   `conjure_import_files` - Define import files
-   `conjure_content_home_page_title` - Set home page title
-   `conjure_content_blog_page_title` - Set blog page title
-   `conjure_after_all_import` - Post-import actions

See `conjure-filters-sample.php` for more examples.

## Testing & Debugging

### Admin Bar Options

When the plugin is active, you'll see ConjureWP options in the WordPress Admin Bar:

-   **Run Setup Wizard** - Launch the setup wizard
-   **Reset Setup Wizard** - Delete the child theme, clear wizard progress, and start fresh

These are useful for testing and development.

**Note:** The reset function will:

-   Switch back to the parent theme if a child theme is active
-   Delete the child theme directory and all its files
-   Clear all ConjureWP options and transients
-   Allow you to run the setup wizard again from scratch

### Server Health Check

The wizard automatically displays a server health check on the Content Import step. This shows:

-   PHP Memory Limit (minimum 512MB recommended)
-   PHP Max Execution Time (minimum 40000s recommended)
-   Visual indicator if requirements are met

The health check helps identify potential timeout issues before importing content.

### Debugging

Logs are written to `wp-content/uploads/conjure-wp/main.log`.

### Common Issues

**Import not appearing:**

-   Verify filter name is `conjure_import_files`
-   Ensure `import_file_name` is set

**Import fails:**

-   Increase PHP memory: `define('WP_MEMORY_LIMIT', '256M');`
-   Increase execution time: `define('WP_MAX_EXECUTION_TIME', 300);`
-   Check logs

## Building Assets

```bash
npm install          # Install dependencies
npm run build        # Production build
npm run dev          # Development build with watching
```

## Credits

Based on [MerlinWP](https://github.com/richtabor/MerlinWP).

Built with:

-   **ConjureWP Importer** - Internal WordPress content importer (based on WP Importer v2)
-   [Monolog](https://github.com/Seldaek/monolog) - Logging library
-   TGMPA - Plugin activation framework

## License

Licensed under GPLv3. See [LICENSE](LICENSE) for details.

## Changelog

### 1.0.0 (Development)

-   Initial release as standalone WordPress plugin
-   PHP 8.4 compatibility
-   WordPress 6.6 compatibility
-   Vite build system (migrated from Gulp)
-   Modern asset pipeline
-   ConjureWP Importer - Internal content import system (no external dependencies)
-   Migrated from ProteusThemes importer to self-contained solution
