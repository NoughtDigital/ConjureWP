=== ConjureWP - WordPress Setup Wizard ===
Contributors: noughtdigital
Tags: demo import, theme setup, content import, onboarding, setup wizard
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
Licence: GPLv3 or later
Licence URI: https://www.gnu.org/licenses/gpl-3.0.html

A powerful WordPress onboarding wizard that helps users set up themes, install plugins, import content, and more.

== Description ==

ConjureWP is a comprehensive WordPress setup wizard that streamlines the theme installation process. Perfect for theme developers who want to provide their users with an effortless onboarding experience.

= All Features (100% Free) =

* **Setup Wizard** - Step-by-step guided installation with progress tracking and visual feedback
* **Child Theme Generator** - Automatically creates child themes for safe customisation
* **Automatic Plugin Installer** - Built-in installer for WordPress.org and custom plugins with one-click installation
* **Content Import** - Import demo pages, posts, categories, tags, and media with self-contained importer
* **Widget Import** - Automatically configure widgets and sidebars
* **Customiser Import** - Apply theme settings and customisations
* **Revolution Slider Import** - Import slider configurations for advanced demo replication
* **Redux Framework Import** - Import theme option panel settings
* **Demo Auto-Discovery** - Automatically detects and registers demo content from theme directory
* **Demo-Specific Plugins** - Show only relevant plugins for each demo variation
* **Theme Bundled Plugins** - Auto-merge plugins from theme's `/conjurewp-plugins/` folder
* **Server Health Monitoring** - Real-time checks for PHP memory, execution time, and MySQL version
* **Comprehensive Logging** - Detailed logs with rotation, filtering, and admin viewer
* **Log Management** - View, download, and clear logs from WordPress admin
* **WP-CLI Support** - Full command-line tools for automated deployments and CI/CD pipelines
* **REST API** - HTTP endpoints for hosting dashboards and remote automation
* **Admin Bar Tools** - Quick access to wizard and reset controls
* **Security Hardened** - Nonce verification, capability checks, and protected directories
* **Update-Safe Storage** - Store demos outside plugin directory to prevent loss during updates

**All features are completely free with no premium upsells or limitations.**

= For Theme Developers =

ConjureWP provides powerful tools for theme developers:

* **Demo Auto-Discovery** - Automatically detects demo content in your theme directory (zero configuration)
* **Multiple Demo Support** - Support unlimited demo variations with dropdown selection
* **Theme Bundled Plugins** - Include premium plugins in theme's `/conjurewp-plugins/` folder
* **Demo-Specific Plugins** - Different plugin requirements for each demo
* **Update-Safe Storage** - Store demos in theme directory to survive plugin updates
* **Custom Preview Images** - Visual demo selection with preview images
* **Extensive Hooks** - Over 50 filters and actions for customisation
* **WP-CLI Integration** - Automate demo imports for hosting control panels
* **REST API** - HTTP endpoints for hosting dashboards
* **Server Health API** - Customisable server requirement checks
* **Redirect Control** - Control post-wizard redirect behaviour
* **Configuration Examples** - Comprehensive examples in `/examples/` directory
* **Premium Features Helper** - Helper class for gating custom theme features

= Documentation =

