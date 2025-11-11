/**
 * Unit tests for OrgEncryption module
 * 
 * These tests validate the client-side encryption functionality.
 * Run in browser console or with a test runner like Jest/Mocha.
 * 
 * Requirements:
 * - Modern browser with Web Crypto API support
 * - orgEncryption.js loaded
 */

const CryptoTests = (function() {
    'use strict';

    let testResults = [];
    let testCount = 0;
    let passCount = 0;
    let failCount = 0;

    function assert(condition, message) {
        testCount++;
        if (condition) {
            passCount++;
            console.log('✓ ' + message);
            testResults.push({ passed: true, message });
        } else {
            failCount++;
            console.error('✗ ' + message);
            testResults.push({ passed: false, message });
            throw new Error('Assertion failed: ' + message);
        }
    }

    function assertEqual(actual, expected, message) {
        assert(actual === expected, message + ` (expected: ${expected}, actual: ${actual})`);
    }

    function assertNotNull(value, message) {
        assert(value !== null && value !== undefined, message);
    }

    async function testKeyGeneration() {
        console.log('\n=== Test: Key Generation ===');
        
        // Test RSA key pair generation
        const keyPair = await OrgEncryption.generateUserKeyPair();
        assertNotNull(keyPair, 'Key pair generated');
        assertNotNull(keyPair.publicKey, 'Public key exists');
        assertNotNull(keyPair.privateKey, 'Private key exists');
        assert(keyPair.publicKey.type === 'public', 'Public key type is public');
        assert(keyPair.privateKey.type === 'private', 'Private key type is private');
        
        // Test DEK generation
        const dek = await OrgEncryption.generateDEK();
        assertNotNull(dek, 'DEK generated');
        assert(dek.type === 'secret', 'DEK type is secret');
        
        // Test salt generation
        const salt = OrgEncryption.generateSalt();
        assertNotNull(salt, 'Salt generated');
        assertEqual(salt.length, OrgEncryption.CONFIG.PBKDF2_SALT_LENGTH, 'Salt has correct length');
        
        // Test IV generation
        const iv = OrgEncryption.generateIV();
        assertNotNull(iv, 'IV generated');
        assertEqual(iv.length, OrgEncryption.CONFIG.AES_IV_LENGTH, 'IV has correct length');
    }

    async function testPasswordBasedEncryption() {
        console.log('\n=== Test: Password-Based Private Key Encryption ===');
        
        const password = 'TestPassword123!';
        const keyPair = await OrgEncryption.generateUserKeyPair();
        const salt = OrgEncryption.generateSalt();
        
        // Wrap private key with password
        const { wrappedKey, iv } = await OrgEncryption.wrapPrivateKeyWithPassword(
            keyPair.privateKey,
            password,
            salt
        );
        
        assertNotNull(wrappedKey, 'Private key wrapped');
        assertNotNull(iv, 'IV generated for wrapping');
        
        // Unwrap private key with password
        const unwrappedKey = await OrgEncryption.unwrapPrivateKeyWithPassword(
            wrappedKey,
            iv,
            password,
            salt
        );
        
        assertNotNull(unwrappedKey, 'Private key unwrapped');
        assert(unwrappedKey.type === 'private', 'Unwrapped key is private key');
    }

    async function testPasswordBasedEncryptionWrongPassword() {
        console.log('\n=== Test: Wrong Password Fails ===');
        
        const password = 'CorrectPassword123!';
        const wrongPassword = 'WrongPassword456!';
        const keyPair = await OrgEncryption.generateUserKeyPair();
        const salt = OrgEncryption.generateSalt();
        
        const { wrappedKey, iv } = await OrgEncryption.wrapPrivateKeyWithPassword(
            keyPair.privateKey,
            password,
            salt
        );
        
        try {
            await OrgEncryption.unwrapPrivateKeyWithPassword(
                wrappedKey,
                iv,
                wrongPassword,
                salt
            );
            assert(false, 'Should have thrown error with wrong password');
        } catch (e) {
            assert(true, 'Wrong password correctly rejected');
        }
    }

    async function testDEKWrapping() {
        console.log('\n=== Test: DEK Wrapping with Public Key ===');
        
        const keyPair = await OrgEncryption.generateUserKeyPair();
        const dek = await OrgEncryption.generateDEK();
        
        // Wrap DEK with public key
        const wrappedDEK = await OrgEncryption.wrapDEKWithPublicKey(dek, keyPair.publicKey);
        assertNotNull(wrappedDEK, 'DEK wrapped with public key');
        
        // Unwrap DEK with private key
        const unwrappedDEK = await OrgEncryption.unwrapDEKWithPrivateKey(wrappedDEK, keyPair.privateKey);
        assertNotNull(unwrappedDEK, 'DEK unwrapped with private key');
        assert(unwrappedDEK.type === 'secret', 'Unwrapped DEK is secret key');
    }

    async function testFieldEncryption() {
        console.log('\n=== Test: Field Encryption/Decryption ===');
        
        const plaintext = 'John Doe';
        const dek = await OrgEncryption.generateDEK();
        
        // Encrypt field
        const encrypted = await OrgEncryption.encryptField(plaintext, dek);
        assertNotNull(encrypted, 'Field encrypted');
        assertNotNull(encrypted.ciphertext, 'Ciphertext exists');
        assertNotNull(encrypted.iv, 'IV exists');
        assertNotNull(encrypted.tag, 'Tag exists');
        
        // Decrypt field
        const decrypted = await OrgEncryption.decryptField(
            encrypted.ciphertext,
            encrypted.iv,
            encrypted.tag,
            dek
        );
        
        assertEqual(decrypted, plaintext, 'Decrypted text matches original');
    }

    async function testFieldEncryptionWithDifferentData() {
        console.log('\n=== Test: Multiple Field Encryptions ===');
        
        const dek = await OrgEncryption.generateDEK();
        const plaintexts = [
            'Alice Smith',
            'Bob Johnson',
            'Charlie Brown',
            '日本語テスト', // Japanese characters
            'Emoji test 🎉🎊',
            'Special chars: @#$%^&*()',
        ];
        
        for (const plaintext of plaintexts) {
            const encrypted = await OrgEncryption.encryptField(plaintext, dek);
            const decrypted = await OrgEncryption.decryptField(
                encrypted.ciphertext,
                encrypted.iv,
                encrypted.tag,
                dek
            );
            assertEqual(decrypted, plaintext, `Encryption/decryption works for: ${plaintext}`);
        }
    }

    async function testKeyExportImport() {
        console.log('\n=== Test: Key Export/Import ===');
        
        const keyPair = await OrgEncryption.generateUserKeyPair();
        
        // Export public key
        const exportedKey = await OrgEncryption.exportPublicKey(keyPair.publicKey);
        assertNotNull(exportedKey, 'Public key exported');
        assert(typeof exportedKey === 'string', 'Exported key is string');
        assert(exportedKey.length > 0, 'Exported key has content');
        
        // Import public key
        const importedKey = await OrgEncryption.importPublicKey(exportedKey);
        assertNotNull(importedKey, 'Public key imported');
        assert(importedKey.type === 'public', 'Imported key is public key');
    }

    async function testSessionStorage() {
        console.log('\n=== Test: Session Storage ===');
        
        const keyPair = await OrgEncryption.generateUserKeyPair();
        const dek = await OrgEncryption.generateDEK();
        const orgId = 1;
        
        // Store private key
        await OrgEncryption.storePrivateKeyInSession(keyPair.privateKey);
        const retrievedPrivateKey = await OrgEncryption.getPrivateKeyFromSession();
        assertNotNull(retrievedPrivateKey, 'Private key retrieved from session');
        assert(retrievedPrivateKey.type === 'private', 'Retrieved key is private key');
        
        // Store DEK
        await OrgEncryption.storeDEKInSession(orgId, dek);
        const retrievedDEK = await OrgEncryption.getDEKFromSession(orgId);
        assertNotNull(retrievedDEK, 'DEK retrieved from session');
        assert(retrievedDEK.type === 'secret', 'Retrieved DEK is secret key');
        
        // Clear all keys
        OrgEncryption.clearAllKeys();
        const clearedPrivateKey = await OrgEncryption.getPrivateKeyFromSession();
        const clearedDEK = await OrgEncryption.getDEKFromSession(orgId);
        assert(clearedPrivateKey === null, 'Private key cleared from session');
        assert(clearedDEK === null, 'DEK cleared from session');
    }

    async function testBase64Encoding() {
        console.log('\n=== Test: Base64 Encoding/Decoding ===');
        
        const testData = new Uint8Array([1, 2, 3, 4, 5, 255, 128, 64]);
        const base64 = OrgEncryption.arrayBufferToBase64(testData.buffer);
        assertNotNull(base64, 'Base64 encoded');
        assert(typeof base64 === 'string', 'Base64 is string');
        
        const decoded = new Uint8Array(OrgEncryption.base64ToArrayBuffer(base64));
        assertEqual(decoded.length, testData.length, 'Decoded length matches');
        for (let i = 0; i < testData.length; i++) {
            assertEqual(decoded[i], testData[i], `Byte ${i} matches`);
        }
    }

    async function testEndToEndFlow() {
        console.log('\n=== Test: End-to-End Encryption Flow ===');
        
        // Simulate User A registration
        const passwordA = 'UserAPassword123!';
        const keyPairA = await OrgEncryption.generateUserKeyPair();
        const saltA = OrgEncryption.generateSalt();
        const { wrappedKey: wrappedPrivateKeyA, iv: ivA } = await OrgEncryption.wrapPrivateKeyWithPassword(
            keyPairA.privateKey,
            passwordA,
            saltA
        );
        
        // Simulate User B registration
        const passwordB = 'UserBPassword456!';
        const keyPairB = await OrgEncryption.generateUserKeyPair();
        const saltB = OrgEncryption.generateSalt();
        const { wrappedKey: wrappedPrivateKeyB, iv: ivB } = await OrgEncryption.wrapPrivateKeyWithPassword(
            keyPairB.privateKey,
            passwordB,
            saltB
        );
        
        // Create organization DEK
        const orgDEK = await OrgEncryption.generateDEK();
        
        // Wrap DEK for both users
        const wrappedDEKForA = await OrgEncryption.wrapDEKWithPublicKey(orgDEK, keyPairA.publicKey);
        const wrappedDEKForB = await OrgEncryption.wrapDEKWithPublicKey(orgDEK, keyPairB.publicKey);
        
        // User A encrypts data
        const sensitiveData = 'Confidential Child Name';
        const encrypted = await OrgEncryption.encryptField(sensitiveData, orgDEK);
        
        // User A can decrypt (simulating login)
        const unwrappedPrivateKeyA = await OrgEncryption.unwrapPrivateKeyWithPassword(
            wrappedPrivateKeyA,
            ivA,
            passwordA,
            saltA
        );
        const unwrappedDEKForA = await OrgEncryption.unwrapDEKWithPrivateKey(wrappedDEKForA, unwrappedPrivateKeyA);
        const decryptedByA = await OrgEncryption.decryptField(
            encrypted.ciphertext,
            encrypted.iv,
            encrypted.tag,
            unwrappedDEKForA
        );
        assertEqual(decryptedByA, sensitiveData, 'User A can decrypt data');
        
        // User B can also decrypt (simulating login)
        const unwrappedPrivateKeyB = await OrgEncryption.unwrapPrivateKeyWithPassword(
            wrappedPrivateKeyB,
            ivB,
            passwordB,
            saltB
        );
        const unwrappedDEKForB = await OrgEncryption.unwrapDEKWithPrivateKey(wrappedDEKForB, unwrappedPrivateKeyB);
        const decryptedByB = await OrgEncryption.decryptField(
            encrypted.ciphertext,
            encrypted.iv,
            encrypted.tag,
            unwrappedDEKForB
        );
        assertEqual(decryptedByB, sensitiveData, 'User B can decrypt data');
        
        console.log('✓ Both users can decrypt the same data');
    }

    async function runAllTests() {
        console.log('========================================');
        console.log('  Client-Side Encryption Tests');
        console.log('========================================');
        
        testResults = [];
        testCount = 0;
        passCount = 0;
        failCount = 0;
        
        try {
            await testKeyGeneration();
            await testPasswordBasedEncryption();
            await testPasswordBasedEncryptionWrongPassword();
            await testDEKWrapping();
            await testFieldEncryption();
            await testFieldEncryptionWithDifferentData();
            await testKeyExportImport();
            await testSessionStorage();
            await testBase64Encoding();
            await testEndToEndFlow();
            
            console.log('\n========================================');
            console.log(`  Results: ${passCount}/${testCount} tests passed`);
            if (failCount > 0) {
                console.log(`  ✗ ${failCount} tests failed`);
            } else {
                console.log('  ✓ All tests passed!');
            }
            console.log('========================================');
            
            return {
                total: testCount,
                passed: passCount,
                failed: failCount,
                results: testResults,
            };
        } catch (error) {
            console.error('\n✗ Test suite failed with error:', error);
            console.log('\n========================================');
            console.log(`  Results: ${passCount}/${testCount} tests passed`);
            console.log(`  ✗ ${failCount + 1} tests failed`);
            console.log('========================================');
            
            return {
                total: testCount,
                passed: passCount,
                failed: failCount + 1,
                results: testResults,
                error: error.message,
            };
        }
    }

    return {
        runAllTests,
    };
})();

// Auto-run tests if loaded in browser console
if (typeof window !== 'undefined' && window.OrgEncryption) {
    console.log('OrgEncryption module detected. Run CryptoTests.runAllTests() to test.');
}

// Export for Node.js/module environments
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CryptoTests;
}
