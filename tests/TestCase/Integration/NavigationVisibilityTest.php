<?php
declare(strict_types=1);

namespace App\Test\TestCase\Integration;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Navigation Visibility Integration Test
 *
 * Tests the complete login flow and navigation visibility
 */
class NavigationVisibilityTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Organizations',
        'app.Users',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
    }

    /**
     * Test complete login flow and navigation visibility
     *
     * @return void
     */
    public function testCompleteLoginFlowShowsNavigation(): void
    {
        // 1. Visit login page (not logged in)
        $this->get('/users/login');
        $this->assertResponseOk();
        $this->assertResponseContains('Login');
        
        // Navigation should NOT be visible on login page
        $this->assertResponseNotContains('class="sidebar"');

        // 2. Create test user
        $users = $this->getTableLocator()->get('Users');
        $user = $users->newEntity([
            'organization_id' => 1,
            'email' => 'navtest@integration.com',
            'password' => 'testpass123',
            'role' => 'admin',
        ]);
        $users->save($user);

        // 3. Simulate logged-in session directly (easier for testing)
        $this->session(['Auth' => $user]);

        // 4. Access dashboard with session
        $this->get('/dashboard/index');
        $this->assertResponseOk();
        
        // 5. Check that navigation IS visible after login
        $this->assertResponseContains('sidebar');
        $this->assertResponseContains('Dashboard');
        $this->assertResponseContains('Children');
        $this->assertResponseContains('Schedules');
        
        // 6. Check for logout button
        $this->assertResponseContains('Logout');
        $this->assertResponseContains('/users/logout');
        
        // 7. Check for hamburger menu
        $this->assertResponseContains('hamburger');
        
        // 8. Check for user avatar
        $this->assertResponseContains('user-avatar');
        $this->assertResponseContains('navtest@integration.com');
        
        // 9. Check for language switcher
        $this->assertResponseContains('language-switcher');
    }

    /**
     * Test that navigation is NOT visible on public pages
     *
     * @return void
     */
    public function testNavigationNotVisibleOnPublicPages(): void
    {
        // Landing page should not have navigation
        $this->get('/');
        $this->assertResponseOk();
        $this->assertResponseNotContains('class="sidebar"');
        $this->assertResponseNotContains('Logout');
        
        // Login page should not have navigation
        $this->get('/users/login');
        $this->assertResponseOk();
        $this->assertResponseNotContains('class="sidebar"');
    }

    /**
     * Test that protected pages redirect to login when not authenticated
     *
     * @return void
     */
    public function testProtectedPagesRedirectToLogin(): void
    {
        // Try to access dashboard without login
        $this->get('/dashboard/index');
        
        // Should redirect to login
        $this->assertRedirect();
    }
}
