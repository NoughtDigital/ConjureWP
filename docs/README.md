# ConjureWP Documentation

Comprehensive guides and documentation for developers using ConjureWP.

---

## Available Documentation

### 📘 [Licence Activation Guide](LICENCE-ACTIVATION.md)

**For:** End users and theme developers

Understanding ConjureWP licensing and how it differs from theme licensing.

**Topics Covered:**
- What requires a ConjureWP licence
- Free vs premium features
- Clearing up licensing confusion
- Activation methods
- Managing and troubleshooting licences
- Theme vs plugin licensing explained

**Quick Reference:**
- ✅ **Free features:** Child themes, manual import, widgets, customiser
- 💎 **Premium features:** Auto plugin install, advanced importing, priority support

---

### 📗 [Lifetime Integration Guide](LIFETIME-INTEGRATION.md)

**For:** Theme developers who purchased lifetime ConjureWP integration

Complete guide to implementing lifetime integration in your theme. Allows your users to access all ConjureWP premium features without needing their own licence.

**Topics Covered:**
- What is lifetime integration and why use it
- Three implementation methods (filter hooks, wp-config.php)
- Verification and testing procedures
- Combining with theme licensing systems
- Multisite considerations
- Troubleshooting common issues
- Filter reference and best practices

**Quick Start:**
```php
// Add to your theme's functions.php
add_filter( 'conjurewp_has_lifetime_integration', '__return_true' );
```

---

### 🔌 [Plugin Configuration Guide](PLUGIN-CONFIGURATION.md)

**For:** Theme developers setting up demo imports

Complete guide to configuring plugins for your theme's demo import process.

**Topics Covered:**
- WordPress.org plugins configuration
- Custom/premium plugin integration
- Demo-specific plugin dependencies
- Required vs recommended plugins
- Advanced filtering and dynamic configuration
- Troubleshooting plugin installation issues
- Complete examples for all scenarios

**Quick Start:**
```php
// Add to your demo configuration
'required_plugins' => array(
    array( 'slug' => 'contact-form-7', 'required' => true ),  // WordPress.org
    array(
        'name'   => 'Elementor Pro',
        'slug'   => 'elementor-pro',
        'source' => get_template_directory() . '/plugins/elementor-pro.zip',  // Custom
        'required' => true,
    ),
),
```

---

## More Resources

### Code Examples
See the `/examples/` directory for practical implementation examples:
- Theme integration examples
- CLI automation scripts
- Server health usage
- Demo content setup
- Custom configuration

### Plugin Features
- ✅ One-click demo import
- ✅ Automatic plugin installation
- ✅ Child theme creation
- ✅ Widget & customiser import
- ✅ Redux Framework support
- ✅ WP-CLI support
- ✅ Multisite compatible

---

## Getting Help

### Support Channels

**Documentation:** [conjurewp.com/docs](https://conjurewp.com/docs)  
**Support Tickets:** [conjurewp.com/support](https://conjurewp.com/support)  
**GitHub Issues:** [github.com/NoughtDigital/ConjureWP/issues](https://github.com/NoughtDigital/ConjureWP/issues)  
**Email:** support@conjurewp.com

### Before Requesting Support

1. Check the documentation in `/docs/` and `/examples/`
2. Search existing GitHub issues
3. Test with default WordPress theme to rule out conflicts
4. Enable debug logging and check logs
5. Gather error messages and steps to reproduce

---

## Contributing

We welcome contributions to the documentation!

**To contribute:**
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

**Documentation Guidelines:**
- Use clear, concise English (UK spelling)
- Include code examples where relevant
- Test all code examples before submitting
- Follow existing formatting conventions
- Add your changes to this README

---

## Licence

ConjureWP is licenced under GPL-3.0 for open-source use.

Documentation is © 2018-2024 ConjureWP / Inventionn LLC.

