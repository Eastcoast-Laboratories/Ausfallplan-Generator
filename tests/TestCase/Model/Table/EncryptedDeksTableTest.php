<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\EncryptedDeksTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\EncryptedDeksTable Test Case
 */
class EncryptedDeksTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\EncryptedDeksTable
     */
    protected $EncryptedDeks;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.EncryptedDeks',
        'app.Organizations',
        'app.Users',
        'app.OrganizationUsers',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('EncryptedDeks') ? [] : ['className' => EncryptedDeksTable::class];
        $this->EncryptedDeks = $this->getTableLocator()->get('EncryptedDeks', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->EncryptedDeks);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault(): void
    {
        $validator = $this->EncryptedDeks->validationDefault(new \Cake\Validation\Validator());
        
        $this->assertTrue($validator->hasField('organization_id'));
        $this->assertTrue($validator->hasField('user_id'));
        $this->assertTrue($validator->hasField('wrapped_dek'));
    }

    /**
     * Test getForUser method
     *
     * @return void
     */
    public function testGetForUser(): void
    {
        $result = $this->EncryptedDeks->getForUser(1, 1);
        
        $this->assertNotNull($result);
        $this->assertEquals(1, $result->organization_id);
        $this->assertEquals(1, $result->user_id);
        $this->assertEquals('base64_encoded_wrapped_dek_for_user_1', $result->wrapped_dek);
    }

    /**
     * Test getForUser with non-existent user
     *
     * @return void
     */
    public function testGetForUserNotFound(): void
    {
        $result = $this->EncryptedDeks->getForUser(999, 999);
        
        $this->assertNull($result);
    }

    /**
     * Test setForUser method - create new
     *
     * @return void
     */
    public function testSetForUserCreate(): void
    {
        $result = $this->EncryptedDeks->setForUser(1, 2, 'new_wrapped_dek');
        
        $this->assertNotFalse($result);
        $this->assertEquals(1, $result->organization_id);
        $this->assertEquals(2, $result->user_id);
        $this->assertEquals('new_wrapped_dek', $result->wrapped_dek);
        
        // Verify it was saved
        $saved = $this->EncryptedDeks->getForUser(1, 2);
        $this->assertNotNull($saved);
        $this->assertEquals('new_wrapped_dek', $saved->wrapped_dek);
    }

    /**
     * Test setForUser method - update existing
     *
     * @return void
     */
    public function testSetForUserUpdate(): void
    {
        // First create
        $this->EncryptedDeks->setForUser(1, 3, 'original_dek');
        
        // Now update
        $result = $this->EncryptedDeks->setForUser(1, 3, 'updated_dek');
        
        $this->assertNotFalse($result);
        $this->assertEquals('updated_dek', $result->wrapped_dek);
        
        // Verify only one record exists
        $count = $this->EncryptedDeks->find()
            ->where(['organization_id' => 1, 'user_id' => 3])
            ->count();
        $this->assertEquals(1, $count);
    }

    /**
     * Test revokeForUser method
     *
     * @return void
     */
    public function testRevokeForUser(): void
    {
        // Create a DEK for a user
        $this->EncryptedDeks->setForUser(1, 4, 'to_be_revoked');
        
        // Verify it exists
        $before = $this->EncryptedDeks->getForUser(1, 4);
        $this->assertNotNull($before);
        
        // Revoke
        $result = $this->EncryptedDeks->revokeForUser(1, 4);
        $this->assertTrue($result);
        
        // Verify it's gone
        $after = $this->EncryptedDeks->getForUser(1, 4);
        $this->assertNull($after);
    }

    /**
     * Test revokeForUser with non-existent user
     *
     * @return void
     */
    public function testRevokeForUserNotFound(): void
    {
        $result = $this->EncryptedDeks->revokeForUser(999, 999);
        $this->assertTrue($result); // Should return true even if not found
    }

    /**
     * Test unique constraint on organization_id + user_id
     *
     * @return void
     */
    public function testUniqueConstraint(): void
    {
        // Try to create duplicate
        $entity1 = $this->EncryptedDeks->newEntity([
            'organization_id' => 1,
            'user_id' => 1,
            'wrapped_dek' => 'duplicate_dek',
        ]);
        
        $result = $this->EncryptedDeks->save($entity1);
        
        // Should fail due to unique constraint
        $this->assertFalse($result);
    }

    /**
     * Test foreign key relationships
     *
     * @return void
     */
    public function testAssociations(): void
    {
        $this->assertTrue($this->EncryptedDeks->associations()->has('Organizations'));
        $this->assertTrue($this->EncryptedDeks->associations()->has('Users'));
    }
}
