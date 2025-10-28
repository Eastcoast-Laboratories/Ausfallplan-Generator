<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\WaitlistService;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * WaitlistService Test Case
 * 
 * Tests the core waitlist functionality:
 * - Adding children to waitlist
 * - Removing children from waitlist
 * - Reordering waitlist
 * - Sibling group handling in waitlist
 * - Waitlist ordering (waitlist_order field)
 */
class WaitlistServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.Organizations',
        'app.Users',
        'app.Children',
        'app.SiblingGroups',
        'app.Schedules',
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
            'days_count' => 5,
            'state' => 'draft',
        ]);
        $schedules->save($schedule);

        $result = $this->service->addToWaitlist(
            scheduleId: $schedule->id,
            childId: $child->id,
            waitlistOrder: 5
        );

        $this->assertTrue($result);

        // Verify child was updated
        $childrenTable = TableRegistry::getTableLocator()->get('Children');
        $updatedChild = $childrenTable->get($child->id);

        $this->assertNotNull($updatedChild->waitlist_order);
        $this->assertEquals(5, $updatedChild->waitlist_order);
        $this->assertEquals($schedule->id, $updatedChild->schedule_id);
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
            'days_count' => 5,
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
            'days_count' => 5,
            'state' => 'draft',
        ]);
        $schedules->save($schedule);

        // Add child to waitlist first
        $this->service->addToWaitlist($schedule->id, $child->id, 5);

        // Verify child is on waitlist
        $childrenTable = TableRegistry::getTableLocator()->get('Children');
        $updatedChild = $childrenTable->get($child->id);
        $this->assertNotNull($updatedChild->waitlist_order);

        // Remove it
        $result = $this->service->removeFromWaitlist($child->id);
        $this->assertTrue($result);

        // Verify waitlist_order is cleared
        $removedChild = $childrenTable->get($child->id);
        $this->assertNull($removedChild->waitlist_order);
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
            'days_count' => 5,
            'state' => 'draft',
        ]);
        $schedules->save($schedule);

        // Add child to waitlist first
        $this->service->addToWaitlist($schedule->id, $child->id, 5);

        // Verify initial order
        $childrenTable = TableRegistry::getTableLocator()->get('Children');
        $updatedChild = $childrenTable->get($child->id);
        $this->assertNotNull($updatedChild->waitlist_order);
        $this->assertEquals(5, $updatedChild->waitlist_order);

        // Update waitlist order
        $result = $this->service->updateWaitlistOrder($child->id, 10);
        $this->assertTrue($result);

        // Verify update
        $updatedChild = $childrenTable->get($child->id);
        $this->assertEquals(10, $updatedChild->waitlist_order);
    }
}
