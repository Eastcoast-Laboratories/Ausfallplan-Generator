<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Test Admin access to all schedules
 */
class SchedulesAccessTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Organizations',
        'app.Schedules',
    ];

    /**
     * Test admin can see all schedules from all users
     */
    public function testAdminSeesAllSchedules()
    {
        // Create two organizations
        $orgsTable = $this->getTableLocator()->get('Organizations');
        $org1 = $orgsTable->newEntity(['name' => 'Org 1']);
        $org2 = $orgsTable->newEntity(['name' => 'Org 2']);
        $orgsTable->saveMany([$org1, $org2]);

        // Create users in different organizations
        $usersTable = $this->getTableLocator()->get('Users');
        $admin = $usersTable->newEntity([
            'organization_id' => $org1->id,
            'email' => 'admin@test.com',
            'password' => 'password123',
            'role' => 'admin',
            'status' => 'active',
            'email_verified' => 1,
        ]);
        
        $editor1 = $usersTable->newEntity([
            'organization_id' => $org1->id,
            'email' => 'editor1@test.com',
            'password' => 'password123',
            'role' => 'editor',
            'status' => 'active',
            'email_verified' => 1,
        ]);
        
        $editor2 = $usersTable->newEntity([
            'organization_id' => $org2->id,
            'email' => 'editor2@test.com',
            'password' => 'password123',
            'role' => 'editor',
            'status' => 'active',
            'email_verified' => 1,
        ]);
        
        $usersTable->saveMany([$admin, $editor1, $editor2]);

        // Create schedules for each editor
        $schedulesTable = $this->getTableLocator()->get('Schedules');
        $schedule1 = $schedulesTable->newEntity([
            'user_id' => $editor1->id,
            'organization_id' => $org1->id,
            'title' => 'Schedule Editor 1',
            'starts_on' => '2025-01-01',
            'ends_on' => '2025-01-31',
            'state' => 'draft',
        ]);
        
        $schedule2 = $schedulesTable->newEntity([
            'user_id' => $editor2->id,
            'organization_id' => $org2->id,
            'title' => 'Schedule Editor 2',
            'starts_on' => '2025-02-01',
            'ends_on' => '2025-02-28',
            'state' => 'draft',
        ]);
        
        $schedulesTable->saveMany([$schedule1, $schedule2]);

        // Login as admin
        $this->session([
            'Auth' => [
                'id' => $admin->id,
                'email' => $admin->email,
                'role' => 'admin',
                'organization_id' => $admin->organization_id,
            ]
        ]);

        // Access schedules list
        $this->get('/schedules');
        $this->assertResponseOk();

        // Debug: Print response body
        // echo "\n\n=== RESPONSE BODY ===\n" . $this->_response->getBody() . "\n=== END ===\n\n";

        // Admin should see BOTH schedules
        $this->assertResponseContains('Schedule Editor 1');
        $this->assertResponseContains('Schedule Editor 2');
        
        // Check if User columns are displayed (only for admin)
        $this->assertResponseContains('User'); // Column header - could be translated
        // Note: "Organization" might be translated to German, so check for email instead
        $this->assertResponseContains('editor1@test.com'); // User email
        $this->assertResponseContains('editor2@test.com'); // User email
        $this->assertResponseContains('Org 1'); // Organization name
        $this->assertResponseContains('Org 2'); // Organization name
    }

    /**
     * Test editor only sees own schedules
     */
    public function testEditorSeesOnlyOwnSchedules()
    {
        // Create organization and users
        $orgsTable = $this->getTableLocator()->get('Organizations');
        $org = $orgsTable->newEntity(['name' => 'Test Org']);
        $orgsTable->save($org);

        $usersTable = $this->getTableLocator()->get('Users');
        $editor1 = $usersTable->newEntity([
            'organization_id' => $org->id,
            'email' => 'editor1@test.com',
            'password' => 'password123',
            'role' => 'editor',
            'status' => 'active',
            'email_verified' => 1,
        ]);
        
        $editor2 = $usersTable->newEntity([
            'organization_id' => $org->id,
            'email' => 'editor2@test.com',
            'password' => 'password123',
            'role' => 'editor',
            'status' => 'active',
            'email_verified' => 1,
        ]);
        
        $usersTable->saveMany([$editor1, $editor2]);

        // Create schedules
        $schedulesTable = $this->getTableLocator()->get('Schedules');
        $schedule1 = $schedulesTable->newEntity([
            'user_id' => $editor1->id,
            'organization_id' => $org->id,
            'title' => 'Schedule 1',
            'starts_on' => '2025-01-01',
            'ends_on' => '2025-01-31',
            'state' => 'draft',
        ]);
        
        $schedule2 = $schedulesTable->newEntity([
            'user_id' => $editor2->id,
            'organization_id' => $org->id,
            'title' => 'Schedule 2',
            'starts_on' => '2025-02-01',
            'ends_on' => '2025-02-28',
            'state' => 'draft',
        ]);
        
        $schedulesTable->saveMany([$schedule1, $schedule2]);

        // Login as editor1
        $this->session([
            'Auth' => [
                'id' => $editor1->id,
                'email' => $editor1->email,
                'role' => 'editor',
                'organization_id' => $editor1->organization_id,
            ]
        ]);

        // Access schedules list
        $this->get('/schedules');
        $this->assertResponseOk();

        // Editor should only see OWN schedule
        $this->assertResponseContains('Schedule 1');
        $this->assertResponseNotContains('Schedule 2');
    }
}
