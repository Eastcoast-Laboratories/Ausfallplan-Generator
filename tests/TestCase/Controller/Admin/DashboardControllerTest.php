<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class DashboardControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Organizations',
        'app.Children',
        'app.Schedules',
        'app.SiblingGroups',
    ];

    public function testIndexCountsActiveUsersByStatus(): void
    {
        $usersTable = $this->getTableLocator()->get('Users');
        $admin = $usersTable->get(1);
        $inactiveUser = $usersTable->get(2);
        $inactiveUser->status = 'inactive';
        $usersTable->saveOrFail($inactiveUser);

        $this->session(['Auth' => $admin]);

        $this->get('/admin');
        $this->assertResponseOk();
        $this->assertSame(4, $this->viewVariable('totalUsers'));
        $this->assertSame(3, $this->viewVariable('activeUsers'));
        $this->assertSame(1, $this->viewVariable('systemAdmins'));
    }
}
