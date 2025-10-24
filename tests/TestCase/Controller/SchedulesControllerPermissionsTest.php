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
        'app.Schedules',
        'app.ScheduleDays',
        'app.Children',
        'app.Assignments',
    ];

    /**
     * Test that editor can view own schedule
     */
    public function testEditorCanViewOwnSchedule(): void
    {
        // Login as editor from organization 1
        $this->session([
            'Auth' => [
                'id' => 2, // editor user
                'email' => 'editor@org1.com',
                'role' => 'editor',
                'organization_id' => 1,
            ]
        ]);

        // Try to view schedule from own organization
        $this->get('/schedules/view/1'); // Schedule 1 belongs to org 1
        $this->assertResponseOk();
    }

    /**
     * Test that editor cannot view other organization's schedule
     */
    public function testEditorCannotViewOtherOrgSchedule(): void
    {
        // Login as editor from organization 1
        $this->session([
            'Auth' => [
                'id' => 2,
                'email' => 'editor@org1.com',
                'role' => 'editor',
                'organization_id' => 1,
            ]
        ]);

        // Try to view schedule from organization 2
        $this->get('/schedules/view/2'); // Schedule 2 belongs to org 2
        $this->assertResponseError(); // Should be 403 or redirect
    }

    /**
     * Test that editor can edit own schedule
     */
    public function testEditorCanEditOwnSchedule(): void
    {
        $this->session([
            'Auth' => [
                'id' => 2,
                'email' => 'editor@org1.com',
                'role' => 'editor',
                'organization_id' => 1,
            ]
        ]);

        $this->get('/schedules/edit/1');
        $this->assertResponseOk();
    }

    /**
     * Test that editor cannot edit other organization's schedule
     */
    public function testEditorCannotEditOtherOrgSchedule(): void
    {
        $this->session([
            'Auth' => [
                'id' => 2,
                'email' => 'editor@org1.com',
                'role' => 'editor',
                'organization_id' => 1,
            ]
        ]);

        $this->get('/schedules/edit/2'); // Schedule 2 belongs to org 2
        $this->assertResponseError();
    }

    /**
     * Test that editor cannot delete other organization's schedule
     */
    public function testEditorCannotDeleteOtherOrgSchedule(): void
    {
        $this->session([
            'Auth' => [
                'id' => 2,
                'email' => 'editor@org1.com',
                'role' => 'editor',
                'organization_id' => 1,
            ]
        ]);

        $this->post('/schedules/delete/2'); // Schedule 2 belongs to org 2
        $this->assertResponseError();
    }

    /**
     * Test that admin can view all schedules
     */
    public function testAdminCanViewAllSchedules(): void
    {
        $this->session([
            'Auth' => [
                'id' => 1,
                'email' => 'admin@system.com',
                'role' => 'admin',
                'organization_id' => 1,
            ]
        ]);

        // Admin should be able to view schedule from any organization
        $this->get('/schedules/view/2'); // Schedule 2 belongs to org 2
        $this->assertResponseOk();
    }

    /**
     * Test that viewer can only view, not edit
     */
    public function testViewerCannotEdit(): void
    {
        $this->session([
            'Auth' => [
                'id' => 3,
                'email' => 'viewer@org1.com',
                'role' => 'viewer',
                'organization_id' => 1,
            ]
        ]);

        $this->get('/schedules/edit/1');
        $this->assertResponseError(); // Viewer should not be able to edit
    }

    /**
     * Test that index filters schedules by organization for editor
     */
    public function testIndexFiltersByOrganization(): void
    {
        $this->session([
            'Auth' => [
                'id' => 2,
                'email' => 'editor@org1.com',
                'role' => 'editor',
                'organization_id' => 1,
            ]
        ]);

        $this->get('/schedules');
        $this->assertResponseOk();
        
        // Response should only contain schedules from org 1
        $viewVariable = $this->viewVariable('schedules');
        $this->assertNotEmpty($viewVariable);
        
        foreach ($viewVariable as $schedule) {
            $this->assertEquals(1, $schedule->organization_id);
        }
    }
}
