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
        'app.Users',
        'app.Organizations',
        'app.Children',
    ];

    /**
     * Test viewer can only read
     */
    public function testViewerCanOnlyRead()
    {
        // Login as viewer
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 1,
                    'email' => 'viewer@test.com',
                    'role' => 'viewer',
                    'organization_id' => 1,
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
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 2,
                    'email' => 'editor@test.com',
                    'role' => 'editor',
                    'organization_id' => 1,
                    'status' => 'active',
                    'email_verified' => true,
                ]
            ]
        ]);

        // Can add children
        $this->enableCsrfToken();
        $this->post('/children/add', [
            'name' => 'Test Child',
            'organization_id' => 1,
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
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 3,
                    'email' => 'admin@test.com',
                    'role' => 'admin',
                    'organization_id' => 1,
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
            'organization_id' => 1,
            'is_active' => true,
            'is_integrative' => false,
        ]);
        $this->assertResponseSuccess();
    }
}
