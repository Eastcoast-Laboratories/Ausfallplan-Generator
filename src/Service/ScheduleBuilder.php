<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Child;
use App\Model\Entity\Schedule;
use App\Model\Entity\ScheduleDay;
use App\Model\Table\AssignmentsTable;
use App\Model\Table\ChildrenTable;
use Cake\ORM\TableRegistry;

/**
 * Schedule Builder Service
 * Handles automatic distribution and waitlist application
 */
class ScheduleBuilder
{
    private RulesService $rulesService;
    private AssignmentsTable $assignmentsTable;
    private ChildrenTable $childrenTable;

    public function __construct()
    {
        $this->rulesService = new RulesService();
        $this->assignmentsTable = TableRegistry::getTableLocator()->get('Assignments');
        $this->childrenTable = TableRegistry::getTableLocator()->get('Children');
    }

    /**
     * Build schedule assignments automatically
     *
     * @param \App\Model\Entity\Schedule $schedule Schedule with days and rules
     * @return int Number of assignments created
     */
    public function build(Schedule $schedule): int
    {
        $integrativeWeight = $this->rulesService->getIntegrativeWeight($schedule->rules);
        $alwaysLast = $this->rulesService->getAlwaysLast($schedule->rules);
        $maxPerChild = $this->rulesService->getMaxPerChild($schedule->rules);

        // Get active children from organization
        $children = $this->childrenTable
            ->find()
            ->where([
                'organization_id' => $schedule->organization_id,
                'is_active' => true,
            ])
            ->contain(['SiblingGroups' => ['Children']])
            ->all()
            ->toArray();

        // Separate always_last children
        $normalChildren = [];
        $lastChildren = [];
        
        foreach ($children as $child) {
            if (in_array($child->name, $alwaysLast)) {
                $lastChildren[] = $child;
            } else {
                $normalChildren[] = $child;
            }
        }

        $assignmentsCreated = 0;

        // First pass: normal children
        $assignmentsCreated += $this->distributeChildren(
            $normalChildren,
            $schedule->schedule_days,
            $integrativeWeight,
            $maxPerChild
        );

        // Second pass: always_last children
        $assignmentsCreated += $this->distributeChildren(
            $lastChildren,
            $schedule->schedule_days,
            $integrativeWeight,
            $maxPerChild
        );

        return $assignmentsCreated;
    }

    /**
     * Distribute children across schedule days
     *
     * @param array<\App\Model\Entity\Child> $children Children to distribute
     * @param array<\App\Model\Entity\ScheduleDay> $days Schedule days
     * @param int $integrativeWeight Weight for integrative children
     * @param int $maxPerChild Maximum assignments per child
     * @return int Number of assignments created
     */
    private function distributeChildren(
        array $children,
        array $days,
        int $integrativeWeight,
        int $maxPerChild
    ): int {
        $assignmentsCreated = 0;
        $assignmentCounts = [];
        $dayIndex = 0;
        $dayCount = count($days);

        if ($dayCount === 0) {
            return 0;
        }

        foreach ($children as $child) {
            // Track assignments per child
            if (!isset($assignmentCounts[$child->id])) {
                $assignmentCounts[$child->id] = 0;
            }

            // Skip if max reached
            if ($assignmentCounts[$child->id] >= $maxPerChild) {
                continue;
            }

            // Handle sibling groups
            if ($child->sibling_group_id) {
                $siblings = $this->getSiblingsForGroup($child->sibling_group_id, $children);
                $groupWeight = $this->calculateGroupWeight($siblings, $integrativeWeight);

                // Try to place group
                for ($i = 0; $i < $dayCount; $i++) {
                    $day = $days[$dayIndex];
                    $currentLoad = $this->calculateDayLoad($day);

                    if ($currentLoad + $groupWeight <= $day->capacity) {
                        // Place all siblings
                        foreach ($siblings as $sibling) {
                            $weight = $sibling->is_integrative ? $integrativeWeight : 1;
                            $this->createAssignment($day, $sibling, $weight, 'auto');
                            $assignmentsCreated++;
                            $assignmentCounts[$sibling->id] = ($assignmentCounts[$sibling->id] ?? 0) + 1;
                        }
                        break;
                    }

                    $dayIndex = ($dayIndex + 1) % $dayCount;
                }
            } else {
                // Place individual child
                $weight = $child->is_integrative ? $integrativeWeight : 1;

                for ($i = 0; $i < $dayCount; $i++) {
                    $day = $days[$dayIndex];
                    $currentLoad = $this->calculateDayLoad($day);

                    if ($currentLoad + $weight <= $day->capacity) {
                        $this->createAssignment($day, $child, $weight, 'auto');
                        $assignmentsCreated++;
                        $assignmentCounts[$child->id]++;
                        $dayIndex = ($dayIndex + 1) % $dayCount;
                        break;
                    }

                    $dayIndex = ($dayIndex + 1) % $dayCount;
                }
            }
        }

        return $assignmentsCreated;
    }

    /**
     * Get siblings in a group from child list
     *
     * @param int $groupId Sibling group ID
     * @param array<\App\Model\Entity\Child> $children All children
     * @return array<\App\Model\Entity\Child> Siblings in group
     */
    private function getSiblingsForGroup(int $groupId, array $children): array
    {
        $siblings = [];
        foreach ($children as $child) {
            if ($child->sibling_group_id === $groupId) {
                $siblings[] = $child;
            }
        }
        return $siblings;
    }

    /**
     * Calculate total weight for a sibling group
     *
     * @param array<\App\Model\Entity\Child> $siblings Siblings
     * @param int $integrativeWeight Weight for integrative children
     * @return int Total weight
     */
    private function calculateGroupWeight(array $siblings, int $integrativeWeight): int
    {
        $weight = 0;
        foreach ($siblings as $sibling) {
            $weight += $sibling->is_integrative ? $integrativeWeight : 1;
        }
        return $weight;
    }

    /**
     * Calculate current load on a day
     *
     * @param \App\Model\Entity\ScheduleDay $day Schedule day
     * @return int Current weight sum
     */
    private function calculateDayLoad(ScheduleDay $day): int
    {
        if (!isset($day->assignments)) {
            return 0;
        }

        $load = 0;
        foreach ($day->assignments as $assignment) {
            $load += $assignment->weight;
        }
        return $load;
    }

    /**
     * Create an assignment
     *
     * @param \App\Model\Entity\ScheduleDay $day Schedule day
     * @param \App\Model\Entity\Child $child Child
     * @param int $weight Assignment weight
     * @param string $source Assignment source
     * @return void
     */
    private function createAssignment(
        ScheduleDay $day,
        Child $child,
        int $weight,
        string $source
    ): void {
        $assignment = $this->assignmentsTable->newEntity([
            'schedule_day_id' => $day->id,
            'child_id' => $child->id,
            'weight' => $weight,
            'source' => $source,
        ]);

        $this->assignmentsTable->save($assignment);
    }
}
