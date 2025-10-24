# Email BCC Configuration

## Overview
All emails sent by the application can include a BCC (blind carbon copy) to the system administrator. This feature is **configurable** and can be disabled.

## Configuration

### Environment Variable
Set in your `.env` file:

```bash
# Enable BCC to sysadmin (default)
SYSADMIN_BCC_EMAIL=ausfallplan-sysadmin@it.z11.de

# Disable BCC by setting empty value
SYSADMIN_BCC_EMAIL=
```

**Default:** `ausfallplan-sysadmin@it.z11.de`  
**To disable:** Set to empty string

## Implementation
The BCC functionality is implemented in `src/Service/EmailDebugService.php`:

```php
private static function getSysadminEmail(): ?string
{
    $email = env('SYSADMIN_BCC_EMAIL', 'ausfallplan-sysadmin@it.z11.de');
    return !empty($email) ? $email : null;
}

// In sendRealEmail():
$sysadminEmail = self::getSysadminEmail();
if ($sysadminEmail) {
    $mailer->setBcc($sysadminEmail);
}
```

## Affected Emails
All emails sent through `EmailDebugService::send()` include the BCC:

1. **User Registration**
   - Verification email to new users
   - Location: `UsersController::register()`
   
2. **Password Reset**
   - Password reset code email
   - Location: `UsersController::forgotPassword()`

3. **Future Emails**
   - Any new email using `EmailDebugService::send()` will automatically include BCC

## Environments

### Localhost (Development)
- Emails are **stored in session** for debug display
- View at: `/debug/emails`
- No actual email sending
- Can optionally send real emails by setting `Email.alsoSendOnLocalhost` in config

### Production
- Emails are **sent via SMTP**
- BCC is added if `SYSADMIN_BCC_EMAIL` is configured
- Real email delivery to recipients

## Testing

### Test Script
Run: `php tests/test-email-bcc.php`

### Manual Testing

**On Production:**
```bash
# With BCC enabled
SYSADMIN_BCC_EMAIL=ausfallplan-sysadmin@it.z11.de

# Test - sysadmin will receive BCC
# Register a new user or request password reset
```

**Disable BCC:**
```bash
# Set empty value in .env
SYSADMIN_BCC_EMAIL=

# Or remove the line completely (uses default)
```

## Notes
- ✅ **BCC works on PRODUCTION** (not just localhost)
- ✅ **Configurable** - Can be disabled by setting empty value
- ✅ **Automatic** - No changes needed in email sending code
- ✅ All future emails should use `EmailDebugService::send()` to ensure BCC
- ✅ Sysadmin receives copy of ALL emails for monitoring and auditing (when enabled)