Visit [conjurewp.com](https://conjurewp.com/) for complete documentation, examples, and integration guides.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/conjurewp/`, or install directly through the WordPress plugins screen
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to the setup wizard from the admin menu or dashboard notice
4. Follow the step-by-step wizard to configure your site

For theme developers: see the included examples folder and documentation for integration instructions.

== Frequently Asked Questions ==

= Do I need to configure anything before running the wizard? =

No, the wizard works out of the box for end users. However, theme developers should configure demo content using the provided filters. See the `/examples/` folder for integration code and `docs/` for comprehensive guides.

= Where can I store demo content? =

Demo content can be stored in multiple locations (checked in this priority order):
1. Custom path via `CONJUREWP_DEMO_PATH` constant in wp-config.php
2. Theme directory: `/wp-content/themes/your-theme/conjurewp-demos/`
3. Uploads directory: `/wp-content/uploads/conjurewp-demos/`
4. Plugin directory: `/demo/` (examples only, not recommended for production)

For production, always use options 1 or 2 to prevent loss during plugin updates.

= How do I reset the wizard and run it again? =

Use the "Reset Setup Wizard" option in the WordPress admin bar (ConjureWP Reset menu). This will:
* Delete the child theme (if created by ConjureWP)
* Clear all wizard progress
* Allow you to run the wizard again

For granular control (reset individual steps), enable developer tools: `define( 'CONJURE_TOOLS_ENABLED', true );` in wp-config.php

= Does this work with page builders? =

Yes! ConjureWP works with all page builders:
* Elementor / Elementor Pro
* Beaver Builder
* WPBakery Page Builder
* Divi Builder
* Gutenberg (Block Editor)
* Any other page builder

ConjureWP includes Revolution Slider import support for advanced demos.

= Can I use this with WP-CLI? =

Yes! ConjureWP includes full WP-CLI support:
* `wp conjure list` - List available demos
* `wp conjure import --demo=0` - Import demo by index
* `wp conjure import --demo=slug` - Import demo by slug
* Skip options: `--skip-content`, `--skip-widgets`, `--skip-options`, etc.

Perfect for CI/CD pipelines, hosting automation, and bulk site deployments. See WP-CLI.md for complete documentation.

= Can I use this with hosting control panels? =

Yes! ConjureWP provides a REST API for hosting dashboards:
* `GET /wp-json/conjurewp/v1/demos` - List available demos
* `POST /wp-json/conjurewp/v1/import` - Trigger demo import

Requires administrator authentication (`manage_options` capability). Perfect for hosting control panels without shell access.

= Is everything really free? =

Yes! ConjureWP is 100% free and open source with no premium version or upsells. All features are included:
* Setup wizard with progress tracking
* Child theme generator
* Automatic plugin installation (one-click)
* Demo content, widgets, and customiser import
* Revolution Slider import
* Redux Framework import
* WP-CLI commands
* REST API
* Server health monitoring
* Comprehensive logging

The plugin includes a `Conjure_Premium_Features` helper class for theme developers who wish to gate their own custom features, but all ConjureWP features are completely free.

= How do I view logs? =

Three ways to access logs:
1. **Admin viewer:** Go to Tools → ConjureWP Logs in WordPress admin
2. **Direct access:** SSH/FTP to `/wp-content/uploads/conjure-wp/main.log`
3. **Download:** Use the download button in admin viewer

Logs include automatic rotation (keeps 5 files, 10MB each) and are protected from direct web access.

= Can I bundle plugins with my theme? =

Yes! Create a `/conjurewp-plugins/` folder in your theme with:
* `plugins.json` configuration file
* Plugin ZIP files

ConjureWP will automatically merge these with demo-specific plugins. See `/examples/theme-bundled-plugins/` for complete documentation.

= How do I create multiple demos? =

Create subfolders in your theme's `/conjurewp-demos/` directory:
* `/conjurewp-demos/business/` - content.xml, widgets.json, etc.
* `/conjurewp-demos/portfolio/` - content.xml, widgets.json, etc.

Enable auto-discovery: `define( 'CONJUREWP_AUTO_REGISTER_DEMOS', true );` in wp-config.php

Each demo can have different plugin requirements, preview images, and content.

= Is this secure? =

Yes! ConjureWP includes multiple security measures:
* Nonce verification on all actions
* Capability checks (`manage_options` required)
* Protected log directories (`.htaccess` and `index.php`)
* Path traversal prevention
* Sanitised inputs and escaped outputs
* No external dependencies or API calls (free version)

== Screenshots ==

1. Welcome screen - Start the setup wizard
2. Child theme creation - Customise your theme safely
3. Plugin installation - View and install required plugins
4. Demo content selection - Choose from available demos
5. Import progress - Real-time import status
6. Setup complete - Your site is ready!

== Changelog ==

= 1.0.0 =
**Initial Release**

*Core Features:*
* Setup wizard with progress tracking and visual feedback
* Child theme generator with automatic activation
* Self-contained demo content importer (no external dependencies)
* Widget and customiser import with validation
* Built-in plugin installer supporting WordPress.org and custom sources
* Demo-specific plugin dependencies (different plugins per demo)
* Theme bundled plugins system (auto-merge from `/conjurewp-plugins/`)
* Auto-discovery of demo content from theme directory
* Multiple demo support with preview images
* Update-safe storage locations

*Automation & APIs:*
* Full WP-CLI command suite for automated deployments
* REST API endpoints for hosting dashboards
* Server health monitoring with customisable requirements
* Comprehensive logging with rotation and filtering

*Admin Tools:*
* Log viewer with download and clear functions
* Admin bar shortcuts to wizard and reset
* Developer tools for granular step resets

*All Features Included:*
* Automatic plugin installation (one-click)
* Revolution Slider import
* Redux Framework import
* Premium features helper class for theme developers

*Security & Performance:*
* Nonce verification on all actions
* Capability checks throughout
* Protected log directories
* Path traversal prevention
* Automatic log rotation
* Efficient caching and transients

*Developer Tools:*
* 50+ filters and actions for customisation
* Extensive examples in `/examples/` directory
* Comprehensive documentation in `/docs/`
* Theme integration guides
* Redirect control system

== Upgrade Notice ==

= 1.0.0 =
Initial release of ConjureWP - WordPress Setup Wizard. Streamline your theme installation process with our powerful onboarding wizard.

