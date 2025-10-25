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
            // User 1 (system admin) is org_admin of organization 1
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
            // User 2 is editor in organization 1
            [
                'id' => 2,
                'organization_id' => 1,
                'user_id' => 2,
                'role' => 'editor',
                'is_primary' => true,
                'joined_at' => '2024-10-22 10:00:00',
                'invited_by' => 1,
                'created' => '2024-10-22 10:00:00',
                'modified' => '2024-10-22 10:00:00',
            ],
            // User 3 is viewer in organization 1
            [
                'id' => 3,
                'organization_id' => 1,
                'user_id' => 3,
                'role' => 'viewer',
                'is_primary' => true,
                'joined_at' => '2024-10-22 10:00:00',
                'invited_by' => 1,
                'created' => '2024-10-22 10:00:00',
                'modified' => '2024-10-22 10:00:00',
            ],
            // User 4 is editor in organization 2
            [
                'id' => 4,
                'organization_id' => 2,
                'user_id' => 4,
                'role' => 'editor',
                'is_primary' => true,
                'joined_at' => '2024-10-22 10:00:00',
                'invited_by' => null,
                'created' => '2024-10-22 10:00:00',
                'modified' => '2024-10-22 10:00:00',
            ],
        ];
        parent::init();
    }
}
