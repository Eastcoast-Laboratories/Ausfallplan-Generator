<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\TableRegistry;

/**
 * Report Service
 * 
 * Generates Ausfallplan (substitute plan) reports.
 * Handles sibling groups as atomic units and respects waitlist ordering.
 */
class ReportService
{
    /**
     * Animal names for days (German)
     */
    private const ANIMAL_NAMES = [
        'Ameisen', 'Bienen', 'Chamäleon', 'Dachse', 'Esel', 'Fisch',
        'Gnu', 'Hirsche', 'Insekten', 'Jaguar', 'Kamele', 'Luchse',
        'Marabu', 'Nashörner', 'Ochsen', 'Papageien', 'Quallen', 'Rochen',
        'Schlangen', 'Tiger', 'Uferschnepfen', 'Vögel', 'Wale', 'Xerus',
        'Yaks', 'Zebras'
    ];

    /**
     * Generate report data for a schedule
     *
     * @param int $scheduleId Schedule ID
     * @param int $daysCount Number of days to generate
     * @return array Report data structure
     */
    public function generateReportData(int $scheduleId, int $daysCount): array
    {
        $schedulesTable = TableRegistry::getTableLocator()->get('Schedules');
        
        // Load schedule
        $schedule = $schedulesTable->get($scheduleId, contain: ['Organizations']);

        // Get sorted children by organization_order
        $sortedChildren = $this->getSortedChildrenByOrganizationOrder($scheduleId);
        
        // Get waitlist children for display
        // Only children with organization_order (exclude children without org_order)
        $childrenTable = TableRegistry::getTableLocator()->get('Children');
        $waitlist = $childrenTable->find()
            ->where([
                'schedule_id' => $scheduleId,
                'waitlist_order IS NOT' => null,
                'organization_order IS NOT' => null
            ])
            ->orderBy(['waitlist_order' => 'ASC'])
            ->all()
            ->toArray();

        // Generate day boxes using sorted children with sibling logic
        $days = $this->generateDaysWithSiblings($daysCount, $sortedChildren, $schedule->capacity_per_day ?? 9);

        // Find "always at end" children - those with schedule_id but NO waitlist_order
        $alwaysAtEnd = $this->getAssignedChildren($scheduleId);

        // Calculate statistics
        $childStats = $this->calculateChildStats($sortedChildren, $days);

        return [
            'schedule' => $schedule,
            'days' => $days,
            'waitlist' => $waitlist,
            'alwaysAtEnd' => $alwaysAtEnd,
            'daysCount' => $daysCount,
            'childStats' => $childStats,
        ];
    }

    /**
     * Get sorted children by organization_order
     * Groups siblings together, respects organization_order from children table
     * Children with organization_order = NULL are excluded from report
     *
     * @param int $scheduleId
     * @return array Array of child units (singles or sibling groups)
     */
    private function getSortedChildrenByOrganizationOrder(int $scheduleId): array
    {
        $childrenTable = TableRegistry::getTableLocator()->get('Children');
        
        // Get all children with organization_order (NOT NULL) for this schedule
        $children = $childrenTable->find()
            ->where([
                'schedule_id' => $scheduleId,
                'organization_order IS NOT' => null
            ])
            ->orderBy(['organization_order' => 'ASC'])
            ->all()
            ->toArray();

        if (empty($children)) {
            return [];
        }

        return $this->groupChildrenIntoUnits($children);
    }

