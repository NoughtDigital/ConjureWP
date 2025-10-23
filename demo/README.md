# ConjureWP Demo Files

This directory contains test/demo files for testing the ConjureWP import functionality.

## Files Included

### Core Import Files

1. **content.xml** - WordPress export file containing:

    - 4 pages (Home, Blog, About Us, Contact)
    - 2 blog posts
    - Sample categories and navigation menu
    - Demo content for testing

2. **widgets.json** - Widget configuration containing:

    - Sidebar widgets (Search, Recent Posts, Categories, etc.)
    - Footer widgets
    - Text widgets with demo content

3. **customizer.dat** - Theme customizer settings containing:
    - Site title and tagline
    - Homepage and blog page settings
    - Navigation menu locations
    - Basic theme options

### Optional/Extra Import Files

4. **redux-options.json** - Redux Framework options containing:

    - Theme color settings
    - Typography options
    - Layout configurations
    - Social media links

5. **slider.zip** - Revolution Slider export (placeholder - you need to create your own)
    - Export a slider from Revolution Slider to test this feature

## How to Use These Files

### Option 1: Copy to Your Theme

```bash
cp -r demo /path/to/your/theme/
```

### Option 2: Reference from Plugin

Keep them in the plugin directory and reference with absolute paths.

## Testing the Import

### Basic Import Test

Add this to your theme's `functions.php`:

```php
function mytheme_test_conjure_import() {
    return array(
        array(
            'import_file_name'             => 'Demo Import Test',
            'local_import_file'            => WP_PLUGIN_DIR . '/ConjureWP/demo/content.xml',
            'local_import_widget_file'     => WP_PLUGIN_DIR . '/ConjureWP/demo/widgets.json',
            'local_import_customizer_file' => WP_PLUGIN_DIR . '/ConjureWP/demo/customizer.dat',
            'import_notice'                => __( 'Test import with demo files', 'textdomain' ),
        ),
    );
}
add_filter( 'conjure_import_files', 'mytheme_test_conjure_import' );
```

### Import with Redux Options

```php
function mytheme_test_redux_import() {
    return array(
        array(
            'import_file_name'             => 'Demo with Redux',
            'local_import_file'            => WP_PLUGIN_DIR . '/ConjureWP/demo/content.xml',
            'local_import_redux'           => array(
                array(
                    'file_path'   => WP_PLUGIN_DIR . '/ConjureWP/demo/redux-options.json',
                    'option_name' => 'mytheme_redux_options',
                ),
            ),
        ),
    );
}
add_filter( 'conjure_import_files', 'mytheme_test_redux_import' );
```

## Post-Import Setup

After import completes, add this action:

```php
function mytheme_after_import_setup( $selected_import ) {
    // Assign menus
    $main_menu = get_term_by( 'name', 'Main Menu', 'nav_menu' );
    if ( $main_menu ) {
        set_theme_mod( 'nav_menu_locations', array(
            'primary' => $main_menu->term_id,
        ));
    }

    // Set homepage
    $front_page = get_page_by_title( 'Home' );
    $blog_page  = get_page_by_title( 'Blog' );

    if ( $front_page ) {
        update_option( 'show_on_front', 'page' );
        update_option( 'page_on_front', $front_page->ID );
    }

    if ( $blog_page ) {
        update_option( 'page_for_posts', $blog_page->ID );
    }
}
add_action( 'conjure_after_all_import', 'mytheme_after_import_setup' );
```

## Verification

After import:

1. Check the log file: `wp-content/uploads/conjure-wp/main.log`
2. Verify pages exist: Home, Blog, About Us, Contact
3. Check blog posts are imported
4. Verify widgets in Appearance > Widgets
5. Check menu in Appearance > Menus
6. Confirm customizer settings were applied

## Creating Your Own Demo Files

### Export Content

1. Go to **Tools > Export** in WordPress
2. Select "All content"
3. Click "Download Export File"
4. Save as `content.xml`

### Export Widgets

1. Install [Widget Importer & Exporter](https://wordpress.org/plugins/widget-importer-exporter/)
2. Go to **Tools > Widget Importer & Exporter**
3. Click "Export Widgets"
4. Save as `widgets.json` or `widgets.wie`

### Export Customizer

1. Install [Customizer Export/Import](https://wordpress.org/plugins/customizer-export-import/)
2. Go to **Appearance > Customize**
3. Find "Export/Import" panel
4. Click "Export"
5. Save as `customizer.dat`

### Export Redux Options

1. Go to your Redux Framework panel
2. Click "Import/Export"
3. Copy JSON data
4. Save as `redux-options.json`

### Export Revolution Slider

1. Go to **Revolution Slider**
2. Select slider to export
3. Click "Export Slider"
4. Save as `slider.zip`

## Notes

-   All files are for testing purposes only
-   Replace with your actual demo content before production
-   Ensure file permissions are correct (readable by web server)
-   Always test imports on a staging site first
