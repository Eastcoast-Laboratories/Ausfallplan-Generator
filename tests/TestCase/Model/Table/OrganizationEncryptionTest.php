<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\OrganizationsTable;
use Cake\TestSuite\TestCase;

/**
 * Test Organization Encryption Features
 */
class OrganizationEncryptionTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\OrganizationsTable
     */
    protected $Organizations;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.Organizations',
        'app.Users',
        'app.OrganizationUsers',
        'app.EncryptedDeks',
        'app.Children',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Organizations') ? [] : ['className' => OrganizationsTable::class];
        $this->Organizations = $this->getTableLocator()->get('Organizations', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Organizations);
        parent::tearDown();
    }

    /**
     * Test that encryption_enabled field exists and has default value
     *
     * @return void
     */
    public function testEncryptionEnabledDefault(): void
    {
        $org = $this->Organizations->newEntity([
            'name' => 'Test Org With Encryption',
            'encryption_enabled' => true,
        ]);
        
        $saved = $this->Organizations->save($org);
        $this->assertNotFalse($saved);
        $this->assertTrue($saved->encryption_enabled);
    }

    /**
     * Test that encryption can be disabled
     *
     * @return void
     */
    public function testEncryptionCanBeDisabled(): void
    {
        $org = $this->Organizations->newEntity([
            'name' => 'Test Org Without Encryption',
            'encryption_enabled' => false,
        ]);
        
        $saved = $this->Organizations->save($org);
        $this->assertNotFalse($saved);
        $this->assertFalse($saved->encryption_enabled);
    }

    /**
     * Test organization with encryption enabled can have encrypted DEKs
     *
     * @return void
     */
    public function testOrganizationWithEncryptedDeks(): void
    {
        $org = $this->Organizations->get(1, [
            'contain' => ['EncryptedDeks'],
        ]);
        
        $this->assertTrue($org->encryption_enabled);
        $this->assertNotEmpty($org->encrypted_deks);
        $this->assertCount(1, $org->encrypted_deks);
    }

    /**
     * Test organization encryption toggle
     *
     * @return void
     */
    public function testToggleEncryption(): void
    {
        $org = $this->Organizations->get(1);
        $this->assertTrue($org->encryption_enabled);
        
        // Disable encryption
        $org->encryption_enabled = false;
        $saved = $this->Organizations->save($org);
        $this->assertNotFalse($saved);
        $this->assertFalse($saved->encryption_enabled);
        
        // Re-enable encryption
        $org->encryption_enabled = true;
        $saved = $this->Organizations->save($org);
        $this->assertNotFalse($saved);
        $this->assertTrue($saved->encryption_enabled);
    }

    /**
     * Test that organization has association with EncryptedDeks
     *
     * @return void
     */
    public function testEncryptedDeksAssociation(): void
    {
        $this->assertTrue($this->Organizations->associations()->has('EncryptedDeks'));
        
        $association = $this->Organizations->associations()->get('EncryptedDeks');
        $this->assertEquals('oneToMany', $association->type());
    }

    /**
     * Test validation of encryption_enabled field
     *
     * @return void
     */
    public function testEncryptionEnabledValidation(): void
    {
        $validator = $this->Organizations->validationDefault(new \Cake\Validation\Validator());
        $this->assertTrue($validator->hasField('encryption_enabled'));
    }
}
