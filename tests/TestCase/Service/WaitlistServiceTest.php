<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\WaitlistService;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * WaitlistService Test Case
 */
class WaitlistServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.Organizations',
        'app.Users',
        'app.Children',
        'app.SiblingGroups',
        'app.Schedules',
        'app.WaitlistEntries',
        'app.Rules',
    ];

    private WaitlistService $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = new WaitlistService();
    }

    public function tearDown(): void
    {
        unset($this->service);
        parent::tearDown();
    }

    /**
     * Test adding a child to waitlist
     */
    public function testAddToWaitlist(): void
    {
        // Create test data
        $organizations = TableRegistry::getTableLocator()->get('Organizations');
        $children = TableRegistry::getTableLocator()->get('Children');
        $schedules = TableRegistry::getTableLocator()->get('Schedules');

        // Create organization
        $org = $organizations->newEntity(['name' => 'Test Kita']);
        $organizations->save($org);

        // Create child
        $child = $children->newEntity([
            'organization_id' => $org->id,
            'name' => 'John Doe',
            'is_integrative' => false,
            'is_active' => true,
        ]);
        $children->save($child);

        // Create schedule
        $schedule = $schedules->newEntity([
            'organization_id' => $org->id,
            'title' => 'Test Schedule',
            'starts_on' => '2025-10-01',
            'ends_on' => '2025-10-31',
            'state' => 'draft',
        ]);
        $schedules->save($schedule);

        $result = $this->service->addToWaitlist(
            scheduleId: $schedule->id,
            childId: $child->id,
            priority: 5,
            remaining: 3
        );

        $this->assertTrue($result);

        // Verify entry was created
        $waitlistEntries = TableRegistry::getTableLocator()->get('WaitlistEntries');
        $entry = $waitlistEntries->find()
            ->where([
                'schedule_id' => $schedule->id,
                'child_id' => $child->id,
            ])
            ->first();

        $this->assertNotNull($entry);
        $this->assertEquals(5, $entry->priority);
        $this->assertEquals(3, $entry->remaining);
    }

    /**
     * Test adding duplicate entry fails
     */
    public function testAddToWaitlistDuplicateFails(): void
    {
        // Create test data
        $organizations = TableRegistry::getTableLocator()->get('Organizations');
        $children = TableRegistry::getTableLocator()->get('Children');
        $schedules = TableRegistry::getTableLocator()->get('Schedules');

        $org = $organizations->newEntity(['name' => 'Test Kita']);
        $organizations->save($org);

        $child = $children->newEntity([
            'organization_id' => $org->id,
            'name' => 'John Doe',
            'is_integrative' => false,
            'is_active' => true,
        ]);
        $children->save($child);

        $schedule = $schedules->newEntity([
            'organization_id' => $org->id,
            'title' => 'Test Schedule',
            'starts_on' => '2025-10-01',
            'ends_on' => '2025-10-31',
            'state' => 'draft',
        ]);
        $schedules->save($schedule);

        // Add first entry
        $this->service->addToWaitlist($schedule->id, $child->id, 5, 3);

        // Try to add duplicate
        $result = $this->service->addToWaitlist($schedule->id, $child->id, 5, 3);

        $this->assertFalse($result);
    }

    /**
     * Test removing from waitlist
     */
    public function testRemoveFromWaitlist(): void
    {
        // Create test data
        $organizations = TableRegistry::getTableLocator()->get('Organizations');
        $children = TableRegistry::getTableLocator()->get('Children');
        $schedules = TableRegistry::getTableLocator()->get('Schedules');

        $org = $organizations->newEntity(['name' => 'Test Kita']);
        $organizations->save($org);

        $child = $children->newEntity([
            'organization_id' => $org->id,
            'name' => 'John Doe',
            'is_integrative' => false,
            'is_active' => true,
        ]);
        $children->save($child);

        $schedule = $schedules->newEntity([
            'organization_id' => $org->id,
            'title' => 'Test Schedule',
            'starts_on' => '2025-10-01',
            'ends_on' => '2025-10-31',
            'state' => 'draft',
        ]);
        $schedules->save($schedule);

        // Add entry first
        $this->service->addToWaitlist($schedule->id, $child->id, 5, 3);

        // Get entry ID
        $waitlistEntries = TableRegistry::getTableLocator()->get('WaitlistEntries');
        $entry = $waitlistEntries->find()
            ->where([
                'schedule_id' => $schedule->id,
                'child_id' => $child->id,
            ])
            ->first();

        $this->assertNotNull($entry);

        // Remove it
        $result = $this->service->removeFromWaitlist($entry->id);
        $this->assertTrue($result);

        // Verify it's gone
        $deleted = $waitlistEntries->find()->where(['id' => $entry->id])->first();
        $this->assertNull($deleted);
    }

    /**
     * Test updating priority
     */
    public function testUpdatePriority(): void
    {
        // Create test data
        $organizations = TableRegistry::getTableLocator()->get('Organizations');
        $children = TableRegistry::getTableLocator()->get('Children');
        $schedules = TableRegistry::getTableLocator()->get('Schedules');

        $org = $organizations->newEntity(['name' => 'Test Kita']);
        $organizations->save($org);

        $child = $children->newEntity([
            'organization_id' => $org->id,
            'name' => 'John Doe',
            'is_integrative' => false,
            'is_active' => true,
        ]);
        $children->save($child);

        $schedule = $schedules->newEntity([
            'organization_id' => $org->id,
            'title' => 'Test Schedule',
            'starts_on' => '2025-10-01',
            'ends_on' => '2025-10-31',
            'state' => 'draft',
        ]);
        $schedules->save($schedule);

        // Add entry first
        $this->service->addToWaitlist($schedule->id, $child->id, 5, 3);

        // Get entry ID
        $waitlistEntries = TableRegistry::getTableLocator()->get('WaitlistEntries');
        $entry = $waitlistEntries->find()
            ->where([
                'schedule_id' => $schedule->id,
                'child_id' => $child->id,
            ])
            ->first();

        $this->assertNotNull($entry);
        $this->assertEquals(5, $entry->priority);

        // Update priority
        $result = $this->service->updatePriority($entry->id, 10);
        $this->assertTrue($result);

        // Verify update
        $entry = $waitlistEntries->get($entry->id);
        $this->assertEquals(10, $entry->priority);
    }

    /**
     * Test applying waitlist to schedule
     * 
     * ⚠️  DEPRECATED: applyToSchedule() is obsolete
     */
    public function testApplyToSchedule(): void
    {
        $this->markTestIncomplete('applyToSchedule() is deprecated - waitlist is source, report generates dynamically');
        
        // Create test data
        $organizations = TableRegistry::getTableLocator()->get('Organizations');
        $children = TableRegistry::getTableLocator()->get('Children');
        $schedules = TableRegistry::getTableLocator()->get('Schedules');
        $scheduleDays = TableRegistry::getTableLocator()->get('ScheduleDays');

        // Create organization
        $org = $organizations->newEntity(['name' => 'Test Kita']);
        $organizations->save($org);

        // Create children
        $child1 = $children->newEntity([
            'organization_id' => $org->id,
            'name' => 'John Doe',
            'is_integrative' => false,
            'is_active' => true,
        ]);
        $children->save($child1);

        $child2 = $children->newEntity([
            'organization_id' => $org->id,
            'name' => 'Jane Doe',
            'is_integrative' => false,
            'is_active' => true,
        ]);
        $children->save($child2);

        // Create schedule
        $schedule = $schedules->newEntity([
            'organization_id' => $org->id,
            'title' => 'Test Schedule',
            'starts_on' => '2025-10-01',
            'ends_on' => '2025-10-31',
            'state' => 'draft',
        ]);
        $schedules->save($schedule);

        // Create schedule days
        $day1 = $scheduleDays->newEntity([
            'schedule_id' => $schedule->id,
            'title' => 'Day 1',
            'capacity' => 5,
            'position' => 1,
        ]);
        $scheduleDays->save($day1);

        $day2 = $scheduleDays->newEntity([
            'schedule_id' => $schedule->id,
            'title' => 'Day 2',
            'capacity' => 5,
            'position' => 2,
        ]);
        $scheduleDays->save($day2);

        // Add children to waitlist
        $this->service->addToWaitlist($schedule->id, $child1->id, 10, 2); // High priority, wants 2 days
        $this->service->addToWaitlist($schedule->id, $child2->id, 5, 1);  // Lower priority, wants 1 day

        // Apply waitlist
        $assignmentsCreated = $this->service->applyToSchedule($schedule->id);

        // Should create 3 assignments (2 for child1, 1 for child2)
        $this->assertEquals(3, $assignmentsCreated);

        // Verify assignments were created
        $assignments = TableRegistry::getTableLocator()->get('Assignments');
        $child1Assignments = $assignments->find()
            ->where(['child_id' => $child1->id])
            ->all()
            ->count();
        $this->assertEquals(2, $child1Assignments);

        $child2Assignments = $assignments->find()
            ->where(['child_id' => $child2->id])
            ->all()
            ->count();
        $this->assertEquals(1, $child2Assignments);

        // Verify remaining counters were decremented
        $waitlistEntries = TableRegistry::getTableLocator()->get('WaitlistEntries');
        $entry1 = $waitlistEntries->find()
            ->where(['child_id' => $child1->id])
            ->first();
        $this->assertEquals(0, $entry1->remaining);

        $entry2 = $waitlistEntries->find()
            ->where(['child_id' => $child2->id])
            ->first();
        $this->assertEquals(0, $entry2->remaining);
    }

    /**
     * Test waitlist respects capacity
     * 
     * ⚠️  DEPRECATED: Tests applyToSchedule() which is obsolete
     */
    public function testWaitlistRespectsCapacity(): void
    {
        $this->markTestIncomplete('applyToSchedule() is deprecated - capacity is now checked in ReportService');
        
        // Create test data
        $organizations = TableRegistry::getTableLocator()->get('Organizations');
        $children = TableRegistry::getTableLocator()->get('Children');
        $schedules = TableRegistry::getTableLocator()->get('Schedules');
        $scheduleDays = TableRegistry::getTableLocator()->get('ScheduleDays');
        $assignments = TableRegistry::getTableLocator()->get('Assignments');

        // Create organization
        $org = $organizations->newEntity(['name' => 'Test Kita']);
        $organizations->save($org);

        // Create children
        $child1 = $children->newEntity([
            'organization_id' => $org->id,
            'name' => 'John Doe',
            'is_integrative' => false,
            'is_active' => true,
        ]);
        $children->save($child1);

        $child2 = $children->newEntity([
            'organization_id' => $org->id,
            'name' => 'Jane Doe',
            'is_integrative' => false,
            'is_active' => true,
        ]);
        $children->save($child2);

        // Create schedule
        $schedule = $schedules->newEntity([
            'organization_id' => $org->id,
            'title' => 'Test Schedule',
            'starts_on' => '2025-10-01',
            'ends_on' => '2025-10-31',
            'state' => 'draft',
        ]);
        $schedules->save($schedule);

        // Create schedule day with capacity 2
        $day = $scheduleDays->newEntity([
            'schedule_id' => $schedule->id,
            'title' => 'Day 1',
            'capacity' => 2,
            'position' => 1,
        ]);
        $scheduleDays->save($day);

        // Pre-fill 1 spot
        $preAssignment = $assignments->newEntity([
            'schedule_day_id' => $day->id,
            'child_id' => $child1->id,
            'weight' => 1,
            'source' => 'manual',
            'sort_order' => 0,
        ]);
        $assignments->save($preAssignment);

        // Add child2 to waitlist
        $this->service->addToWaitlist($schedule->id, $child2->id, 5, 1);

        // Apply waitlist
        $assignmentsCreated = $this->service->applyToSchedule($schedule->id);

        // Should create 1 assignment (capacity allows)
        $this->assertEquals(1, $assignmentsCreated);

        // Total assignments should be 2 (1 pre-existing + 1 new)
        $totalAssignments = $assignments->find()
            ->where(['schedule_day_id' => $day->id])
            ->all()
            ->count();
        $this->assertEquals(2, $totalAssignments);
    }

    /**
     * Test waitlist handles integrative children with double weight
     * 
     * ⚠️  DEPRECATED: Tests applyToSchedule() which is obsolete
     */
    public function testWaitlistIntegrativeWeight(): void
    {
        $this->markTestIncomplete('applyToSchedule() is deprecated - integrative weight now handled in ReportService');
        
        // Create test data
        $organizations = TableRegistry::getTableLocator()->get('Organizations');
        $children = TableRegistry::getTableLocator()->get('Children');
        $schedules = TableRegistry::getTableLocator()->get('Schedules');
        $scheduleDays = TableRegistry::getTableLocator()->get('ScheduleDays');
        $assignments = TableRegistry::getTableLocator()->get('Assignments');

        // Create organization
        $org = $organizations->newEntity(['name' => 'Test Kita']);
        $organizations->save($org);

        // Create integrative child
        $child = $children->newEntity([
            'organization_id' => $org->id,
            'name' => 'Special Needs',
            'is_integrative' => true,
            'is_active' => true,
        ]);
        $children->save($child);

        // Create schedule
        $schedule = $schedules->newEntity([
            'organization_id' => $org->id,
            'title' => 'Test Schedule',
            'starts_on' => '2025-10-01',
            'ends_on' => '2025-10-31',
            'state' => 'draft',
        ]);
        $schedules->save($schedule);

        // Create schedule day with capacity 5
        $day = $scheduleDays->newEntity([
            'schedule_id' => $schedule->id,
            'title' => 'Day 1',
            'capacity' => 5,
            'position' => 1,
        ]);
        $scheduleDays->save($day);

        // Add integrative child to waitlist
        $this->service->addToWaitlist($schedule->id, $child->id, 5, 1);

        // Apply waitlist
        $assignmentsCreated = $this->service->applyToSchedule($schedule->id);

        // Should create 1 assignment
        $this->assertEquals(1, $assignmentsCreated);

        // Verify weight is 2
        $assignment = $assignments->find()
            ->where(['child_id' => $child->id])
            ->first();
        $this->assertNotNull($assignment);
        $this->assertEquals(2, $assignment->weight);
    }
}
