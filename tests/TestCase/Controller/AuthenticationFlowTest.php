<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * 🔧 Test authentication flow including email verification and password recovery
 * 
 * WHAT IT TESTS:
 * - User registration creates pending users with email verification
 * - Email verification flow (first user auto-approved, second needs approval)
 * - Login blocks for unverified emails and pending accounts  
 * - Password reset flow with tokens and codes
 * 
 * STATUS: 🔧 Needs session-based locale fix (LocaleMiddleware overwrites I18n::setLocale)
 * FIX: Add $this->session(['Config.language' => 'en']) to each test
 */
class AuthenticationFlowTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Organizations',
        'app.OrganizationUsers',
        'app.PasswordResets',
    ];

    /**
     * Set up test environment
     */
    public function setUp(): void
    {
        parent::setUp();
        
        // Note: Cannot set locale here - LocaleMiddleware will override it
        // Each test must set: $this->session(['Config.language' => 'en'])
    }

    /**
     * 🔧 Test registration creates user with pending status
     * TESTS: User registration → pending status, email_verified=0, email_token set
     */
    public function testRegistrationCreatesPendingUser()
    {
        $this->session(['Config.language' => 'en']); // Set locale via session
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'organization_name' => 'Test Kita',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirm' => 'password123',
            'requested_role' => 'viewer',
        ];

        $this->post('/users/register', $data);

        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'Users', 'action' => 'login']);

        // Check user was created with correct fields
        $usersTable = $this->getTableLocator()->get('Users');
        $user = $usersTable->find()->where(['email' => 'newuser@example.com'])->first();

        $this->assertNotNull($user);
        $this->assertEquals('pending', $user->status);
        $this->assertEquals(0, $user->email_verified);
        $this->assertNotNull($user->email_token);
    }

    /**
     * 🔧 Test email verification activates first user
     * TESTS: First user in org → auto-approved to active after email verification
     */
    public function testEmailVerificationActivatesFirstUser()
    {
        $this->session(['Config.language' => 'en']);
        // Create a user with email token
        $usersTable = $this->getTableLocator()->get('Users');
        $user = $usersTable->newEntity([
            'email' => 'firstuser@test.com',
            'password' => 'password123',
            'status' => 'pending',
            'is_system_admin' => false,
            'email_verified' => 0,
            'email_token' => 'test-token-123',
        ]);
        $usersTable->save($user);
        
        // Create organization membership
        $orgUsersTable = $this->getTableLocator()->get('OrganizationUsers');
        $orgUser = $orgUsersTable->newEntity([
            'organization_id' => 1,
            'user_id' => $user->id,
            'role' => 'org_admin',
            'is_primary' => true,
            'joined_at' => new \DateTime(),
        ]);
        $orgUsersTable->save($orgUser);

        // Verify email
        $this->get('/users/verify/test-token-123');

        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'Users', 'action' => 'login']);

        // Check user is now active
        $user = $usersTable->get($user->id);
        $this->assertEquals(1, $user->email_verified);
        $this->assertNull($user->email_token);
        $this->assertEquals('active', $user->status);
        $this->assertNotNull($user->approved_at);
    }

    /**
     * 🔧 Test email verification sets pending for second user
     * TESTS: Second+ user in org → stays pending after email verification (needs admin approval)
     */
    public function testEmailVerificationSetsPendingForSecondUser()
    {
        $this->session(['Config.language' => 'en']);
        // Create first user (already active)
        $usersTable = $this->getTableLocator()->get('Users');
        $firstUser = $usersTable->newEntity([
            'email' => 'admin@test.com',
            'password' => 'password123',
            'status' => 'active',
            'is_system_admin' => false,
            'email_verified' => 1,
        ]);
        $usersTable->save($firstUser);
        
        $orgUsersTable = $this->getTableLocator()->get('OrganizationUsers');
        $orgUsersTable->save($orgUsersTable->newEntity([
            'organization_id' => 1,
            'user_id' => $firstUser->id,
            'role' => 'org_admin',
            'is_primary' => true,
            'joined_at' => new \DateTime(),
        ]));

        // Create second user
        $secondUser = $usersTable->newEntity([
            'email' => 'seconduser@test.com',
            'password' => 'password123',
            'status' => 'pending',
            'is_system_admin' => false,
            'email_verified' => 0,
            'email_token' => 'test-token-456',
        ]);
        $usersTable->save($secondUser);
        
        $orgUsersTable->save($orgUsersTable->newEntity([
            'organization_id' => 1,
            'user_id' => $secondUser->id,
            'role' => 'viewer',
            'is_primary' => true,
            'joined_at' => new \DateTime(),
        ]));

        // Verify email
        $this->get('/users/verify/test-token-456');

        $this->assertResponseSuccess();

        // Check user is verified but still pending
        $user = $usersTable->get($secondUser->id);
        $this->assertEquals(1, $user->email_verified);
        $this->assertEquals('pending', $user->status);
        $this->assertNull($user->approved_at);
    }

    /**
     * 🔧 Test login blocks unverified email  
     * TESTS: Login attempt with email_verified=0 → blocked with flash message
     */
    public function testLoginBlocksUnverifiedEmail()
    {
        $this->session(['Config.language' => 'en']);
        $usersTable = $this->getTableLocator()->get('Users');
        $user = $usersTable->newEntity([
            'email' => 'unverified@test.com',
            'password' => 'password123',
            'status' => 'pending',
            'is_system_admin' => false,
            'email_verified' => 0,
        ]);
        $usersTable->save($user);
        
        $orgUsersTable = $this->getTableLocator()->get('OrganizationUsers');
        $orgUsersTable->save($orgUsersTable->newEntity([
            'organization_id' => 1,
            'user_id' => $user->id,
            'role' => 'viewer',
            'is_primary' => true,
            'joined_at' => new \DateTime(),
        ]));

        $this->enableCsrfToken();
        $this->post('/users/login', [
            'email' => 'unverified@test.com',
            'password' => 'password123',
        ]);

        $this->assertSession('Please verify your email before logging in.', 'Flash.flash.0.message');
    }

    /**
     * 🔧 Test login blocks pending status
     * TESTS: Login attempt with status='pending' → blocked with flash message
     */
    public function testLoginBlocksPendingStatus()
    {
        $this->session(['Config.language' => 'en']);
        $usersTable = $this->getTableLocator()->get('Users');
        $user = $usersTable->newEntity([
            'email' => 'pending@test.com',
            'password' => 'password123',
            'status' => 'pending',
            'is_system_admin' => false,
            'email_verified' => 1,
        ]);
        $usersTable->save($user);
        
        $orgUsersTable = $this->getTableLocator()->get('OrganizationUsers');
        $orgUsersTable->save($orgUsersTable->newEntity([
            'organization_id' => 1,
            'user_id' => $user->id,
            'role' => 'viewer',
            'is_primary' => true,
            'joined_at' => new \DateTime(),
        ]));

        $this->enableCsrfToken();
        $this->post('/users/login', [
            'email' => 'pending@test.com',
            'password' => 'password123',
        ]);

        $this->assertSession('Your account is pending approval by an administrator.', 'Flash.flash.0.message');
    }

    /**
     * 🔧 Test password reset creates reset entry
     * TESTS: Forgot password → creates PasswordResets entry with 6-digit code
     */
    public function testPasswordResetCreatesEntry()
    {
        $this->session(['Config.language' => 'en']);
        $usersTable = $this->getTableLocator()->get('Users');
        $user = $usersTable->newEntity([
            'email' => 'resetme@test.com',
            'password' => 'oldpassword',
            'status' => 'active',
            'is_system_admin' => false,
            'email_verified' => 1,
        ]);
        $usersTable->save($user);
        
        $orgUsersTable = $this->getTableLocator()->get('OrganizationUsers');
        $orgUsersTable->save($orgUsersTable->newEntity([
            'organization_id' => 1,
            'user_id' => $user->id,
            'role' => 'viewer',
            'is_primary' => true,
            'joined_at' => new \DateTime(),
        ]));

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
     * 🔧 Test password reset with valid code
     * TESTS: Password reset with valid code → password changed, reset marked as used
     */
    public function testPasswordResetWithValidCode()
    {
        $this->session(['Config.language' => 'en']);
        // Create user
        $usersTable = $this->getTableLocator()->get('Users');
        $user = $usersTable->newEntity([
            'email' => 'resetme2@test.com',
            'password' => 'oldpassword',
            'status' => 'active',
            'is_system_admin' => false,
            'email_verified' => 1,
        ]);
        $usersTable->save($user);
        
        $orgUsersTable = $this->getTableLocator()->get('OrganizationUsers');
        $orgUsersTable->save($orgUsersTable->newEntity([
            'organization_id' => 1,
            'user_id' => $user->id,
            'role' => 'viewer',
            'is_primary' => true,
            'joined_at' => new \DateTime(),
        ]));

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
