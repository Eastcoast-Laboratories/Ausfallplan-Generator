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
     * Enable debug logging for waitlist
     */
    private const DEBUG_WAITLIST = true;
    
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
        
        // Build flat list of individual children for firstOnWaitlist round-robin
        // Flatten sibling groups into individual children
        $waitlistChildren = [];
        foreach ($childUnits as $unit) {
            if ($unit['type'] === 'sibling_group') {
                // Add each sibling individually
                foreach ($unit['siblings'] as $sib) {
                    if ($sib['child']->waitlist_order !== null) {
                        $waitlistChildren[] = [
                            'child' => $sib['child'],
                            'is_integrative' => $sib['is_integrative'],
                            'sibling_group_id' => $sib['child']->sibling_group_id,
                        ];
                    }
                }
            } else {
                // Single child
                if ($unit['child']->waitlist_order !== null) {
                    $waitlistChildren[] = [
                        'child' => $unit['child'],
                        'is_integrative' => $unit['is_integrative'],
                        'sibling_group_id' => $unit['child']->sibling_group_id,
                    ];
                }
            }
        }
        
        // Sort by waitlist_order ASC
        usort($waitlistChildren, function($a, $b) {
            return $a['child']->waitlist_order <=> $b['child']->waitlist_order;
        });
        
        // Analyze: Find consecutive sibling groups in waitlist
        $consecutiveSiblings = []; // [sibling_group_id => [child_ids]]
        for ($idx = 0; $idx < count($waitlistChildren); $idx++) {
            $child = $waitlistChildren[$idx];
            if (!$child['sibling_group_id']) continue;
            
            $siblingGroupId = $child['sibling_group_id'];
            if (isset($consecutiveSiblings[$siblingGroupId])) continue;
            
            // Collect consecutive siblings from this group
            $groupChildren = [$child['child']->id];
            $j = $idx + 1;
            while ($j < count($waitlistChildren)) {
                $nextChild = $waitlistChildren[$j];
                if ($nextChild['sibling_group_id'] == $siblingGroupId) {
                    $groupChildren[] = $nextChild['child']->id;
                    $j++;
                } else {
                    break;
                }
            }
            
            if (count($groupChildren) > 1) {
                $consecutiveSiblings[$siblingGroupId] = $groupChildren;
            }
        }
        
        // Helper function to refill queue with all children
        $refillQueue = function() use ($waitlistChildren, $consecutiveSiblings) {
            $queue = [];
            $skipUntilIndex = -1;
            for ($idx = 0; $idx < count($waitlistChildren); $idx++) {
                if ($idx <= $skipUntilIndex) continue;
                
                $child = $waitlistChildren[$idx];
                $siblingGroupId = $child['sibling_group_id'];
                
                if ($siblingGroupId && isset($consecutiveSiblings[$siblingGroupId])) {
                    $groupChildren = $consecutiveSiblings[$siblingGroupId];
                    $firstChildId = $groupChildren[0];
                    // Add first child N times (N = number of siblings)
                    foreach ($groupChildren as $sibId) {
                        $queue[] = $firstChildId;
                    }
                    $skipUntilIndex = $idx + count($groupChildren) - 1;
                } else {
                    $queue[] = $child['child']->id;
                }
            }
            return $queue;
        };
        
        // STEP 1: Generate days with children (without firstOnWaitlist yet)
        for ($i = 0; $i < $daysCount; $i++) {
            $animalName = self::ANIMAL_NAMES[$i % count(self::ANIMAL_NAMES)];
            
            $dayChildren = [];
            $countingSum = 0;
            
            // Fill day with units (simple round-robin)
            $attempts = 0;
            $maxAttempts = count($childUnits) * 2;
            
            while ($countingSum < $capacity && $attempts < $maxAttempts && !empty($childUnits)) {
                $unit = $childUnits[$currentIndex % count($childUnits)];
                $currentIndex++;
                $attempts++;
                
                // Skip if already in day
                if ($this->isUnitInDay($unit, $dayChildren)) {
                    continue;
                }
                
                $unitCapacity = $this->getUnitCapacity($unit);
                
                if ($countingSum + $unitCapacity <= $capacity) {
                    $dayChildren = array_merge($dayChildren, $this->expandUnitToChildren($unit));
                    $countingSum += $unitCapacity;
                }
            }
            
            $days[] = [
                'number' => $i + 1,
                'animalName' => $animalName,
                'title' => sprintf('%s-Tag %d', $animalName, $i + 1),
                'children' => $dayChildren,
                'firstOnWaitlistChild' => null, // Will be filled in step 2
                'countingChildrenSum' => $countingSum,
                'debugLog' => [],
            ];
        }
        
        // STEP 2: Generate firstOnWaitlist assignments
        // Try different start indices until all non-sibling children appear
        $bestAssignments = null;
        for ($startIdx = 0; $startIdx < count($waitlistChildren); $startIdx++) {
            $assignments = $this->generateFirstOnWaitlistAssignments(
                $daysCount,
                $days,
                $waitlistChildren,
                $consecutiveSiblings,
                $startIdx
            );
            
            if ($this->allNonSiblingChildrenAppear($assignments, $waitlistChildren)) {
                $bestAssignments = $assignments;
                if (self::DEBUG_WAITLIST) {
                    error_log("Found valid assignment with startIndex=$startIdx");
                }
                break;
            }
        }
        
        // If no valid assignment found, use first attempt
        if (!$bestAssignments) {
            $bestAssignments = $this->generateFirstOnWaitlistAssignments(
                $daysCount,
                $days,
                $waitlistChildren,
                $consecutiveSiblings,
                0
            );
            if (self::DEBUG_WAITLIST) {
                error_log("No valid assignment found, using startIndex=0");
            }
        }
        
        // Apply assignments to days
        foreach ($days as $i => $day) {
            $days[$i]['firstOnWaitlistChild'] = $bestAssignments[$i] ?? null;
        }
        
        // STEP 2.5: Balance sibling appearances
        // If one sibling appears much more than another, try to swap
        $days = $this->balanceSiblingAppearances($days, $waitlistChildren, $consecutiveSiblings);
        
        // STEP 3: Generate debug logs (if enabled)
        if (self::DEBUG_WAITLIST) {
            // Regenerate with debug logging
            $firstOnWaitlistQueue = $refillQueue();
            
            for ($i = 0; $i < $daysCount; $i++) {
                $debugLog = [];
                $debugLog[] = "=== Tag " . ($i + 1) . " ===";
                
                // Show queue with names
                $queueNames = [];
                foreach ($firstOnWaitlistQueue as $qid) {
                    foreach ($waitlistChildren as $wc) {
                        if ($wc['child']->id == $qid) {
                            $queueNames[] = $wc['child']->name;
                            break;
                        }
                    }
                }
                $debugLog[] = "Queue: [" . implode(", ", $queueNames) . "]";
                
                if ($days[$i]['firstOnWaitlistChild']) {
                    $usedName = $days[$i]['firstOnWaitlistChild']['child']->name;
                    
                    // Check if this was swapped
                    if (isset($days[$i]['swappedSibling'])) {
                        $swap = $days[$i]['swappedSibling'];
                        $debugLog[] = "✓ Verwendet: " . $usedName . " (getauscht von " . $swap['from'] . " → " . $swap['to'] . " für Balance)";
                    } else {
                        $debugLog[] = "✓ Verwendet: " . $usedName;
                    }
                    
                    // Remove from queue
                    $usedId = $days[$i]['firstOnWaitlistChild']['child']->id;
                    $key = array_search($usedId, $firstOnWaitlistQueue);
                    if ($key !== false) {
                        array_splice($firstOnWaitlistQueue, $key, 1);
                    }
                }
                
                $days[$i]['debugLog'] = $debugLog;
            }
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

    /**
     * Generate firstOnWaitlist assignments starting from a specific waitlist index
     * 
     * @param int $daysCount Number of days
     * @param array $days Array of days with children
     * @param array $waitlistChildren Flat list of waitlist children
     * @param array $consecutiveSiblings Map of consecutive sibling groups
     * @param int $startIndex Starting index in waitlist (0-based)
     * @return array Array of firstOnWaitlistChild for each day
     */
    private function generateFirstOnWaitlistAssignments(
        int $daysCount,
        array $days,
        array $waitlistChildren,
        array $consecutiveSiblings,
        int $startIndex
    ): array {
        // Helper function to refill queue with all children
        $refillQueue = function() use ($waitlistChildren, $consecutiveSiblings, $startIndex) {
            $queue = [];
            $skipUntilIndex = -1;
            
            // Start from startIndex and wrap around
            $totalChildren = count($waitlistChildren);
            for ($offset = 0; $offset < $totalChildren; $offset++) {
                $idx = ($startIndex + $offset) % $totalChildren;
                
                if ($idx <= $skipUntilIndex && $offset < $startIndex) continue;
                
                $child = $waitlistChildren[$idx];
                $siblingGroupId = $child['sibling_group_id'];
                
                if ($siblingGroupId && isset($consecutiveSiblings[$siblingGroupId])) {
                    $groupChildren = $consecutiveSiblings[$siblingGroupId];
                    $firstChildId = $groupChildren[0];
                    foreach ($groupChildren as $sibId) {
                        $queue[] = $firstChildId;
                    }
                    $skipUntilIndex = $idx + count($groupChildren) - 1;
                } else {
                    $queue[] = $child['child']->id;
                }
            }
            return $queue;
        };
        
        $firstOnWaitlistQueue = $refillQueue();
        $assignments = [];
        
        for ($i = 0; $i < $daysCount; $i++) {
            $dayChildren = $days[$i]['children'] ?? [];
            $firstOnWaitlistChild = null;
            
            if (!empty($waitlistChildren)) {
                $triedCount = 0;
                $queueSize = count($firstOnWaitlistQueue);
                
                while ($triedCount < $queueSize && !$firstOnWaitlistChild) {
                    $childId = $firstOnWaitlistQueue[$triedCount];
                    $triedCount++;
                    
                    $candidate = null;
                    foreach ($waitlistChildren as $wc) {
                        if ($wc['child']->id == $childId) {
                            $candidate = $wc;
                            break;
                        }
                    }
                    
                    if (!$candidate) continue;
                    
                    $isInDay = false;
                    foreach ($dayChildren as $dc) {
                        if ($dc['child']->id == $candidate['child']->id) {
                            $isInDay = true;
                            break;
                        }
                    }
                    
                    if (!$isInDay) {
                        array_splice($firstOnWaitlistQueue, $triedCount - 1, 1);
                        $firstOnWaitlistChild = $candidate;
                    }
                }
                
                // If no child found, refill and try again
                if (!$firstOnWaitlistChild && $triedCount >= $queueSize) {
                    $newChildren = $refillQueue();
                    foreach ($newChildren as $childId) {
                        $firstOnWaitlistQueue[] = $childId;
                    }
                    
                    $triedCount = 0;
                    $queueSize = count($firstOnWaitlistQueue);
                    while ($triedCount < $queueSize && !$firstOnWaitlistChild) {
                        $childId = $firstOnWaitlistQueue[$triedCount];
                        $triedCount++;
                        
                        $candidate = null;
                        foreach ($waitlistChildren as $wc) {
                            if ($wc['child']->id == $childId) {
                                $candidate = $wc;
                                break;
                            }
                        }
                        
                        if (!$candidate) continue;
                        
                        $isInDay = false;
                        foreach ($dayChildren as $dc) {
                            if ($dc['child']->id == $candidate['child']->id) {
                                $isInDay = true;
                                break;
                            }
                        }
                        
                        if (!$isInDay) {
                            array_splice($firstOnWaitlistQueue, $triedCount - 1, 1);
                            $firstOnWaitlistChild = $candidate;
                        }
                    }
                }
            }
            
            $assignments[] = $firstOnWaitlistChild;
        }
        
        return $assignments;
    }

    /**
     * Check if all non-sibling children appear at least once in assignments
     * 
     * @param array $assignments Array of firstOnWaitlistChild assignments
     * @param array $waitlistChildren Flat list of waitlist children
     * @return bool True if all non-sibling children appear at least once
     */
    private function allNonSiblingChildrenAppear(array $assignments, array $waitlistChildren): bool
    {
        // Get all non-sibling child IDs
        $nonSiblingChildIds = [];
        foreach ($waitlistChildren as $wc) {
            if (!$wc['sibling_group_id']) {
                $nonSiblingChildIds[] = $wc['child']->id;
            }
        }
        
        // Get all child IDs that appear in assignments
        $appearingChildIds = [];
        foreach ($assignments as $assignment) {
            if ($assignment) {
                $appearingChildIds[] = $assignment['child']->id;
            }
        }
        
        // Check if all non-sibling children appear
        foreach ($nonSiblingChildIds as $childId) {
            if (!in_array($childId, $appearingChildIds)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Balance sibling appearances in firstOnWaitlist
     * If one sibling appears much more than another from same group, try to swap
     * 
     * @param array $days Array of days with assignments
     * @param array $waitlistChildren Flat list of waitlist children
     * @param array $consecutiveSiblings Map of consecutive sibling groups
     * @return array Modified days array
     */
    private function balanceSiblingAppearances(array $days, array $waitlistChildren, array $consecutiveSiblings): array
    {
        // Count appearances per child in firstOnWaitlist
        $appearances = [];
        foreach ($days as $day) {
            if ($day['firstOnWaitlistChild']) {
                $childId = $day['firstOnWaitlistChild']['child']->id;
                $appearances[$childId] = ($appearances[$childId] ?? 0) + 1;
            }
        }
        
        // Find sibling groups with unbalanced appearances
        foreach ($consecutiveSiblings as $siblingGroupId => $siblingIds) {
            // Count appearances for each sibling in this group
            $siblingCounts = [];
            foreach ($siblingIds as $sibId) {
                $siblingCounts[$sibId] = $appearances[$sibId] ?? 0;
            }
            
            // Find max and min
            $maxCount = max($siblingCounts);
            $minCount = min($siblingCounts);
            
            // If difference > 1, try to balance
            if ($maxCount - $minCount > 1) {
                $maxSiblingId = array_search($maxCount, $siblingCounts);
                $minSiblingId = array_search($minCount, $siblingCounts);
                
                // Find a day where maxSibling is firstOnWaitlist and minSibling is NOT in day
                foreach ($days as $dayIdx => $day) {
                    if (!$day['firstOnWaitlistChild']) continue;
                    
                    $currentChildId = $day['firstOnWaitlistChild']['child']->id;
                    if ($currentChildId != $maxSiblingId) continue;
                    
                    // Check if minSibling is in this day
                    $minSiblingInDay = false;
                    foreach ($day['children'] as $dc) {
                        if ($dc['child']->id == $minSiblingId) {
                            $minSiblingInDay = true;
                            break;
                        }
                    }
                    
                    if (!$minSiblingInDay) {
                        // Swap! Replace maxSibling with minSibling
                        $oldName = $day['firstOnWaitlistChild']['child']->name;
                        foreach ($waitlistChildren as $wc) {
                            if ($wc['child']->id == $minSiblingId) {
                                $days[$dayIdx]['firstOnWaitlistChild'] = $wc;
                                $days[$dayIdx]['swappedSibling'] = [
                                    'from' => $oldName,
                                    'to' => $wc['child']->name,
                                    'reason' => 'balance'
                                ];
                                if (self::DEBUG_WAITLIST) {
                                    error_log("Balanced siblings: Replaced {$oldName} with {$wc['child']->name} on day " . ($dayIdx + 1));
                                }
                                break 2; // Exit both loops, one swap per group is enough
                            }
                        }
                    }
                }
            }
        }
        
        return $days;
    }
}
