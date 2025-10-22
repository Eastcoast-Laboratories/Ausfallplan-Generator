<?php
declare(strict_types=1);

namespace App\Test\TestCase\View;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Authenticated Layout Test Case
 *
 * Tests that navigation and logout button are visible when logged in
 */
class AuthenticatedLayoutTest extends TestCase
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
        
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        
        // Set English locale for tests
        \Cake\I18n\I18n::setLocale('en_US');
    }

    /**
     * Test that navigation is visible when logged in
     *
     * @return void
     */
    public function testNavigationVisibleWhenLoggedIn(): void
    {
        // Create and simulate logged in user
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'organization_id' => 1,
            'email' => 'navtest@test.com',
            'password' => 'password123',
            'role' => 'viewer',
        ]);
        $users->save($user);

        // Simulate logged in user
        $this->session(['Auth' => $user]);

        // Visit dashboard (requires authentication)
        $this->get('/dashboard/index');

        $this->assertResponseOk();
        
        // Check for navigation elements
        $this->assertResponseContains('sidebar');
        $this->assertResponseContains('Ausfallplan');
        $this->assertResponseContains('Dashboard');
        $this->assertResponseContains('Children');
        $this->assertResponseContains('Schedules');
        
        // Check for user menu elements
        $this->assertResponseContains('user-menu');
        $this->assertResponseContains('user-avatar');
        
        // Check for logout button
        $this->assertResponseContains('Logout');
        $this->assertResponseContains('/users/logout');
    }

    /**
     * Test that hamburger menu exists for mobile
     *
     * @return void
     */
    public function testHamburgerMenuExists(): void
    {
        // Create and simulate logged in user
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'organization_id' => 1,
            'email' => 'mobile@test.com',
            'password' => 'password123',
            'role' => 'viewer',
        ]);
        $users->save($user);

        // Simulate logged in user
        $this->session(['Auth' => $user]);

        // Visit dashboard
        $this->get('/dashboard/index');

        $this->assertResponseOk();
        
        // Check for hamburger button
        $this->assertResponseContains('hamburger');
        $this->assertResponseContains('id="hamburger"');
        
        // Check for mobile overlay
        $this->assertResponseContains('sidebar-overlay');
    }

    /**
     * Test that user email is displayed in avatar
     *
     * @return void
     */
    public function testUserEmailInAvatar(): void
    {
        // Create and simulate logged in user
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'organization_id' => 1,
            'email' => 'avatar@test.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);
        $users->save($user);

        // Simulate logged in user
        $this->session(['Auth' => $user]);

        // Visit dashboard
        $this->get('/dashboard/index');

        $this->assertResponseOk();
        
        // Check that email is in the response (user dropdown)
        $this->assertResponseContains('avatar@test.com');
        
        // Check that role is displayed
        $this->assertResponseContains('admin');
    }

    /**
     * Test that language switcher is visible
     *
     * @return void
     */
    public function testLanguageSwitcherVisible(): void
    {
        // Create and simulate logged in user
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'organization_id' => 1,
            'email' => 'lang@test.com',
            'password' => 'password123',
            'role' => 'viewer',
        ]);
        $users->save($user);

        // Simulate logged in user
        $this->session(['Auth' => $user]);

        // Visit dashboard
        $this->get('/dashboard/index');

        $this->assertResponseOk();
        
        // Check for language switcher
        $this->assertResponseContains('language-switcher');
        $this->assertResponseContains('/users/change-language/de');
        $this->assertResponseContains('/users/change-language/en');
    }
}
