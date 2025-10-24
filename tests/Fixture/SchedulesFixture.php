<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * SchedulesFixture
 */
class SchedulesFixture extends TestFixture
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
                'user_id' => 2, // Editor from org 1
                'title' => 'Schedule for Org 1',
                'state' => 'draft',
                'starts_on' => '2025-01-01',
                'ends_on' => null,
                'days_count' => 5,
                'capacity_per_day' => 10,
                'created' => '2024-10-22 10:00:00',
                'modified' => '2024-10-22 10:00:00',
            ],
            [
                'id' => 2,
                'organization_id' => 2,
                'user_id' => 4, // Editor from org 2
                'title' => 'Schedule for Org 2',
                'state' => 'draft',
                'starts_on' => '2025-01-01',
                'ends_on' => null,
                'days_count' => 5,
                'capacity_per_day' => 10,
                'created' => '2024-10-22 10:00:00',
                'modified' => '2024-10-22 10:00:00',
            ],
        ];
        parent::init();
    }
}
