# Security Documentation: Client-Side Multi-User Encryption

## Overview

This application implements **optional client-side encryption** for sensitive data fields, primarily children's names. The system uses envelope encryption to enable multiple users within an organization to decrypt the same data while maintaining a Zero-Knowledge architecture where the server never sees plaintext.

## Architecture

### Envelope Encryption Model

```
User Password → KEK (Key Encryption Key)
                 ↓
           Private Key (encrypted)
                 ↓
           Public Key (plaintext)
                 ↓
           DEK (Data Encryption Key, wrapped)
                 ↓
           Sensitive Data (encrypted)
```

### Key Components

1. **Data Encryption Key (DEK)**
   - One per organization
   - AES-GCM-256 symmetric key
   - Used to encrypt/decrypt actual data fields
   - Stored encrypted (wrapped) for each user

2. **User Key Pair**
   - RSA-OAEP-SHA256, 2048-bit (or EC P-256)
   - Public key: stored in database (plaintext)
   - Private key: encrypted with password-derived KEK
   - Used to wrap/unwrap the organization's DEK

3. **Key Encryption Key (KEK)**
   - Derived from user's password using PBKDF2-SHA256
   - 210,000 iterations (OWASP 2023 recommendation)
   - 16-byte random salt per user
   - Never stored, only exists during key operations

## Security Policies

### Encryption Standards

- **Data Encryption**: AES-GCM-256
  - Random 96-bit IV per record
  - 128-bit authentication tag
  - No IV reuse (fresh random for each encryption)

- **Key Wrapping**: RSA-OAEP-SHA256
  - 2048-bit RSA keys (or EC P-256 alternative)
  - OAEP with SHA-256 padding

- **Password-Based Derivation**: PBKDF2-SHA256
  - 210,000 iterations
  - 128-bit random salt per user
  - Never reuses salts

### Key Storage

| Key Type | Storage Location | Encryption | Lifetime |
|----------|------------------|------------|----------|
| Public Key | Database (users.public_key) | None (public) | Permanent |
| Private Key | Database (users.encrypted_private_key) | KEK (password-derived) | Permanent |
| KEK | Never stored | N/A | Derived on-demand |
| DEK | Database (encrypted_deks.wrapped_dek) | User's Public Key | Per organization |
| DEK (unwrapped) | Browser sessionStorage | None | Until logout/close |
| Private Key (unwrapped) | Browser sessionStorage | None | Until logout/close |

### Zero-Knowledge Guarantees

1. **Server Never Sees**:
   - User passwords in plaintext (only hashed with bcrypt)
   - Unwrapped private keys
   - Unwrapped DEKs
   - Plaintext sensitive data (when encryption enabled)

2. **Client-Only Operations**:
   - Password-to-KEK derivation
   - Private key unwrapping
   - DEK unwrapping
   - Data encryption/decryption

3. **Server Only Sees**:
   - Ciphertext
   - IVs and authentication tags
   - Wrapped (encrypted) keys
   - Public keys

## Data Flow

### Registration Flow

1. User enters password
2. Browser generates:
   - Random salt
   - RSA key pair
   - KEK from password + salt
3. Browser encrypts private key with KEK
4. Browser sends to server:
   - Public key (plaintext)
   - Encrypted private key
   - Salt
   - User credentials

### Login Flow

1. User enters password
2. Server sends:
   - Encrypted private key
   - Salt
   - Wrapped DEK(s) for user's organizations
3. Browser:
   - Derives KEK from password + salt
   - Unwraps private key with KEK
   - Unwraps DEK(s) with private key
   - Stores keys in sessionStorage

### Data Encryption Flow (Write)

