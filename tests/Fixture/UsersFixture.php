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
                'organization_id' => 1,
                'email' => 'admin@test.com',
                'password' => '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890', // 'password' hashed
                'role' => 'admin',
                'email_verified_at' => '2024-01-01 10:00:00',
                'last_login_at' => '2024-10-22 06:00:00',
                'created' => '2024-01-01 10:00:00',
                'modified' => '2024-01-01 10:00:00',
            ],
            [
                'id' => 2,
                'organization_id' => 1,
                'email' => 'editor@test.com',
                'password' => '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890',
                'role' => 'editor',
                'email_verified_at' => '2024-01-01 10:00:00',
                'last_login_at' => null,
                'created' => '2024-01-01 10:00:00',
                'modified' => '2024-01-01 10:00:00',
            ],
            [
                'id' => 3,
                'organization_id' => 1,
                'email' => 'viewer@test.com',
                'password' => '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890',
                'role' => 'viewer',
                'email_verified_at' => null,
                'last_login_at' => null,
                'created' => '2024-01-01 10:00:00',
                'modified' => '2024-01-01 10:00:00',
            ],
        ];
        parent::init();
    }
}
