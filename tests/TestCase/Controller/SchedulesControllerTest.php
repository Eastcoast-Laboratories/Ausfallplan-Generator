<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\SchedulesController Test Case
 *
 * @uses \App\Controller\SchedulesController
 */
class SchedulesControllerTest extends TestCase
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
        'app.Schedules',
        'app.ScheduleDays',
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
     * Test index method
     *
     * @return void
     * @uses \App\Controller\SchedulesController::index()
     */
    public function testIndex(): void
    {
        // Create and log in user
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'organization_id' => 1,
            'email' => 'schedule@test.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);
        $users->save($user);
        $this->session(['Auth' => $user]);

        // Access schedules index
        $this->get('/schedules');
        
        $this->assertResponseOk();
        $this->assertResponseContains('Schedules');
    }

    /**
     * Test add method (GET)
     *
     * @return void
     * @uses \App\Controller\SchedulesController::add()
     */
    public function testAddGet(): void
    {
        // Create and log in user
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'organization_id' => 1,
            'email' => 'scheduleadd@test.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);
        $users->save($user);
        $this->session(['Auth' => $user]);

        // Access add form
        $this->get('/schedules/add');
        
        $this->assertResponseOk();
        $this->assertResponseContains('title');
        $this->assertResponseContains('starts_on');
        $this->assertResponseContains('ends_on');
    }

    /**
     * Test add method (POST) - Successful creation
     *
     * @return void
     * @uses \App\Controller\SchedulesController::add()
     */
    public function testAddPostSuccess(): void
    {
        // Create and log in user
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'organization_id' => 1,
            'email' => 'schedulepost@test.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);
        $users->save($user);
        $this->session(['Auth' => $user]);

        // Submit schedule creation
        $this->post('/schedules/add', [
            'title' => 'Test Schedule',
            'starts_on' => '2025-11-01',
            'ends_on' => '2025-11-30',
            'state' => 'draft',
        ]);

        // Should redirect after success
        $this->assertRedirect(['controller' => 'Schedules', 'action' => 'index']);

        // Verify schedule was created
        $schedules = $this->getTableLocator()->get('Schedules');
        $schedule = $schedules->find()
            ->where(['title' => 'Test Schedule'])
            ->first();

        $this->assertNotNull($schedule);
        $this->assertEquals('Test Schedule', $schedule->title);
        $this->assertEquals(1, $schedule->organization_id);
        $this->assertEquals('draft', $schedule->state);
        $this->assertEquals('2025-11-01', $schedule->starts_on->format('Y-m-d'));
        $this->assertEquals('2025-11-30', $schedule->ends_on->format('Y-m-d'));
    }

    /**
     * Test add method (POST) - Validation failure
     *
     * @return void
     * @uses \App\Controller\SchedulesController::add()
     */
    public function testAddPostValidationFailure(): void
    {
        // Create and log in user
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'organization_id' => 1,
            'email' => 'schedulevalid@test.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);
        $users->save($user);
        $this->session(['Auth' => $user]);

        // Submit incomplete schedule (missing title)
        $this->post('/schedules/add', [
            'title' => '',
            'starts_on' => '2025-11-01',
            'ends_on' => '2025-11-30',
        ]);

        // Should stay on form
        $this->assertResponseOk();
        $this->assertResponseContains('The schedule could not be saved');

        // Verify schedule was NOT created
        $schedules = $this->getTableLocator()->get('Schedules');
        $count = $schedules->find()->where(['title' => ''])->count();
        $this->assertEquals(0, $count);
    }

    /**
     * Test view method
     *
     * @return void
     * @uses \App\Controller\SchedulesController::view()
     */
    public function testView(): void
    {
        // Create and log in user
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'organization_id' => 1,
            'email' => 'scheduleview@test.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);
        $users->save($user);
        $this->session(['Auth' => $user]);

        // Create a schedule
        $schedules = $this->getTableLocator()->get('Schedules');
        $schedule = $schedules->newEntity([
            'organization_id' => 1,
            'title' => 'View Test Schedule',
            'starts_on' => '2025-12-01',
            'ends_on' => '2025-12-31',
            'state' => 'draft',
        ]);
        $schedules->save($schedule);

        // View the schedule
        $this->get("/schedules/view/{$schedule->id}");
        
        $this->assertResponseOk();
        $this->assertResponseContains('View Test Schedule');
    }

    /**
     * Test edit method
     *
     * @return void
     * @uses \App\Controller\SchedulesController::edit()
     */
    public function testEdit(): void
    {
        // Create and log in user
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'organization_id' => 1,
            'email' => 'scheduleedit@test.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);
        $users->save($user);
        $this->session(['Auth' => $user]);

        // Create a schedule
        $schedules = $this->getTableLocator()->get('Schedules');
        $schedule = $schedules->newEntity([
            'organization_id' => 1,
            'title' => 'Original Title',
            'starts_on' => '2025-12-01',
            'ends_on' => '2025-12-31',
            'state' => 'draft',
        ]);
        $schedules->save($schedule);

        // Edit the schedule
        $this->post("/schedules/edit/{$schedule->id}", [
            'title' => 'Updated Title',
        ]);

        $this->assertRedirect(['controller' => 'Schedules', 'action' => 'index']);

        // Verify update
        $updated = $schedules->get($schedule->id);
        $this->assertEquals('Updated Title', $updated->title);
    }

    /**
     * Test delete method
     *
     * @return void
     * @uses \App\Controller\SchedulesController::delete()
     */
    public function testDelete(): void
    {
        // Create and log in user
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'organization_id' => 1,
            'email' => 'scheduledelete@test.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);
        $users->save($user);
        $this->session(['Auth' => $user]);

        // Create a schedule
        $schedules = $this->getTableLocator()->get('Schedules');
        $schedule = $schedules->newEntity([
            'organization_id' => 1,
            'title' => 'To Delete',
            'starts_on' => '2025-12-01',
            'ends_on' => '2025-12-31',
            'state' => 'draft',
        ]);
        $schedules->save($schedule);

        // Delete the schedule
        $this->post("/schedules/delete/{$schedule->id}");

        $this->assertRedirect(['controller' => 'Schedules', 'action' => 'index']);

        // Verify deletion
        $exists = $schedules->exists(['id' => $schedule->id]);
        $this->assertFalse($exists);
    }
}