1. User enters sensitive data (e.g., child's name)
2. Browser:
   - Retrieves DEK from sessionStorage
   - Generates random IV
   - Encrypts data with AES-GCM
   - Sends ciphertext + IV + tag to server
3. Server stores encrypted data

### Data Decryption Flow (Read)

1. Server sends encrypted data + IV + tag
2. Browser:
   - Retrieves DEK from sessionStorage
   - Decrypts with AES-GCM
   - Displays plaintext to user

### Password Change Flow

1. User enters old + new password
2. Browser:
   - Derives old KEK, unwraps private key
   - Generates new salt
   - Derives new KEK from new password + new salt
   - Re-encrypts private key with new KEK
   - Sends updated encrypted private key + new salt
3. **No data re-encryption needed** (DEK unchanged)

### New User to Organization Flow

1. Admin invites new user (has public key)
2. Browser (admin's):
   - Retrieves organization DEK
   - Retrieves new user's public key
   - Wraps DEK with new user's public key
   - Sends wrapped DEK to server
3. Server stores in encrypted_deks table
4. New user can now decrypt organization data

### User Revocation Flow

1. Admin removes user from organization
2. Server deletes user's wrapped DEK entry
3. User can no longer decrypt organization data
4. **Optional**: Admin can rotate DEK for enhanced security
   - Generate new DEK
   - Re-encrypt all data with new DEK
   - Re-wrap new DEK for remaining users

## Optional Encryption Toggle

Organizations can disable encryption entirely:

### When `encryption_enabled = true` (default):
- All new child names stored encrypted
- Existing plaintext names encrypted on next edit (lazy migration)
- All client-side crypto activated

### When `encryption_enabled = false`:
- All child names stored in plaintext
- Crypto layer bypassed
- Previous encrypted data remains encrypted (not lost)
- Can be re-enabled later to resume encryption

### Admin Toggle UI
- Clear warning about security implications
- Confirmation dialog required
- Immediate effect on new records
- No automatic decryption of existing data

## Database Schema

### organizations
```sql
encryption_enabled BOOLEAN DEFAULT TRUE NOT NULL
```

### users
```sql
public_key TEXT NULL
encrypted_private_key TEXT NULL
key_salt VARCHAR(255) NULL
```

### encrypted_deks
```sql
id INT PRIMARY KEY
organization_id INT NOT NULL
user_id INT NOT NULL
wrapped_dek TEXT NOT NULL
created DATETIME
modified DATETIME
UNIQUE(organization_id, user_id)
```

### children
```sql
name VARCHAR(255) NOT NULL  -- Plaintext or compatibility
name_encrypted TEXT NULL    -- AES-GCM ciphertext
name_iv VARCHAR(255) NULL   -- Base64 IV
name_tag VARCHAR(255) NULL  -- Base64 auth tag
```

## Testing

### Unit Tests

- ✅ Encryption/decryption round-trip
- ✅ Key generation and wrapping
- ✅ DEK wrapping for multiple users
- ✅ Password change rewrap
- ✅ Organization encryption toggle
- ✅ Plaintext mode operation

### Integration Tests

- ✅ Full registration with key generation
- ✅ Full login with key unwrapping
- ✅ Child CRUD with encryption enabled
- ✅ Child CRUD with encryption disabled
- ✅ Multi-user access to same encrypted data
- ✅ Admin toggle between modes

### Security Tests

- ✅ No plaintext in database when enabled
- ✅ No plaintext in server logs
- ✅ Keys cleared on logout
- ✅ Proper key storage (sessionStorage, not localStorage)
- ✅ Authentication tag validation

## Backward Compatibility

### Migration Strategy

1. **Phase 1: Add Fields** (this migration)
   - Add encryption columns (nullable)
   - Add encryption toggle (default true)
   - Existing data remains unchanged

2. **Phase 2: Lazy Migration** (automatic)
   - When user edits a child record:
     - If encryption enabled: encrypt name, store in new fields
     - If encryption disabled: keep plaintext
   
3. **Phase 3: Bulk Migration** (optional, admin-triggered)
   - Batch encrypt all plaintext names
   - Only for organizations with encryption enabled

### Rollback Plan

If encryption needs to be removed:
1. Set all `encryption_enabled = false`
2. All plaintext names still readable
3. Encrypted data preserved but not used
4. Can be re-enabled without data loss

## Threat Model

### Protected Against

- ✅ Database breach (encrypted at rest)
- ✅ Server compromise (Zero-Knowledge)
- ✅ Man-in-the-middle (HTTPS required)
- ✅ Unauthorized user access (per-user keys)
- ✅ Password reuse attacks (unique salts)

### Not Protected Against

- ⚠️ Client compromise (keys in memory)
- ⚠️ Weak user passwords (PBKDF2 slows but doesn't prevent)
- ⚠️ Physical access to unlocked device
- ⚠️ Browser vulnerabilities
- ⚠️ XSS attacks (standard web app security required)

## Best Practices

1. **Always use HTTPS** in production
2. **Enforce strong passwords** (minimum length, complexity)
3. **Clear sessionStorage on logout** (implemented)
4. **Never log plaintext data** (verified in tests)
5. **Regular security audits** (CodeQL enabled)
6. **Key rotation** on suspected compromise
7. **Multi-factor authentication** recommended (future enhancement)

## Compliance Notes

This encryption architecture supports:
- **GDPR**: Right to erasure (delete wrapped DEK)
- **HIPAA**: Encryption at rest and in transit
- **Zero Trust**: Client-side encryption, server doesn't trust
- **Privacy by Design**: Encryption enabled by default

## Performance Considerations

- Key generation: ~1-2 seconds (one-time, registration)
- PBKDF2 derivation: ~100-200ms (login only)
- AES-GCM encryption: <1ms per field
- AES-GCM decryption: <1ms per field
- RSA wrapping: ~10ms per DEK
- RSA unwrapping: ~10ms per DEK

All operations are client-side, no additional server load.

## Future Enhancements

1. **Key Rotation**: Scheduled automatic DEK rotation
2. **Audit Log**: Track all key access and encryption operations
3. **Hardware Security Module**: For high-security deployments
4. **Biometric Authentication**: Additional layer for mobile
5. **Encrypted Backup**: Automatic encrypted backups
6. **Additional Fields**: Extend encryption to notes, waitlist data

## Contact

For security issues, contact: security@example.com
