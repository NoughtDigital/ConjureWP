# WP-CLI Commands for ConjureWP

ConjureWP now includes WP-CLI command support, enabling hosts and CI scripts to bootstrap WordPress sites without browser interaction.

## Available Commands

### List Available Demos

Lists all registered demo imports with their indices and slugs.

```bash
wp conjure list
```

**Output:**

```
Available demo imports:

+-------+---------------+--------------+
| Index | Name          | Slug         |
+-------+---------------+--------------+
| 0     | Demo Content  | demo-content |
+-------+---------------+--------------+
```

### Import Demo Content

Import a specific demo by slug or index.

```bash
wp conjure import --demo=<slug|index>
```

#### Basic Examples

```bash
# Import demo by slug
wp conjure import --demo=demo-content

# Import demo by index
wp conjure import --demo=0
```

#### Advanced Options

By default, all available content types are imported. You can skip specific types:

```bash
# Skip widgets and sliders
wp conjure import --demo=0 --skip-widgets --skip-sliders

# Import only content, skip everything else
wp conjure import --demo=0 --skip-widgets --skip-options --skip-sliders --skip-redux
```

**Available Skip Flags:**

-   `--skip-content` - Skip content import (posts, pages, etc.)
-   `--skip-widgets` - Skip widgets import
-   `--skip-options` - Skip customizer options import
-   `--skip-sliders` - Skip Revolution Sliders import
-   `--skip-redux` - Skip Redux options import

## Use Cases

### CI/CD Pipelines

Automate site setup in your deployment pipeline:

```bash
#!/bin/bash
# setup-site.sh

# Install WordPress core
wp core install --url=example.com --title="My Site" --admin_user=admin --admin_email=admin@example.com

# Activate theme
wp theme activate my-theme

# Install and activate required plugins
wp plugin install contact-form-7 --activate

# Import demo content
wp conjure import --demo=demo-content
```

### Host Provisioning

Hosting providers can offer one-click demo content installation:

```bash
# In your provisioning script
wp conjure list
wp conjure import --demo=0
```

### Development Environments

Quickly set up development sites with demo content:

```bash
# Reset and import fresh content
wp db reset --yes
wp core install --url=dev.local --title="Dev Site" --admin_user=admin --admin_email=dev@example.com
wp conjure import --demo=0
```

### Docker Initialization

Add to your Docker entrypoint script:

```dockerfile
# docker-entrypoint.sh
#!/bin/bash
set -e

# Wait for database
while ! mysqladmin ping -h"$WORDPRESS_DB_HOST" --silent; do
    sleep 1
done

# Install WordPress if not already installed
if ! wp core is-installed; then
    wp core install \
        --url="$WORDPRESS_URL" \
        --title="$WORDPRESS_TITLE" \
        --admin_user="$WORDPRESS_ADMIN_USER" \
        --admin_password="$WORDPRESS_ADMIN_PASSWORD" \
        --admin_email="$WORDPRESS_ADMIN_EMAIL"

    # Import demo content
    wp conjure import --demo=0
fi

exec "$@"
```

## Registering Demo Imports

Demo imports are registered using the `conjure_import_files` filter:

```php
function my_theme_demo_imports( $files ) {
    $demo_path = get_template_directory() . '/demo/';

    return array(
        array(
            'import_file_name'             => 'Main Demo',
            'local_import_file'            => $demo_path . 'content.xml',
            'local_import_widget_file'     => $demo_path . 'widgets.json',
            'local_import_customizer_file' => $demo_path . 'customizer.dat',
            'import_notice'                => __( 'Main demo import', 'my-theme' ),
            'preview_url'                  => 'https://example.com/main-demo',
        ),
        array(
            'import_file_name'             => 'Alternative Demo',
            'local_import_file'            => $demo_path . 'alternative/content.xml',
            'local_import_widget_file'     => $demo_path . 'alternative/widgets.json',
            'local_import_customizer_file' => $demo_path . 'alternative/customizer.dat',
            'import_notice'                => __( 'Alternative demo import', 'my-theme' ),
            'preview_url'                  => 'https://example.com/alternative-demo',
        ),
    );
}
add_filter( 'conjure_import_files', 'my_theme_demo_imports' );
```

## Import Process

The WP-CLI commands follow the same import pipeline as the browser interface:

1. **Before Import Setup** - Runs `import_start` action
2. **Content Import** - Imports posts, pages, custom post types, and media
3. **Widgets Import** - Imports widget configurations
4. **Options Import** - Imports customizer settings
5. **Sliders Import** - Imports Revolution Slider data (if plugin is active)
6. **Redux Import** - Imports Redux framework options (if available)
7. **After Import Setup** - Runs `import_end` action
8. **After All Import** - Runs `conjure_after_all_import` action

## Progress Tracking

The import command displays progress for content imports:

```
Starting import for: Demo Content

Importing content...
Found 42 items to import...
Importing content  42/42 [============================] 100%
Content imported successfully.
Importing widgets...
Widgets imported successfully.
Importing customizer options...
Customizer options imported successfully.

Import completed successfully!
```

## Error Handling

The commands provide clear error messages:

```bash
# Missing demo parameter
$ wp conjure import
Error: You must specify a demo using --demo=<slug|index>. Use "wp conjure list" to see available demos.

# Demo not found
$ wp conjure import --demo=nonexistent
Error: Demo "nonexistent" not found. Use "wp conjure list" to see available demos.

# No demos registered
$ wp conjure list
Warning: No demo imports are registered.
To register demo imports, use the conjure_import_files filter in your theme.
```

## Hooks and Filters

All standard ConjureWP hooks and filters are available during CLI imports:

```php
// Before content import
add_action( 'import_start', 'my_before_import_setup' );

// After content import
add_action( 'import_end', 'my_after_import_setup' );

// After all import steps complete
add_action( 'conjure_after_all_import', 'my_after_all_import', 10, 1 );

// Modify import data
add_filter( 'conjure_get_base_content', 'my_modify_import_data', 10, 2 );
```

## Requirements

-   WP-CLI 2.0 or higher
-   WordPress 5.0 or higher
-   ConjureWP plugin installed and activated
-   Registered demo imports via `conjure_import_files` filter

## Troubleshooting

### Commands Not Available

If `wp conjure` commands are not available, ensure:

1. WP-CLI is installed: `wp --version`
2. ConjureWP plugin is active: `wp plugin list`
3. Demo imports are registered: `wp conjure list`

### Import Fails

If import fails:

1. Check file permissions on demo files
2. Verify demo files exist at specified paths
3. Check PHP memory limit (increase if needed)
4. Review WordPress debug log for detailed errors

### Memory Issues

For large imports, increase PHP memory:

```bash
wp conjure import --demo=0 --max_execution_time=0 --memory_limit=512M
```

Or set in wp-config.php:

```php
define( 'WP_MEMORY_LIMIT', '512M' );
define( 'WP_MAX_MEMORY_LIMIT', '512M' );
```

## Support

For issues or questions:

-   GitHub: https://github.com/NoughtDigital/ConjureWP
-   Documentation: https://conjurewp.com/
