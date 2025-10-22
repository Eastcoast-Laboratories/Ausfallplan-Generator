<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\ReportService;
use Cake\TestSuite\TestCase;

/**
 * ReportService Test Case
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
        'app.ScheduleDays',
        'app.Assignments',
        'app.WaitlistEntries',
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
        // Create test data
        $scheduleId = $this->createTestSchedule();
        
        $reportData = $this->service->generateReportData($scheduleId, 5);

        $this->assertIsArray($reportData);
        $this->assertArrayHasKey('schedule', $reportData);
        $this->assertArrayHasKey('days', $reportData);
        $this->assertArrayHasKey('waitlist', $reportData);
        $this->assertArrayHasKey('alwaysAtEnd', $reportData);
        $this->assertArrayHasKey('daysCount', $reportData);
        
        $this->assertEquals(5, $reportData['daysCount']);
        $this->assertCount(5, $reportData['days']);
    }

    /**
     * Test animal names are assigned correctly
     */
    public function testAnimalNamesGeneration(): void
    {
        $scheduleId = $this->createTestSchedule();
        
        $reportData = $this->service->generateReportData($scheduleId, 3);

        $this->assertEquals('Ameisen-Tag 1', $reportData['days'][0]['title']);
        $this->assertEquals('Bienen-Tag 2', $reportData['days'][1]['title']);
        $this->assertEquals('Chamäleon-Tag 3', $reportData['days'][2]['title']);
    }

    /**
     * Test children distribution with weights
     */
    public function testChildrenDistributionWithWeights(): void
    {
        $scheduleId = $this->createTestScheduleWithChildren();
        
        $reportData = $this->service->generateReportData($scheduleId, 3);

        // First day should have children
        $firstDay = $reportData['days'][0];
        $this->assertNotEmpty($firstDay['children']);
        
        // Each child should have a weight
        foreach ($firstDay['children'] as $child) {
            $this->assertArrayHasKey('child', $child);
            $this->assertArrayHasKey('weight', $child);
            $this->assertGreaterThan(0, $child['weight']);
        }
    }

    /**
     * Test leaving child identification
     */
    public function testLeavingChildIdentification(): void
    {
        $scheduleId = $this->createTestScheduleWithChildren();
        
        $reportData = $this->service->generateReportData($scheduleId, 2);

        // First day should have a leaving child
        $firstDay = $reportData['days'][0];
        $this->assertNotNull($firstDay['leavingChild']);
        $this->assertArrayHasKey('child', $firstDay['leavingChild']);
        $this->assertArrayHasKey('weight', $firstDay['leavingChild']);
    }

    /**
     * Test respects capacity per day
     */
    public function testRespectsCapacityPerDay(): void
    {
        $scheduleId = $this->createTestScheduleWithManyChildren();
        
        $reportData = $this->service->generateReportData($scheduleId, 1);

        $firstDay = $reportData['days'][0];
        // Should not exceed capacity (default 9)
        $this->assertLessThanOrEqual(9, count($firstDay['children']));
    }

    /**
     * Test always at end children are identified
     */
    public function testAlwaysAtEndIdentification(): void
    {
        $scheduleId = $this->createTestScheduleWithHighWeightChildren();
        
        $reportData = $this->service->generateReportData($scheduleId, 1);

        // Should have children with weight >= 10 in alwaysAtEnd
        $this->assertNotEmpty($reportData['alwaysAtEnd']);
        
        foreach ($reportData['alwaysAtEnd'] as $child) {
            $this->assertGreaterThanOrEqual(10, $child['weight']);
        }
    }

    /**
     * Helper: Create a test schedule
     */
    private function createTestSchedule(): int
    {
        $schedulesTable = $this->getTableLocator()->get('Schedules');
        $schedule = $schedulesTable->newEntity([
            'organization_id' => 1,
            'title' => 'Test Schedule',
            'starts_on' => '2025-01-01',
            'ends_on' => '2025-12-31',
            'state' => 'draft',
            'capacity_per_day' => 9,
        ]);
        $schedulesTable->save($schedule);
        
        return $schedule->id;
    }

    /**
     * Helper: Create test schedule with children
     */
    private function createTestScheduleWithChildren(): int
    {
        $scheduleId = $this->createTestSchedule();
        
        // Create schedule day
        $scheduleDaysTable = $this->getTableLocator()->get('ScheduleDays');
        $scheduleDay = $scheduleDaysTable->newEntity([
            'schedule_id' => $scheduleId,
            'title' => 'Day 1',
            'position' => 1,
            'capacity' => 9,
        ]);
        $scheduleDaysTable->save($scheduleDay);
        
        // Create children and assignments
        $childrenTable = $this->getTableLocator()->get('Children');
        $assignmentsTable = $this->getTableLocator()->get('Assignments');
        
        for ($i = 1; $i <= 5; $i++) {
            $child = $childrenTable->newEntity([
                'organization_id' => 1,
                'name' => 'Test Child ' . $i,
                'is_integrative' => false,
                'is_active' => true,
            ]);
            $childrenTable->save($child);
            
            $assignment = $assignmentsTable->newEntity([
                'schedule_day_id' => $scheduleDay->id,
                'child_id' => $child->id,
                'weight' => $i, // Different weights
                'source' => 'manual',
            ]);
            $assignmentsTable->save($assignment);
        }
        
        return $scheduleId;
    }

    /**
     * Helper: Create test schedule with many children
     */
    private function createTestScheduleWithManyChildren(): int
    {
        $scheduleId = $this->createTestSchedule();
        
        $scheduleDaysTable = $this->getTableLocator()->get('ScheduleDays');
        $scheduleDay = $scheduleDaysTable->newEntity([
            'schedule_id' => $scheduleId,
            'title' => 'Day 1',
            'position' => 1,
            'capacity' => 9,
        ]);
        $scheduleDaysTable->save($scheduleDay);
        
        $childrenTable = $this->getTableLocator()->get('Children');
        $assignmentsTable = $this->getTableLocator()->get('Assignments');
        
        // Create 15 children (more than capacity)
        for ($i = 1; $i <= 15; $i++) {
            $child = $childrenTable->newEntity([
                'organization_id' => 1,
                'name' => 'Child ' . $i,
                'is_integrative' => false,
                'is_active' => true,
            ]);
            $childrenTable->save($child);
            
            $assignment = $assignmentsTable->newEntity([
                'schedule_day_id' => $scheduleDay->id,
                'child_id' => $child->id,
                'weight' => $i,
                'source' => 'manual',
            ]);
            $assignmentsTable->save($assignment);
        }
        
        return $scheduleId;
    }

    /**
     * Helper: Create test schedule with high weight children
     */
    private function createTestScheduleWithHighWeightChildren(): int
    {
        $scheduleId = $this->createTestSchedule();
        
        $scheduleDaysTable = $this->getTableLocator()->get('ScheduleDays');
        $scheduleDay = $scheduleDaysTable->newEntity([
            'schedule_id' => $scheduleId,
            'title' => 'Day 1',
            'position' => 1,
            'capacity' => 9,
        ]);
        $scheduleDaysTable->save($scheduleDay);
        
        $childrenTable = $this->getTableLocator()->get('Children');
        $assignmentsTable = $this->getTableLocator()->get('Assignments');
        
        // Create 2 high-weight children
        foreach ([['Noah', 15], ['Amadeus', 12]] as $childData) {
            $child = $childrenTable->newEntity([
                'organization_id' => 1,
                'name' => $childData[0],
                'is_integrative' => false,
                'is_active' => true,
            ]);
            $childrenTable->save($child);
            
            $assignment = $assignmentsTable->newEntity([
                'schedule_day_id' => $scheduleDay->id,
                'child_id' => $child->id,
                'weight' => $childData[1],
                'source' => 'manual',
            ]);
            $assignmentsTable->save($assignment);
        }
        
        return $scheduleId;
    }
}