    /**
     * Group children into units (singles or sibling groups)
     * 
     * @param array $children Array of child entities
     * @return array Array of child units
     */
    private function groupChildrenIntoUnits(array $children): array
    {
        // Create a map for quick access
        $childrenMap = [];
        foreach ($children as $child) {
            $childrenMap[$child->id] = $child;
        }

        // Group by sibling_group_id
        $processedIds = [];
        $result = [];

        foreach ($children as $child) {
            $childId = $child->id;
            
            if (in_array($childId, $processedIds)) {
                continue;
            }

            $child = $childrenMap[$childId] ?? null;
            if (!$child) {
                continue;
            }

            if ($child->sibling_group_id) {
                // Find all siblings
                $siblings = [];
                foreach ($children as $potentialSibling) {
                    if ($potentialSibling->sibling_group_id === $child->sibling_group_id) {
                        $siblings[] = [
                            'child' => $potentialSibling,
                            'is_integrative' => $potentialSibling->is_integrative,
                        ];
                        $processedIds[] = $potentialSibling->id;
                    }
                }

                // Add sibling group as a unit
                $result[] = [
                    'type' => 'sibling_group',
                    'sibling_group_id' => $child->sibling_group_id,
                    'siblings' => $siblings,
                    'total_capacity' => $this->calculateSiblingGroupCapacity($siblings),
                ];
            } else {
                // Single child
                $result[] = [
                    'type' => 'single',
                    'child' => $child,
                    'is_integrative' => $child->is_integrative,
                    'capacity' => $child->is_integrative ? 2 : 1,
                ];
                $processedIds[] = $childId;
            }
        }

        return $result;
    }

    /**
     * Calculate total capacity for a sibling group
     */
    private function calculateSiblingGroupCapacity(array $siblings): int
    {
        $total = 0;
        foreach ($siblings as $sibling) {
            $total += $sibling['is_integrative'] ? 2 : 1;
        }
        return $total;
    }

