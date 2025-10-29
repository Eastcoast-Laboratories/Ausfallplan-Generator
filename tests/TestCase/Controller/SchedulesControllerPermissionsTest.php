<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\SchedulesController Permissions Test Case
 *
 * Tests that editors can only access their own organization's schedules
 */
class SchedulesControllerPermissionsTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Organizations',
        'app.Users',
        'app.OrganizationUsers',
        'app.Schedules',
        'app.Children',
    ];

    /**
     * Test that editor can view own schedule
     */
    public function testEditorCanViewOwnSchedule(): void
    {
        // Create editor user
        $this->createAndLoginUser('editor-view@test.com', 'editor', 1);
        $this->session(['Config.language' => 'en']);

        // Try to view schedule from own organization
        $this->get('/schedules/view/1'); // Schedule 1 belongs to org 1
        $this->assertResponseOk();
    }

    /**
     * Test that editor cannot view other organization's schedule
     */
    public function testEditorCannotViewOtherOrgSchedule(): void
    {
        // Create editor user from organization 1
        $this->createAndLoginUser('editor-other@test.com', 'editor', 1);
        $this->session(['Config.language' => 'en']);

        // Try to view schedule from organization 2
        $this->get('/schedules/view/2'); // Schedule 2 belongs to org 2
        // Should be blocked - either 403 or 302 redirect is acceptable
        $this->assertTrue(
            $this->_response->getStatusCode() >= 302 && $this->_response->getStatusCode() <= 403,
            'Expected 302 redirect or 403 forbidden, got ' . $this->_response->getStatusCode()
        );
    }

    /**
     * Test that editor can edit own schedule
     */
    public function testEditorCanEditOwnSchedule(): void
    {
        // Create editor user
        $this->createAndLoginUser('editor-edit@test.com', 'editor', 1);
        $this->session(['Config.language' => 'en']);

        $this->get('/schedules/edit/1');
        $this->assertResponseOk();
    }

    /**
     * Test that editor cannot edit other organization's schedule
     */
    public function testEditorCannotEditOtherOrgSchedule(): void
    {
        // Create editor user from organization 1
        $this->createAndLoginUser('editor-noedit@test.com', 'editor', 1);
        $this->session(['Config.language' => 'en']);

        $this->get('/schedules/edit/2'); // Schedule 2 belongs to org 2
        // Should be blocked - either 403 or 302 redirect is acceptable
        $this->assertTrue(
            $this->_response->getStatusCode() >= 302 && $this->_response->getStatusCode() <= 403,
            'Expected 302 redirect or 403 forbidden, got ' . $this->_response->getStatusCode()
        );
    }

    /**
     * Test that editor cannot delete other organization's schedule
     */
    public function testEditorCannotDeleteOtherOrgSchedule(): void
    {
        // Create editor user from organization 1
        $this->createAndLoginUser('editor-nodelete@test.com', 'editor', 1);
        $this->session(['Config.language' => 'en']);

        $this->post('/schedules/delete/2'); // Schedule 2 belongs to org 2
        $this->assertResponseError();
    }

    /**
     * Test that admin can view all schedules
     */
    public function testAdminCanViewAllSchedules(): void
    {
        // Create system admin
        $users = $this->getTableLocator()->get('Users');
        $admin = $users->newEntity([
            'email' => 'sysadmin-test@example.com',
            'password' => '84hbfUb_3dsf',
            'is_system_admin' => true,
            'status' => 'active',
            'email_verified' => 1,
            'email_token' => null,
            'approved_at' => new \DateTime(),
            'approved_by' => null,
        ]);
        $users->save($admin);
        
        $this->session(['Auth' => $admin]);
        $this->session(['Config.language' => 'en']);

        // Admin should be able to view schedule from any organization
        $this->get('/schedules/view/2'); // Schedule 2 belongs to org 2
        $this->assertResponseOk();
    }

    /**
     * Test that viewer can only view, not edit
     */
    public function testViewerCannotEdit(): void
    {
        // Create viewer user
        $this->createAndLoginUser('viewer-test@test.com', 'viewer', 1);
        $this->session(['Config.language' => 'en']);

        $this->get('/schedules/edit/1');
        // Viewer should be blocked - either 403 or 302 redirect is acceptable
        $this->assertTrue(
            $this->_response->getStatusCode() >= 302 && $this->_response->getStatusCode() <= 403,
            'Expected 302 redirect or 403 forbidden, got ' . $this->_response->getStatusCode()
        );
    }

    /**
     * Test that index filters schedules by organization for editor
     */
    public function testIndexFiltersByOrganization(): void
    {
        // Create editor user
        $this->createAndLoginUser('editor-index@test.com', 'editor', 1);
        $this->session(['Config.language' => 'en']);

        $this->get('/schedules');
        $this->assertResponseOk();
        
        // Response should only contain schedules from org 1
        $viewVariable = $this->viewVariable('schedules');
        $this->assertNotEmpty($viewVariable);
        
        foreach ($viewVariable as $schedule) {
            $this->assertEquals(1, $schedule->organization_id);
        }
    }

    /**
     * Helper: Create user with organization membership and log in
     */
    private function createAndLoginUser(string $email, string $role = 'org_admin', int $orgId = 1): void
    {
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'email' => $email,
            'password' => '84hbfUb_3dsf',
            'is_system_admin' => false,
            'status' => 'active',
            'email_verified' => 1,
            'email_token' => null,
            'approved_at' => new \DateTime(),
            'approved_by' => null,
        ]);
        $users->save($user);
        
        $orgUsers = $this->getTableLocator()->get('OrganizationUsers');
        $orgUsers->save($orgUsers->newEntity([
            'organization_id' => $orgId,
            'user_id' => $user->id,
            'role' => $role,
            'is_primary' => true,
            'joined_at' => new \DateTime(),
        ]));
        
        // Set session with correct format (just the user entity)
        $this->session(['Auth' => $user]);
    }
}
