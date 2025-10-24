<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * OrganizationUsersFixture
 */
class OrganizationUsersFixture extends TestFixture
{
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'organization_id' => 1,
                'user_id' => 1,
                'role' => 'org_admin',
                'is_primary' => true,
                'joined_at' => '2024-10-22 10:00:00',
                'invited_by' => null,
                'created' => '2024-10-22 10:00:00',
                'modified' => '2024-10-22 10:00:00',
            ],
            [
                'id' => 2,
                'organization_id' => 1,
                'user_id' => 2,
                'role' => 'editor',
                'is_primary' => false,
                'joined_at' => '2024-10-22 10:00:00',
                'invited_by' => 1,
                'created' => '2024-10-22 10:00:00',
                'modified' => '2024-10-22 10:00:00',
            ],
        ];
        parent::init();
    }
}