    /**
     * Generate days with sibling logic
     * Siblings are always together or not at all
     */
    private function generateDaysWithSiblings(int $daysCount, array $childUnits, int $capacity): array
    {
        $days = [];
        $currentIndex = 0;
        $skippedUnits = []; // Units that didn't fit (priority for next day)
        
        // Build list of units with waitlist_order for round-robin firstOnWaitlist
        $waitlistUnits = array_filter($childUnits, function($unit) {
            if ($unit['type'] === 'sibling_group') {
                return $unit['siblings'][0]['child']->waitlist_order !== null;
            }
            return $unit['child']->waitlist_order !== null;
        });
        $waitlistUnits = array_values($waitlistUnits); // Re-index
        $firstOnWaitlistIndex = 0;
        $firstOnWaitlistQueue = []; // Queue for children that were in day (try again next day)

        for ($i = 0; $i < $daysCount; $i++) {
            $animalName = self::ANIMAL_NAMES[$i % count(self::ANIMAL_NAMES)];
            
            $dayChildren = [];
            $countingSum = 0;
            
            // FIRST: Try to add skipped units from previous day
            $remainingSkipped = [];
            foreach ($skippedUnits as $unit) {
                $unitCapacity = $this->getUnitCapacity($unit);
                
                if ($countingSum + $unitCapacity <= $capacity) {
                    $dayChildren = array_merge($dayChildren, $this->expandUnitToChildren($unit));
                    $countingSum += $unitCapacity;
                } else {
                    $remainingSkipped[] = $unit;
                }
            }
            $skippedUnits = $remainingSkipped;
            
            // THEN: Continue with normal round-robin
            $attempts = 0;
            $maxAttempts = count($childUnits) * 3;
            
            while ($countingSum < $capacity && $attempts < $maxAttempts) {
                if (empty($childUnits)) {
                    break;
                }
                
                $unit = $childUnits[$currentIndex % count($childUnits)];
                $currentIndex++;
                $attempts++;
                
                // Check if unit already in day
                if ($this->isUnitInDay($unit, $dayChildren)) {
                    continue;
                }
                
                $unitCapacity = $this->getUnitCapacity($unit);
                
                if ($countingSum + $unitCapacity <= $capacity) {
                    $dayChildren = array_merge($dayChildren, $this->expandUnitToChildren($unit));
                    $countingSum += $unitCapacity;
                } else {
                    // Doesn't fit - skip for next day
                    $skippedUnits[] = $unit;
                }
            }
            
            // Find firstOnWaitlist unit (not in this day)
            // Strategy: 
            // 1. First try queue (children that were skipped because they were in day)
            // 2. If queue empty, use current index from waitlist
            // 3. If child is in day, add to queue for next day and try next child
            // 4. Only increment index when we use a child from the main list (not from queue)
            $firstOnWaitlistChild = null;
            
            if (!empty($waitlistUnits)) {
                // FIRST: Try children from queue (were in day before)
                if (!empty($firstOnWaitlistQueue)) {
                    $queueUnit = array_shift($firstOnWaitlistQueue);
                    
                    // Check if unit is NOT in this day
                    if (!$this->isUnitInDay($queueUnit, $dayChildren)) {
                        // Use it! (don't increment index - this was from queue)
                        if ($queueUnit['type'] === 'sibling_group') {
                            $firstOnWaitlistChild = $queueUnit['siblings'][0];
                        } else {
                            $firstOnWaitlistChild = [
                                'child' => $queueUnit['child'],
                                'is_integrative' => $queueUnit['is_integrative'],
                            ];
                        }
                    } else {
                        // Still in day, put back in queue
                        $firstOnWaitlistQueue[] = $queueUnit;
                    }
                }
                
                // SECOND: If no child from queue, try current index
                if (!$firstOnWaitlistChild) {
                    $candidateUnit = $waitlistUnits[$firstOnWaitlistIndex % count($waitlistUnits)];
                    
                    // Check if unit is NOT in this day
                    if (!$this->isUnitInDay($candidateUnit, $dayChildren)) {
                        // Use it and increment index
                        if ($candidateUnit['type'] === 'sibling_group') {
                            $firstOnWaitlistChild = $candidateUnit['siblings'][0];
                        } else {
                            $firstOnWaitlistChild = [
                                'child' => $candidateUnit['child'],
                                'is_integrative' => $candidateUnit['is_integrative'],
                            ];
                        }
                        // Increment index for next day
                        $firstOnWaitlistIndex++;
                    } else {
                        // Child is in day, add to queue (only if not already in queue) and increment index
                        $alreadyInQueue = false;
                        foreach ($firstOnWaitlistQueue as $qUnit) {
                            if ($this->isSameUnit($qUnit, $candidateUnit)) {
                                $alreadyInQueue = true;
                                break;
                            }
                        }
                        if (!$alreadyInQueue) {
                            $firstOnWaitlistQueue[] = $candidateUnit;
                        }
                        $firstOnWaitlistIndex++;
                        
                        // Try to find another child for this day (not in day and not in queue)
                        for ($attempt = 0; $attempt < count($waitlistUnits); $attempt++) {
                            $nextIndex = ($firstOnWaitlistIndex + $attempt) % count($waitlistUnits);
                            $nextUnit = $waitlistUnits[$nextIndex];
                            
                            // Skip if already in queue
                            $inQueue = false;
                            foreach ($firstOnWaitlistQueue as $qUnit) {
                                if ($this->isSameUnit($qUnit, $nextUnit)) {
                                    $inQueue = true;
                                    break;
                                }
                            }
                            
                            if (!$inQueue && !$this->isUnitInDay($nextUnit, $dayChildren)) {
                                // Found one!
                                if ($nextUnit['type'] === 'sibling_group') {
                                    $firstOnWaitlistChild = $nextUnit['siblings'][0];
                                } else {
                                    $firstOnWaitlistChild = [
                                        'child' => $nextUnit['child'],
                                        'is_integrative' => $nextUnit['is_integrative'],
                                    ];
                                }
                                break;
                            }
                        }
                    }
                }
            }

            $days[] = [
                'number' => $i + 1,
                'animalName' => $animalName,
                'title' => sprintf('%s-Tag %d', $animalName, $i + 1),
                'children' => $dayChildren,
                'firstOnWaitlistChild' => $firstOnWaitlistChild,
                'countingChildrenSum' => $countingSum,
            ];
        }

        return $days;
    }

    /**
     * Get capacity requirement for a unit
     */
    private function getUnitCapacity(array $unit): int
    {
        if ($unit['type'] === 'sibling_group') {
            return $unit['total_capacity'];
        }
        return $unit['capacity'];
    }

