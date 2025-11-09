/**
 * Example Usage of OrgEncryption Module
 * 
 * This file demonstrates how to integrate the client-side encryption
 * into your application's frontend code.
 */

// ============================================================================
// Example 1: User Registration with Key Generation
// ============================================================================

async function handleUserRegistration(email, password, organizationName) {
    try {
        // 1. Generate user's RSA key pair
        const keyPair = await OrgEncryption.generateUserKeyPair();
        
        // 2. Generate salt for password-based key derivation
        const salt = OrgEncryption.generateSalt();
        
        // 3. Encrypt private key with password-derived KEK
        const { wrappedKey, iv } = await OrgEncryption.wrapPrivateKeyWithPassword(
            keyPair.privateKey,
            password,
            salt
        );
        
        // 4. Export public key for storage
        const publicKeyBase64 = await OrgEncryption.exportPublicKey(keyPair.publicKey);
        
        // 5. Convert wrapped private key and salt to base64
        const encryptedPrivateKeyBase64 = OrgEncryption.arrayBufferToBase64(wrappedKey);
        const ivBase64 = OrgEncryption.arrayBufferToBase64(iv);
        const saltBase64 = OrgEncryption.arrayBufferToBase64(salt);
        
        // 6. Send to server
        const response = await fetch('/users/register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                email: email,
                password: password, // Server will hash separately
                organization_name: organizationName,
                public_key: publicKeyBase64,
                encrypted_private_key: encryptedPrivateKeyBase64,
                private_key_iv: ivBase64,
                key_salt: saltBase64,
            }),
        });
        
        const data = await response.json();
        
        if (data.success) {
            console.log('Registration successful!');
            // Proceed to login
            await handleUserLogin(email, password);
        }
    } catch (error) {
        console.error('Registration error:', error);
        alert('Registration failed. Please try again.');
    }
}

// ============================================================================
// Example 2: User Login with Key Unwrapping
// ============================================================================

async function handleUserLogin(email, password) {
    try {
        // 1. Authenticate with server
        const response = await fetch('/users/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                email: email,
                password: password,
            }),
        });
        
        const data = await response.json();
        
        if (!data.success) {
            alert('Login failed');
            return;
        }
        
        // 2. Retrieve encryption keys from response
        const encryptedPrivateKeyBase64 = data.user.encrypted_private_key;
        const ivBase64 = data.user.private_key_iv;
        const saltBase64 = data.user.key_salt;
        
        // If user has no encryption keys, skip this step
        if (!encryptedPrivateKeyBase64) {
            console.log('User has no encryption keys (legacy user or encryption disabled)');
            return;
        }
        
        // 3. Convert from base64
        const wrappedPrivateKey = OrgEncryption.base64ToArrayBuffer(encryptedPrivateKeyBase64);
        const iv = new Uint8Array(OrgEncryption.base64ToArrayBuffer(ivBase64));
        const salt = new Uint8Array(OrgEncryption.base64ToArrayBuffer(saltBase64));
        
        // 4. Unwrap private key with password
        const privateKey = await OrgEncryption.unwrapPrivateKeyWithPassword(
            wrappedPrivateKey,
            iv,
            password,
            salt
        );
        
        // 5. Store private key in session storage
        await OrgEncryption.storePrivateKeyInSession(privateKey);
        
        // 6. Unwrap DEKs for user's organizations
        for (const orgDek of data.user.organization_deks) {
            const wrappedDek = OrgEncryption.base64ToArrayBuffer(orgDek.wrapped_dek);
            const dek = await OrgEncryption.unwrapDEKWithPrivateKey(wrappedDek, privateKey);
            await OrgEncryption.storeDEKInSession(orgDek.organization_id, dek);
        }
        
        console.log('Login successful! Keys unwrapped and stored.');
        
    } catch (error) {
        console.error('Login error:', error);
        alert('Login failed. Incorrect password or encryption keys corrupted.');
    }
}

// ============================================================================
// Example 3: Creating a Child with Encryption
// ============================================================================

