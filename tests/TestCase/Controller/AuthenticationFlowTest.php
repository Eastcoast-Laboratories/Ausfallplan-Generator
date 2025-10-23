<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Test authentication flow including email verification and password recovery
 */
class AuthenticationFlowTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Organizations',
        'app.PasswordResets',
    ];

    /**
     * Test registration creates user with pending status
     */
    public function testRegistrationCreatesPendingUser()
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'organization_name' => 'Test Kita',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'role' => 'viewer',
        ];

        $this->post('/users/register', $data);

        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'Users', 'action' => 'login']);

        // Check user was created with correct fields
        $usersTable = $this->getTableLocator()->get('Users');
        $user = $usersTable->find()->where(['email' => 'newuser@example.com'])->first();

        $this->assertNotNull($user);
        $this->assertEquals('pending', $user->status);
        $this->assertFalse($user->email_verified);
        $this->assertNotNull($user->email_token);
    }

    /**
     * Test email verification activates first user
     */
    public function testEmailVerificationActivatesFirstUser()
    {
        // Create a user with email token
        $usersTable = $this->getTableLocator()->get('Users');
        $user = $usersTable->newEntity([
            'organization_id' => 1,
            'email' => 'firstuser@test.com',
            'password' => 'password123',
            'role' => 'admin',
            'status' => 'pending',
            'email_verified' => false,
            'email_token' => 'test-token-123',
        ]);
        $usersTable->save($user);

        // Verify email
        $this->get('/users/verify/test-token-123');

        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'Users', 'action' => 'login']);

        // Check user is now active
        $user = $usersTable->get($user->id);
        $this->assertTrue($user->email_verified);
        $this->assertNull($user->email_token);
        $this->assertEquals('active', $user->status);
        $this->assertNotNull($user->approved_at);
    }

    /**
     * Test email verification sets pending for second user
     */
    public function testEmailVerificationSetsPendingForSecondUser()
    {
        // Create first user (already active)
        $usersTable = $this->getTableLocator()->get('Users');
        $firstUser = $usersTable->newEntity([
            'organization_id' => 1,
            'email' => 'admin@test.com',
            'password' => 'password123',
            'role' => 'admin',
            'status' => 'active',
            'email_verified' => true,
        ]);
        $usersTable->save($firstUser);

        // Create second user
        $secondUser = $usersTable->newEntity([
            'organization_id' => 1,
            'email' => 'seconduser@test.com',
            'password' => 'password123',
            'role' => 'viewer',
            'status' => 'pending',
            'email_verified' => false,
            'email_token' => 'test-token-456',
        ]);
        $usersTable->save($secondUser);

        // Verify email
        $this->get('/users/verify/test-token-456');

        $this->assertResponseSuccess();

        // Check user is verified but still pending
        $user = $usersTable->get($secondUser->id);
        $this->assertTrue($user->email_verified);
        $this->assertEquals('pending', $user->status);
        $this->assertNull($user->approved_at);
    }

    /**
     * Test login blocks unverified email
     */
    public function testLoginBlocksUnverifiedEmail()
    {
        $usersTable = $this->getTableLocator()->get('Users');
        $user = $usersTable->newEntity([
            'organization_id' => 1,
            'email' => 'unverified@test.com',
            'password' => 'password123',
            'role' => 'viewer',
            'status' => 'pending',
            'email_verified' => false,
        ]);
        $usersTable->save($user);

        $this->enableCsrfToken();
        $this->post('/users/login', [
            'email' => 'unverified@test.com',
            'password' => 'password123',
        ]);

        $this->assertSession('Please verify your email', 'Flash.flash.0.message');
    }

    /**
     * Test login blocks pending status
     */
    public function testLoginBlocksPendingStatus()
    {
        $usersTable = $this->getTableLocator()->get('Users');
        $user = $usersTable->newEntity([
            'organization_id' => 1,
            'email' => 'pending@test.com',
            'password' => 'password123',
            'role' => 'viewer',
            'status' => 'pending',
            'email_verified' => true,
        ]);
        $usersTable->save($user);

        $this->enableCsrfToken();
        $this->post('/users/login', [
            'email' => 'pending@test.com',
            'password' => 'password123',
        ]);

        $this->assertSession('pending approval', 'Flash.flash.0.message');
    }

    /**
     * Test password reset creates reset entry
     */
    public function testPasswordResetCreatesEntry()
    {
        $usersTable = $this->getTableLocator()->get('Users');
        $user = $usersTable->newEntity([
            'organization_id' => 1,
            'email' => 'resetme@test.com',
            'password' => 'oldpassword',
            'role' => 'viewer',
            'status' => 'active',
            'email_verified' => true,
        ]);
        $usersTable->save($user);

        $this->enableCsrfToken();
        $this->post('/users/forgot-password', [
            'email' => 'resetme@test.com',
        ]);

        $this->assertRedirect(['controller' => 'Users', 'action' => 'resetPassword']);

        // Check reset entry was created
        $resetsTable = $this->getTableLocator()->get('PasswordResets');
        $reset = $resetsTable->find()->where(['user_id' => $user->id])->first();

        $this->assertNotNull($reset);
        $this->assertNotNull($reset->reset_code);
        $this->assertEquals(6, strlen($reset->reset_code));
        $this->assertNull($reset->used_at);
    }

    /**
     * Test password reset with valid code
     */
    public function testPasswordResetWithValidCode()
    {
        // Create user
        $usersTable = $this->getTableLocator()->get('Users');
        $user = $usersTable->newEntity([
            'organization_id' => 1,
            'email' => 'resetme2@test.com',
            'password' => 'oldpassword',
            'role' => 'viewer',
            'status' => 'active',
            'email_verified' => true,
        ]);
        $usersTable->save($user);

        // Create reset entry
        $resetsTable = $this->getTableLocator()->get('PasswordResets');
        $reset = $resetsTable->newEntity([
            'user_id' => $user->id,
            'reset_token' => 'abc123',
            'reset_code' => '123456',
            'expires_at' => new \DateTime('+1 hour'),
        ]);
        $resetsTable->save($reset);

        // Reset password
        $this->enableCsrfToken();
        $this->post('/users/reset-password', [
            'code' => '123456',
            'new_password' => 'newpassword123',
        ]);

        $this->assertRedirect(['controller' => 'Users', 'action' => 'login']);

        // Check password was changed and reset marked as used
        $reset = $resetsTable->get($reset->id);
        $this->assertNotNull($reset->used_at);

        // Try logging in with new password
        $this->post('/users/login', [
            'email' => 'resetme2@test.com',
            'password' => 'newpassword123',
        ]);

        $this->assertRedirect();
    }
}
