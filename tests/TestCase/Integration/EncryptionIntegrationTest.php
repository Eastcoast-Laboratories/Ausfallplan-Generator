<?php
declare(strict_types=1);

namespace App\Test\TestCase\Integration;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Encryption Integration Test
 *
 * Tests the complete encryption flow including:
 * - Organization with encryption enabled stores encrypted data
 * - Organization with encryption disabled stores plaintext data
 * - Multiple users can access same encrypted data
 * - Encryption toggle works correctly
 */
class EncryptionIntegrationTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Organizations',
        'app.Users',
        'app.OrganizationUsers',
        'app.Children',
        'app.EncryptedDeks',
        'app.SiblingGroups',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
    }

    /**
     * Test that organization with encryption enabled exists
     *
     * @return void
     */
    public function testOrganizationWithEncryptionEnabled(): void
    {
        $organizations = $this->getTableLocator()->get('Organizations');
        $org = $organizations->get(1);
        
        $this->assertTrue($org->encryption_enabled, 'Organization 1 has encryption enabled');
    }

    /**
     * Test that organization with encryption disabled exists
     *
     * @return void
     */
    public function testOrganizationWithEncryptionDisabled(): void
    {
        $organizations = $this->getTableLocator()->get('Organizations');
        $org = $organizations->get(2);
        
        $this->assertFalse($org->encryption_enabled, 'Organization 2 has encryption disabled');
    }

    /**
     * Test storing child with encryption enabled
     * 
     * This test validates that when encryption is enabled:
     * - Encrypted fields can be stored
     * - Data is properly persisted
     *
     * @return void
     */
    public function testStoreChildWithEncryption(): void
    {
        $children = $this->getTableLocator()->get('Children');
        
        $child = $children->newEntity([
            'organization_id' => 1, // Encryption enabled
            'name' => 'Test Child',
            'name_encrypted' => 'encrypted_base64_data',
            'name_iv' => 'iv_base64_data',
            'name_tag' => 'tag_base64_data',
        ]);
        
        $result = $children->save($child);
        $this->assertNotFalse($result, 'Child with encrypted data saved');
        
        // Retrieve and verify
        $saved = $children->get($result->id);
        $this->assertEquals('encrypted_base64_data', $saved->name_encrypted);
        $this->assertEquals('iv_base64_data', $saved->name_iv);
        $this->assertEquals('tag_base64_data', $saved->name_tag);
    }

    /**
     * Test storing child without encryption (plaintext mode)
     *
     * @return void
     */
    public function testStoreChildWithoutEncryption(): void
    {
        $children = $this->getTableLocator()->get('Children');
        
        $child = $children->newEntity([
            'organization_id' => 2, // Encryption disabled
            'name' => 'Plain Text Child',
            'name_encrypted' => null,
            'name_iv' => null,
            'name_tag' => null,
        ]);
        
        $result = $children->save($child);
        $this->assertNotFalse($result, 'Child with plaintext saved');
        
        // Verify plaintext is stored
        $saved = $children->get($result->id);
        $this->assertEquals('Plain Text Child', $saved->name);
        $this->assertNull($saved->name_encrypted);
        $this->assertNull($saved->name_iv);
        $this->assertNull($saved->name_tag);
    }

    /**
     * Test encrypted DEK storage for multiple users
     *
     * @return void
     */
    public function testMultipleUsersHaveWrappedDeks(): void
    {
        $encryptedDeks = $this->getTableLocator()->get('EncryptedDeks');
        
        // User 1 should have wrapped DEK for organization 1
        $dek1 = $encryptedDeks->getForUser(1, 1);
        $this->assertNotNull($dek1, 'User 1 has wrapped DEK for org 1');
        
        // Create wrapped DEK for second user
        $result = $encryptedDeks->setForUser(1, 2, 'wrapped_dek_for_user_2');
        $this->assertNotFalse($result, 'Wrapped DEK created for user 2');
        
        // Both users have different wrapped DEKs for same organization
        $dek2 = $encryptedDeks->getForUser(1, 2);
        $this->assertNotNull($dek2, 'User 2 has wrapped DEK for org 1');
        $this->assertNotEquals($dek1->wrapped_dek, $dek2->wrapped_dek, 'Users have different wrapped DEKs');
    }

    /**
     * Test revoking user access by deleting wrapped DEK
     *
     * @return void
     */
    public function testRevokeUserAccess(): void
    {
        $encryptedDeks = $this->getTableLocator()->get('EncryptedDeks');
        
        // Create DEK for a user
        $encryptedDeks->setForUser(1, 3, 'wrapped_dek_for_user_3');
        $before = $encryptedDeks->getForUser(1, 3);
        $this->assertNotNull($before, 'User 3 has access');
        
        // Revoke access
        $result = $encryptedDeks->revokeForUser(1, 3);
        $this->assertTrue($result, 'Access revoked');
        
        // Verify access is gone
        $after = $encryptedDeks->getForUser(1, 3);
        $this->assertNull($after, 'User 3 no longer has access');
    }

    /**
     * Test toggling encryption on organization
     *
     * @return void
     */
    public function testToggleOrganizationEncryption(): void
    {
        $organizations = $this->getTableLocator()->get('Organizations');
        $org = $organizations->get(1);
        
        // Initially enabled
        $this->assertTrue($org->encryption_enabled);
        
        // Disable
        $org->encryption_enabled = false;
        $result = $organizations->save($org);
        $this->assertNotFalse($result, 'Organization encryption disabled');
        
        // Verify
        $updated = $organizations->get(1);
        $this->assertFalse($updated->encryption_enabled);
        
        // Re-enable
        $updated->encryption_enabled = true;
        $result = $organizations->save($updated);
        $this->assertNotFalse($result, 'Organization encryption re-enabled');
        
        // Verify
        $final = $organizations->get(1);
        $this->assertTrue($final->encryption_enabled);
    }

    /**
     * Test lazy migration scenario: existing plaintext, add encryption
     *
     * @return void
     */
    public function testLazyMigrationPlaintextToEncrypted(): void
    {
        $children = $this->getTableLocator()->get('Children');
        
        // Create child with only plaintext (simulating old data)
        $child = $children->newEntity([
            'organization_id' => 1,
            'name' => 'Old Plaintext Name',
            'name_encrypted' => null,
            'name_iv' => null,
            'name_tag' => null,
        ]);
        
        $saved = $children->save($child);
        $this->assertNotFalse($saved);
        
        // Simulate lazy migration: add encrypted fields on edit
        $saved->name_encrypted = 'newly_encrypted_data';
        $saved->name_iv = 'new_iv';
        $saved->name_tag = 'new_tag';
        
        $updated = $children->save($saved);
        $this->assertNotFalse($updated, 'Encrypted fields added');
        
        // Verify both plaintext (for compatibility) and encrypted exist
        $final = $children->get($saved->id);
        $this->assertEquals('Old Plaintext Name', $final->name);
        $this->assertEquals('newly_encrypted_data', $final->name_encrypted);
    }

    /**
     * Test that encrypted fields are nullable
     *
     * @return void
     */
    public function testEncryptedFieldsAreNullable(): void
    {
        $children = $this->getTableLocator()->get('Children');
        
        // Create child without encrypted fields (encryption disabled mode)
        $child = $children->newEntity([
            'organization_id' => 2,
            'name' => 'No Encryption Child',
        ]);
        
        $saved = $children->save($child);
        $this->assertNotFalse($saved);
        
        // Verify encrypted fields are null
        $final = $children->get($saved->id);
        $this->assertNull($final->name_encrypted);
        $this->assertNull($final->name_iv);
        $this->assertNull($final->name_tag);
    }

    /**
     * Test that organization can have multiple wrapped DEKs
     *
     * @return void
     */
    public function testOrganizationHasMultipleWrappedDeks(): void
    {
        $organizations = $this->getTableLocator()->get('Organizations');
        $encryptedDeks = $this->getTableLocator()->get('EncryptedDeks');
        
        // Add DEKs for multiple users
        $encryptedDeks->setForUser(1, 1, 'dek_for_user_1');
        $encryptedDeks->setForUser(1, 2, 'dek_for_user_2');
        
        // Load organization with DEKs
        $org = $organizations->get(1, [
            'contain' => ['EncryptedDeks'],
        ]);
        
        $this->assertNotEmpty($org->encrypted_deks);
        $this->assertGreaterThanOrEqual(2, count($org->encrypted_deks));
    }

    /**
     * Test user has encryption keys fields
     *
     * @return void
     */
    public function testUserCanHaveEncryptionKeys(): void
    {
        $users = $this->getTableLocator()->get('Users');
        
        // Update user with encryption keys
        $user = $users->get(1);
        $user->public_key = 'public_key_base64';
        $user->encrypted_private_key = 'encrypted_private_key_base64';
        $user->key_salt = 'salt_base64';
        
        $result = $users->save($user);
        $this->assertNotFalse($result, 'User encryption keys saved');
        
        // Verify
        $saved = $users->get(1);
        $this->assertEquals('public_key_base64', $saved->public_key);
        $this->assertEquals('encrypted_private_key_base64', $saved->encrypted_private_key);
        $this->assertEquals('salt_base64', $saved->key_salt);
    }

    /**
     * Test that user encryption keys are hidden from JSON
     *
     * @return void
     */
    public function testUserEncryptionKeysHiddenInJson(): void
    {
        $users = $this->getTableLocator()->get('Users');
        $user = $users->get(1);
        
        $user->public_key = 'public_key_data';
        $user->encrypted_private_key = 'private_key_data';
        $user->key_salt = 'salt_data';
        $users->save($user);
        
        // Get as JSON
        $json = json_decode(json_encode($user), true);
        
        // Private key and salt should be hidden
        $this->assertArrayNotHasKey('encrypted_private_key', $json);
        $this->assertArrayNotHasKey('key_salt', $json);
        
        // Public key is visible (it's public)
        $this->assertArrayHasKey('public_key', $json);
    }
}
