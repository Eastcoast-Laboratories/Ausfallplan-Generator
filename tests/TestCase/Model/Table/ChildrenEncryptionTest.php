<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ChildrenTable;
use Cake\TestSuite\TestCase;

/**
 * Test Children Encryption Features
 */
class ChildrenEncryptionTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ChildrenTable
     */
    protected $Children;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.Organizations',
        'app.Children',
        'app.SiblingGroups',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Children') ? [] : ['className' => ChildrenTable::class];
        $this->Children = $this->getTableLocator()->get('Children', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Children);
        parent::tearDown();
    }

    /**
     * Test that encrypted name fields can be stored
     *
     * @return void
     */
    public function testStoreEncryptedName(): void
    {
        $child = $this->Children->newEntity([
            'organization_id' => 1,
            'name' => 'PlaintextName', // Keep for compatibility
            'name_encrypted' => 'base64_encrypted_data',
            'name_iv' => 'base64_iv_data',
            'name_tag' => 'base64_tag_data',
        ]);
        
        $saved = $this->Children->save($child);
        $this->assertNotFalse($saved);
        $this->assertEquals('base64_encrypted_data', $saved->name_encrypted);
        $this->assertEquals('base64_iv_data', $saved->name_iv);
        $this->assertEquals('base64_tag_data', $saved->name_tag);
    }

    /**
     * Test that child can exist with only plaintext name (encryption disabled)
     *
     * @return void
     */
    public function testStorePlaintextNameOnly(): void
    {
        $child = $this->Children->newEntity([
            'organization_id' => 2, // Organization with encryption disabled
            'name' => 'John Doe',
            'name_encrypted' => null,
            'name_iv' => null,
            'name_tag' => null,
        ]);
        
        $saved = $this->Children->save($child);
        $this->assertNotFalse($saved);
        $this->assertEquals('John Doe', $saved->name);
        $this->assertNull($saved->name_encrypted);
        $this->assertNull($saved->name_iv);
        $this->assertNull($saved->name_tag);
    }

    /**
     * Test that both plaintext and encrypted can coexist (for migration)
     *
     * @return void
     */
    public function testBothPlaintextAndEncrypted(): void
    {
        $child = $this->Children->newEntity([
            'organization_id' => 1,
            'name' => 'John Doe', // Plaintext for backward compatibility
            'name_encrypted' => 'encrypted_john_doe',
            'name_iv' => 'iv_data',
            'name_tag' => 'tag_data',
        ]);
        
        $saved = $this->Children->save($child);
        $this->assertNotFalse($saved);
        $this->assertEquals('John Doe', $saved->name);
        $this->assertEquals('encrypted_john_doe', $saved->name_encrypted);
    }

    /**
     * Test retrieval of child with encrypted fields
     *
     * @return void
     */
    public function testRetrieveEncryptedChild(): void
    {
        // First create
        $child = $this->Children->newEntity([
            'organization_id' => 1,
            'name' => 'Test Child',
            'name_encrypted' => 'encrypted_test_child',
            'name_iv' => 'test_iv',
            'name_tag' => 'test_tag',
        ]);
        
        $saved = $this->Children->save($child);
        $this->assertNotFalse($saved);
        
        // Now retrieve
        $retrieved = $this->Children->get($saved->id);
        $this->assertEquals('encrypted_test_child', $retrieved->name_encrypted);
        $this->assertEquals('test_iv', $retrieved->name_iv);
        $this->assertEquals('test_tag', $retrieved->name_tag);
    }

    /**
     * Test that encrypted fields are accessible
     *
     * @return void
     */
    public function testEncryptedFieldsAccessible(): void
    {
        $child = $this->Children->newEmptyEntity();
        
        $this->assertTrue($child->isAccessible('name_encrypted'));
        $this->assertTrue($child->isAccessible('name_iv'));
        $this->assertTrue($child->isAccessible('name_tag'));
    }

    /**
     * Test updating encrypted fields
     *
     * @return void
     */
    public function testUpdateEncryptedFields(): void
    {
        // Create initial
        $child = $this->Children->newEntity([
            'organization_id' => 1,
            'name' => 'Original Name',
            'name_encrypted' => 'original_encrypted',
            'name_iv' => 'original_iv',
            'name_tag' => 'original_tag',
        ]);
        
        $saved = $this->Children->save($child);
        $this->assertNotFalse($saved);
        
        // Update encrypted fields
        $saved->name_encrypted = 'updated_encrypted';
        $saved->name_iv = 'updated_iv';
        $saved->name_tag = 'updated_tag';
        
        $updated = $this->Children->save($saved);
        $this->assertNotFalse($updated);
        $this->assertEquals('updated_encrypted', $updated->name_encrypted);
        $this->assertEquals('updated_iv', $updated->name_iv);
        $this->assertEquals('updated_tag', $updated->name_tag);
    }

    /**
     * Test that we can clear encrypted fields (when disabling encryption)
     *
     * @return void
     */
    public function testClearEncryptedFields(): void
    {
        // Create with encrypted fields
        $child = $this->Children->newEntity([
            'organization_id' => 1,
            'name' => 'Test Name',
            'name_encrypted' => 'encrypted_data',
            'name_iv' => 'iv_data',
            'name_tag' => 'tag_data',
        ]);
        
        $saved = $this->Children->save($child);
        $this->assertNotFalse($saved);
        
        // Clear encrypted fields
        $saved->name_encrypted = null;
        $saved->name_iv = null;
        $saved->name_tag = null;
        
        $updated = $this->Children->save($saved);
        $this->assertNotFalse($updated);
        $this->assertNull($updated->name_encrypted);
        $this->assertNull($updated->name_iv);
        $this->assertNull($updated->name_tag);
    }
}
