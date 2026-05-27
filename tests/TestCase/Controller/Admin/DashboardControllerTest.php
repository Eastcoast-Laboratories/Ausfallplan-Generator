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

    public function testIndexCountsActiveUsersByIsActiveFlag(): void
    {
        $usersTable = $this->getTableLocator()->get('Users');
        $admin = $usersTable->find()
            ->where(['is_system_admin' => true])
            ->firstOrFail();
        $userToDeactivate = $usersTable->find()
            ->where([
                'id !=' => $admin->id,
                'is_active' => true,
            ])
            ->firstOrFail();
        $userToDeactivate->status = 'inactive';
        $userToDeactivate->is_active = false;
        $usersTable->saveOrFail($userToDeactivate);
        $expectedTotalUsers = $usersTable->find()->count();
        $expectedActiveUsers = $usersTable->find()
            ->where(['is_active' => true])
            ->count();
        $expectedSystemAdmins = $usersTable->find()
            ->where(['is_system_admin' => true])
            ->count();

        $this->session(['Auth' => $admin]);

        $this->get('/admin');
        $this->assertResponseOk();
        $this->assertSame($expectedTotalUsers, $this->viewVariable('totalUsers'));
        $this->assertSame($expectedActiveUsers, $this->viewVariable('activeUsers'));
        $this->assertSame($expectedSystemAdmins, $this->viewVariable('systemAdmins'));
    }
}
