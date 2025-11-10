<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\ReportService;
use Cake\TestSuite\TestCase;

/**
 * ReportService Test Case
 * 
 * Tests the dynamic report generation based on waitlist.
 * 
 * Verifies:
 * - Report generation with sorted children
 * - Sibling group handling in reports
 * - Day assignment distribution
 * - Statistics calculation (Z, D, ⬇️)
 * - "Always at End" section
 */
class ReportServiceTest extends TestCase
{
    /**
     * Fixtures
     */
    protected array $fixtures = [
        'app.Organizations',
        'app.Users',
        'app.Schedules',
        'app.Children',
        'app.SiblingGroups',
    ];

    private ReportService $service;

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->service = new ReportService();
    }

    /**
     * tearDown method
     */
    public function tearDown(): void
    {
        unset($this->service);
        parent::tearDown();
    }

    /**
     * Test generateReportData returns correct structure
     */
    public function testGenerateReportDataStructure(): void
    {
        // Create schedule with children in waitlist
        $scheduleId = $this->createScheduleWithWaitlist();
        
        $result = $this->service->generateReportData($scheduleId, 3);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('schedule', $result);
        $this->assertArrayHasKey('days', $result);
        $this->assertArrayHasKey('waitlist', $result);
        $this->assertArrayHasKey('daysCount', $result);
        $this->assertArrayHasKey('childStats', $result);
        
        $this->assertEquals(3, $result['daysCount']);
        $this->assertCount(3, $result['days']);
    }

    /**
     * Test animal names are assigned correctly
     */
    public function testAnimalNamesGeneration(): void
    {
        $scheduleId = $this->createScheduleWithWaitlist();
        
        $result = $this->service->generateReportData($scheduleId, 3);
        
        $this->assertEquals('Ameisen', $result['days'][0]['animalName']);
        $this->assertEquals('Bienen', $result['days'][1]['animalName']);
        $this->assertEquals('Chamäleon', $result['days'][2]['animalName']);
    }

    /**
     * Test children are distributed based on waitlist priority
     */
    public function testChildrenDistributionByPriority(): void
    {
        $scheduleId = $this->createScheduleWithWaitlist();
        
        $result = $this->service->generateReportData($scheduleId, 2);
        
        // Verify we have children distributed
        $this->assertNotEmpty($result['days']);
        
        foreach ($result['days'] as $day) {
            $this->assertArrayHasKey('animalName', $day);
            $this->assertArrayHasKey('children', $day);
            // Each day should respect capacity (9 by default)
            $this->assertLessThanOrEqual(9, count($day['children']));
        }
    }

    /**
     * Test respects capacity per day
     */
    public function testRespectsCapacityPerDay(): void
    {
        $scheduleId = $this->createScheduleWithManyChildren();
        
        $result = $this->service->generateReportData($scheduleId, 2);
        
        foreach ($result['days'] as $day) {
            // Should not exceed capacity of 9
            $this->assertLessThanOrEqual(9, count($day['children']));
        }
    }

    /**
     * Test child statistics are calculated
     */
    public function testChildStatisticsCalculation(): void
    {
        $scheduleId = $this->createScheduleWithWaitlist();
        
        $result = $this->service->generateReportData($scheduleId, 3);
        
        $this->assertIsArray($result['childStats']);
        
        // Each child in waitlist should have stats
        foreach ($result['childStats'] as $childId => $stats) {
            $this->assertArrayHasKey('daysCount', $stats);
            $this->assertIsInt($stats['daysCount']);
            $this->assertGreaterThanOrEqual(0, $stats['daysCount']);
        }
    }

    /**
     * Test "Always at End" children - those with schedule_id but NO waitlist_order
     */
    public function testAlwaysAtEndChildren(): void
    {
        $schedulesTable = $this->getTableLocator()->get('Schedules');
        $schedule = $schedulesTable->newEntity([
            'organization_id' => 1,
            'title' => 'Test Schedule Always At End',
            'starts_on' => '2025-01-01',
            'ends_on' => '2025-12-31',
            'days_count' => 3,
            'state' => 'draft',
            'capacity_per_day' => 9,
            'user_id' => 1,
        ]);
        $schedulesTable->save($schedule);
        
        $childrenTable = $this->getTableLocator()->get('Children');
        
        // Create children WITH waitlist_order (normal)
        $normalChild1 = $childrenTable->newEntity([
            'organization_id' => 1,
            'name' => 'Normal Child 1',
            'is_integrative' => false,
            'is_active' => true,
            'schedule_id' => $schedule->id,
            'waitlist_order' => 1,
        ]);
        $childrenTable->save($normalChild1);
        
        $normalChild2 = $childrenTable->newEntity([
            'organization_id' => 1,
            'name' => 'Normal Child 2',
            'is_integrative' => false,
            'is_active' => true,
            'schedule_id' => $schedule->id,
            'waitlist_order' => 2,
        ]);
        $childrenTable->save($normalChild2);
        
        // Create children WITHOUT waitlist_order but WITH schedule_id (should be "Always at End")
        $alwaysAtEndChild1 = $childrenTable->newEntity([
            'organization_id' => 1,
            'name' => 'Always At End Child 1',
            'is_integrative' => false,
            'is_active' => true,
            'schedule_id' => $schedule->id,
            'waitlist_order' => null, // NO waitlist order
            'organization_order' => null,
        ]);
        $childrenTable->save($alwaysAtEndChild1);
        
        $alwaysAtEndChild2 = $childrenTable->newEntity([
            'organization_id' => 1,
            'name' => 'Always At End Child 2',
            'is_integrative' => true,
            'is_active' => true,
            'schedule_id' => $schedule->id,
            'waitlist_order' => null, // NO waitlist order
            'organization_order' => null,
        ]);
        $childrenTable->save($alwaysAtEndChild2);
        
        // Generate report
        $result = $this->service->generateReportData($schedule->id, 3);
        
        // Verify alwaysAtEnd contains the correct children
        $this->assertArrayHasKey('alwaysAtEnd', $result);
        $this->assertIsArray($result['alwaysAtEnd']);
        $this->assertCount(2, $result['alwaysAtEnd'], 'Should have exactly 2 "Always at End" children');
        
        // Extract child IDs from alwaysAtEnd
        $alwaysAtEndIds = [];
        foreach ($result['alwaysAtEnd'] as $childData) {
            $alwaysAtEndIds[] = $childData['child']->id;
        }
        
        // Verify correct children are in alwaysAtEnd
        $this->assertContains($alwaysAtEndChild1->id, $alwaysAtEndIds, 'Always At End Child 1 should be in alwaysAtEnd');
        $this->assertContains($alwaysAtEndChild2->id, $alwaysAtEndIds, 'Always At End Child 2 should be in alwaysAtEnd');
        
        // Verify normal children are NOT in alwaysAtEnd
        $this->assertNotContains($normalChild1->id, $alwaysAtEndIds, 'Normal Child 1 should NOT be in alwaysAtEnd');
        $this->assertNotContains($normalChild2->id, $alwaysAtEndIds, 'Normal Child 2 should NOT be in alwaysAtEnd');
        
        // Also test Grid generation to ensure "Always at End" appear in right column
        $gridService = new \App\Service\ReportGridService();
        $gridData = $gridService->generateGrid($result);
        
        $this->assertIsArray($gridData);
        $this->assertArrayHasKey('grid', $gridData);
        
        $grid = $gridData['grid'];
        
        // Find "Always at end:" label in right column
        $foundLabel = false;
        $foundChild1 = false;
        $foundChild2 = false;
        
        foreach ($grid as $rowIndex => $row) {
            // Right column is the last cell in each row
            $rightCell = end($row);
            
            if ($rightCell['type'] === 'label' && $rightCell['value'] === __('Always at end:')) {
                $foundLabel = true;
            }
            
            if ($rightCell['type'] === 'child' && $rightCell['value'] === __('Always At End Child 1')) {
                $foundChild1 = true;
            }
            
            if ($rightCell['type'] === 'child' && $rightCell['value'] === __('Always At End Child 2')) {
                $foundChild2 = true;
            }
        }
        
        $this->assertTrue($foundLabel, '"Always at end" label should appear in right column');
        $this->assertTrue($foundChild1, 'Always At End Child 1 should appear in right column');
        $this->assertTrue($foundChild2, 'Always At End Child 2 should appear in right column');
    }

    /**
     * Helper: Create a test schedule with waitlist
     */
    private function createScheduleWithWaitlist(): int
    {
        $schedulesTable = $this->getTableLocator()->get('Schedules');
        $schedule = $schedulesTable->newEntity([
            'organization_id' => 1,
            'title' => 'Test Schedule',
            'starts_on' => '2025-01-01',
            'ends_on' => '2025-12-31',
            'days_count' => 5,
            'state' => 'draft',
            'capacity_per_day' => 9,
            'user_id' => 1,
        ]);
        $schedulesTable->save($schedule);
        
        // Create children and add to waitlist (new schema: children have schedule_id and waitlist_order directly)
        $childrenTable = $this->getTableLocator()->get('Children');
        
        for ($i = 1; $i <= 5; $i++) {
            $child = $childrenTable->newEntity([
                'organization_id' => 1,
                'name' => 'Test Child ' . $i,
                'is_integrative' => false,
                'is_active' => true,
                'schedule_id' => $schedule->id,
                'waitlist_order' => $i,
            ]);
            $childrenTable->save($child);
        }
        
        return $schedule->id;
    }

    /**
     * Helper: Create test schedule with many children (more than capacity)
     */
    private function createScheduleWithManyChildren(): int
    {
        $schedulesTable = $this->getTableLocator()->get('Schedules');
        $schedule = $schedulesTable->newEntity([
            'organization_id' => 1,
            'title' => 'Test Schedule Many',
            'starts_on' => '2025-01-01',
            'ends_on' => '2025-12-31',
            'days_count' => 5,
            'state' => 'draft',
            'capacity_per_day' => 9,
            'user_id' => 1,
        ]);
        $schedulesTable->save($schedule);
        
        $childrenTable = $this->getTableLocator()->get('Children');
        
        // Create 20 children (more than capacity of 9)
        for ($i = 1; $i <= 20; $i++) {
            $child = $childrenTable->newEntity([
                'organization_id' => 1,
                'name' => 'Child ' . $i,
                'is_integrative' => ($i % 5 == 0), // Every 5th is integrative
                'is_active' => true,
                'schedule_id' => $schedule->id,
                'waitlist_order' => $i,
            ]);
            $childrenTable->save($child);
        }
        
        return $schedule->id;
    }
}
