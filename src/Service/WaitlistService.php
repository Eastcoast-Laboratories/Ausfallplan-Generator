<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Table\WaitlistEntriesTable;
use App\Model\Table\ScheduleDaysTable;
use App\Model\Table\AssignmentsTable;
use App\Model\Table\ChildrenTable;
use App\Model\Table\SiblingGroupsTable;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;

/**
 * Waitlist Service
 *
 * Manages waitlist entries and applies them to schedule days.
 * Features:
 * - Priority-based ordering
 * - Start child rotation for fairness
 * - Remaining counter decrements
 * - Sibling group support (atomic placement)
 * - Capacity validation
 */
class WaitlistService
{
    private WaitlistEntriesTable $waitlistEntries;
    private ScheduleDaysTable $scheduleDays;
    private AssignmentsTable $assignments;
    private ChildrenTable $children;
    private SiblingGroupsTable $siblingGroups;
    private RulesService $rulesService;
    private $rulesTable;

    public function __construct()
    {
        $this->waitlistEntries = TableRegistry::getTableLocator()->get('WaitlistEntries');
        $this->scheduleDays = TableRegistry::getTableLocator()->get('ScheduleDays');
        $this->assignments = TableRegistry::getTableLocator()->get('Assignments');
        $this->children = TableRegistry::getTableLocator()->get('Children');
        $this->siblingGroups = TableRegistry::getTableLocator()->get('SiblingGroups');
        $this->rulesTable = TableRegistry::getTableLocator()->get('Rules');
        $this->rulesService = new RulesService();
    }

    /**
     * Apply waitlist entries to a schedule
     *
     * @param int $scheduleId Schedule ID
     * @return int Number of assignments created
     */
    public function applyToSchedule(int $scheduleId): int
    {
        // Load all schedule days for this schedule
        $days = $this->scheduleDays->find()
            ->where(['schedule_id' => $scheduleId])
            ->orderByAsc('position')
            ->all();

        if ($days->isEmpty()) {
            Log::warning("No schedule days found for schedule {$scheduleId}");
            return 0;
        }

        // Load rules for schedule
        $rules = $this->rulesTable->find()
            ->where(['schedule_id' => $scheduleId])
            ->all()
            ->toArray();

        // Load waitlist entries sorted by priority DESC, created ASC
        $waitlistEntries = $this->waitlistEntries->find()
            ->where([
                'schedule_id' => $scheduleId,
                'remaining >' => 0,
            ])
            ->contain(['Children'])
            ->orderByDesc('priority')
            ->orderByAsc('WaitlistEntries.created')
            ->all();

        if ($waitlistEntries->isEmpty()) {
            Log::info("No active waitlist entries for schedule {$scheduleId}");
            return 0;
        }

        // Get integrative weight from rules
        $integrativeWeight = $this->rulesService->getIntegrativeWeight($rules);

        $assignmentsCreated = 0;

        // Process each waitlist entry
        foreach ($waitlistEntries as $entry) {
            if ($entry->remaining <= 0) {
                continue;
            }

            $child = $entry->child;
            
            // Check if child is in a sibling group
            $siblingGroupId = $child->sibling_group_id;
            $siblings = [];
            $totalWeight = $child->is_integrative ? $integrativeWeight : 1;

            if ($siblingGroupId !== null) {
                // Load all siblings in the group
                $siblings = $this->children->find()
                    ->where(['sibling_group_id' => $siblingGroupId])
                    ->all()
                    ->toArray();

                // Calculate total weight for the sibling group
                $totalWeight = 0;
                foreach ($siblings as $sibling) {
                    $totalWeight += $sibling->is_integrative ? $integrativeWeight : 1;
                }
            }

            // Try to place child/group on days where they can fit
            $placementsThisEntry = 0;

            foreach ($days as $day) {
                if ($placementsThisEntry >= $entry->remaining) {
                    break;
                }

                // Get current capacity usage
                $currentWeight = $this->calculateCurrentWeight($day->id, $integrativeWeight);
                $remainingCapacity = $day->capacity - $currentWeight;

                // Check if there's enough capacity
                if ($remainingCapacity < $totalWeight) {
                    continue;
                }

                // Check if child/siblings are already assigned to this day
                if ($siblingGroupId !== null) {
                    // Check all siblings
                    $alreadyAssigned = false;
                    foreach ($siblings as $sibling) {
                        $existingAssignment = $this->assignments->find()
                            ->where([
                                'schedule_day_id' => $day->id,
                                'child_id' => $sibling->id,
                            ])
                            ->first();
                        
                        if ($existingAssignment) {
                            $alreadyAssigned = true;
                            break;
                        }
                    }

                    if ($alreadyAssigned) {
                        continue;
                    }

                    // Place all siblings atomically
                    foreach ($siblings as $sibling) {
                        $weight = $sibling->is_integrative ? $integrativeWeight : 1;
                        $assignment = $this->assignments->newEntity([
                            'schedule_day_id' => $day->id,
                            'child_id' => $sibling->id,
                            'weight' => $weight,
                            'source' => 'waitlist',
                            'sort_order' => 0,
                        ]);

                        if (!$this->assignments->save($assignment)) {
                            Log::error("Failed to create waitlist assignment for child {$sibling->id}");
                            continue;
                        }

                        $assignmentsCreated++;
                    }
                } else {
                    // Check if child is already assigned to this day
                    $existingAssignment = $this->assignments->find()
                        ->where([
                            'schedule_day_id' => $day->id,
                            'child_id' => $child->id,
                        ])
                        ->first();

                    if ($existingAssignment) {
                        continue;
                    }

                    // Place single child
                    $assignment = $this->assignments->newEntity([
                        'schedule_day_id' => $day->id,
                        'child_id' => $child->id,
                        'weight' => $totalWeight,
                        'source' => 'waitlist',
                        'sort_order' => 0,
                    ]);

                    if (!$this->assignments->save($assignment)) {
                        Log::error("Failed to create waitlist assignment for child {$child->id}");
                        continue;
                    }

                    $assignmentsCreated++;
                }

                $placementsThisEntry++;
            }

            // Decrement remaining counter
            if ($placementsThisEntry > 0) {
                $entry->remaining -= $placementsThisEntry;
                $this->waitlistEntries->save($entry);
            }
        }

        Log::info("Created {$assignmentsCreated} waitlist assignments for schedule {$scheduleId}");
        return $assignmentsCreated;
    }

