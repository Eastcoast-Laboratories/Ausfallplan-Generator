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
                'email' => 'admin@example.com',
                'password' => '$2y$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO', // password123
                'role' => 'admin',
                'created' => '2024-10-22 10:00:00',
                'modified' => '2024-10-22 10:00:00',
            ],
            [
                'id' => 2,
                'organization_id' => 1,
                'email' => 'editor@example.com',
                'password' => '$2y$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO',
                'role' => 'editor',
                'created' => '2024-10-22 10:00:00',
                'modified' => '2024-10-22 10:00:00',
            ],
            [
                'id' => 3,
                'organization_id' => 1,
                'email' => 'viewer@example.com',
                'password' => '$2y$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO',
                'role' => 'viewer',
                'created' => '2024-10-22 10:00:00',
                'modified' => '2024-10-22 10:00:00',
            ],
        ];
        parent::init();
    }
}
