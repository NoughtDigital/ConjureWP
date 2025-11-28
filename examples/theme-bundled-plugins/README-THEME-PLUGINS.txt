THEME PLUGIN BUNDLING
======================

ConjureWP allows theme developers to bundle plugins directly with their theme
using a simple JSON configuration file.

OVERVIEW
--------

Instead of configuring plugins for each demo separately, you can:
1. Create a /conjurewp-plugins/ folder in your theme
2. Add a plugins.json configuration file
3. Place plugin ZIP files in the folder (for premium/custom plugins)
4. ConjureWP automatically discovers and registers them

Benefits:
- ✅ Plugins apply to ALL demos automatically
- ✅ No need to configure for each demo individually
- ✅ Support for both free (WordPress.org) and premium (bundled) plugins
- ✅ Mark plugins as "mandatory" (theme requires) or optional (recommended)
- ✅ Bundle your own custom theme extensions
- ✅ One-time configuration, works across all demos

SETUP INSTRUCTIONS
------------------

### Step 1: Create Plugin Folder

In your theme directory, create:

```
/wp-content/themes/your-theme/conjurewp-plugins/
```

### Step 2: Create Configuration File

Create `plugins.json` in that folder:

```json
{
  "plugins": [
    {
      "name": "Contact Form 7",
      "slug": "contact-form-7",
      "required": true,
      "description": "Required for contact forms"
    },
    {
      "name": "Elementor Pro",
      "slug": "elementor-pro",
      "file": "elementor-pro.zip",
      "required": true,
      "version": "3.16.0",
      "description": "Premium page builder (bundled)"
    },
    {
      "name": "Yoast SEO",
      "slug": "wordpress-seo",
      "required": false,
      "description": "Recommended for SEO"
    }
  ]
}
```

### Step 3: Add Plugin Files (for premium plugins)

For bundled/premium plugins, place ZIP files in the folder:

```
/conjurewp-plugins/
├── plugins.json
├── elementor-pro.zip
└── mytheme-extensions.zip
```

CONFIGURATION OPTIONS
---------------------

Each plugin in the JSON file supports these fields:

### Required Fields:

**name** (string, required)
- Display name shown to users
- Example: "Contact Form 7"

**slug** (string, required)
- WordPress plugin slug
- Example: "contact-form-7"

### Optional Fields:

**required** (boolean, default: false)
- true = Theme requires this plugin (user must install)
- false = Optional/recommended plugin
- Example: true
- Note: "mandatory" also works (backward compatibility)

**file** (string, optional)
- Filename of bundled ZIP in conjurewp-plugins folder
- Only needed for premium/custom plugins
- Omit for WordPress.org free plugins
- Example: "elementor-pro.zip"

**version** (string, optional)
- Plugin version (for documentation)
- Example: "3.16.0"

**description** (string, optional)
- Why this plugin is needed
- Example: "Required for contact forms"

PLUGIN TYPES - THREE OPTIONS
-----------------------------

### Option 1: Free WordPress.org Plugins

No "file" or "url" field - ConjureWP fetches from WordPress.org:

```json
{
  "name": "Contact Form 7",
  "slug": "contact-form-7",
  "required": true,
  "description": "Free from WordPress.org"
}
```

### Option 2: Bundled Plugins (ZIP in Theme Folder)

Include "file" field pointing to ZIP in conjurewp-plugins folder:

```json
{
  "name": "Elementor Pro",
  "slug": "elementor-pro",
  "file": "elementor-pro.zip",
  "required": true,
  "version": "3.16.0",
  "description": "Bundled with theme purchase"
}
```

### Option 3: External URL (GitHub/GitLab/Direct Download)

Include "url" field with direct download link:

```json
{
  "name": "Custom Plugin",
  "slug": "my-custom-plugin",
  "url": "https://github.com/username/plugin/releases/download/v1.0.0/plugin.zip",
  "required": true,
  "version": "1.0.0",
  "description": "From GitHub releases"
}
```

### Supported External Sources:

✅ **GitHub Releases:**
```
https://github.com/user/repo/releases/download/v1.0.0/plugin.zip
```

✅ **GitLab:**
```
https://gitlab.com/user/repo/-/archive/v1.0.0/repo-v1.0.0.zip
```

✅ **Bitbucket:**
```
https://bitbucket.org/user/repo/downloads/plugin.zip
```

✅ **Direct Download URLs:**
```
https://updates.yoursite.com/downloads/plugin.zip
```

✅ **Private Update Servers:**
```
https://licenses.yourtheme.com/download/plugin.zip?key=abc123
```

REQUIRED VS OPTIONAL
--------------------

