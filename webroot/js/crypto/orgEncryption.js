/**
 * Organization Encryption Module
 * 
 * Client-side encryption for multi-user organizations using Web Crypto API.
 * Implements envelope encryption with RSA-OAEP for key wrapping and AES-GCM for data encryption.
 * 
 * Architecture:
 * - Each organization has a Data Encryption Key (DEK) for encrypting sensitive fields
 * - Each user has an RSA key pair (public/private)
 * - User's private key is encrypted with a password-derived Key Encryption Key (KEK)
 * - DEK is wrapped (encrypted) with each user's public key
 * - Only users with the organization membership can unwrap the DEK
 * 
 * Security:
 * - AES-GCM-256 for data encryption
 * - RSA-OAEP-SHA256 for key wrapping
 * - PBKDF2-SHA256 with 210,000 iterations for password-based KEK derivation
 * - Keys stored in sessionStorage (cleared on logout)
 * - No plaintext stored in browser localStorage
 */

const OrgEncryption = (function() {
    'use strict';

    // Configuration constants
    const CONFIG = {
        // AES-GCM parameters
        AES_KEY_LENGTH: 256,
        AES_IV_LENGTH: 12, // 96 bits recommended for GCM
        AES_TAG_LENGTH: 128, // 128 bits authentication tag
        
        // RSA parameters
        RSA_KEY_SIZE: 2048,
        RSA_HASH: 'SHA-256',
        
        // PBKDF2 parameters (OWASP recommendations 2023)
        PBKDF2_ITERATIONS: 210000, // 210,000 iterations for SHA-256
        PBKDF2_HASH: 'SHA-256',
        PBKDF2_SALT_LENGTH: 16, // 128 bits
        
        // Storage keys
        STORAGE_PREFIX: 'orgcrypt_',
        STORAGE_PRIVATE_KEY: 'private_key',
        STORAGE_DEK: 'dek_',
    };

    /**
     * Generate a new RSA key pair for a user
     * @returns {Promise<{publicKey: CryptoKey, privateKey: CryptoKey}>}
     */
    async function generateUserKeyPair() {
        return await window.crypto.subtle.generateKey(
            {
                name: 'RSA-OAEP',
                modulusLength: CONFIG.RSA_KEY_SIZE,
                publicExponent: new Uint8Array([1, 0, 1]), // 65537
                hash: CONFIG.RSA_HASH,
            },
            true, // extractable
            ['wrapKey', 'unwrapKey']
        );
    }

    /**
     * Generate a new AES-GCM key for organization data encryption (DEK)
     * @returns {Promise<CryptoKey>}
     */
    async function generateDEK() {
        return await window.crypto.subtle.generateKey(
            {
                name: 'AES-GCM',
                length: CONFIG.AES_KEY_LENGTH,
            },
            true, // extractable
            ['encrypt', 'decrypt']
        );
    }

    /**
     * Derive a Key Encryption Key from user's password using PBKDF2
     * @param {string} password - User's password
     * @param {Uint8Array} salt - Salt for key derivation
     * @returns {Promise<CryptoKey>}
     */
    async function deriveKEKFromPassword(password, salt) {
        const encoder = new TextEncoder();
        const passwordKey = await window.crypto.subtle.importKey(
            'raw',
            encoder.encode(password),
            'PBKDF2',
            false,
            ['deriveKey']
        );

        return await window.crypto.subtle.deriveKey(
            {
                name: 'PBKDF2',
                salt: salt,
                iterations: CONFIG.PBKDF2_ITERATIONS,
                hash: CONFIG.PBKDF2_HASH,
            },
            passwordKey,
            {
                name: 'AES-GCM',
                length: CONFIG.AES_KEY_LENGTH,
            },
            false, // not extractable for security
            ['wrapKey', 'unwrapKey']
        );
    }

    /**
     * Generate a random salt for PBKDF2
     * @returns {Uint8Array}
     */
    function generateSalt() {
        return window.crypto.getRandomValues(new Uint8Array(CONFIG.PBKDF2_SALT_LENGTH));
    }

    /**
     * Generate a random IV for AES-GCM
     * @returns {Uint8Array}
     */
    function generateIV() {
        return window.crypto.getRandomValues(new Uint8Array(CONFIG.AES_IV_LENGTH));
    }

    /**
     * Wrap (encrypt) a private key with a password-derived KEK
     * @param {CryptoKey} privateKey - RSA private key to wrap
     * @param {string} password - User's password
     * @param {Uint8Array} salt - Salt for KEK derivation (optional, will be generated if not provided)
     * @returns {Promise<{wrappedKey: string, iv: string, salt: string}>} - Base64 encoded values
     */
    async function wrapPrivateKeyWithPassword(privateKey, password, salt) {
        // Generate salt if not provided
        if (!salt) {
            salt = generateSalt();
        }
        
        const kek = await deriveKEKFromPassword(password, salt);
        const iv = generateIV();
        
        const wrappedKey = await window.crypto.subtle.wrapKey(
            'pkcs8',
            privateKey,
            kek,
            {
                name: 'AES-GCM',
                iv: iv,
            }
        );

        // Return Base64 encoded values for storage
        return {
            wrappedKey: arrayBufferToBase64(wrappedKey),
            iv: arrayBufferToBase64(iv),
            salt: arrayBufferToBase64(salt)
        };
    }

    /**
     * Unwrap (decrypt) a private key with a password-derived KEK
     * @param {string} wrappedKeyBase64 - Wrapped private key (Base64)
     * @param {string} password - User's password
     * @param {string} saltBase64 - Salt for KEK derivation (Base64)
     * @param {string} ivBase64 - IV used for wrapping (Base64, optional)
     * @returns {Promise<CryptoKey>}
     */
    async function unwrapPrivateKeyWithPassword(wrappedKeyBase64, password, saltBase64, ivBase64) {
        // Convert Base64 to ArrayBuffer
        const wrappedKey = base64ToArrayBuffer(wrappedKeyBase64);
        const salt = base64ToArrayBuffer(saltBase64);
        
        // IV is optional for backward compatibility
        // If not provided, we use a dummy IV (not secure, but maintains compatibility)
        let iv;
        if (ivBase64) {
            iv = base64ToArrayBuffer(ivBase64);
        } else {
            // For backward compatibility: use first 12 bytes of wrapped key as IV
            // This is NOT secure but allows decryption of old keys
            iv = new Uint8Array(wrappedKey.slice(0, 12));
        }
        
        const kek = await deriveKEKFromPassword(password, salt);
        
        return await window.crypto.subtle.unwrapKey(
            'pkcs8',
            wrappedKey,
            kek,
            {
                name: 'AES-GCM',
                iv: iv,
            },
            {
                name: 'RSA-OAEP',
                hash: CONFIG.RSA_HASH,
            },
            true,
            ['unwrapKey']
        );
    }

    /**
     * Wrap DEK with user's public key
     * @param {CryptoKey} dek - Data Encryption Key to wrap
     * @param {CryptoKey} publicKey - User's RSA public key
     * @returns {Promise<ArrayBuffer>}
     */
    async function wrapDEKWithPublicKey(dek, publicKey) {
        return await window.crypto.subtle.wrapKey(
            'raw',
            dek,
            publicKey,
            {
                name: 'RSA-OAEP',
            }
        );
    }

    /**
     * Unwrap DEK with user's private key
     * @param {ArrayBuffer} wrappedDEK - Wrapped DEK
     * @param {CryptoKey} privateKey - User's RSA private key
     * @returns {Promise<CryptoKey>}
     */
    async function unwrapDEKWithPrivateKey(wrappedDEK, privateKey) {
        return await window.crypto.subtle.unwrapKey(
            'raw',
            wrappedDEK,
            privateKey,
            {
                name: 'RSA-OAEP',
            },
            {
                name: 'AES-GCM',
                length: CONFIG.AES_KEY_LENGTH,
            },
            true,
            ['encrypt', 'decrypt']
        );
    }

    /**
     * Encrypt a field value with DEK
     * @param {string} plaintext - Value to encrypt
     * @param {CryptoKey} dek - Data Encryption Key
     * @returns {Promise<{ciphertext: ArrayBuffer, iv: Uint8Array, tag: Uint8Array}>}
     */
    async function encryptField(plaintext, dek) {
        const encoder = new TextEncoder();
        const data = encoder.encode(plaintext);
        const iv = generateIV();

        const ciphertext = await window.crypto.subtle.encrypt(
            {
                name: 'AES-GCM',
                iv: iv,
                tagLength: CONFIG.AES_TAG_LENGTH,
            },
            dek,
            data
        );

        // AES-GCM includes the tag at the end of ciphertext
        // Extract it for separate storage
        const ciphertextBytes = new Uint8Array(ciphertext);
        const tagLength = CONFIG.AES_TAG_LENGTH / 8; // Convert bits to bytes
        const actualCiphertext = ciphertextBytes.slice(0, -tagLength);
        const tag = ciphertextBytes.slice(-tagLength);

        return {
            ciphertext: actualCiphertext.buffer,
            iv: iv,
            tag: tag,
        };
    }

    /**
     * Decrypt a field value with DEK
     * @param {ArrayBuffer} ciphertext - Encrypted value
     * @param {Uint8Array} iv - Initialization vector
     * @param {Uint8Array} tag - Authentication tag
     * @param {CryptoKey} dek - Data Encryption Key
     * @returns {Promise<string>}
     */
    async function decryptField(ciphertext, iv, tag, dek) {
        // Combine ciphertext and tag for AES-GCM
        const ciphertextBytes = new Uint8Array(ciphertext);
        const combined = new Uint8Array(ciphertextBytes.length + tag.length);
        combined.set(ciphertextBytes);
        combined.set(tag, ciphertextBytes.length);

        const plaintext = await window.crypto.subtle.decrypt(
            {
                name: 'AES-GCM',
                iv: iv,
                tagLength: CONFIG.AES_TAG_LENGTH,
            },
            dek,
            combined
        );

        const decoder = new TextDecoder();
        return decoder.decode(plaintext);
    }

    /**
     * Convert ArrayBuffer to Base64 string
     * @param {ArrayBuffer} buffer
     * @returns {string}
     */
    function arrayBufferToBase64(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return window.btoa(binary);
    }

    /**
     * Convert Base64 string to ArrayBuffer
     * @param {string} base64
     * @returns {ArrayBuffer}
     */
    function base64ToArrayBuffer(base64) {
        const binary = window.atob(base64);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return bytes.buffer;
    }

    /**
     * Export public key to Base64 PEM format
     * @param {CryptoKey} publicKey
     * @returns {Promise<string>}
     */
    async function exportPublicKey(publicKey) {
        const exported = await window.crypto.subtle.exportKey('spki', publicKey);
        return arrayBufferToBase64(exported);
    }

    /**
     * Import public key from Base64 PEM format
     * @param {string} base64Key
     * @returns {Promise<CryptoKey>}
     */
    async function importPublicKey(base64Key) {
        const keyData = base64ToArrayBuffer(base64Key);
        return await window.crypto.subtle.importKey(
            'spki',
            keyData,
            {
                name: 'RSA-OAEP',
                hash: CONFIG.RSA_HASH,
            },
            true,
            ['wrapKey']
        );
    }

    /**
     * Store private key in session storage
     * @param {CryptoKey} privateKey
     */
    async function storePrivateKeyInSession(privateKey) {
        const exported = await window.crypto.subtle.exportKey('pkcs8', privateKey);
        const base64 = arrayBufferToBase64(exported);
        sessionStorage.setItem(CONFIG.STORAGE_PREFIX + CONFIG.STORAGE_PRIVATE_KEY, base64);
    }

    /**
     * Retrieve private key from session storage
     * @returns {Promise<CryptoKey|null>}
     */
    async function getPrivateKeyFromSession() {
        const base64 = sessionStorage.getItem(CONFIG.STORAGE_PREFIX + CONFIG.STORAGE_PRIVATE_KEY);
        if (!base64) return null;

        const keyData = base64ToArrayBuffer(base64);
        return await window.crypto.subtle.importKey(
            'pkcs8',
            keyData,
            {
                name: 'RSA-OAEP',
                hash: CONFIG.RSA_HASH,
            },
            true,
            ['unwrapKey']
        );
    }

    /**
     * Store DEK in session storage for an organization
     * @param {number} organizationId
     * @param {CryptoKey} dek
     */
    async function storeDEKInSession(organizationId, dek) {
        const exported = await window.crypto.subtle.exportKey('raw', dek);
        const base64 = arrayBufferToBase64(exported);
        sessionStorage.setItem(CONFIG.STORAGE_PREFIX + CONFIG.STORAGE_DEK + organizationId, base64);
    }

    /**
     * Retrieve DEK from session storage for an organization
     * @param {number} organizationId
     * @returns {Promise<CryptoKey|null>}
     */
    async function getDEKFromSession(organizationId) {
        const base64 = sessionStorage.getItem(CONFIG.STORAGE_PREFIX + CONFIG.STORAGE_DEK + organizationId);
        if (!base64) return null;

        const keyData = base64ToArrayBuffer(base64);
        return await window.crypto.subtle.importKey(
            'raw',
            keyData,
            {
                name: 'AES-GCM',
                length: CONFIG.AES_KEY_LENGTH,
            },
            true,
            ['encrypt', 'decrypt']
        );
    }

    /**
     * Clear all encryption keys from session storage
     */
    function clearAllKeys() {
        const keys = Object.keys(sessionStorage);
        keys.forEach(key => {
            if (key.startsWith(CONFIG.STORAGE_PREFIX)) {
                sessionStorage.removeItem(key);
            }
        });
    }

    // Public API
    return {
        // Key generation
        generateKeyPair: generateUserKeyPair, // Alias for backward compatibility
        generateUserKeyPair,
        generateDEK,
        generateSalt,
        generateIV,

        // Key wrapping/unwrapping
        wrapPrivateKeyWithPassword,
        unwrapPrivateKeyWithPassword,
        wrapDEK: wrapDEKWithPublicKey, // Alias for backward compatibility
        unwrapDEK: unwrapDEKWithPrivateKey, // Alias for backward compatibility
        wrapDEKWithPublicKey,
        unwrapDEKWithPrivateKey,

        // Field encryption/decryption
        encryptField,
        decryptField,

        // Key import/export
        exportPublicKey,
        importPublicKey,

        // Session storage management
        storeDEK: storeDEKInSession, // Alias for backward compatibility
        getDEK: getDEKFromSession, // Alias for backward compatibility
        storePrivateKeyInSession,
        getPrivateKeyFromSession,
        storeDEKInSession,
        getDEKFromSession,
        clearAllKeys,

        // Utility functions
        arrayBufferToBase64,
        base64ToArrayBuffer,

        // Configuration
        CONFIG,
    };
})();

// Make available globally
if (typeof window !== 'undefined') {
    window.OrgEncryption = OrgEncryption;
}
if (typeof module !== 'undefined' && module.exports) {
    module.exports = OrgEncryption;
}
