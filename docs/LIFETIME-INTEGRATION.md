# ConjureWP Lifetime Integration Guide

For theme developers who purchased lifetime ConjureWP integration. This allows your users to access all ConjureWP premium features without needing their own ConjureWP licence.

---

## What is Lifetime Integration?

Lifetime Integration is a one-time purchase option for theme developers that removes the ConjureWP licence requirement for your theme users. When activated, your customers get full access to all premium features automatically.

### Benefits

- ✅ **No user friction** - Users never see licence activation prompts
- ✅ **Premium features included** - All ConjureWP premium features work out of the box
- ✅ **Professional experience** - Seamless onboarding without extra licence purchases
- ✅ **Better conversions** - No additional steps means higher setup completion rates
- ✅ **Theme value** - Premium demo import included with your theme

---

## What Features Are Included?

When lifetime integration is active, your users automatically get:

| Feature | Description |
|---------|-------------|
| **Automatic Plugin Installation** | One-click plugin installation from WordPress.org and external sources |
| **Advanced Demo Importing** | Full demo content import with progress tracking |
| **Priority Support Features** | Enhanced logging and debugging capabilities |
| **Remote Updates** | Automatic ConjureWP plugin updates |
| **Multi-Demo Support** | Import multiple demo variations |
| **Redux/Options Import** | Theme options and Redux Framework settings import |
| **Widget Import** | Automatic widget area population |
| **Customiser Import** | WordPress Customiser settings import |

---

## Implementation Methods

Choose the method that best suits your workflow. All methods are equivalent in functionality.

### Method 1: Filter Hook (Recommended)

The simplest and most theme-friendly approach. Add this to your theme's `functions.php`:

```php
/**
 * Enable lifetime ConjureWP integration.
 * Users get full premium access without a licence key.
 */
add_filter( 'conjurewp_has_lifetime_integration', '__return_true' );
```

**Pros:**
- ✅ Simple one-line implementation
- ✅ Survives theme updates (when in child theme or functions.php)
- ✅ No server configuration needed
- ✅ Easy to remove/disable

---

### Method 2: Theme-Specific Filter with Validation

Use this if you need conditional logic or want to verify the theme:

```php
/**
 * Enable lifetime integration with custom logic.
 *
 * @param bool   $is_lifetime   Current lifetime integration status.
 * @param string $theme_slug    Current theme slug.
 * @param string $theme_name    Current theme name.
 * @return bool
 */
function mytheme_enable_conjurewp_lifetime( $is_lifetime, $theme_slug, $theme_name ) {
    // Grant lifetime access for this specific theme.
    if ( 'my-theme-slug' === $theme_slug ) {
        return true;
    }
    
    return $is_lifetime;
}
add_filter( 'conjurewp_has_lifetime_integration', 'mytheme_enable_conjurewp_lifetime', 10, 3 );
```

**Pros:**
- ✅ Validates theme slug matches registered theme
- ✅ Can add custom conditions
- ✅ Better security for theme-specific licensing

---

### Method 3: wp-config.php (Server Administrators)

Add to `wp-config.php` above the "That's all, stop editing!" line. Useful for server administrators managing multiple sites.

**Option A - Single Theme:**

```php
define( 'CONJUREWP_LIFETIME_THEMES', 'my-theme-slug' );
```

**Option B - Multiple Themes:**

```php
define( 'CONJUREWP_LIFETIME_THEMES', array(
    'theme-one-slug',
    'theme-two-slug',
    'theme-three-slug',
) );
```

**Pros:**
- ✅ Server-level configuration
- ✅ Works across all sites on multisite
- ✅ Single configuration point
- ✅ Theme-independent

**Cons:**
- ⚠️ Requires server access
- ⚠️ Not portable with theme files

---

## Verification & Testing

Use this debug function to verify lifetime integration is working correctly.

### Debug Code

Add this temporarily to your theme's `functions.php`:

```php
/**
 * Debug: Check if lifetime integration is active.
 * Visit any admin page to see the status.
 */
function debug_conjurewp_lifetime_status() {
    if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
        return;
    }
    
    if ( class_exists( 'Conjure_Freemius' ) ) {
        $has_lifetime = Conjure_Freemius::has_lifetime_integration();
        $has_access = Conjure_Freemius::has_free_access();
        
        error_log( 'ConjureWP Lifetime Integration Status:' );
        error_log( '  - Has Lifetime: ' . ( $has_lifetime ? 'YES' : 'NO' ) );
        error_log( '  - Has Access: ' . ( $has_access ? 'YES' : 'NO' ) );
        error_log( '  - Theme Slug: ' . get_stylesheet() );
        error_log( '  - Theme Name: ' . wp_get_theme()->get( 'Name' ) );
        
        // Show admin notice.
        add_action( 'admin_notices', function() use ( $has_lifetime, $has_access ) {
            $status = $has_lifetime ? '✅ Active' : '❌ Not Active';
            $access = $has_access ? '✅ Granted' : '❌ Denied';
            echo '<div class="notice notice-info"><p>';
            echo '<strong>ConjureWP Lifetime Integration:</strong> ' . esc_html( $status ) . '<br>';
            echo '<strong>Premium Access:</strong> ' . esc_html( $access );
            echo '</p></div>';
        });
    }
}
add_action( 'admin_init', 'debug_conjurewp_lifetime_status' );
```

