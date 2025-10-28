<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Table\ChildrenTable;
use App\Model\Table\SiblingGroupsTable;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;

/**
 * Waitlist Service - NEW ARCHITECTURE
 * 
 * Uses children table fields directly:
 * - children.schedule_id (which schedule the child is assigned to)
 * - children.waitlist_order (position in waitlist, null = not on waitlist)
 * - children.organization_order (general org-wide ordering)
 * 
 * No separate waitlist_entries table anymore!
 */
class WaitlistService
{
    private ChildrenTable $children;
    private SiblingGroupsTable $siblingGroups;

    public function __construct()
    {
        $this->children = TableRegistry::getTableLocator()->get('Children');
        $this->siblingGroups = TableRegistry::getTableLocator()->get('SiblingGroups');
    }

    /**
     * Get children on waitlist for a schedule
     * 
     * @param int $scheduleId Schedule ID
     * @return array Children with waitlist_order set, ordered by waitlist_order ASC
     */
    public function getWaitlistChildren(int $scheduleId): array
    {
        return $this->children->find()
            ->where([
                'schedule_id' => $scheduleId,
                'waitlist_order IS NOT' => null
            ])
            ->orderBy(['waitlist_order' => 'ASC'])
            ->all()
            ->toArray();
    }

    /**
     * Add a child to the waitlist
     *
     * @param int $scheduleId Schedule ID
     * @param int $childId Child ID
     * @param int|null $waitlistOrder Position in waitlist (null = add at end)
     * @return bool Success
     */
    public function addToWaitlist(int $scheduleId, int $childId, ?int $waitlistOrder = null): bool
    {
        $child = $this->children->get($childId);

        // Check if already on waitlist for this schedule
        if ($child->schedule_id == $scheduleId && $child->waitlist_order !== null) {
            Log::warning("Child {$childId} is already on waitlist for schedule {$scheduleId}");
            return false;
        }

        // If no order specified, add at end
        if ($waitlistOrder === null) {
            $maxOrder = $this->children->find()
                ->where([
                    'schedule_id' => $scheduleId,
                    'waitlist_order IS NOT' => null
                ])
                ->select(['max_order' => $this->children->find()->func()->max('waitlist_order')])
                ->first();
            
            $waitlistOrder = ($maxOrder && $maxOrder->max_order !== null) ? $maxOrder->max_order + 1 : 1;
        }

        $child->schedule_id = $scheduleId;
        $child->waitlist_order = $waitlistOrder;

        if (!$this->children->save($child)) {
            Log::error("Failed to add child {$childId} to waitlist for schedule {$scheduleId}");
            return false;
        }

        Log::info("Added child {$childId} to waitlist for schedule {$scheduleId} with order {$waitlistOrder}");
        return true;
    }

    /**
     * Remove a child from the waitlist
     *
     * @param int $childId Child ID
     * @return bool Success
     */
    public function removeFromWaitlist(int $childId): bool
    {
        $child = $this->children->get($childId);

        if (!$child) {
            Log::error("Child {$childId} not found");
            return false;
        }

        if ($child->waitlist_order === null) {
            Log::warning("Child {$childId} is not on a waitlist");
            return false;
        }

        $child->waitlist_order = null;
        // Keep schedule_id - child stays "assigned" but not on waitlist

        if (!$this->children->save($child)) {
            Log::error("Failed to remove child {$childId} from waitlist");
            return false;
        }

        Log::info("Removed child {$childId} from waitlist");
        return true;
    }

    /**
     * Update waitlist order of a child
     *
     * @param int $childId Child ID
     * @param int $newOrder New waitlist order value
     * @return bool Success
     */
    public function updateWaitlistOrder(int $childId, int $newOrder): bool
    {
        $child = $this->children->get($childId);

        if (!$child) {
            Log::error("Child {$childId} not found");
            return false;
        }

        if ($child->waitlist_order === null) {
            Log::warning("Child {$childId} is not on a waitlist");
            return false;
        }

        $child->waitlist_order = $newOrder;

        if (!$this->children->save($child)) {
            Log::error("Failed to update waitlist order for child {$childId}");
            return false;
        }

        Log::info("Updated waitlist order for child {$childId} to {$newOrder}");
        return true;
    }

    /**
     * Reorder entire waitlist (shift orders)
     * 
     * @param int $scheduleId Schedule ID
     * @param int $childId Child to move
     * @param int $newPosition New position (1-based)
     * @return bool Success
     */
    public function reorderWaitlist(int $scheduleId, int $childId, int $newPosition): bool
    {
        $child = $this->children->get($childId);
        if (!$child || $child->schedule_id != $scheduleId) {
            return false;
        }

        $currentPosition = $child->waitlist_order;
        if ($currentPosition === null) {
            return false;
        }

        // Get all waitlist children for this schedule
        $waitlistChildren = $this->getWaitlistChildren($scheduleId);
        
        // Remove child from list temporarily
        $tempList = array_filter($waitlistChildren, fn($c) => $c->id != $childId);
        
        // Insert at new position
        array_splice($tempList, $newPosition - 1, 0, [$child]);
        
        // Save new orders
        $order = 1;
        foreach ($tempList as $c) {
            $c->waitlist_order = $order++;
            $this->children->save($c);
        }

        Log::info("Reordered waitlist for schedule {$scheduleId}, moved child {$childId} to position {$newPosition}");
        return true;
    }
}

