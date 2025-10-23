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

        return [
            'schedule' => $schedule,
            'days' => $days,
            'waitlist' => $waitlist,
            'alwaysAtEnd' => $alwaysAtEnd,
            'daysCount' => $daysCount,
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
     * @param array $children
     * @param int $capacity
     * @return array
     */
    private function generateDays(int $daysCount, array $children, int $capacity): array
    {
        $days = [];
        $childrenPool = $children;

        for ($i = 0; $i < $daysCount; $i++) {
            $animalName = self::ANIMAL_NAMES[$i % count(self::ANIMAL_NAMES)];
            
            // Distribute children for this day
            $dayChildren = $this->distributeChildrenForDay($childrenPool, $capacity);
            
            // Determine who leaves at end of day (lowest weight/priority)
            $leavingChild = $this->findLeavingChild($dayChildren);

            $days[] = [
                'number' => $i + 1,
                'animalName' => $animalName,
                'title' => sprintf('%s-Tag %d', $animalName, $i + 1),
                'children' => $dayChildren,
                'leavingChild' => $leavingChild,
            ];

            // Remove leaving child from pool for next day
            if ($leavingChild) {
                $childrenPool = array_filter($childrenPool, function ($c) use ($leavingChild) {
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
}
