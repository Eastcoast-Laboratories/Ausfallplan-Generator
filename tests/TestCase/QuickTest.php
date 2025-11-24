<?php
declare(strict_types=1);

namespace App\Test\TestCase;

use Cake\TestSuite\TestCase;

/**
 * Quick Test - Fast sanity check for all major modules
 * 
 * Run this at the end of a task to verify nothing is broken.
 * Much faster than running the full test suite.
 * 
 * Run command:
 * docker compose -f docker/docker-compose.yml exec -T app vendor/bin/phpunit tests/TestCase/QuickTest.php
 */
class QuickTest extends TestCase
{
    protected array $fixtures = [
        'app.Organizations',
        'app.Users',
        'app.Children',
        'app.SiblingGroups',
        'app.Schedules',
        'app.Rules',
        'app.OrganizationUsers',
    ];

    /**
     * Test Organizations module
     */
    public function testOrganizationsModule(): void
    {
        $organizations = $this->getTableLocator()->get('Organizations');
        
        // Create
        $org = $organizations->newEntity(['name' => 'Quick Test Org']);
        $this->assertTrue($organizations->save($org) !== false);
        
        // Read
        $found = $organizations->get($org->id);
        $this->assertEquals('Quick Test Org', $found->name);
        
        // Update
        $found->name = 'Updated Org';
        $this->assertTrue($organizations->save($found) !== false);
        
        // Delete
        $this->assertTrue($organizations->delete($found));
    }

    /**
     * Test Children module
     */
    public function testChildrenModule(): void
    {
        $children = $this->getTableLocator()->get('Children');
        $organizations = $this->getTableLocator()->get('Organizations');
        
        // Create org first
        $org = $organizations->newEntity(['name' => 'Test Org']);
        $organizations->save($org);
        
        // Create child
        $child = $children->newEntity([
            'organization_id' => $org->id,
            'name' => 'Test Child',
            'is_integrative' => false,
            'is_active' => true,
        ]);
        $this->assertTrue($children->save($child) !== false);
        
        // Read
        $found = $children->get($child->id);
        $this->assertEquals('Test Child', $found->name);
        
        // Update
        $found->name = 'Updated Child';
        $this->assertTrue($children->save($found) !== false);
        
        // Delete
        $this->assertTrue($children->delete($found));
    }

    /**
     * Test Schedules module
     */
    public function testSchedulesModule(): void
    {
        $schedules = $this->getTableLocator()->get('Schedules');
        $organizations = $this->getTableLocator()->get('Organizations');
        
        // Create org first
        $org = $organizations->newEntity(['name' => 'Test Org']);
        $organizations->save($org);
        
        // Create schedule
        $schedule = $schedules->newEntity([
            'organization_id' => $org->id,
            'title' => 'Test Schedule',
            'starts_on' => '2025-01-01',
            'ends_on' => '2025-01-31',
            'days_count' => 5,
            'state' => 'draft',
            'animal_names_sequence' => '',
        ]);
        $this->assertTrue($schedules->save($schedule) !== false);
        
        // Read
        $found = $schedules->get($schedule->id);
        $this->assertEquals('Test Schedule', $found->title);
        
        // Update
        $found->title = 'Updated Schedule';
        $this->assertTrue($schedules->save($found) !== false);
        
        // Delete
        $this->assertTrue($schedules->delete($found));
    }

    /**
     * Test Users module
     */
    public function testUsersModule(): void
    {
        $users = $this->getTableLocator()->get('Users');
        
        // Create
        $user = $users->newEntity([
            'email' => 'quicktest@example.com',
            'password' => 'test123456',
            'is_system_admin' => false,
            'email_verified' => 1,
            'status' => 'active',
        ]);
        $this->assertTrue($users->save($user) !== false);
        
        // Read
        $found = $users->get($user->id);
        $this->assertEquals('quicktest@example.com', $found->email);
        
        // Update
        $found->email = 'updated@example.com';
        $this->assertTrue($users->save($found) !== false);
        
        // Delete
        $this->assertTrue($users->delete($found));
    }

    /**
     * Test SiblingGroups module
     */
    public function testSiblingGroupsModule(): void
    {
        $siblingGroups = $this->getTableLocator()->get('SiblingGroups');
        $organizations = $this->getTableLocator()->get('Organizations');
        
        // Create org first
        $org = $organizations->newEntity(['name' => 'Test Org']);
        $organizations->save($org);
        
        // Create sibling group (no name field, just org_id)
        $group = $siblingGroups->newEntity([
            'organization_id' => $org->id,
        ]);
        $saved = $siblingGroups->save($group);
        $this->assertTrue($saved !== false);
        
        // Read
        $found = $siblingGroups->get($group->id);
        $this->assertEquals($org->id, $found->organization_id);
        
        // Delete
        $this->assertTrue($siblingGroups->delete($found));
    }

    /**
     * Test OrganizationUsers module (role system)
     */
    public function testOrganizationUsersModule(): void
    {
        $orgUsers = $this->getTableLocator()->get('OrganizationUsers');
        $organizations = $this->getTableLocator()->get('Organizations');
        $users = $this->getTableLocator()->get('Users');
        
        // Create org and user
        $org = $organizations->newEntity(['name' => 'Test Org']);
        $organizations->save($org);
        
        $user = $users->newEntity([
            'email' => 'roletest@example.com',
            'password' => 'test123456',
            'is_system_admin' => false,
            'email_verified' => 1,
            'status' => 'active',
        ]);
        $users->save($user);
        
        // Create org-user relationship with joined_at
        $orgUser = $orgUsers->newEntity([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'role' => 'editor',
            'is_primary' => true,
            'joined_at' => new \DateTime(),
        ]);
        $saved = $orgUsers->save($orgUser);
        $this->assertTrue($saved !== false, 'Failed to save OrganizationUser');
        
        // Read
        $found = $orgUsers->get($orgUser->id);
        $this->assertEquals('editor', $found->role);
        
        // Update
        $found->role = 'org_admin';
        $this->assertTrue($orgUsers->save($found) !== false);
        
        // Verify role system works
        $this->assertContains($found->role, ['viewer', 'editor', 'org_admin']);
    }
}
