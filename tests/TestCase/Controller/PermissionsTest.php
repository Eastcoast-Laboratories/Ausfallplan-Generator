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
        $usersTable = $this->getTableLocator()->get('Users');
        $viewer = $usersTable->get(3);
        
        $this->session(['Auth' => $viewer]);
        $this->session(['Config.language' => 'en']);

        // Can access index
        $this->get('/children');
        $this->assertResponseOk();

        // Cannot add (needs editor role)
        $this->enableCsrfToken();
        $this->session(['Config.language' => 'en']);
        $this->post('/children/add', ['name' => 'Test Child']);
        
        // Should not allow (403 Forbidden or redirect)
        $statusCode = $this->_response->getStatusCode();
        $this->assertNotEquals(200, $statusCode, 'Viewer should not be able to add children');
    }

    /**
     * Test editor can edit own org
     */
    public function testEditorCanEditOwnOrg()
    {
        // Login as editor (User ID 2 from fixture)
        $usersTable = $this->getTableLocator()->get('Users');
        $editor = $usersTable->get(2);
        
        $this->session(['Auth' => $editor]);
        $this->session(['Config.language' => 'en']);

        // Can add children
        $this->enableCsrfToken();
        $this->post('/children/add', [
            'name' => 'Test Child',
            'is_active' => true,
            'is_integrative' => false,
        ]);
        $this->assertResponseSuccess();

        // Cannot access user management
        $this->session(['Config.language' => 'en']);
        $this->get('/users/index');
        // Should not allow (403 or redirect)
        $statusCode = $this->_response->getStatusCode();
        $this->assertNotEquals(200, $statusCode, 'Editor should not access user management');
    }

    /**
     * Test admin can do everything
     */
    public function testAdminCanDoEverything()
    {
        // Login as system admin (User ID 1 from fixture)
        $usersTable = $this->getTableLocator()->get('Users');
        $admin = $usersTable->get(1);
        
        $this->session(['Auth' => $admin]);
        $this->session(['Config.language' => 'en']);

        // Can access everything
        $this->get('/children');
        $this->assertResponseOk();

        $this->session(['Config.language' => 'en']);
        $this->get('/admin/users');
        $this->assertResponseOk();

        $this->enableCsrfToken();
        $this->session(['Config.language' => 'en']);
        $this->post('/children/add', [
            'name' => 'Test Child',
            'is_active' => true,
            'is_integrative' => false,
        ]);
        $this->assertResponseSuccess();
    }
}
