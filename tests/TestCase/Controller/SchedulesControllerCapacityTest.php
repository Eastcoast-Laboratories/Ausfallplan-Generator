<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * SchedulesController Capacity Per Day Test Case
 *
 * Tests that capacity_per_day is properly saved and displayed
 */
class SchedulesControllerCapacityTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.Organizations',
        'app.Users',
        'app.OrganizationUsers',
        'app.Schedules',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        
        // Set English locale for tests
        \Cake\I18n\I18n::setLocale('en_US');
    }

    /**
     * Test that capacity_per_day is saved and displayed correctly
     *
     * @return void
     */
    public function testCapacityPerDayIsSavedAndDisplayed(): void
    {
        // Create test user
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'email' => 'capacity@test.com',
            'password' => 'password123',
            'is_system_admin' => false,
            'email_verified' => 1,
            'status' => 'active',
        ]);
        $users->save($user);
        
        // Add to organization
        $orgUsers = $this->getTableLocator()->get('OrganizationUsers');
        $orgUsers->save($orgUsers->newEntity([
            'user_id' => $user->id,
            'organization_id' => 1,
            'role' => 'org_admin',
            'is_primary' => true,
            'joined_at' => new \DateTime(),
        ]));
        
        $this->session(['Auth' => ['id' => $user->id, 'email' => $user->email]]);

        // Create schedule with capacity_per_day
        $this->post('/schedules/add', [
            'title' => 'Test Schedule with Capacity',
            'starts_on' => '2025-01-01',
            'ends_on' => '2025-12-31',
            'capacity_per_day' => 15,
            'state' => 'draft',
        ]);

        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'Schedules', 'action' => 'index']);

        // Get the created schedule
        $schedules = $this->getTableLocator()->get('Schedules');
        $schedule = $schedules->find()
            ->where(['title' => 'Test Schedule with Capacity'])
            ->first();

        $this->assertNotNull($schedule, 'Schedule should be created');
        $this->assertEquals(15, $schedule->capacity_per_day, 'Capacity per day should be 15');

        // Now edit the schedule and change capacity
        $this->get('/schedules/edit/' . $schedule->id);
        $this->assertResponseOk();
        $this->assertResponseContains('15'); // Should display current value

        // Update capacity to 20
        $this->post('/schedules/edit/' . $schedule->id, [
            'title' => 'Test Schedule with Capacity',
            'starts_on' => '2025-01-01',
            'ends_on' => '2025-12-31',
            'capacity_per_day' => 20,
            'state' => 'draft',
        ]);

        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'Schedules', 'action' => 'view', $schedule->id]);

        // Verify updated value
        $schedules->clearCache('Schedules');
        $schedule = $schedules->get($schedule->id);
        $this->assertEquals(20, $schedule->capacity_per_day, 'Updated capacity should be 20');

        // Edit again to verify display
        $this->get('/schedules/edit/' . $schedule->id);
        $this->assertResponseOk();
        $this->assertResponseContains('20'); // Should display updated value
    }

    /**
     * Test that capacity uses default value when not provided
     *
     * @return void
     */
    public function testCapacityPerDayCanBeNull(): void
    {
        $schedules = $this->getTableLocator()->get('Schedules');
        $schedule = $schedules->newEntity([
            'organization_id' => 1,
            'title' => 'Test Schedule',
            'starts_on' => '2025-01-01',
            'ends_on' => '2025-12-31',
            'state' => 'draft',
        ]);
        $schedules->save($schedule);

        $this->session(['Auth' => ['id' => 1, 'email' => 'test@test.com']]);

        // Edit without providing capacity
        $this->post('/schedules/edit/' . $schedule->id, [
            'title' => 'Test Schedule',
            'starts_on' => '2025-01-01',
            'ends_on' => '2025-12-31',
            'state' => 'draft',
            // No capacity_per_day field
        ]);

        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'Schedules', 'action' => 'view', $schedule->id]);

        // Verify it uses default value (9)
        $schedules->clearCache('Schedules');
        $schedule = $schedules->get($schedule->id);
        $this->assertEquals(9, $schedule->capacity_per_day, 'Capacity should use default value when not provided');
    }
}
