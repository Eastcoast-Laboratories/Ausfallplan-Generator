<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\TableRegistry;

/**
 * Report Service
 * 
 * Generates Ausfallplan (substitute plan) reports
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
        $childrenTable = TableRegistry::getTableLocator()->get('Children');
        $waitlistTable = TableRegistry::getTableLocator()->get('WaitlistEntries');
        $assignmentsTable = TableRegistry::getTableLocator()->get('Assignments');

        // Load schedule
        $schedule = $schedulesTable->get($scheduleId, contain: ['Organizations']);

        // Get all assigned children with their assignments
        $assignedChildren = $this->getAssignedChildren($scheduleId);
        
        // Get waitlist
        $waitlist = $waitlistTable->find()
            ->contain(['Children'])
            ->where(['WaitlistEntries.schedule_id' => $scheduleId])
            ->orderBy(['WaitlistEntries.priority' => 'ASC'])
            ->all()
            ->toArray();

        // Convert waitlist to children array (only waitlist children should appear in day boxes)
        $waitlistChildren = [];
        foreach ($waitlist as $entry) {
            $waitlistChildren[] = [
                'child' => $entry->child,
                'weight' => 1, // Default weight for waitlist children
                'is_integrative' => $entry->child->is_integrative,
            ];
        }

        // Generate day boxes (only with waitlist children, using round-robin)
        $days = $this->generateDays($daysCount, $waitlistChildren, $schedule->capacity_per_day ?? 9);

        // Find "always at end" children (assigned but NOT on waitlist)
        $alwaysAtEnd = $this->findAlwaysAtEndChildren($assignedChildren, $waitlist);

        // Calculate statistics for each waitlist child
        $childStats = $this->calculateChildStats($waitlist, $days);

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
     * Get assigned children with weights
     *
     * @param int $scheduleId
     * @return array
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
     * Generate days with children distribution
     *
     * @param int $daysCount
     * @param array $children Waitlist children in priority order
     * @param int $capacity Maximum counting children per day (default 9)
     * @return array
     */
    private function generateDays(int $daysCount, array $children, int $capacity): array
    {
        $days = [];
        $waitlistIndex = 0; // Current position in waitlist (round-robin)
        $currentDayChildren = []; // Children currently assigned to days

        for ($i = 0; $i < $daysCount; $i++) {
            $animalName = self::ANIMAL_NAMES[$i % count(self::ANIMAL_NAMES)];
            
            // Fill day with children from waitlist (round-robin), respecting capacity
            $dayChildren = [];
            $countingChildrenSum = 0;
            $attempts = 0;
            $maxAttempts = count($children) * 2; // Prevent infinite loop
            
            while ($countingChildrenSum < $capacity && $attempts < $maxAttempts) {
                if (empty($children)) {
                    break;
                }
                
                // Get next child from waitlist (round-robin)
                $nextChild = $children[$waitlistIndex % count($children)];
                $waitlistIndex++;
                $attempts++;
                
                // Check if child is already in this day
                $alreadyInDay = false;
                foreach ($dayChildren as $dc) {
                    if ($dc['child']->id === $nextChild['child']->id) {
                        $alreadyInDay = true;
                        break;
                    }
                }
                
                if ($alreadyInDay) {
                    continue;
                }
                
                // Calculate counting value (integrative = 2, normal = 1)
                $countingValue = $nextChild['is_integrative'] ? 2 : 1;
                
                // Check if adding this child would exceed capacity
                if ($countingChildrenSum + $countingValue <= $capacity) {
                    $dayChildren[] = $nextChild;
                    $countingChildrenSum += $countingValue;
                    $currentDayChildren[] = $nextChild;
                }
            }
            
            // Determine who leaves at end of day (first child from waitlist NOT already in this day)
            $leavingChild = null;
            foreach ($children as $waitlistChild) {
                $isInDay = false;
                foreach ($dayChildren as $dc) {
                    if ($dc['child']->id === $waitlistChild['child']->id) {
                        $isInDay = true;
                        break;
                    }
                }
                if (!$isInDay) {
                    $leavingChild = $waitlistChild;
                    break;
                }
            }

            $days[] = [
                'number' => $i + 1,
                'animalName' => $animalName,
                'title' => sprintf('%s-Tag %d', $animalName, $i + 1),
                'children' => $dayChildren,
                'leavingChild' => $leavingChild,
                'countingChildrenSum' => $countingChildrenSum,
            ];

            // Remove leaving child from current pool for next days
            if ($leavingChild) {
                $currentDayChildren = array_filter($currentDayChildren, function ($c) use ($leavingChild) {
                    return $c['child']->id !== $leavingChild['child']->id;
                });
            }
        }

        return $days;
    }

    /**
     * Distribute children for a single day
     *
     * @param array $children
     * @param int $capacity
     * @return array
     */
    private function distributeChildrenForDay(array $children, int $capacity): array
    {
        // Sort by weight (higher weight = higher priority to stay)
        usort($children, function ($a, $b) {
            return $b['weight'] <=> $a['weight'];
        });

        // Take up to capacity
        return array_slice($children, 0, min($capacity, count($children)));
    }

    /**
     * Find child that leaves at end of day (lowest weight)
     *
     * @param array $dayChildren
     * @return array|null
     */
    private function findLeavingChild(array $dayChildren): ?array
    {
        if (empty($dayChildren)) {
            return null;
        }

        // Find child with lowest weight
        $leavingChild = $dayChildren[0];
        foreach ($dayChildren as $child) {
            if ($child['weight'] < $leavingChild['weight']) {
                $leavingChild = $child;
            }
        }

        return $leavingChild;
    }

    /**
     * Find children that are "always at end"
     * These are children assigned to the schedule but NOT on the waitlist
     *
     * @param array $children All assigned children
     * @param array $waitlist Waitlist entries
     * @return array
     */
    private function findAlwaysAtEndChildren(array $children, array $waitlist): array
    {
        // Get IDs of children on waitlist
        $waitlistChildIds = [];
        foreach ($waitlist as $entry) {
            $waitlistChildIds[] = $entry->child_id;
        }

        // Return only children NOT on waitlist
        return array_filter($children, function ($child) use ($waitlistChildIds) {
            return !in_array($child['child']->id, $waitlistChildIds);
        });
    }

    /**
     * Calculate statistics for each child
     * - How many times they appear in day boxes
     * - How many times they appear as leaving child
     *
     * @param array $waitlist Waitlist entries
     * @param array $days Generated days
     * @return array Statistics per child ID
     */
    private function calculateChildStats(array $waitlist, array $days): array
    {
        $stats = [];
        
        // Initialize stats for all waitlist children
        foreach ($waitlist as $entry) {
            // Use child->id instead of child_id property
            $childId = $entry->child->id;
            $stats[$childId] = [
                'daysCount' => 0,
                'leavingCount' => 0,
            ];
        }
        
        // Count appearances in days and as leaving child
        foreach ($days as $day) {
            // Count children in day boxes
            if (isset($day['children'])) {
                foreach ($day['children'] as $childData) {
                    $childId = $childData['child']->id;
                    if (isset($stats[$childId])) {
                        $stats[$childId]['daysCount']++;
                    }
                }
            }
            
            // Count leaving children
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
