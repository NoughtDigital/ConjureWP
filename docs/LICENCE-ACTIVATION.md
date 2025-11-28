# ConjureWP Licence Activation Guide

Understanding ConjureWP licensing, activation, and what features require a licence.

---

## Understanding the Licence

ConjureWP has **two separate licensing systems** that work independently:

### 1. ConjureWP Plugin Licence (This Document)

Controls access to ConjureWP premium features like automatic plugin installation and advanced importing.

### 2. Theme Licence (Separate)

Your theme may have its own licensing system (EDD, Freemius, etc.). This is completely separate from ConjureWP.

---

## What Requires a Licence?

### Premium Features (ConjureWP Licence Required)

| Feature | Description | Free Version |
|---------|-------------|--------------|
| **Automatic Plugin Installation** | One-click install from WordPress.org and external sources | ❌ Manual only |
| **Advanced Demo Importing** | Full demo content import with progress tracking | ❌ Upload only |
| **Priority Support** | Enhanced support and documentation | ❌ Community support |
| **Remote Updates** | Automatic ConjureWP plugin updates | ❌ Manual updates |
| **Multi-Demo Support** | Import multiple demo variations | ❌ Limited |

### Free Features (No Licence Required)

| Feature | Description |
|---------|-------------|
| **Basic Setup Wizard** | Step-by-step theme setup |
| **Child Theme Creation** | Generate and activate child themes |
| **Manual Content Import** | Upload .xml files directly |
| **Customiser Import** | Import WordPress Customiser settings |
| **Widget Import** | Import widget configurations |
| **WP-CLI Support** | Command-line interface for automation |

---

## When You'll See the Licence Prompt

### Activation Wizard (Freemius)

When you first install ConjureWP, you may see:

**"Enter Your ConjureWP Licence Key"**

This is asking for your **ConjureWP licence** to unlock premium features.

**Options:**
- **Enter Licence Key** - Unlock premium features immediately
- **Continue with Free Version** - Use free features only, upgrade later

### Setup Wizard Licence Step

During theme setup, you may see a licence step with:

**"Enter Your ConjureWP Licence Key"**

This step can be:
- **Skipped** - Continue with free features
- **Completed** - Enter licence to unlock premium features

**Note:** If your theme developer purchased lifetime integration, you won't see this step at all!

---

## Clearing Up Confusion

### ❓ "Activate Twenty Twenty-Five" - What is this?

**Old behaviour (before v1.0.0):**
The Freemius SDK used to show the active theme name in the activation wizard, making it extremely confusing.

**New behaviour (v1.0.0+):**
Now clearly shows: **"Enter Your ConjureWP Licence Key"**

### ❓ Is this asking for my theme licence?

**No.** This is asking for your **ConjureWP licence**.

- Theme licences are handled separately by your theme
- ConjureWP and theme licences are independent
- You may need both, one, or neither depending on your setup

### ❓ Do I need a ConjureWP licence if I have a theme licence?

**It depends:**

- **Theme with lifetime integration:** No ConjureWP licence needed ✅
- **Open-source theme:** No ConjureWP licence needed ✅
- **Premium theme without lifetime integration:** Yes, if you want premium features ⚠️

### ❓ Can I use ConjureWP without any licence?

**Yes!** The free version includes:
- Child theme creation
- Manual content import (upload files)
- Widget and customiser import
- WP-CLI commands

---

## Activation Methods

### Method 1: During First Install (Recommended)

1. Install and activate ConjureWP
2. You'll see: **"Enter Your ConjureWP Licence Key"**
3. Enter your licence key
4. Click **"Activate"**
5. Premium features are now unlocked ✅

### Method 2: During Theme Setup

1. Start theme setup wizard
2. On the licence step, enter your ConjureWP licence
3. Continue through wizard
4. Premium features available immediately

### Method 3: Skip and Activate Later

1. Click **"Continue with Free Version"** or **"Later"**
2. Complete theme setup with free features
3. Purchase licence later
4. Return to wizard to activate
5. Premium features unlock retroactively

### Method 4: Lifetime Integration (Theme Developers)

If your theme developer purchased lifetime integration:
- No licence activation required ✅
- Premium features work automatically
- No prompts or steps shown

See [LIFETIME-INTEGRATION.md](LIFETIME-INTEGRATION.md) for developer documentation.

---

## Purchasing a Licence

### Individual Users

