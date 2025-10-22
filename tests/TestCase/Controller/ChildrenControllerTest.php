<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\ChildrenController Test Case
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
    }

    /**
     * Test index method
     *
     * @return void
     * @uses \App\Controller\ChildrenController::index()
     */
    public function testIndex(): void
    {
        // Create and log in user
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'organization_id' => 1,
            'email' => 'children@test.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);
        $users->save($user);
        $this->session(['Auth' => $user]);

        // Access children index
        $this->get('/children');
        
        $this->assertResponseOk();
        $this->assertResponseContains('Children');
    }

    /**
     * Test add method (GET)
     *
     * @return void
     * @uses \App\Controller\ChildrenController::add()
     */
    public function testAddGet(): void
    {
        // Create and log in user
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'organization_id' => 1,
            'email' => 'childadd@test.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);
        $users->save($user);
        $this->session(['Auth' => $user]);

        // Access add form
        $this->get('/children/add');
        
        $this->assertResponseOk();
        $this->assertResponseContains('name');
        $this->assertResponseContains('is_active');
        $this->assertResponseContains('is_integrative');
    }

    /**
     * Test add method (POST) - Successful creation
     *
     * @return void
     * @uses \App\Controller\ChildrenController::add()
     */
    public function testAddPostSuccess(): void
    {
        // Create and log in user
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'organization_id' => 1,
            'email' => 'childpost@test.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);
        $users->save($user);
        $this->session(['Auth' => $user]);

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
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'organization_id' => 1,
            'email' => 'integrative@test.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);
        $users->save($user);
        $this->session(['Auth' => $user]);

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
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'organization_id' => 1,
            'email' => 'childvalid@test.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);
        $users->save($user);
        $this->session(['Auth' => $user]);

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
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'organization_id' => 1,
            'email' => 'childview@test.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);
        $users->save($user);
        $this->session(['Auth' => $user]);

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
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'organization_id' => 1,
            'email' => 'childedit@test.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);
        $users->save($user);
        $this->session(['Auth' => $user]);

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
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'organization_id' => 1,
            'email' => 'childdelete@test.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);
        $users->save($user);
        $this->session(['Auth' => $user]);

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
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'organization_id' => 1,
            'email' => 'inactive@test.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);
        $users->save($user);
        $this->session(['Auth' => $user]);

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
}
