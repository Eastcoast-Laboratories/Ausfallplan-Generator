<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\ReportService;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * ReportService "Always at End" Test
 */
class ReportServiceAlwaysAtEndTest extends TestCase
{
    protected array $fixtures = [
        'app.Organizations',
        'app.Users',
        'app.Schedules',
        'app.Children',
        'app.SiblingGroups',
    ];

    private ReportService $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = new ReportService();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        unset($this->service);
    }

    /**
     * Test that children assigned to schedule but NOT on waitlist appear in "alwaysAtEnd"
     */
    public function testAlwaysAtEndShowsChildrenNotOnWaitlist(): void
    {
        $organizationsTable = TableRegistry::getTableLocator()->get('Organizations');
        $schedulesTable = TableRegistry::getTableLocator()->get('Schedules');
        $childrenTable = TableRegistry::getTableLocator()->get('Children');

        // Create organization
        $organization = $organizationsTable->newEntity([
            'name' => 'Test Org',
            'postal_code' => '12345',
        ]);
        $organizationsTable->save($organization);

        // Create schedule
        $schedule = $schedulesTable->newEntity([
            'title' => 'Test Schedule',
            'organization_id' => $organization->id,
            'user_id' => 1,  // Required field
            'state' => 'draft',
            'starts_on' => '2025-01-01',
            'capacity_per_day' => 5,
            'days_count' => 3,
        ]);
        $saved = $schedulesTable->save($schedule);
        if (!$saved) {
            debug($schedule->getErrors());
        }
        $this->assertNotFalse($saved, 'Schedule should be saved successfully');
        $this->assertNotNull($schedule->id, 'Schedule should have an ID');

        // Create child 1: On waitlist (schedule_id + waitlist_order)
        $childOnWaitlist = $childrenTable->newEntity([
            'name' => 'Child on Waitlist',
            'display_name' => 'Child on Waitlist',
            'first_name' => 'Test',
            'last_name' => 'One',
            'organization_id' => $organization->id,
            'schedule_id' => $schedule->id,
            'waitlist_order' => 1,  // On waitlist
        ]);
        $saved1 = $childrenTable->save($childOnWaitlist);
        $this->assertNotFalse($saved1, 'Child 1 should be saved');
        
        // Verify child 1 is in DB
        $check1 = $childrenTable->find()->where(['id' => $childOnWaitlist->id])->first();
        echo "\nChild 1 saved - ID: " . ($childOnWaitlist->id ?? 'NULL') . "\n";
        echo "Child 1 schedule_id: " . ($check1->schedule_id ?? 'NULL') . "\n";
        echo "Child 1 waitlist_order: " . ($check1->waitlist_order ?? 'NULL') . "\n";

        // Create child 2: Always at end (schedule_id but NO waitlist_order)
        $childAlwaysAtEnd = $childrenTable->newEntity([
            'name' => 'Child Always at End',
            'display_name' => 'Child Always at End',
            'first_name' => 'Test',
            'last_name' => 'Two',
            'organization_id' => $organization->id,
            'schedule_id' => $schedule->id,
            'waitlist_order' => null,  // NOT on waitlist
        ]);
        $saved2 = $childrenTable->save($childAlwaysAtEnd);
        $this->assertNotFalse($saved2, 'Child 2 should be saved');

        // Create child 3: Not assigned to schedule at all
        $childNotAssigned = $childrenTable->newEntity([
            'name' => 'Child Not Assigned',
            'display_name' => 'Child Not Assigned',
            'first_name' => 'Test',
            'last_name' => 'Three',
            'organization_id' => $organization->id,
            'schedule_id' => null,  // Not assigned
            'waitlist_order' => null,
        ]);
        $saved3 = $childrenTable->save($childNotAssigned);
        $this->assertNotFalse($saved3, 'Child 3 should be saved');

        // Generate report
        $reportData = $this->service->generateReportData($schedule->id, 3);

        // Debug output
        echo "\n=== DEBUG ===\n";
        echo "Waitlist children: " . count($reportData['waitlist'] ?? []) . "\n";
        echo "AlwaysAtEnd children: " . count($reportData['alwaysAtEnd'] ?? []) . "\n";
        
        // Check database directly
        $dbChildrenOnWaitlist = $childrenTable->find()
            ->where([
                'schedule_id' => $schedule->id,
                'waitlist_order IS NOT' => null
            ])
            ->count();
        echo "DB: Children on waitlist: $dbChildrenOnWaitlist\n";
        
        $dbChildrenAlwaysAtEnd = $childrenTable->find()
            ->where([
                'schedule_id' => $schedule->id,
                'waitlist_order IS' => null
            ])
            ->count();
        echo "DB: Children always at end: $dbChildrenAlwaysAtEnd\n";
        echo "=============\n\n";

        // Assertions
        $this->assertIsArray($reportData);
        $this->assertArrayHasKey('alwaysAtEnd', $reportData);

        $alwaysAtEnd = $reportData['alwaysAtEnd'];
        $this->assertIsArray($alwaysAtEnd);

        // alwaysAtEnd should NOT be empty
        $this->assertNotEmpty($alwaysAtEnd, 'alwaysAtEnd should contain child 2');

        // Extract child IDs from alwaysAtEnd
        $alwaysAtEndIds = array_map(function($item) {
            return $item['child']->id;
        }, $alwaysAtEnd);

        // Child 2 should be in alwaysAtEnd
        $this->assertContains(
            $childAlwaysAtEnd->id,
            $alwaysAtEndIds,
            'Child assigned to schedule but not on waitlist should be in alwaysAtEnd'
        );

        // Child 1 should NOT be in alwaysAtEnd (it's on waitlist)
        $this->assertNotContains(
            $childOnWaitlist->id,
            $alwaysAtEndIds,
            'Child on waitlist should NOT be in alwaysAtEnd'
        );

        // Child 3 should NOT be in alwaysAtEnd (not assigned to schedule)
        $this->assertNotContains(
            $childNotAssigned->id,
            $alwaysAtEndIds,
            'Child not assigned to schedule should NOT be in alwaysAtEnd'
        );
    }
}
