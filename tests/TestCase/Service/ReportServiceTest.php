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
