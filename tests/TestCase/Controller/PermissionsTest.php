<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Test role-based permissions
 */
class PermissionsTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Organizations',
        'app.Users',
        'app.OrganizationUsers',
        'app.Children',
    ];

    /**
     * Test viewer can only read
     */
    public function testViewerCanOnlyRead()
    {
        // Login as viewer (User ID 3 from fixture)
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 3,
                    'email' => 'viewer@example.com',
                    'is_system_admin' => false,
                    'status' => 'active',
                    'email_verified' => true,
                ]
            ]
        ]);

        // Can access index
        $this->get('/children');
        $this->assertResponseOk();

        // Cannot add
        $this->enableCsrfToken();
        $this->post('/children/add', ['name' => 'Test Child']);
        $this->assertResponseCode(403);
    }

    /**
     * Test editor can edit own org
     */
    public function testEditorCanEditOwnOrg()
    {
        // Login as editor (User ID 2 from fixture)
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 2,
                    'email' => 'editor@example.com',
                    'is_system_admin' => false,
                    'status' => 'active',
                    'email_verified' => true,
                ]
            ]
        ]);

        // Can add children
        $this->enableCsrfToken();
        $this->post('/children/add', [
            'name' => 'Test Child',
            'is_active' => true,
            'is_integrative' => false,
        ]);
        $this->assertResponseSuccess();

        // Cannot access user management
        $this->get('/users/index');
        $this->assertResponseCode(403);
    }

    /**
     * Test admin can do everything
     */
    public function testAdminCanDoEverything()
    {
        // Login as system admin (User ID 1 from fixture)
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 1,
                    'email' => 'admin@example.com',
                    'is_system_admin' => true,
                    'status' => 'active',
                    'email_verified' => true,
                ]
            ]
        ]);

        // Can access everything
        $this->get('/children');
        $this->assertResponseOk();

        $this->get('/admin/users');
        $this->assertResponseOk();

        $this->enableCsrfToken();
        $this->post('/children/add', [
            'name' => 'Test Child',
            'is_active' => true,
            'is_integrative' => false,
        ]);
        $this->assertResponseSuccess();
    }
}
