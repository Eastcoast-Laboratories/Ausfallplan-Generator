<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Cake\I18n\DateTime;

/**
 * App\Model\Table\OrganizationUsersTable Test Case
 */
class OrganizationUsersTableTest extends TestCase
{
    protected array $fixtures = [
        'app.Organizations',
        'app.Users',
        'app.OrganizationUsers',
    ];

    protected $OrganizationUsers;

    public function setUp(): void
    {
        parent::setUp();
        
        // Set English locale for tests
        \Cake\I18n\I18n::setLocale('en_US');
        $this->OrganizationUsers = $this->getTableLocator()->get('OrganizationUsers');
    }

    public function tearDown(): void
    {
        unset($this->OrganizationUsers);
        parent::tearDown();
    }

    /**
     * Test that user can be added to organization
     */
    public function testAddUserToOrganization(): void
    {
        $data = [
            'organization_id' => 1,
            'user_id' => 1,
            'role' => 'editor',
            'is_primary' => true,
            'joined_at' => new DateTime(),
        ];

        $orgUser = $this->OrganizationUsers->newEntity($data);
        $result = $this->OrganizationUsers->save($orgUser);

        $this->assertNotFalse($result);
        $this->assertEquals('editor', $result->role);
    }

    /**
     * Test that user cannot be added twice to same organization
     */
    public function testCannotAddUserTwiceToSameOrganization(): void
    {
        $data = [
            'organization_id' => 1,
            'user_id' => 1,
            'role' => 'editor',
            'is_primary' => false,
            'joined_at' => new DateTime(),
        ];

        // First time should work
        $orgUser1 = $this->OrganizationUsers->newEntity($data);
        $this->OrganizationUsers->save($orgUser1);

        // Second time should fail
        $orgUser2 = $this->OrganizationUsers->newEntity($data);
        $result = $this->OrganizationUsers->save($orgUser2);

        $this->assertFalse($result);
        $this->assertNotEmpty($orgUser2->getErrors());
    }

    /**
     * Test role validation
     */
    public function testInvalidRoleIsRejected(): void
    {
        $data = [
            'organization_id' => 1,
            'user_id' => 1,
            'role' => 'invalid_role',
            'is_primary' => false,
            'joined_at' => new DateTime(),
        ];

        $orgUser = $this->OrganizationUsers->newEntity($data);
        $result = $this->OrganizationUsers->save($orgUser);

        $this->assertFalse($result);
        $this->assertArrayHasKey('role', $orgUser->getErrors());
    }

    /**
     * Test that valid roles are accepted
     */
    public function testValidRolesAreAccepted(): void
    {
        $roles = ['org_admin', 'editor', 'viewer'];

        foreach ($roles as $index => $role) {
            $data = [
                'organization_id' => 1,
                'user_id' => $index + 2, // Different user each time
                'role' => $role,
                'is_primary' => false,
                'joined_at' => new DateTime(),
            ];

            $orgUser = $this->OrganizationUsers->newEntity($data);
            $result = $this->OrganizationUsers->save($orgUser);

            $this->assertNotFalse($result, "Role {$role} should be valid");
        }
    }
}
