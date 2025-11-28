# Email Duplicate Issue - Debugging Guide

## Problem

User reports receiving **duplicate** verification emails when registering.

## Possible Causes

### 1. **Double Form Submission**
- User clicks "Register" button twice
- Browser back/forward causing re-submission
- Form validation failing, then succeeding

**Check:**
- Look for duplicate entries in `users` table
- Check server logs for duplicate POST requests

### 2. **Email Debug Storage vs Actual Send**
- Email stored ONCE in debug log
- Email sent ONCE via SMTP
- User might be seeing both (debug view + actual email)

**This is NOT a duplicate** - just two views of same email!

### 3. **BCC + Direct Email Confusion**
- User verification email has BCC to sysadmin
- Sysadmin notification email sent directly
- Sysadmin sees 2 emails (one BCC, one direct) - **This is expected!**

**This is NOT a bug** - sysadmin should get both!

### 4. **CakePHP Mailer deliver() Called Twice**
- Unlikely but possible if EmailDebugService::send() called twice

## Debugging Steps

### Step 1: Check Logs

```bash
# On production server
tail -f /var/log/nginx/fairnestplan.de-error | grep EmailDebugService

# Look for:
[EmailDebugService] Sent email to user@example.com: Verify your email address
```

**Expected:** ONE line per registration  
**Bug:** TWO lines with same timestamp

### Step 2: Check User Table

```sql
SELECT id, email, created FROM users ORDER BY created DESC LIMIT 5;
```

**Expected:** ONE user entry per registration  
**Bug:** TWO entries with same email (duplicate registration)

### Step 3: Test Locally

1. Register new user
2. Check `/debug/emails` page
3. Count verification emails

**Expected:** ONE verification email  
**Bug:** TWO verification emails in debug viewer

### Step 4: Check Email Provider

If using Gmail/similar:
- Check "Sent" folder
- Look for duplicate sends with exact same timestamp

## Current Implementation

### User Registration Email Flow:

```php
// UsersController::register()

// 1. User verification email (with BCC to sysadmin)
EmailDebugService::send([
    'to' => $user->email,
    'subject' => 'Verify your email address',
    // BCC: ausfallplan-sysadmin@it.z11.de (automatic)
]);

// 2. Sysadmin notification email (direct, not BCC)
$this->notifySysadminAboutNewUser($user, $organization, $role, $isNewOrganization);
```

### Sysadmin Receives:
1. **BCC** of user verification email
2. **Direct** notification email about new registration

**Both are expected!** This is not a duplicate - these are two different emails.

## Solutions

### If Double Form Submission:

```javascript
// Add to registration form
let submitting = false;
form.addEventListener('submit', function(e) {
    if (submitting) {
        e.preventDefault();
        return false;
    }
    submitting = true;
    setTimeout(() => submitting = false, 3000);
});
```

### If CakePHP Mailer Issue:

```php
// Add guard in EmailDebugService::send()
private static $sentEmails = [];

public static function send(array $email): bool
{
    $hash = md5($email['to'] . $email['subject'] . ($email['body'] ?? ''));
    
    if (isset(self::$sentEmails[$hash])) {
        error_log('[EmailDebugService] Preventing duplicate send: ' . $hash);
        return true; // Already sent
    }
    
    self::$sentEmails[$hash] = true;
    
    // ... rest of method
}
```

## Test Results

**Date:** 2025-11-28  
**Tester:** _____  
**Result:** _____

- [ ] Checked logs - found duplicates?
- [ ] Checked database - found duplicate users?
- [ ] Tested locally - reproduced issue?
- [ ] Checked email provider - found duplicates?

**Conclusion:** _____________________