Visit [conjurewp.com/pricing](https://conjurewp.com/pricing) to purchase:

- **Personal** - Single site
- **Professional** - Up to 5 sites
- **Agency** - Unlimited sites

### Theme Developers

Consider purchasing **Lifetime Integration** instead:
- One-time payment
- All your users get premium features
- No per-user licensing
- Better user experience

See [LIFETIME-INTEGRATION.md](LIFETIME-INTEGRATION.md) for details.

---

## Managing Your Licence

### Check Licence Status

Add this temporarily to your `functions.php`:

```php
add_action( 'admin_notices', function() {
    if ( class_exists( 'Conjure_Freemius' ) && function_exists( 'con_fs' ) ) {
        $fs = con_fs();
        $has_access = Conjure_Freemius::has_free_access();
        
        echo '<div class="notice notice-info"><p>';
        echo '<strong>ConjureWP Status:</strong> ';
        
        if ( $has_access ) {
            echo '✅ Premium Access Active';
        } else {
            echo '⚠️ Free Version Only';
        }
        
        echo '</p></div>';
    }
});
```

### Deactivate Licence

To move your licence to another site:

1. Go to WordPress Admin → Plugins
2. Find ConjureWP in the list
3. Hover and click **"Account"** (if available)
4. Click **"Deactivate Licence"**
5. Activate on new site

### Upgrade Plan

To upgrade from Personal to Professional/Agency:

1. Visit [conjurewp.com/account](https://conjurewp.com/account)
2. Log in with your email
3. Click **"Upgrade"** next to your licence
4. Pay the difference
5. No need to reactivate - upgrades automatically

---

## Troubleshooting

### Issue: Licence Activation Fails

**Symptoms:**
- Error message when entering licence key
- "Invalid licence" message
- Activation button doesn't work

**Solutions:**

1. **Verify licence key is correct:**
   - Check for extra spaces
   - Ensure you copied the full key
   - Check it's not expired

2. **Check site URL matches:**
   - Licence is tied to your domain
   - HTTP vs HTTPS matters
   - www vs non-www matters

3. **Deactivate on old site:**
   - You may have reached activation limit
   - Deactivate on unused sites first

4. **Contact support:**
   - Email your licence key (safe to share)
   - Include error messages
   - Mention steps you've tried

### Issue: Features Still Locked After Activation

**Symptoms:**
- Licence activated successfully
- But premium features don't work
- Still shows "upgrade" prompts

**Solutions:**

1. **Clear all caches:**
   ```php
   wp_cache_flush(); // If using WP-CLI
   ```

2. **Check licence status:**
   - Use the debug code above
   - Verify it shows "Premium Access Active"

3. **Check for conflicts:**
   - Disable other licence management plugins temporarily
   - Test with default WordPress theme
   - Check JavaScript console for errors

4. **Verify ConjureWP version:**
   - Must be v1.0.0+
   - Update to latest version

### Issue: Confused About Theme vs Plugin Licence

**Quick check:**

```php
// Add to functions.php temporarily
add_action( 'admin_notices', function() {
    echo '<div class="notice notice-info"><p>';
    
    // Check theme licence (example for EDD)
    $theme_licence = get_option( 'your_theme_licence_status' ); // Replace with actual option
    echo 'Theme Licence: ' . ( $theme_licence === 'valid' ? '✅ Active' : '❌ Inactive' ) . '<br>';
    
    // Check ConjureWP licence
    if ( class_exists( 'Conjure_Freemius' ) ) {
        $has_access = Conjure_Freemius::has_free_access();
        echo 'ConjureWP Access: ' . ( $has_access ? '✅ Active' : '❌ Inactive' );
    }
    
    echo '</p></div>';
});
```

This shows both licences separately.

---

## For Theme Developers

If you're a theme developer and users are confused about licensing:

### Option 1: Purchase Lifetime Integration

Best solution - your users never see ConjureWP licence prompts.

See [LIFETIME-INTEGRATION.md](LIFETIME-INTEGRATION.md)

### Option 2: Document Clearly

Add to your theme documentation:

> **ConjureWP Licensing**
> 
> Our theme uses ConjureWP for demo importing. You have two options:
> 
> 1. **Free Version:** Manual content upload, child theme creation
> 2. **Premium Version:** Automatic plugin installation, advanced importing
> 
> ConjureWP licensing is separate from theme licensing. You'll need both:
> - A valid theme licence (to use our theme)
> - A ConjureWP licence (for premium import features, optional)

### Option 3: Add Support Article

Create a support article titled:
**"Understanding ConjureWP Activation During Theme Setup"**

Link to this document or copy relevant sections.

---

## Common Questions

**Q: Do I need to renew annually?**
A: Depends on your plan. Check your purchase confirmation email.

**Q: Can I use my licence on multiple sites?**
A: Depends on your plan (Personal=1, Professional=5, Agency=unlimited).

**Q: What happens if my licence expires?**
A: Premium features stop working, but imported content remains. Free features continue working.

**Q: Can I downgrade to free version?**
A: Yes, just don't renew. Your data is safe, premium features become locked.

**Q: Is there a trial?**
A: The free version is unlimited. Try it before purchasing premium.

**Q: Can I get a refund?**
A: Check the refund policy at [conjurewp.com/refund-policy](https://conjurewp.com/refund-policy)

---

## Support

### Getting Help

**Documentation:** [conjurewp.com/docs](https://conjurewp.com/docs)  
**Support Tickets:** [conjurewp.com/support](https://conjurewp.com/support)  
**Account Management:** [conjurewp.com/account](https://conjurewp.com/account)  
**Email:** support@conjurewp.com

### Before Contacting Support

Please have ready:
- Your licence key (safe to share)
- Website URL where ConjureWP is installed
- Error messages or screenshots
- Steps you've already tried

---

**Last Updated:** November 2024  
**Version:** 1.0.0+

Need more help? Visit [conjurewp.com/support](https://conjurewp.com/support)


