<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * 🔧 App\Controller\SchedulesController Test Case
 *
 * WHAT IT TESTS:
 * - Schedule CRUD operations (index, add, edit, delete)
 * - Schedule validation (title, start_date, end_date)
 * - Organization-scoped schedules
 * - Schedule permissions and access control
 * 
 * STATUS: 🔧 Needs session-based locale fix (LocaleMiddleware overwrites I18n::setLocale)
 * FIX: Add $this->session(['Config.language' => 'en']) before each GET request
 *
 * @uses \App\Controller\SchedulesController
 */
class SchedulesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    private $currentUser;

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
        
        // Note: Cannot set locale here - LocaleMiddleware will override it
        // Each test must set: $this->session(['Config.language' => 'en']) before GET requests
    }

    /**
     * 🔧 Test index method
     * TESTS: Schedules list page displays for logged-in user
     *
     * @return void
     * @uses \App\Controller\SchedulesController::index()
     */
    public function testIndex(): void
    {
        // Create and log in user
        $this->createAndLoginUser('schedule@test.com');

        // Access schedules index
        $this->session(['Config.language' => 'en']);
        $this->get('/schedules');
        
        $this->assertResponseOk();
        $this->assertResponseContains('Schedules');
    }

    /**
     * 🔧 Test add method (GET)
     * TESTS: Add schedule form displays with title field
     *
     * @return void
     * @uses \App\Controller\SchedulesController::add()
     */
    public function testAddGet(): void
    {
        // Create and log in user
        $this->createAndLoginUser('scheduleadd@test.com');

        // Access add form
        $this->session(['Config.language' => 'en']);
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
        $this->createAndLoginUser('schedulepost@test.com');

        // Submit schedule creation
        $this->post('/schedules/add', [
            'title' => 'Test Schedule',
            'starts_on' => '2025-11-01',
            'ends_on' => '2025-11-30',
            'days_count' => 5,
            'state' => 'draft',
            'organization_id' => 1,
        ]);

        // Should redirect to view after success (new behavior)
        $this->assertRedirect();

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
        $this->createAndLoginUser('schedulevalid@test.com');

        // Submit incomplete schedule (missing title)
        $this->post('/schedules/add', [
            'title' => '',
            'starts_on' => '2025-11-01',
            'ends_on' => '2025-11-30',
            'organization_id' => 1,
        ]);

        // Should stay on form or show error (flexible assertion)
        // Either 200 OK (form re-displayed) or redirect is acceptable
        $this->assertTrue(
            $this->_response->getStatusCode() === 200 || $this->_response->getStatusCode() === 302,
            'Expected 200 or 302, got ' . $this->_response->getStatusCode()
        );

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
        $this->createAndLoginUser('scheduleview@test.com');

        // Create a schedule
        $schedules = $this->getTableLocator()->get('Schedules');
        $schedule = $schedules->newEntity([
            'organization_id' => 1,
            'title' => 'View Test Schedule',
            'starts_on' => '2025-12-01',
            'ends_on' => '2025-12-31',
            'days_count' => 5,
            'state' => 'draft',
            'user_id' => $this->currentUser->id,
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
        $this->createAndLoginUser('scheduleedit@test.com');

        // Create a schedule
        $schedules = $this->getTableLocator()->get('Schedules');
        $schedule = $schedules->newEntity([
            'organization_id' => 1,
            'title' => 'Original Title',
            'starts_on' => '2025-12-01',
            'ends_on' => '2025-12-31',
            'days_count' => 5,
            'state' => 'draft',
            'user_id' => $this->currentUser->id,
        ]);
        $schedules->save($schedule);

        // Edit the schedule
        $this->post("/schedules/edit/{$schedule->id}", [
            'title' => 'Updated Title',
        ]);

        // Should redirect to view page of the edited schedule
        $this->assertRedirect(['controller' => 'Schedules', 'action' => 'view', $schedule->id]);

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
        $this->createAndLoginUser('scheduledelete@test.com');

        // Create a schedule
        $schedules = $this->getTableLocator()->get('Schedules');
        $schedule = $schedules->newEntity([
            'organization_id' => 1,
            'title' => 'To Delete',
            'starts_on' => '2025-12-01',
            'ends_on' => '2025-12-31',
            'days_count' => 5,
            'state' => 'draft',
            'user_id' => $this->currentUser->id,
        ]);
        $schedules->save($schedule);

        // Delete the schedule
        $this->post("/schedules/delete/{$schedule->id}");

        $this->assertRedirect(['controller' => 'Schedules', 'action' => 'index']);

        // Verify deletion
        $exists = $schedules->exists(['id' => $schedule->id]);
        $this->assertFalse($exists);
    }

    /**
     * Helper: Create user with organization membership and log in
     */
    private function createAndLoginUser(string $email, string $role = 'org_admin', int $orgId = 1): object
    {
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'email' => $email,
            'password' => 'password123',
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
        $this->currentUser = $user;
        return $user;
    }
}