### Required (required: true)

- Marked as "REQUIRED" in the wizard
- Theme functionality depends on it
- Users must install before importing demos
- Pre-checked by default in premium version
- Example: Your custom theme plugin, required page builders

### Optional (required: false)

- Marked as "RECOMMENDED" in the wizard
- Theme works without it, but enhanced with it
- Users can skip installation
- Not checked by default
- Example: SEO plugins, analytics, optional features

EXAMPLE CONFIGURATIONS
-----------------------

### Example 1: Minimal Theme (Free Plugins Only)

```json
{
  "plugins": [
    {
      "name": "Contact Form 7",
      "slug": "contact-form-7",
      "mandatory": true
    }
  ]
}
```

### Example 2: Premium Theme with Bundled Plugins

```json
{
  "plugins": [
    {
      "name": "Elementor Pro",
      "slug": "elementor-pro",
      "file": "elementor-pro.zip",
      "required": true,
      "version": "3.16.0"
    },
    {
      "name": "ACF Pro",
      "slug": "advanced-custom-fields-pro",
      "file": "acf-pro.zip",
      "required": true,
      "version": "6.2.0"
    },
    {
      "name": "Theme Extensions",
      "slug": "mytheme-pro",
      "file": "mytheme-pro.zip",
      "required": true,
      "version": "1.0.0"
    }
  ]
}
```

### Example 3: Using GitHub Releases

```json
{
  "plugins": [
    {
      "name": "My Custom Plugin",
      "slug": "my-custom-plugin",
      "url": "https://github.com/username/my-plugin/releases/download/v2.0.0/my-plugin.zip",
      "required": true,
      "version": "2.0.0",
      "description": "Hosted on GitHub releases"
    },
    {
      "name": "Theme Companion",
      "slug": "mytheme-companion",
      "url": "https://github.com/username/companion/releases/latest/download/companion.zip",
      "required": true,
      "description": "Always gets latest from GitHub"
    }
  ]
}
```

### Example 4: ALL THREE OPTIONS MIXED

This shows the full power - mixing WordPress.org, bundled ZIPs, and external URLs:

```json
{
  "plugins": [
    {
      "name": "Contact Form 7",
      "slug": "contact-form-7",
      "required": true,
      "description": "Free from WordPress.org"
    },
    {
      "name": "Elementor Pro",
      "slug": "elementor-pro",
      "file": "elementor-pro.zip",
      "required": true,
      "version": "3.16.0",
      "description": "Bundled premium plugin in theme folder"
    },
    {
      "name": "Theme Extensions",
      "slug": "mytheme-extensions",
      "url": "https://github.com/yourname/extensions/releases/download/v1.5.0/extensions.zip",
      "required": true,
      "version": "1.5.0",
      "description": "From GitHub releases"
    },
    {
      "name": "Private Updates",
      "slug": "mytheme-pro-updates",
      "url": "https://updates.yoursite.com/api/download/plugin.zip",
      "required": false,
      "version": "2.0.0",
      "description": "From your private update server"
    },
    {
      "name": "Yoast SEO",
      "slug": "wordpress-seo",
      "required": false,
      "description": "Recommended from WordPress.org"
    }
  ]
}
```

### Example 5: Using GitLab

```json
{
  "plugins": [
    {
      "name": "GitLab Plugin",
      "slug": "gitlab-plugin",
      "url": "https://gitlab.com/username/plugin/-/archive/v1.0.0/plugin-v1.0.0.zip",
      "required": true,
      "version": "1.0.0"
    }
  ]
}
```

### Example 6: Private Server with Authentication

```json
{
  "plugins": [
    {
      "name": "Licensed Plugin",
      "slug": "licensed-plugin",
      "url": "https://license-server.com/download.php?product=plugin&key=YOUR_KEY",
      "required": true,
      "version": "3.0.0",
      "description": "Requires valid license key in URL"
    }
  ]
}
```

HOW IT WORKS
------------

### For Free Users (WordPress.org Plugin):
1. ConjureWP reads plugins.json from your theme
2. Displays plugin list in the wizard
3. Free users see "Install" links to WordPress.org
4. Users manually install and activate plugins
5. Return to wizard to continue

### For Premium Users (with ConjureWP Premium):
1. ConjureWP reads plugins.json from your theme
2. Displays plugin list with "Install All" button
3. Automatically installs bundled ZIPs or downloads from WordPress.org
4. Automatically activates plugins
5. Continues wizard automatically

COMBINING WITH DEMO-SPECIFIC PLUGINS
-------------------------------------

Theme-level plugins are automatically added to ALL demos.
You can still define demo-specific plugins that only apply to certain demos.