    /**
     * Expand unit to children array for day display
     */
    private function expandUnitToChildren(array $unit): array
    {
        if ($unit['type'] === 'sibling_group') {
            return $unit['siblings'];
        }
        return [[
            'child' => $unit['child'],
            'is_integrative' => $unit['is_integrative'],
        ]];
    }

    /**
     * Check if two units are the same
     */
    private function isSameUnit(array $unit1, array $unit2): bool
    {
        if ($unit1['type'] !== $unit2['type']) {
            return false;
        }
        
        if ($unit1['type'] === 'sibling_group') {
            // Compare by sibling group ID (first sibling's child ID)
            return $unit1['siblings'][0]['child']->id === $unit2['siblings'][0]['child']->id;
        }
        
        // Compare single children
        return $unit1['child']->id === $unit2['child']->id;
    }

    /**
     * Check if unit is already in day
     */
    private function isUnitInDay(array $unit, array $dayChildren): bool
    {
        if ($unit['type'] === 'sibling_group') {
            // Check if ANY sibling is in day
            foreach ($unit['siblings'] as $sibling) {
                foreach ($dayChildren as $dc) {
                    if ($dc['child']->id === $sibling['child']->id) {
                        return true;
                    }
                }
            }
        } else {
            // Check single child
            foreach ($dayChildren as $dc) {
                if ($dc['child']->id === $unit['child']->id) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get children for "always at end" section
     * 
     * Finds children assigned to schedule (schedule_id set) but not on waitlist (waitlist_order = NULL)
     */
    private function getAssignedChildren(int $scheduleId): array
    {
        $childrenTable = TableRegistry::getTableLocator()->get('Children');
        
        // Find children assigned to schedule but NOT on waitlist
        $children = $childrenTable->find()
            ->where([
                'schedule_id' => $scheduleId,
                'waitlist_order IS' => null  // NOT on waitlist
            ])
            ->all()
            ->toArray();
        
        // Format for compatibility with findAlwaysAtEndChildren
        $result = [];
        foreach ($children as $child) {
            $result[] = [
                'child' => $child,
                'weight' => $child->is_integrative ? 2 : 1
            ];
        }
        
        return $result;
    }

    /**
     * Find "always at end" children
     */
    private function findAlwaysAtEndChildren(array $allChildren, array $sortedUnits): array
    {
        $sortedChildIds = [];
        foreach ($sortedUnits as $unit) {
            if ($unit['type'] === 'sibling_group') {
                foreach ($unit['siblings'] as $sibling) {
                    $sortedChildIds[] = $sibling['child']->id;
                }
            } else {
                $sortedChildIds[] = $unit['child']->id;
            }
        }

        return array_filter($allChildren, function ($child) use ($sortedChildIds) {
            return !in_array($child['child']->id, $sortedChildIds);
        });
    }

    /**
     * Calculate child statistics
     */
    private function calculateChildStats(array $sortedUnits, array $days): array
    {
        $stats = [];
        
        // Initialize for all children
        foreach ($sortedUnits as $unit) {
            if ($unit['type'] === 'sibling_group') {
                foreach ($unit['siblings'] as $sibling) {
                    $stats[$sibling['child']->id] = ['daysCount' => 0, 'firstOnWaitlistCount' => 0];
                }
            } else {
                $stats[$unit['child']->id] = ['daysCount' => 0, 'firstOnWaitlistCount' => 0];
            }
        }
        
        // Count appearances
        foreach ($days as $day) {
            if (isset($day['children'])) {
                foreach ($day['children'] as $childData) {
                    $childId = $childData['child']->id;
                    if (isset($stats[$childId])) {
                        $stats[$childId]['daysCount']++;
                    }
                }
            }
            
            if (isset($day['firstOnWaitlistChild']) && $day['firstOnWaitlistChild']) {
                $firstOnWaitlistChildId = $day['firstOnWaitlistChild']['child']->id;
                if (isset($stats[$firstOnWaitlistChildId])) {
                    $stats[$firstOnWaitlistChildId]['firstOnWaitlistCount']++;
                }
            }
        }
        
        return $stats;
    }
}
