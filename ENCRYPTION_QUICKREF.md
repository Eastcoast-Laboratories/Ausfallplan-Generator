# Client-Side Encryption - Quick Reference

## 🚀 Quick Start

### For Frontend Developers

#### 1. Include the Module
```html
<script src="/js/crypto/orgEncryption.js"></script>
```

#### 2. Registration (Generate Keys)
```javascript
// Generate keys during registration
const keyPair = await OrgEncryption.generateUserKeyPair();
const salt = OrgEncryption.generateSalt();
const { wrappedKey, iv } = await OrgEncryption.wrapPrivateKeyWithPassword(
    keyPair.privateKey,
    password,
    salt
);

// Send to server
{
    public_key: await OrgEncryption.exportPublicKey(keyPair.publicKey),
    encrypted_private_key: OrgEncryption.arrayBufferToBase64(wrappedKey),
    key_salt: OrgEncryption.arrayBufferToBase64(salt)
}
```

#### 3. Login (Unwrap Keys)
```javascript
// Unwrap private key after login
const privateKey = await OrgEncryption.unwrapPrivateKeyWithPassword(
    wrappedPrivateKey,
    iv,
    password,
    salt
);

// Unwrap DEK
const dek = await OrgEncryption.unwrapDEKWithPrivateKey(wrappedDek, privateKey);

// Store in session
await OrgEncryption.storePrivateKeyInSession(privateKey);
await OrgEncryption.storeDEKInSession(organizationId, dek);
```

#### 4. Encrypt Data
```javascript
// Get DEK from session
const dek = await OrgEncryption.getDEKFromSession(organizationId);

// Encrypt field
const encrypted = await OrgEncryption.encryptField(plaintext, dek);

// Send to server
{
    name_encrypted: OrgEncryption.arrayBufferToBase64(encrypted.ciphertext),
    name_iv: OrgEncryption.arrayBufferToBase64(encrypted.iv),
    name_tag: OrgEncryption.arrayBufferToBase64(encrypted.tag)
}
```

#### 5. Decrypt Data
```javascript
// Get DEK from session
const dek = await OrgEncryption.getDEKFromSession(organizationId);

// Decrypt field
const plaintext = await OrgEncryption.decryptField(
    OrgEncryption.base64ToArrayBuffer(name_encrypted),
    new Uint8Array(OrgEncryption.base64ToArrayBuffer(name_iv)),
    new Uint8Array(OrgEncryption.base64ToArrayBuffer(name_tag)),
    dek
);
```

#### 6. Logout
```javascript
// Clear all keys from browser
OrgEncryption.clearAllKeys();
```

### For Backend Developers

#### 1. Database Fields

**users table:**
- `public_key` (TEXT, nullable) - User's RSA public key (base64)
- `encrypted_private_key` (TEXT, nullable) - Private key encrypted with password
- `key_salt` (VARCHAR 255, nullable) - Salt for PBKDF2

**organizations table:**
- `encryption_enabled` (BOOLEAN, default TRUE) - Enable/disable encryption

**encrypted_deks table:**
- `organization_id` (INT) - Which organization
- `user_id` (INT) - Which user
- `wrapped_dek` (TEXT) - DEK encrypted with user's public key

**children table:**
- `name` (VARCHAR) - Plaintext name (for compatibility)
- `name_encrypted` (TEXT, nullable) - Encrypted name
- `name_iv` (VARCHAR 255, nullable) - IV for encryption
- `name_tag` (VARCHAR 255, nullable) - Auth tag for encryption

#### 2. Controller Logic

**Registration:**
```php
// Save user encryption keys
$user->public_key = $data['public_key'];
$user->encrypted_private_key = $data['encrypted_private_key'];
$user->key_salt = $data['key_salt'];

// Generate and wrap initial DEK
$dek = random_bytes(32); // Generate DEK on server (one-time)
// Wrap DEK with user's public key (requires PHP OpenSSL)
// Store in encrypted_deks table
```

**Login Response:**
```php
// Return user's encryption keys
return [
    'user' => [
        'encrypted_private_key' => $user->encrypted_private_key,
        'key_salt' => $user->key_salt,
        'organization_deks' => [
            [
                'organization_id' => 1,
                'wrapped_dek' => $wrappedDek
            ]
        ]
    ]
];
```

**Child Create/Update:**
```php
// Check if encryption is enabled
$org = $this->Organizations->get($child->organization_id);

if ($org->encryption_enabled && isset($data['name_encrypted'])) {
    // Save encrypted fields
    $child->name_encrypted = $data['name_encrypted'];
    $child->name_iv = $data['name_iv'];
    $child->name_tag = $data['name_tag'];
    $child->name = $data['name']; // Keep plaintext for compatibility
} else {
    // Save plaintext only
    $child->name = $data['name'];
    $child->name_encrypted = null;
}
```

#### 3. Using EncryptedDeksTable

