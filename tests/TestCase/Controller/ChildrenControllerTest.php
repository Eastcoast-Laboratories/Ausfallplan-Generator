<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * 🔧 App\Controller\ChildrenController Test Case
 *
 * WHAT IT TESTS:
 * - Children CRUD operations (index, add, edit, delete)
 * - Permission checks for children management
 * - Organization-scoped child records
 * - Active/inactive child status handling
 * 
 * STATUS: 🔧 Needs session-based locale fix (LocaleMiddleware overwrites I18n::setLocale)
 * FIX: Add $this->session(['Config.language' => 'en']) before each GET request
 *
 * @uses \App\Controller\ChildrenController
 */
class ChildrenControllerTest extends TestCase
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
        'app.Children',
        'app.SiblingGroups',
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
     * TESTS: Children list page displays for logged-in user
     *
     * @return void
     * @uses \App\Controller\ChildrenController::index()
     */
    public function testIndex(): void
    {
        // Create and log in user
        $this->createAndLoginUser('children@test.com');

        // Access children index
        $this->session(['Config.language' => 'en']); // Must be AFTER createAndLoginUser
        $this->get('/children');
        
        $this->assertResponseOk();
        $this->assertResponseContains('Children');
    }

    /**
     * 🔧 Test add method (GET)
     * TESTS: Add child form displays with name, is_active, is_integrative fields
     *
     * @return void
     * @uses \App\Controller\ChildrenController::add()
     */
    public function testAddGet(): void
    {
        // Create and log in user
        $this->createAndLoginUser('childadd@test.com');

        // Access add form
        $this->session(['Config.language' => 'en']);
        $this->get('/children/add');
        
        $this->assertResponseOk();
        $this->assertResponseContains('name');
        $this->assertResponseContains('is_active');
        $this->assertResponseContains('is_integrative');
    }

    /**
     * 🔧 Test add method (POST) - Successful creation
     * TESTS: Child creation with valid data → redirects, saves to DB
     *
     * @return void
     * @uses \App\Controller\ChildrenController::add()
     */
    public function testAddPostSuccess(): void
    {
        // Create and log in user
        $this->createAndLoginUser('childpost@test.com');

        // Submit child creation
        $this->post('/children/add', [
            'name' => 'Max Mustermann',
            'is_active' => true,
            'is_integrative' => false,
        ]);

        // Should redirect after success
        $this->assertRedirect(['controller' => 'Children', 'action' => 'index']);

        // Verify child was created
        $children = $this->getTableLocator()->get('Children');
        $child = $children->find()
            ->where(['name' => 'Max Mustermann'])
            ->first();

        $this->assertNotNull($child);
        $this->assertEquals('Max Mustermann', $child->name);
        $this->assertEquals(1, $child->organization_id);
        $this->assertTrue($child->is_active);
        $this->assertFalse($child->is_integrative);
    }

    /**
     * Test add integrative child
     *
     * @return void
     * @uses \App\Controller\ChildrenController::add()
     */
    public function testAddIntegrativeChild(): void
    {
        // Create and log in user
        $this->createAndLoginUser('integrative@test.com');

        // Submit integrative child
        $this->post('/children/add', [
            'name' => 'Anna Schmidt',
            'is_active' => true,
            'is_integrative' => true,
        ]);

        $this->assertRedirect(['controller' => 'Children', 'action' => 'index']);

        // Verify integrative flag
        $children = $this->getTableLocator()->get('Children');
        $child = $children->find()
            ->where(['name' => 'Anna Schmidt'])
            ->first();

        $this->assertNotNull($child);
        $this->assertTrue($child->is_integrative);
    }

    /**
     * Test add method (POST) - Validation failure
     *
     * @return void
     * @uses \App\Controller\ChildrenController::add()
     */
    public function testAddPostValidationFailure(): void
    {
        // Create and log in user
        $this->createAndLoginUser('childvalid@test.com');

        // Submit incomplete child (missing name)
        $this->post('/children/add', [
            'name' => '',
            'is_active' => true,
        ]);

        // Should stay on form
        $this->assertResponseOk();
        $this->assertResponseContains('could not be saved');

        // Verify child was NOT created
        $children = $this->getTableLocator()->get('Children');
        $count = $children->find()->where(['name' => ''])->count();
        $this->assertEquals(0, $count);
    }

    /**
     * Test view method
     *
     * @return void
     * @uses \App\Controller\ChildrenController::view()
     */
    public function testView(): void
    {
        // Create and log in user
        $this->createAndLoginUser('childview@test.com');

        // Create a child
        $children = $this->getTableLocator()->get('Children');
        $child = $children->newEntity([
            'organization_id' => 1,
            'name' => 'View Test Child',
            'is_active' => true,
            'is_integrative' => false,
        ]);
        $children->save($child);

        // View the child
        $this->get("/children/view/{$child->id}");
        
        $this->assertResponseOk();
        $this->assertResponseContains('View Test Child');
    }

    /**
     * Test edit method
     *
     * @return void
     * @uses \App\Controller\ChildrenController::edit()
     */
    public function testEdit(): void
    {
        // Create and log in user
        $this->createAndLoginUser('childedit@test.com');

        // Create a child
        $children = $this->getTableLocator()->get('Children');
        $child = $children->newEntity([
            'organization_id' => 1,
            'name' => 'Original Name',
            'is_active' => true,
            'is_integrative' => false,
        ]);
        $children->save($child);

        // Edit the child
        $this->post("/children/edit/{$child->id}", [
            'name' => 'Updated Name',
        ]);

        $this->assertRedirect(['controller' => 'Children', 'action' => 'index']);

        // Verify update
        $updated = $children->get($child->id);
        $this->assertEquals('Updated Name', $updated->name);
    }

    /**
     * Test delete method
     *
     * @return void
     * @uses \App\Controller\ChildrenController::delete()
     */
    public function testDelete(): void
    {
        // Create and log in user
        $this->createAndLoginUser('childdelete@test.com');

        // Create a child
        $children = $this->getTableLocator()->get('Children');
        $child = $children->newEntity([
            'organization_id' => 1,
            'name' => 'To Delete',
            'is_active' => true,
            'is_integrative' => false,
        ]);
        $children->save($child);

        // Delete the child
        $this->post("/children/delete/{$child->id}");

        $this->assertRedirect(['controller' => 'Children', 'action' => 'index']);

        // Verify deletion
        $exists = $children->exists(['id' => $child->id]);
        $this->assertFalse($exists);
    }

    /**
     * Test inactive child
     *
     * @return void
     * @uses \App\Controller\ChildrenController::add()
     */
    public function testAddInactiveChild(): void
    {
        // Create and log in user
        $this->createAndLoginUser('inactive@test.com');

        // Submit inactive child
        $this->post('/children/add', [
            'name' => 'Inactive Child',
            'is_active' => false,
            'is_integrative' => false,
        ]);

        $this->assertRedirect();

        // Verify
        $children = $this->getTableLocator()->get('Children');
        $child = $children->find()
            ->where(['name' => 'Inactive Child'])
            ->first();

        $this->assertNotNull($child);
        $this->assertFalse($child->is_active);
    }

    /**
     * Helper: Create user with organization membership and log in
     * NOTE: Call $this->session(['Config.language' => 'en']) AFTER this method and BEFORE GET requests
     */
    private function createAndLoginUser(string $email, string $role = 'org_admin', int $orgId = 1): void
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
    }
}