async function handleChildCreate(childData, organizationId) {
    try {
        // 1. Check if organization has encryption enabled
        const orgResponse = await fetch(`/organizations/${organizationId}`);
        const orgData = await orgResponse.json();
        
        let requestData = { ...childData };
        
        if (orgData.organization.encryption_enabled) {
            // 2. Get DEK from session storage
            const dek = await OrgEncryption.getDEKFromSession(organizationId);
            
            if (!dek) {
                throw new Error('DEK not found. Please re-login.');
            }
            
            // 3. Encrypt child's name
            const encrypted = await OrgEncryption.encryptField(childData.name, dek);
            
            // 4. Convert to base64
            requestData.name_encrypted = OrgEncryption.arrayBufferToBase64(encrypted.ciphertext);
            requestData.name_iv = OrgEncryption.arrayBufferToBase64(encrypted.iv);
            requestData.name_tag = OrgEncryption.arrayBufferToBase64(encrypted.tag);
            
            // 5. Keep plaintext for compatibility (optional)
            requestData.name = childData.name;
        } else {
            // Encryption disabled, send plaintext only
            requestData.name = childData.name;
        }
        
        // 6. Send to server
        const response = await fetch('/children', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData),
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('Child created successfully!');
        }
        
    } catch (error) {
        console.error('Child creation error:', error);
        alert('Failed to create child. Please try again.');
    }
}

// ============================================================================
// Example 4: Displaying Children (Decryption)
// ============================================================================

async function displayChildren(organizationId) {
    try {
        // 1. Fetch children from server
        const response = await fetch(`/children?organization_id=${organizationId}`);
        const data = await response.json();
        
        // 2. Check if organization has encryption enabled
        const orgResponse = await fetch(`/organizations/${organizationId}`);
        const orgData = await orgResponse.json();
        
        const children = data.children;
        
        if (orgData.organization.encryption_enabled) {
            // 3. Get DEK from session storage
            const dek = await OrgEncryption.getDEKFromSession(organizationId);
            
            if (!dek) {
                throw new Error('DEK not found. Please re-login.');
            }
            
            // 4. Decrypt each child's name
            for (const child of children) {
                if (child.name_encrypted) {
                    const ciphertext = OrgEncryption.base64ToArrayBuffer(child.name_encrypted);
                    const iv = new Uint8Array(OrgEncryption.base64ToArrayBuffer(child.name_iv));
                    const tag = new Uint8Array(OrgEncryption.base64ToArrayBuffer(child.name_tag));
                    
                    const decryptedName = await OrgEncryption.decryptField(ciphertext, iv, tag, dek);
                    child.name_decrypted = decryptedName;
                } else {
                    // No encrypted data, use plaintext
                    child.name_decrypted = child.name;
                }
            }
        } else {
            // Encryption disabled, use plaintext
            for (const child of children) {
                child.name_decrypted = child.name;
            }
        }
        
        // 5. Display children
        children.forEach(child => {
            console.log(`Child: ${child.name_decrypted}`);
            // Update UI with decrypted name
            document.getElementById(`child-${child.id}`).textContent = child.name_decrypted;
        });
        
    } catch (error) {
        console.error('Display children error:', error);
        alert('Failed to display children. Please re-login.');
    }
}

// ============================================================================
// Example 5: Password Change (Re-wrap Private Key)
// ============================================================================

