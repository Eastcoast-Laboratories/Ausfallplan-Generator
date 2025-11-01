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
        
        // Initialize queue
        $firstOnWaitlistQueue = $refillQueue();

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
            
            // Find firstOnWaitlist child (not in this day)
            // Work through queue until we find one that fits
            $firstOnWaitlistChild = null;
            $debugLog = []; // Debug info for this day
            
            if (!empty($waitlistChildren)) {
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
                
                // Try each child in queue until we find one that fits
                $triedCount = 0;
                $queueSize = count($firstOnWaitlistQueue);
                
                while ($triedCount < $queueSize && !$firstOnWaitlistChild) {
                    $childId = $firstOnWaitlistQueue[$triedCount]; // Read from queue (don't remove yet)
                    $triedCount++;
                    
                    // Find child data
                    $candidate = null;
                    foreach ($waitlistChildren as $wc) {
                        if ($wc['child']->id == $childId) {
                            $candidate = $wc;
                            break;
                        }
                    }
                    
                    if (!$candidate) continue;
                    
                    $debugLog[] = "Versuch: " . $candidate['child']->name;
                    
                    // Check if child is in this day
                    $isInDay = false;
                    foreach ($dayChildren as $dc) {
                        if ($dc['child']->id == $candidate['child']->id) {
                            $isInDay = true;
                            break;
                        }
                    }
                    
                    if (!$isInDay) {
                        // Found one! Remove from queue now
                        array_splice($firstOnWaitlistQueue, $triedCount - 1, 1);
                        $firstOnWaitlistChild = $candidate;
                        $debugLog[] = "✓ Verwendet: " . $candidate['child']->name . " (aus Queue entfernt)";
                    }
                }
                
                // If no child was found, refill queue and try again
                if (!$firstOnWaitlistChild && $triedCount >= $queueSize) {
                    $debugLog[] = "Kein Kind gefunden → Queue neu befüllen und nochmal versuchen";
                    $newChildren = $refillQueue();
                    foreach ($newChildren as $childId) {
                        $firstOnWaitlistQueue[] = $childId;
                    }
                    
                    // Try again with refilled queue
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
                        
                        $debugLog[] = "Versuch (2. Runde): " . $candidate['child']->name;
                        
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
                            $debugLog[] = "✓ Verwendet: " . $candidate['child']->name . " (aus Queue entfernt)";
                        }
                    }
                }
                
                // Show final queue state
                if (self::DEBUG_WAITLIST) {
                    $finalQueueNames = [];
                    foreach ($firstOnWaitlistQueue as $qid) {
                        foreach ($waitlistChildren as $wc) {
                            if ($wc['child']->id == $qid) {
                                $finalQueueNames[] = $wc['child']->name;
                                break;
                            }
                        }
                    }
                    $debugLog[] = "Queue nach Tag: [" . implode(", ", $finalQueueNames) . "]";
                }
            }

            if(!self::DEBUG_WAITLIST) {
                $debugLog = [];
            }
            $days[] = [
                'number' => $i + 1,
                'animalName' => $animalName,
                'title' => sprintf('%s-Tag %d', $animalName, $i + 1),
                'children' => $dayChildren,
                'firstOnWaitlistChild' => $firstOnWaitlistChild,
                'countingChildrenSum' => $countingSum,
                'debugLog' => $debugLog,
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
}