### What to Look For

After adding the debug code:

1. Visit any admin page in WordPress
2. Check the admin notice at the top of the page
3. Check `wp-content/debug.log` for detailed status

**Expected Output:**

```
ConjureWP Lifetime Integration: ✅ Active
Premium Access: ✅ Granted
```

**If it's not working:**

```
ConjureWP Lifetime Integration: ❌ Not Active
Premium Access: ❌ Denied
```

---

## User Experience

### Without Lifetime Integration

1. User installs your theme
2. Activates theme → redirected to setup wizard
3. **Sees ConjureWP licence activation page** ⚠️
4. Must purchase/enter ConjureWP licence
5. Only then can use automatic plugin installation

### With Lifetime Integration

1. User installs your theme
2. Activates theme → redirected to setup wizard
3. **No licence page shown** ✅
4. Premium features work immediately
5. Seamless one-click plugin installation and demo import

---

## Combining with Theme Licences

ConjureWP's lifetime integration works independently of your theme's licensing system. You can combine both for advanced use cases.

### Example: Require Theme Licence for ConjureWP Access

If you want ConjureWP features only available to users with valid theme licences:

```php
/**
 * Grant ConjureWP access only if theme licence is valid.
 *
 * @param bool   $is_lifetime   Current lifetime integration status.
 * @param string $theme_slug    Current theme slug.
 * @param string $theme_name    Current theme name.
 * @return bool
 */
function mytheme_conditional_conjurewp_access( $is_lifetime, $theme_slug, $theme_name ) {
    // Check your theme's licence status.
    $theme_licence_status = get_option( 'my_theme_licence_status' );
    
    if ( 'valid' === $theme_licence_status ) {
        return true; // Grant ConjureWP access if theme licence is valid.
    }
    
    return $is_lifetime;
}
add_filter( 'conjurewp_has_lifetime_integration', 'mytheme_conditional_conjurewp_access', 10, 3 );
```

### Use Cases for Conditional Access

- **Premium theme tiers** - Only include ConjureWP in your "Pro" tier
- **Subscription themes** - Require active subscription for ConjureWP features
- **Upgrade incentive** - Use ConjureWP as a reason to upgrade to paid version
- **Licensed-only demos** - Restrict demo import to licensed customers

---

## Filter Reference

### `conjurewp_has_lifetime_integration`

Controls whether the current theme has lifetime ConjureWP integration.

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$is_lifetime` | `bool` | Current lifetime integration status (default: `false`) |
| `$theme_slug` | `string` | Current theme's stylesheet slug (e.g., `my-theme`) |
| `$theme_name` | `string` | Current theme's name (e.g., `My Theme`) |

**Returns:** `bool` - `true` to grant lifetime access, `false` otherwise

**Priority:** Default is `10`. Use higher priority to override other filters.

**Example - Theme-Specific:**

```php
add_filter( 'conjurewp_has_lifetime_integration', function( $is_lifetime, $theme_slug, $theme_name ) {
    $allowed_themes = array( 'genesis-pro', 'genesis-child', 'genesis-sample' );
    return in_array( $theme_slug, $allowed_themes, true ) ? true : $is_lifetime;
}, 10, 3 );
```

---

## Multisite Considerations

### Network-Wide Activation

If you're using WordPress Multisite and want lifetime integration across all sites:

**Option 1 - wp-config.php (Recommended for Multisite):**

```php
define( 'CONJUREWP_LIFETIME_THEMES', 'my-theme-slug' );
```

**Option 2 - Must-Use Plugin:**

Create `/wp-content/mu-plugins/conjurewp-lifetime.php`:

```php
<?php
/**
 * Plugin Name: ConjureWP Lifetime Integration
 * Description: Enables lifetime ConjureWP integration for specific themes.
 * Version: 1.0.0
 */

