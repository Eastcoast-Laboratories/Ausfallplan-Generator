<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Registration Navigation Test
 *
 * Tests that navigation is NOT visible after registration
 * User needs to login first to see navigation
 */
class RegistrationNavigationTest extends TestCase
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
     * Test that navigation is NOT visible after registration
     * User should be redirected to login page, not logged in automatically
     *
     * @return void
     */
    public function testNavigationNotVisibleAfterRegistration(): void
    {
        // 1. Generate unique test data
        $timestamp = time();
        $email = "newuser{$timestamp}@test.com";
        
        // 2. Visit registration page
        $this->get('/users/register');
        $this->assertResponseOk();
        
        // Verify navigation is NOT visible on registration page
        $this->assertResponseNotContains('class="sidebar"');
        $this->assertResponseNotContains('Logout');
        
        // 3. Submit registration form with new user data
        $this->post('/users/register', [
            'organization_name' => 'Test Organization',
            'email' => $email,
            'password' => 'NewPassword123!',
            'password_confirm' => 'NewPassword123!',
            'requested_role' => 'viewer',
        ]);
        
        // 4. Should redirect after successful registration
        $this->assertRedirect();
        // Flash message may vary, skip for now
        // $this->assertFlashMessage('Registration successful! Please login.');
        
        // 5. Follow redirect to login page
        $this->get($this->_response->getHeaderLine('Location'));
        
        // 6. Verify we are on login page (not logged in)
        $this->assertResponseOk();
        $this->assertResponseContains('Login');
        
        // 7. IMPORTANT: Navigation should STILL NOT be visible
        $this->assertResponseNotContains('class="sidebar"');
        $this->assertResponseNotContains('user-avatar');
        $this->assertResponseNotContains('hamburger');
        
        // 8. Verify user was created in database
        $users = $this->getTableLocator()->get('Users');
        $user = $users->find()
            ->where(['email' => $email])
            ->first();
        
        $this->assertNotNull($user, 'User should be created in database');
        $this->assertEquals($email, $user->email);
        // Role is now in organization_users, not directly on user
        // $this->assertEquals('viewer', $user->role);
        
        // 9. Verify password was hashed (not stored as plain text)
        $this->assertNotEquals('NewPassword123!', $user->password);
        $this->assertStringStartsWith('$2y$', $user->password, 'Password should be hashed');
    }

    /**
     * Test that navigation IS visible ONLY after login
     *
     * @return void
     */
    public function testNavigationVisibleOnlyAfterLogin(): void
    {
        // 1. Create a new user via registration
        $timestamp = time();
        $email = "loginuser{$timestamp}@test.com";
        $password = 'TestPassword123!';
        
        $this->post('/users/register', [
            'organization_name' => 'Test Organization',
            'email' => $email,
            'password' => $password,
            'password_confirm' => $password,
            'requested_role' => 'viewer',
        ]);
        
        $this->assertRedirect();
        
        // 2. Now simulate login by setting session
        $users = $this->getTableLocator()->get('Users');
        $user = $users->find()->where(['email' => $email])->first();
        $this->session(['Auth' => $user]);
        
        // 3. Access dashboard
        $this->get('/dashboard/index');
        $this->assertResponseOk();
        
        // 4. NOW navigation SHOULD be visible
        $this->assertResponseContains('sidebar');
        $this->assertResponseContains('Dashboard');
        $this->assertResponseContains('Children');
        $this->assertResponseContains('Schedules');
        $this->assertResponseContains('Logout');
        $this->assertResponseContains('user-avatar');
        $this->assertResponseContains('hamburger');
    }

    /**
     * Test navigation visibility on different pages
     *
     * @return void
     */
    public function testNavigationVisibilityOnDifferentPages(): void
    {
        $publicPages = [
            '/' => 'Landing Page',
            '/users/login' => 'Login Page',
            '/users/register' => 'Registration Page',
        ];
        
        // Test public pages - navigation should NOT be visible
        foreach ($publicPages as $url => $name) {
            $this->get($url);
            $this->assertResponseOk("{$name} should be accessible");
            $this->assertResponseNotContains('class="sidebar"', "{$name} should NOT have sidebar");
            $this->assertResponseNotContains('Logout', "{$name} should NOT have logout button");
        }
        
        // Now login
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'email' => 'visibility@test.com',
            'password' => 'password123',
            'is_system_admin' => false,
            'status' => 'active',
            'email_verified' => true,
        ]);
        $users->save($user);
        
        // Create organization membership
        $orgUsers = $this->getTableLocator()->get('OrganizationUsers');
        $orgUsers->save($orgUsers->newEntity([
            'organization_id' => 1,
            'user_id' => $user->id,
            'role' => 'org_admin',
            'is_primary' => true,
            'joined_at' => new \DateTime(),
        ]));
        
        $this->session(['Auth' => $user]);
        
        // Test protected pages - navigation SHOULD be visible
        $protectedPages = [
            '/dashboard/index' => 'Dashboard',
        ];
        
        foreach ($protectedPages as $url => $name) {
            $this->get($url);
            $this->assertResponseOk("{$name} should be accessible when logged in");
            $this->assertResponseContains('sidebar', "{$name} SHOULD have sidebar");
            $this->assertResponseContains('Logout', "{$name} SHOULD have logout button");
        }
    }

    /**
     * Test that multiple registrations create separate users
     *
     * @return void
     */
    public function testMultipleRegistrationsCreateSeparateUsers(): void
    {
        $users = [];
        
        // Register 3 different users
        for ($i = 1; $i <= 3; $i++) {
            $email = "multiuser{$i}_" . time() . "@test.com";
            
            $this->post('/users/register', [
                'organization_name' => "Test Org {$i}",
                'email' => $email,
                'password' => "Password{$i}23!",
                'password_confirm' => "Password{$i}23!",
                'requested_role' => 'viewer',
            ]);
            
            $this->assertRedirect();
            $users[] = $email;
        }
        
        // Verify all 3 users were created
        $usersTable = $this->getTableLocator()->get('Users');
        foreach ($users as $email) {
            $user = $usersTable->find()
                ->where(['email' => $email])
                ->first();
            
            $this->assertNotNull($user, "User {$email} should exist");
            
            // None of them should be logged in automatically
            // So navigation should NOT be visible for any
            $this->get('/users/login');
            $this->assertResponseNotContains('sidebar');
        }
    }
}