async function handlePasswordChange(oldPassword, newPassword) {
    try {
        // 1. Get current user data
        const userResponse = await fetch('/users/current');
        const userData = await userResponse.json();
        
        // 2. Unwrap private key with old password
        const wrappedPrivateKey = OrgEncryption.base64ToArrayBuffer(userData.encrypted_private_key);
        const iv = new Uint8Array(OrgEncryption.base64ToArrayBuffer(userData.private_key_iv));
        const salt = new Uint8Array(OrgEncryption.base64ToArrayBuffer(userData.key_salt));
        
        const privateKey = await OrgEncryption.unwrapPrivateKeyWithPassword(
            wrappedPrivateKey,
            iv,
            oldPassword,
            salt
        );
        
        // 3. Generate new salt
        const newSalt = OrgEncryption.generateSalt();
        
        // 4. Re-wrap private key with new password
        const { wrappedKey: newWrappedKey, iv: newIv } = await OrgEncryption.wrapPrivateKeyWithPassword(
            privateKey,
            newPassword,
            newSalt
        );
        
        // 5. Convert to base64
        const newEncryptedPrivateKeyBase64 = OrgEncryption.arrayBufferToBase64(newWrappedKey);
        const newIvBase64 = OrgEncryption.arrayBufferToBase64(newIv);
        const newSaltBase64 = OrgEncryption.arrayBufferToBase64(newSalt);
        
        // 6. Send to server
        const response = await fetch('/users/change-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                old_password: oldPassword,
                new_password: newPassword,
                encrypted_private_key: newEncryptedPrivateKeyBase64,
                private_key_iv: newIvBase64,
                key_salt: newSaltBase64,
            }),
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('Password changed successfully!');
            alert('Password changed successfully. Please login again.');
        }
        
    } catch (error) {
        console.error('Password change error:', error);
        alert('Failed to change password. Old password may be incorrect.');
    }
}

// ============================================================================
// Example 6: Adding New User to Organization (Wrap DEK)
// ============================================================================

async function handleAddUserToOrganization(organizationId, newUserId, newUserPublicKey) {
    try {
        // 1. Get DEK from session storage (admin must be logged in)
        const dek = await OrgEncryption.getDEKFromSession(organizationId);
        
        if (!dek) {
            throw new Error('DEK not found. Please re-login.');
        }
        
        // 2. Import new user's public key
        const publicKey = await OrgEncryption.importPublicKey(newUserPublicKey);
        
        // 3. Wrap DEK with new user's public key
        const wrappedDek = await OrgEncryption.wrapDEKWithPublicKey(dek, publicKey);
        
        // 4. Convert to base64
        const wrappedDekBase64 = OrgEncryption.arrayBufferToBase64(wrappedDek);
        
        // 5. Send to server
        const response = await fetch(`/organizations/${organizationId}/wrap-dek`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: newUserId,
                wrapped_dek: wrappedDekBase64,
            }),
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('User added to organization successfully!');
        }
        
    } catch (error) {
        console.error('Add user error:', error);
        alert('Failed to add user to organization.');
    }
}

// ============================================================================
// Example 7: Logout (Clear All Keys)
// ============================================================================

function handleLogout() {
    // Clear all encryption keys from session storage
    OrgEncryption.clearAllKeys();
    
    console.log('All encryption keys cleared from browser.');
    
    // Proceed with normal logout
    window.location.href = '/users/logout';
}

// ============================================================================
// Example 8: Toggle Organization Encryption
// ============================================================================

async function handleToggleEncryption(organizationId, enableEncryption) {
    try {
        const response = await fetch(`/organizations/${organizationId}/toggle-encryption`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                encryption_enabled: enableEncryption,
            }),
        });
        
        const result = await response.json();
        
        if (result.success) {
            const status = enableEncryption ? 'enabled' : 'disabled';
            console.log(`Encryption ${status} for organization ${organizationId}`);
            alert(`Encryption ${status} successfully.`);
        }
        
    } catch (error) {
        console.error('Toggle encryption error:', error);
        alert('Failed to toggle encryption.');
    }
}

// ============================================================================
// Integration with Page Load
// ============================================================================

document.addEventListener('DOMContentLoaded', function() {
    // Check if Web Crypto API is available
    if (!window.crypto || !window.crypto.subtle) {
        console.error('Web Crypto API not available!');
        alert('Your browser does not support encryption. Please use a modern browser.');
        return;
    }
    
    // Setup logout handler
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', handleLogout);
    }
    
    // Setup child form encryption
    const childForm = document.getElementById('child-form');
    if (childForm) {
        childForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(childForm);
            const childData = Object.fromEntries(formData);
            const organizationId = childData.organization_id;
            
            await handleChildCreate(childData, organizationId);
        });
    }
    
    console.log('Encryption module initialized.');
});