add_filter( 'conjurewp_has_lifetime_integration', function( $is_lifetime, $theme_slug ) {
    return $theme_slug === 'my-theme-slug' ? true : $is_lifetime;
}, 10, 2 );
```

### Per-Site Activation

Add the filter to each site's theme or use the `switch_to_blog()` function in a network plugin.

---

## Troubleshooting

### Issue: Lifetime Integration Not Working

**Symptoms:**
- Users still see licence activation prompts
- Premium features require licence key
- Debug shows "Not Active"

**Solutions:**

1. **Verify theme slug:**
   ```php
   echo get_stylesheet(); // Check this matches your registered slug
   ```

2. **Check filter priority:**
   - Make sure no other filter is overriding yours
   - Use priority `999` to ensure yours runs last

3. **Clear caches:**
   - Browser cache
   - WordPress object cache (`wp cache flush` in WP-CLI)
   - Server-side caching (Redis, Memcached, etc.)

4. **Verify ConjureWP version:**
   - Lifetime integration requires ConjureWP v1.0.0+
   - Update to latest version

5. **Check plugin activation:**
   - ConjureWP plugin must be active
   - Run the debug code above to verify class exists

### Issue: Works on One Site, Not Another

**Common causes:**

- **Different theme slugs** - Parent vs child theme
- **Cached results** - Clear all caches
- **Multisite** - Check if using network activation
- **Server config** - wp-config.php not loading

**Debug:**

```php
add_action( 'admin_notices', function() {
    echo '<div class="notice notice-warning"><p>';
    echo 'Theme Slug: <strong>' . get_stylesheet() . '</strong><br>';
    echo 'Theme Name: <strong>' . wp_get_theme()->get('Name') . '</strong>';
    echo '</p></div>';
});
```

### Issue: Features Still Locked

If lifetime integration shows "Active" but features are still locked:

1. Check if `conjurewp_has_free_access` filter is being overridden
2. Verify Freemius SDK is loaded correctly
3. Check for JavaScript errors in browser console
4. Test with default WordPress theme to rule out conflicts

---

## Migration Guide

### Migrating from Free to Lifetime Integration

If you're upgrading existing users from the free version to lifetime integration:

**Steps:**

1. Add the lifetime integration filter to your theme
2. Release theme update
3. Users update theme → lifetime integration activates automatically
4. No user action required ✅

### Migrating from Individual Licences to Lifetime

If users already have individual ConjureWP licences:

- Their existing licences will continue to work
- Lifetime integration will grant access even without their licence
- No data loss or migration needed
- Users can deactivate their personal licences if desired

---

## Best Practices

### For Theme Developers

✅ **DO:**
- Add lifetime integration filter in your theme's functions.php
- Document the feature in your theme's changelog/readme
- Test thoroughly before release
- Use the debug function during development
- Mention "Premium demo import included" in theme description

❌ **DON'T:**
- Don't hardcode theme slugs in plugin modifications
- Don't modify ConjureWP plugin files directly
- Don't promise features without verifying lifetime integration works
- Don't use this in free themes (violates purchase agreement)

### For Server Administrators

✅ **DO:**
- Use wp-config.php for server-wide configuration
- Document which themes have lifetime integration
- Keep a backup of wp-config.php modifications
- Test on staging before production

❌ **DON'T:**
- Don't add lifetime integration for themes you don't own
- Don't share wp-config.php constants publicly
- Don't grant lifetime access to pirated themes

---

## Pricing & Purchase

To purchase lifetime ConjureWP integration for your theme:

1. Visit [conjurewp.com/lifetime-integration](https://conjurewp.com/lifetime-integration)
2. Select your plan (single theme or unlimited themes)
3. Complete purchase
4. Register your theme slug(s)
5. Implement using methods above

### Plans Available

- **Single Theme** - Lifetime integration for one theme
- **Theme Shop** - Unlimited themes from your account/company
- **Agency** - Unlimited themes for your agency's clients

---

## Support

### Getting Help

If you purchased lifetime integration but need assistance:

1. **Documentation:** [conjurewp.com/docs/lifetime-integration](https://conjurewp.com/docs/lifetime-integration)
2. **Support Tickets:** [conjurewp.com/support](https://conjurewp.com/support)
3. **Email:** support@conjurewp.com

**Include in support requests:**
- Your order number
- Theme name and slug
- Debug output from verification code above
- Steps you've already tried

### Common Questions

**Q: Can I use lifetime integration in multiple themes?**
A: Depends on your plan. Single Theme = 1 theme. Theme Shop/Agency = unlimited.

**Q: Do I need to renew annually?**
A: No, lifetime integration is a one-time purchase with lifetime access.

**Q: What happens if I stop using ConjureWP?**
A: Your users keep access as long as the filter is in your theme. Remove the filter to disable.

**Q: Can users still buy individual ConjureWP licences?**
A: Yes, but unnecessary if your theme has lifetime integration.

**Q: Does this work with child themes?**
A: Yes, use the child theme's slug in your implementation.

**Q: Can I transfer lifetime integration to another theme?**
A: Contact support for theme transfers on single-theme plans.

---

## Changelog

### Version 1.0.0 (2024)
- Initial release of lifetime integration
- Support for filter hooks and wp-config.php constants
- Multi-theme support via array
- Theme-specific validation
- Debug/verification tools

---

## License & Terms

Lifetime integration is subject to the ConjureWP Terms of Service:
- Valid for legitimate theme developers only
- Not for redistribution or resale
- Theme must be legally developed and distributed
- ConjureWP reserves the right to revoke access for Terms violations

---

**Need help?** Open a support ticket at [conjurewp.com/support](https://conjurewp.com/support)

**Have feedback?** Let us know how we can improve this documentation.


