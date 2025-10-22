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
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        
        // Disable CSRF for tests
        $this->enableCsrfToken();
        $this->enableSecurityToken();
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
        $this->assertResponseContains('organizations');
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
            'organization_id' => 1,
            'email' => 'newuser@test.com',
            'password' => 'SecurePassword123!',
            'role' => 'editor',
        ];

        $this->post('/users/register', $data);
        
        $this->assertResponseSuccess();
        $this->assertRedirect(['action' => 'login']);
        $this->assertFlashMessage('Your account has been created. Please check your email to verify your account.');
        
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
            'organization_id' => 1,
            'email' => 'viewer@test.com',
            'password' => 'SecurePassword123!',
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
            'organization_id' => 1,
            'email' => '', // Empty email
            'password' => 'short', // Too short password
        ];

        $this->post('/users/register', $data);
        
        $this->assertResponseOk();
        $this->assertFlashMessage('Unable to create your account. Please try again.');
        
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
            'organization_id' => 1,
            'email' => 'duplicate@test.com',
            'password' => 'SecurePassword123!',
        ];
        $this->post('/users/register', $data1);
        $this->assertResponseSuccess();

        // Try to create second user with same email in same organization
        $data2 = [
            'organization_id' => 1,
            'email' => 'duplicate@test.com',
            'password' => 'AnotherPassword456!',
        ];
        $this->post('/users/register', $data2);
        
        $this->assertResponseOk();
        $this->assertFlashMessage('Unable to create your account. Please try again.');
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
            'organization_id' => 1,
            'email' => 'hashtest@test.com',
            'password' => $plainPassword,
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
            'organization_id' => 1,
            'email' => 'security@test.com',
            'password' => 'SecurePassword123!',
        ];

        $this->post('/users/register', $data);
        
        // Verify password is not in the response
        $this->assertResponseNotContains('SecurePassword123!');
        $this->assertResponseNotContains('$2y$'); // No password hash in response either
    }
}
