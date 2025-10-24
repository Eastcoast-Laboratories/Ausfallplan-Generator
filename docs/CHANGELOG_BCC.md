# BCC Feature Changelog

## 2025-10-24 - BCC Configuration Update

### ✅ Changes Made

1. **Configurable BCC Email**
   - BCC email address is now configurable via environment variable
   - Variable: `SYSADMIN_BCC_EMAIL`
   - Default: `ausfallplan-sysadmin@it.z11.de`

2. **Optional Feature**
   - Can be **disabled** by setting empty value: `SYSADMIN_BCC_EMAIL=`
   - BCC is only added if email address is configured

3. **Production First**
   - Feature works on **production** (not just localhost)
   - Real emails sent via SMTP include BCC
   - Localhost still stores in session for debugging

4. **Files Modified**
   - `src/Service/EmailDebugService.php`
     - Added `getSysadminEmail()` method
     - Made BCC conditional based on configuration
     - Added env() import
   
   - `docs/EMAIL_BCC.md`
     - Updated documentation
     - Added configuration examples
     - Added testing instructions

5. **Production Configuration**
   - Added to `.env` on production server
   - Value: `ausfallplan-sysadmin@it.z11.de`

### 📧 How It Works

```php
// Get BCC email from environment (or default)
$email = env('SYSADMIN_BCC_EMAIL', 'ausfallplan-sysadmin@it.z11.de');

// Only add BCC if configured (not empty)
if (!empty($email)) {
    $mailer->setBcc($email);
}
```

### 🔧 Configuration Options

**Enable (Default):**
```bash
SYSADMIN_BCC_EMAIL=ausfallplan-sysadmin@it.z11.de
```

**Disable:**
```bash
SYSADMIN_BCC_EMAIL=
```

**Change Email:**
```bash
SYSADMIN_BCC_EMAIL=admin@example.com
```

### ✅ Affected Emails

All emails sent through `EmailDebugService::send()`:
- User registration verification emails
- Password reset emails
- Any future emails added to the system

### 🧪 Testing

```bash
# Run test script
php tests/test-email-bcc.php

# On production - register new user and sysadmin will receive BCC
# Or request password reset
```

### 📝 Migration Notes

**For existing installations:**
1. Add `SYSADMIN_BCC_EMAIL` to your `.env` file
2. Set to desired email or leave empty to disable
3. Clear cache if needed: `rm -rf tmp/cache/*`

**No code changes needed** - existing email sending code continues to work!
