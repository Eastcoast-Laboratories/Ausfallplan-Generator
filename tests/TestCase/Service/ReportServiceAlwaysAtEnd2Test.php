<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\ReportService;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Simple test for "Always at End" functionality
 */
class ReportServiceAlwaysAtEnd2Test extends TestCase
{
    protected array $fixtures = [
        'app.Organizations',
        'app.Users',
        'app.Schedules',
        'app.Children',
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
     * Test getAssignedChildren directly
     */
    public function testGetAssignedChildren(): void
    {
        $childrenTable = TableRegistry::getTableLocator()->get('Children');
        $schedulesTable = TableRegistry::getTableLocator()->get('Schedules');
        
        // Get first schedule from fixtures
        $schedule = $schedulesTable->find()->first();
        $this->assertNotNull($schedule, 'Schedule should exist from fixtures');
        
        // Create test child ASSIGNED to schedule but NOT on waitlist
        $child = $childrenTable->newEntity([
            'name' => 'Test Always At End Child',
            'organization_id' => $schedule->organization_id,
            'schedule_id' => $schedule->id,      // Assigned to schedule
            'waitlist_order' => null,           // NOT on waitlist
        ]);
        
        $result = $childrenTable->save($child);
        $this->assertNotFalse($result, 'Child should be saved');
        
        // Get child back from DB to verify
        $savedChild = $childrenTable->get($child->id);
        $this->assertEquals($schedule->id, $savedChild->schedule_id, 'Child should be assigned to schedule');
        $this->assertNull($savedChild->waitlist_order, 'Child should NOT be on waitlist');
        
        // Test getAssignedChildren via ReportService
        $reportData = $this->service->generateReportData($schedule->id, 3);
        
        $this->assertIsArray($reportData);
        $this->assertArrayHasKey('alwaysAtEnd', $reportData);
        
        // Find our child in alwaysAtEnd
        $alwaysAtEnd = $reportData['alwaysAtEnd'];
        $foundChild = false;
        
        foreach ($alwaysAtEnd as $item) {
            if ($item['child']->id === $child->id) {
                $foundChild = true;
                break;
            }
        }
        
        $this->assertTrue(
            $foundChild,
            'Child assigned to schedule but not on waitlist should appear in alwaysAtEnd. ' .
            'Found ' . count($alwaysAtEnd) . ' children in alwaysAtEnd.'
        );
    }
}
