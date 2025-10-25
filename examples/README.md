# ConjureWP Examples

This directory contains example files, sample configurations, and testing scripts for developers.

## Files

### Integration Examples

**theme-integration.php**
Complete theme integration example showing all available features including Redux, Revolution Slider, and post-import setup.

**demo-theme-integration.php**
Quick demo setup using the plugin's included demo files for testing and development.

**theme-redirect-integration.php**
Theme-level integration for controlling automatic redirect behavior when theme is activated. Perfect for adding custom welcome pages before demo import.

**redirect-control-examples.php**
Comprehensive examples showing how to customize redirect behavior using filter hooks in your theme.

**cli-integration.sh**
Example bash script showing how to use WP-CLI commands for automated imports in CI/CD pipelines.

**server-health-usage.php**
Example of how to use the server health check functionality to verify server requirements before import.

### Sample Configuration Files

**conjure-filters-sample.php**
Complete reference showing all available filters and hooks with examples.

**conjure-config-sample.php**
Legacy configuration file (for theme-based integration, old MerlinWP style).

**conjure-logging-config-sample.php**
Reference documentation for all logging configuration options.

### Testing Scripts

**verify-setup.sh**
Verifies development environment is properly configured. Checks PHP, Node.js, Composer, and npm versions.

**test-cli-import.sh**
Test script for CLI import functionality. Use this to test WP-CLI commands during development.

**test-import-setup.php**
Generic test setup for theme-based demo imports. Shows examples of local and remote imports.

**validate-import-setup.php**
Validation script to check if import configuration is correct and all required files exist.

## Quick Reference (Root Directory)

**wp-config-demo-example.php** - wp-config.php constants for custom demo paths (kept in root for visibility)

## Demo Content Files

The `/demo/` directory contains sample demo files for testing:

-   `content.xml` - WordPress export with sample pages and posts
-   `widgets.json` - Sample widget configuration
-   `customizer.dat` - Sample customiser settings
-   `redux-options.json` - Sample Redux Framework options

**Important:** These demo files in `/demo/` are for testing only and will be overwritten during plugin updates. For production, use the wp-config.php method or store demos in your theme directory.

## Usage

All files in this directory are for reference only and are not loaded by the plugin. Copy and adapt them for your specific needs.

### Getting Started

1. **For basic theme integration**: Start with `theme-integration.php`
2. **For quick testing**: Use `demo-theme-integration.php` with the included `/demo/` files
3. **For custom redirect/welcome pages**: See `theme-redirect-integration.php`
4. **For custom demo paths**: See `wp-config-demo-example.php` in the root
5. **For all available hooks**: Reference `conjure-filters-sample.php`

For complete documentation, see the main [README.md](../README.md) in the plugin root.
