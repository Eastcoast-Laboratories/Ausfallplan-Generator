<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\TableRegistry;

/**
 * Report Service
 * 
 * Generates Ausfallplan (substitute plan) reports
 * Uses Assignment sort_order instead of Waitlist priority
 * Handles sibling groups as atomic units
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

        // Get sorted children from assignments (NEW: using sort_order instead of waitlist)
        $sortedChildren = $this->getSortedChildrenFromAssignments($scheduleId);
        
        // Get waitlist for backward compatibility (still needed for "always at end" display)
        $waitlistTable = TableRegistry::getTableLocator()->get('WaitlistEntries');
        $waitlist = $waitlistTable->find()
            ->contain(['Children'])
            ->where(['WaitlistEntries.schedule_id' => $scheduleId])
            ->orderBy(['WaitlistEntries.priority' => 'ASC'])
            ->all()
            ->toArray();

        // Generate day boxes using sorted children with sibling logic
        $days = $this->generateDaysWithSiblings($daysCount, $sortedChildren, $schedule->capacity_per_day ?? 9);

        // Find "always at end" children
        $allAssignedChildren = $this->getAssignedChildren($scheduleId);
        $alwaysAtEnd = $this->findAlwaysAtEndChildren($allAssignedChildren, $sortedChildren);

        // Calculate statistics
        $childStats = $this->calculateChildStats($sortedChildren, $days);

        return [
            'schedule' => $schedule,
            'days' => $days,
            'waitlist' => $waitlist, // Keep for backward compatibility
            'alwaysAtEnd' => $alwaysAtEnd,
            'daysCount' => $daysCount,
            'childStats' => $childStats,
        ];
    }

    /**
     * Get sorted children from waitlist (NOT assignments!)
     * Groups siblings together, respects priority from waitlist_entries
     *
     * @param int $scheduleId
     * @return array Array of child units (singles or sibling groups)
     */
    private function getSortedChildrenFromAssignments(int $scheduleId): array
    {
        $waitlistTable = TableRegistry::getTableLocator()->get('WaitlistEntries');
        $childrenTable = TableRegistry::getTableLocator()->get('Children');
        
        // Get all child IDs from WAITLIST with their priority (not assignments!)
        $childSortMap = $waitlistTable->find()
            ->select(['child_id', 'priority'])
            ->where(['WaitlistEntries.schedule_id' => $scheduleId])
            ->orderBy(['priority' => 'ASC'])
            ->all()
            ->toArray();

        if (empty($childSortMap)) {
            return [];
        }

        $childIds = array_map(fn($row) => $row->child_id, $childSortMap);

        // Load all children with sibling_group_id
        $children = $childrenTable->find()
            ->where(['Children.id IN' => $childIds])
            ->all()
            ->toArray();

        // Create a map for quick access
        $childrenMap = [];
        foreach ($children as $child) {
            $childrenMap[$child->id] = $child;
        }

        // Group by sibling_group_id
        $siblingGroups = [];
        $processedIds = [];
        $result = [];

        foreach ($childSortMap as $row) {
            $childId = $row->child_id;
            
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
        $leavingIndex = 0;
        $skippedUnits = []; // Units that didn't fit (priority for next day)

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
            
            // Find leaving unit (not in this day)
            $leavingChild = null;
            $leavingAttempts = 0;
            while ($leavingAttempts < count($childUnits) && !$leavingChild) {
                $candidateUnit = $childUnits[$leavingIndex % count($childUnits)];
                $leavingIndex++;
                $leavingAttempts++;
                
                if (!$this->isUnitInDay($candidateUnit, $dayChildren)) {
                    // Return first child of unit for display
                    if ($candidateUnit['type'] === 'sibling_group') {
                        $leavingChild = $candidateUnit['siblings'][0];
                    } else {
                        $leavingChild = [
                            'child' => $candidateUnit['child'],
                            'is_integrative' => $candidateUnit['is_integrative'],
                        ];
                    }
                    break;
                }
            }

            $days[] = [
                'number' => $i + 1,
                'animalName' => $animalName,
                'title' => sprintf('%s-Tag %d', $animalName, $i + 1),
                'children' => $dayChildren,
                'leavingChild' => $leavingChild,
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
     * Get all assigned children (for "always at end")
     */
    private function getAssignedChildren(int $scheduleId): array
    {
        $assignmentsTable = TableRegistry::getTableLocator()->get('Assignments');
        
        $assignments = $assignmentsTable->find()
            ->contain(['Children', 'ScheduleDays'])
            ->innerJoinWith('ScheduleDays', function ($q) use ($scheduleId) {
                return $q->where(['ScheduleDays.schedule_id' => $scheduleId]);
            })
            ->all();

        $children = [];
        foreach ($assignments as $assignment) {
            $childId = $assignment->child->id;
            if (!isset($children[$childId])) {
                $children[$childId] = [
                    'child' => $assignment->child,
                    'weight' => $assignment->weight ?? 1,
                    'is_integrative' => $assignment->child->is_integrative,
                ];
            }
        }

        return array_values($children);
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
                    $stats[$sibling['child']->id] = ['daysCount' => 0, 'leavingCount' => 0];
                }
            } else {
                $stats[$unit['child']->id] = ['daysCount' => 0, 'leavingCount' => 0];
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
            
            if (isset($day['leavingChild']) && $day['leavingChild']) {
                $leavingChildId = $day['leavingChild']['child']->id;
                if (isset($stats[$leavingChildId])) {
                    $stats[$leavingChildId]['leavingCount']++;
                }
            }
        }
        
        return $stats;
    }
}