Example:
- Theme level: Contact Form 7 (all demos need it)
- Demo specific: WooCommerce (only shop demo needs it)

### Theme plugins.json:
```json
{
  "plugins": [
    {
      "name": "Contact Form 7",
      "slug": "contact-form-7",
      "mandatory": true
    }
  ]
}
```

### Demo configuration:
```php
function mytheme_import_files() {
    return array(
        array(
            'import_file_name' => 'Shop Demo',
            'required_plugins' => array(
                array( 'slug' => 'woocommerce', 'required' => true ),
            ),
        ),
    );
}
```

Result: Shop demo gets Contact Form 7 + WooCommerce
        Other demos get just Contact Form 7

FOLDER STRUCTURE EXAMPLE
-------------------------

```
/wp-content/themes/your-theme/
├── style.css
├── functions.php
├── conjurewp-plugins/              ← Create this folder
│   ├── plugins.json                ← Configuration file
│   ├── elementor-pro.zip           ← Premium plugin
│   ├── acf-pro.zip                 ← Premium plugin
│   └── mytheme-extensions.zip      ← Your custom plugin
└── demos/
    ├── main/
    │   └── content.xml
    └── shop/
        └── content.xml
```

VALIDATION
----------

ConjureWP automatically validates your plugins.json file.

Common errors:
- ❌ Missing "name" field → Plugin skipped
- ❌ Missing "slug" field → Plugin skipped
- ❌ Invalid JSON syntax → All plugins ignored
- ❌ Missing ZIP file (when "file" specified) → Plugin skipped

Check the ConjureWP log file for validation errors:
/wp-content/uploads/conjure-wp/main.log

BEST PRACTICES
--------------

### 1. Include Version Numbers
Helps with support and troubleshooting:
```json
"version": "3.16.0"
```

### 2. Add Descriptions
Explain why each plugin is needed:
```json
"description": "Required for contact forms throughout the theme"
```

### 3. Mark Dependencies as Required
If theme breaks without it, set required: true:
```json
"required": true
```

### 4. Keep Bundled Plugins Updated
Update ZIP files when plugins release security fixes

### 5. Test Without ConjureWP Premium
Ensure manual installation flow works for free users

LICENSING CONSIDERATIONS
------------------------

When bundling premium plugins with your theme:

✅ DO:
- Only bundle plugins you have rights to redistribute
- Check plugin licenses (GPL, split licenses, etc.)
- Include license information with theme documentation
- Provide license keys to your customers if required
- Keep bundled plugins updated

❌ DON'T:
- Bundle plugins without permission
- Violate plugin licensing terms
- Forget to provide activation keys (if plugin needs them)
- Bundle outdated versions with security issues

TESTING
-------

### Test Theme Plugin Bundling:

1. Create /conjurewp-plugins/ folder in theme
2. Add plugins.json file
3. Add any premium plugin ZIPs
4. Activate your theme
5. Run ConjureWP wizard
6. Check that plugins appear in plugin list
7. Test both free (manual) and premium (automatic) install flows

### Validation Command:

Use this code to validate your configuration:

```php
$result = Conjure_Theme_Plugins::validate_config( 
    get_template_directory() . '/conjurewp-plugins/plugins.json' 
);

if ( is_wp_error( $result ) ) {
    echo 'Error: ' . $result->get_error_message();
} else {
    echo 'Valid! Found ' . $result['plugins'] . ' plugins.';
}
```

TROUBLESHOOTING
---------------

### Plugins Not Appearing in Wizard

Check:
1. Folder name is exactly "conjurewp-plugins" (no typo)
2. Folder is in THEME directory, not plugin directory
3. JSON file is named exactly "plugins.json"
4. JSON syntax is valid (use a JSON validator)
5. Each plugin has required "name" and "slug" fields

### Bundled Plugin Not Installing

Check:
1. ZIP file exists in conjurewp-plugins folder
2. "file" field matches ZIP filename exactly
3. ZIP file is valid WordPress plugin package
4. Check ConjureWP logs for specific errors

### All Plugins Marked as Optional

Check:
1. "required" field is set to true (boolean, not string)
2. Correct: "required": true
3. Wrong: "required": "true"
4. Note: "mandatory" still works but "required" is preferred

SUPPORT
-------

Need help?
- Check ConjureWP logs: /wp-content/uploads/conjure-wp/main.log
- Visit: https://conjurewp.com/support/
- Or ask in the support forum

EXAMPLE THEMES
--------------

Want to see real examples?
Check out these themes using theme plugin bundling:
- [Coming soon - example theme links]

This feature was built to make theme onboarding seamless for your users!

