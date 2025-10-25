<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\UsersController Test Case
 *
 * @uses \App\Controller\UsersController
 */
class UsersControllerTest extends TestCase
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
        'app.PasswordResets',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        
        // Enable CSRF tokens for tests
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        
        // Disable authentication redirect for tests
        $this->configRequest([
            'headers' => ['Accept' => 'text/html'],
        ]);
    }

    /**
     * Test register method with GET request
     *
     * @return void
     * @uses \App\Controller\UsersController::register()
     */
    public function testRegisterGet(): void
    {
        $this->get('/users/register');
        
        $this->assertResponseOk();
        $this->assertResponseContains('Register New Account');
    }

    /**
     * Test register method with valid POST data
     *
     * @return void
     * @uses \App\Controller\UsersController::register()
     */
    public function testRegisterPostSuccess(): void
    {
        $data = [
            'organization_name' => 'Test Kita',
            'email' => 'newuser@test.com',
            'password' => 'SecurePassword123!',
            'password_confirm' => 'SecurePassword123!',
            'requested_role' => 'editor',
        ];

        $this->post('/users/register', $data);
        
        $this->assertResponseSuccess();
        $this->assertRedirect(['action' => 'login']);
        $this->assertFlashMessage('Registration successful. Please login.');
        
        // Verify user was created in database
        $users = $this->getTableLocator()->get('Users');
        $user = $users->findByEmail('newuser@test.com')->first();
        
        $this->assertNotNull($user);
        $this->assertEquals('newuser@test.com', $user->email);
        $this->assertEquals('editor', $user->role);
        $this->assertEquals(1, $user->organization_id);
        
        // Verify password was hashed
        $this->assertNotEquals('SecurePassword123!', $user->password);
        $this->assertStringStartsWith('$2y$', $user->password); // bcrypt hash
    }

    /**
     * Test register method with default role when not specified
     *
     * @return void
     * @uses \App\Controller\UsersController::register()
     */
    public function testRegisterPostDefaultRole(): void
    {
        $data = [
            'organization_name' => 'Test Kita',
            'email' => 'viewer@test.com',
            'password' => 'SecurePassword123!',
            'password_confirm' => 'SecurePassword123!',
        ];

        $this->post('/users/register', $data);
        
        $this->assertResponseSuccess();
        
        // Verify default role is 'viewer'
        $users = $this->getTableLocator()->get('Users');
        $user = $users->findByEmail('viewer@test.com')->first();
        
        $this->assertNotNull($user);
        $this->assertEquals('viewer', $user->role);
    }

    /**
     * Test register method with invalid data (missing required fields)
     *
     * @return void
     * @uses \App\Controller\UsersController::register()
     */
    public function testRegisterPostInvalidData(): void
    {
        $data = [
            'organization_name' => 'Test Kita',
            'email' => '', // Empty email
            'password' => '', // Empty password
            'password_confirm' => '',
        ];

        $this->post('/users/register', $data);
        
        $this->assertResponseOk();
        // Form should be reshown with errors, not redirected
        $this->assertResponseContains('Register New Account');
        
        // Verify user was NOT created
        $users = $this->getTableLocator()->get('Users');
        $count = $users->find()->where(['email' => ''])->count();
        $this->assertEquals(0, $count);
    }

    /**
     * Test register method with duplicate email
     *
     * @return void
     * @uses \App\Controller\UsersController::register()
     */
    public function testRegisterPostDuplicateEmail(): void
    {
        // Create first user
        $data1 = [
            'organization_name' => 'Test Kita',
            'email' => 'duplicate@test.com',
            'password' => 'SecurePassword123!',
            'password_confirm' => 'SecurePassword123!',
        ];
        $this->post('/users/register', $data1);
        $this->assertResponseSuccess();

        // Try to create second user with same email in same organization
        $data2 = [
            'organization_name' => 'Test Kita',
            'email' => 'duplicate@test.com',
            'password' => 'AnotherPassword456!',
            'password_confirm' => 'AnotherPassword456!',
        ];
        $this->post('/users/register', $data2);
        
        $this->assertResponseOk();
        // Form should be reshown with validation error
        $this->assertResponseContains('Register New Account');
        
        // Verify only one user exists
        $users = $this->getTableLocator()->get('Users');
        $count = $users->find()->where(['email' => 'duplicate@test.com'])->count();
        $this->assertEquals(1, $count);
    }

    /**
     * Test that password is properly hashed
     *
     * @return void
     * @uses \App\Controller\UsersController::register()
     */
    public function testPasswordHashing(): void
    {
        $plainPassword = 'MySecretPassword123!';
        
        $data = [
            'organization_name' => 'Test Kita',
            'email' => 'hashtest@test.com',
            'password' => $plainPassword,
            'password_confirm' => $plainPassword,
        ];

        $this->post('/users/register', $data);
        
        // Retrieve user from database
        $users = $this->getTableLocator()->get('Users');
        $user = $users->findByEmail('hashtest@test.com')->first();
        
        // Password should be hashed (not plain text)
        $this->assertNotEquals($plainPassword, $user->password);
        
        // Password should be bcrypt hash (starts with $2y$)
        $this->assertStringStartsWith('$2y$', $user->password);
        
        // Password hash should be at least 60 characters long (bcrypt standard)
        $this->assertGreaterThanOrEqual(60, strlen($user->password));
        
        // Verify the hash can be verified with the original password
        $hasher = new \Authentication\PasswordHasher\DefaultPasswordHasher();
        $this->assertTrue($hasher->check($plainPassword, $user->password));
    }

    /**
     * Test that sensitive data is not exposed in responses
     *
     * @return void
     * @uses \App\Controller\UsersController::register()
     */
    public function testPasswordNotExposedInResponse(): void
    {
        $data = [
            'organization_name' => 'Test Kita',
            'email' => 'security@test.com',
            'password' => 'SecurePassword123!',
            'password_confirm' => 'SecurePassword123!',
        ];

        $this->post('/users/register', $data);
        
        // Verify password is not in the response
        $this->assertResponseNotContains('SecurePassword123!');
        $this->assertResponseNotContains('$2y$'); // No password hash in response either
    }

    /**
     * Test login page loads
     *
     * @return void
     * @uses \App\Controller\UsersController::login()
     */
    public function testLoginPageLoads(): void
    {
        $this->get('/users/login');
        
        $this->assertResponseOk();
        $this->assertResponseContains('Login');
    }

    /**
     * Test profile update email
     *
     * @return void
     * @uses \App\Controller\UsersController::profile()
     */
    public function testProfileUpdateEmail(): void
    {
        // Create and login as test user
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'email' => 'profile@test.com',
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
            'organization_id' => 1,
            'user_id' => $user->id,
            'role' => 'viewer',
            'is_primary' => true,
            'joined_at' => new \DateTime(),
        ]));

        // Simulate logged in user
        $this->session(['Auth' => ['User' => $user]]);

        // Update email
        $this->post('/users/profile', [
            'email' => 'newemail@test.com',
        ]);

        $this->assertResponseSuccess();
        $this->assertFlashMessage('Your profile has been updated.');
        
        // Verify email was updated
        $updatedUser = $users->get($user->id);
        $this->assertEquals('newemail@test.com', $updatedUser->email);
    }

    /**
     * Test profile password change
     *
     * @return void
     * @uses \App\Controller\UsersController::profile()
     */
    public function testProfileChangePassword(): void
    {
        // Create and login as test user
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'email' => 'changepass@test.com',
            'password' => 'oldpassword123',
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
            'organization_id' => 1,
            'user_id' => $user->id,
            'role' => 'viewer',
            'is_primary' => true,
            'joined_at' => new \DateTime(),
        ]));
        $oldPasswordHash = $user->password;

        // Simulate logged in user
        $this->session(['Auth' => ['User' => $user]]);

        // Change password
        $this->post('/users/profile', [
            'email' => 'changepass@test.com',
            'new_password' => 'newpassword123',
            'confirm_password' => 'newpassword123',
        ]);

        $this->assertResponseSuccess();
        $this->assertFlashMessage('Your profile has been updated.');
        
        // Verify password was changed
        $updatedUser = $users->get($user->id);
        $this->assertNotEquals($oldPasswordHash, $updatedUser->password);
        
        // Verify new password works
        $hasher = new \Authentication\PasswordHasher\DefaultPasswordHasher();
        $this->assertTrue($hasher->check('newpassword123', $updatedUser->password));
    }

    /**
     * Test profile password mismatch
     *
     * @return void
     * @uses \App\Controller\UsersController::profile()
     */
    public function testProfilePasswordMismatch(): void
    {
        // Create and login as test user
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'email' => 'mismatch@test.com',
            'password' => 'oldpassword123',
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
            'organization_id' => 1,
            'user_id' => $user->id,
            'role' => 'viewer',
            'is_primary' => true,
            'joined_at' => new \DateTime(),
        ]));
        $oldPasswordHash = $user->password;

        // Simulate logged in user
        $this->session(['Auth' => ['User' => $user]]);

        // Try to change password with mismatch
        $this->post('/users/profile', [
            'email' => 'mismatch@test.com',
            'new_password' => 'newpassword123',
            'confirm_password' => 'differentpassword',
        ]);

        $this->assertResponseOk();
        
        // Verify password was NOT changed
        $updatedUser = $users->get($user->id);
        $this->assertEquals($oldPasswordHash, $updatedUser->password);
    }

    /**
     * Test profile cannot change role
     *
     * @return void
     * @uses \App\Controller\UsersController::profile()
     */
    public function testProfileCannotChangeRole(): void
    {
        // Create and login as test user
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'email' => 'norole@test.com',
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
            'organization_id' => 1,
            'user_id' => $user->id,
            'role' => 'viewer',
            'is_primary' => true,
            'joined_at' => new \DateTime(),
        ]));

        // Simulate logged in user
        $this->session(['Auth' => ['User' => $user]]);

        // Try to change role
        $this->post('/users/profile', [
            'email' => 'norole@test.com',
            'role' => 'admin',
        ]);

        $this->assertResponseSuccess();
        
        // Verify role was NOT changed
        $updatedUser = $users->get($user->id);
        $this->assertEquals('viewer', $updatedUser->role);
    }
}
