<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * UsersFixture
 */
class UsersFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'email' => 'ausfallplan-sysadmin@it.z11.de',
                'password' => '$2y$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO', // password123
                'is_system_admin' => true,
                'status' => 'active',
                'email_verified' => 1,
                'email_token' => null,
                'approved_at' => '2024-10-22 10:00:00',
                'approved_by' => null,
                'created' => '2024-10-22 10:00:00',
                'modified' => '2024-10-22 10:00:00',
            ],
            [
                'id' => 2,
                'email' => 'editor@example.com',
                'password' => '$2y$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO',
                'is_system_admin' => false,
                'status' => 'active',
                'email_verified' => 1,
                'email_token' => null,
                'approved_at' => '2024-10-22 10:00:00',
                'approved_by' => 1,
                'created' => '2024-10-22 10:00:00',
                'modified' => '2024-10-22 10:00:00',
            ],
            [
                'id' => 3,
                'email' => 'viewer@example.com',
                'password' => '$2y$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO',
                'is_system_admin' => false,
                'status' => 'active',
                'email_verified' => 1,
                'email_token' => null,
                'approved_at' => '2024-10-22 10:00:00',
                'approved_by' => 1,
                'created' => '2024-10-22 10:00:00',
                'modified' => '2024-10-22 10:00:00',
            ],
            [
                'id' => 4,
                'email' => 'editor2@example.com',
                'password' => '$2y$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO',
                'is_system_admin' => false,
                'status' => 'active',
                'email_verified' => 1,
                'email_token' => null,
                'approved_at' => '2024-10-22 10:00:00',
                'approved_by' => 1,
                'created' => '2024-10-22 10:00:00',
                'modified' => '2024-10-22 10:00:00',
            ],
        ];
        parent::init();
    }
}