```php
$encryptedDeks = $this->getTableLocator()->get('EncryptedDeks');

// Get wrapped DEK for a user
$dek = $encryptedDeks->getForUser($organizationId, $userId);

// Set wrapped DEK for a user
$encryptedDeks->setForUser($organizationId, $userId, $wrappedDek);

// Revoke user access
$encryptedDeks->revokeForUser($organizationId, $userId);
```

## 📊 Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                         Browser                              │
│  ┌────────────┐  ┌──────────┐  ┌────────────┐             │
│  │  Password  │──▶│   KEK    │──▶│ Private Key│             │
│  └────────────┘  └──────────┘  └─────┬──────┘             │
│                                        │                     │
│  ┌─────────────────────────────────┐  │                     │
│  │     Session Storage              │  │                     │
│  │  • Private Key (unwrapped)      │◀─┘                     │
│  │  • DEKs (unwrapped)             │                        │
│  └─────────────────────────────────┘                        │
│                      │                                       │
│                      ▼                                       │
│  ┌─────────────────────────────────┐                        │
│  │  Encryption/Decryption           │                        │
│  │  • AES-GCM-256                   │                        │
│  │  • Random IV per record          │                        │
│  └─────────────────────────────────┘                        │
└──────────────────┬──────────────────────────────────────────┘
                   │ Encrypted Data + IV + Tag
                   ▼
┌─────────────────────────────────────────────────────────────┐
│                         Server                               │
│  ┌──────────────────────────────────────────────────────┐   │
│  │             Database                                  │   │
│  │  • users (public_key, encrypted_private_key, salt)  │   │
│  │  • encrypted_deks (wrapped DEKs)                    │   │
│  │  • children (name_encrypted, name_iv, name_tag)     │   │
│  │  • organizations (encryption_enabled)                │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘

Server never sees:
  ✗ User passwords (plaintext)
  ✗ Unwrapped private keys
  ✗ Unwrapped DEKs
  ✗ Plaintext sensitive data (when encryption enabled)
```

## 🔐 Security Checklist

### Before Deployment
- [ ] HTTPS enabled in production
- [ ] Strong password policy enforced
- [ ] Session timeout configured
- [ ] CSP headers configured
- [ ] CodeQL scan passed
- [ ] All tests passing

### After Deployment
- [ ] Verify encryption_enabled default is TRUE
- [ ] Test user registration with key generation
- [ ] Test user login with key unwrapping
- [ ] Test child creation with encryption
- [ ] Verify no plaintext in database (when enabled)
- [ ] Verify keys cleared on logout
- [ ] Test encryption toggle

## 🧪 Testing

### Run All Tests
```bash
# PHP unit tests
vendor/bin/phpunit tests/TestCase/Model/Table/EncryptedDeksTableTest.php
vendor/bin/phpunit tests/TestCase/Model/Table/OrganizationEncryptionTest.php
vendor/bin/phpunit tests/TestCase/Model/Table/ChildrenEncryptionTest.php

# PHP integration tests
vendor/bin/phpunit tests/TestCase/Integration/EncryptionIntegrationTest.php

# JavaScript tests (open in browser)
webroot/js/crypto/test.html
```

### Manual Testing
1. Register new user → Check keys in database
2. Login → Check sessionStorage for keys
3. Create child → Check encrypted fields in database
4. View child → Check decryption works
5. Logout → Check sessionStorage cleared
6. Toggle encryption → Check flag updated
7. Password change → Check private key re-wrapped

## 📝 Common Issues

### "DEK not found" Error
**Cause:** User not logged in or session expired  
**Solution:** Re-login to unwrap keys

### "Decryption failed" Error
**Cause:** Wrong password or corrupted keys  
**Solution:** Verify password, check database for valid keys

### "Web Crypto API not available"
**Cause:** Browser too old or insecure context (HTTP)  
**Solution:** Use modern browser, enable HTTPS

### Data Not Encrypted
**Cause:** Organization has encryption_enabled = false  
**Solution:** Check organization settings, toggle if needed

## 🎯 Best Practices

1. **Always use HTTPS** - Web Crypto requires secure context
2. **Clear keys on logout** - Use `OrgEncryption.clearAllKeys()`
3. **Handle errors gracefully** - Show user-friendly messages
4. **Test in multiple browsers** - Chrome, Firefox, Safari
5. **Monitor performance** - Encryption adds minimal overhead
6. **Document API changes** - Keep integration docs updated
7. **Regular security audits** - Run CodeQL regularly
8. **Backup strategy** - Keys lost = data inaccessible

## 📚 Resources

- **Main Documentation:** README_SECURITY.md
- **Implementation Status:** ENCRYPTION_STATUS.md
- **Usage Examples:** webroot/js/crypto/examples.js
- **Test Suite:** webroot/js/crypto/test.html
- **API Reference:** [Web Crypto API](https://developer.mozilla.org/en-US/docs/Web/API/Web_Crypto_API)

## 🆘 Support

**Security Issues:** Report immediately to security team  
**Bug Reports:** Create GitHub issue with "encryption" label  
**Questions:** Check documentation first, then ask team

---

**Last Updated:** 2025-11-09  
**Version:** 1.0.0  
**Status:** Core implementation complete, API integration pending