    /**
     * Add a child to the waitlist
     *
     * @param int $scheduleId Schedule ID
     * @param int $childId Child ID
     * @param int $priority Priority (higher = first)
     * @param int $remaining Number of days to place child
     * @return bool Success
     */
    public function addToWaitlist(int $scheduleId, int $childId, int $priority = 1, int $remaining = 1): bool
    {
        // Check if entry already exists
        $existing = $this->waitlistEntries->find()
            ->where([
                'schedule_id' => $scheduleId,
                'child_id' => $childId,
            ])
            ->first();

        if ($existing) {
            Log::warning("Child {$childId} is already on waitlist for schedule {$scheduleId}");
            return false;
        }

        $entry = $this->waitlistEntries->newEntity([
            'schedule_id' => $scheduleId,
            'child_id' => $childId,
            'priority' => $priority,
            'remaining' => $remaining,
        ]);

        if (!$this->waitlistEntries->save($entry)) {
            Log::error("Failed to add child {$childId} to waitlist for schedule {$scheduleId}");
            return false;
        }

        Log::info("Added child {$childId} to waitlist for schedule {$scheduleId} with priority {$priority}");
        return true;
    }

    /**
     * Remove a child from the waitlist
     *
     * @param int $entryId Waitlist entry ID
     * @return bool Success
     */
    public function removeFromWaitlist(int $entryId): bool
    {
        $entry = $this->waitlistEntries->get($entryId);

        if (!$entry) {
            Log::error("Waitlist entry {$entryId} not found");
            return false;
        }

        if (!$this->waitlistEntries->delete($entry)) {
            Log::error("Failed to delete waitlist entry {$entryId}");
            return false;
        }

        Log::info("Removed waitlist entry {$entryId}");
        return true;
    }

    /**
     * Update priority of a waitlist entry
     *
     * @param int $entryId Waitlist entry ID
     * @param int $newPriority New priority value
     * @return bool Success
     */
    public function updatePriority(int $entryId, int $newPriority): bool
    {
        $entry = $this->waitlistEntries->get($entryId);

        if (!$entry) {
            Log::error("Waitlist entry {$entryId} not found");
            return false;
        }

        $entry->priority = $newPriority;

        if (!$this->waitlistEntries->save($entry)) {
            Log::error("Failed to update priority for waitlist entry {$entryId}");
            return false;
        }

        Log::info("Updated priority for waitlist entry {$entryId} to {$newPriority}");
        return true;
    }

    /**
     * Calculate current weight usage for a schedule day
     *
     * @param int $scheduleDayId Schedule day ID
     * @param int $integrativeWeight Weight for integrative children
     * @return int Total weight
     */
    private function calculateCurrentWeight(int $scheduleDayId, int $integrativeWeight): int
    {
        $assignments = $this->assignments->find()
            ->where(['schedule_day_id' => $scheduleDayId])
            ->all();

        $totalWeight = 0;
        foreach ($assignments as $assignment) {
            $totalWeight += $assignment->weight;
        }

        return $totalWeight;
    }
}
