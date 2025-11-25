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
    private const DEBUG_WAITLIST = false;
    
    /**
     * Animal names for days (German)
     */
    private const ANIMAL_NAMES = [
        'de' => [
            'A'=>['Ameisen', 'Aale'],
            'B'=>['Bienen', 'Biber'],
            'C'=>['Chamäleon'],
            'D'=>['Dachse', 'Dorsch'],
            'E'=>['Esel', 'Emu'],
            'F'=>['Fisch', 'Floh'],
            'G'=>['Gnu', 'Giraffen'],
            'H'=>['Hirsche', 'Hunde'],
            'I'=>['Insekten', 'Igel'],
            'J'=>['Jaguar'],
            'K'=>['Kamele', 'Kuh'],
            'L'=>['Luchse', 'Lurch'],
            'M'=>['Marabu', 'Mandril'],
            'N'=>['Nashörner', 'Nielpferd'],
            'O'=>['Ochsen'],
            'P'=>['Papageien', 'Pinguin'],
            'Q'=>['Quallen'],
            'R'=>['Rochen', 'Ratten'],
            'S'=>['Schlangen', 'Schildkröten'],
            'T'=>['Tiger', 'Tupan'],
            'U'=>['Uferschnepfen', 'Uhu'],
            'V'=>['Vögel'],
            'W'=>['Wale', 'Wildschwein'],
            'X'=>['Xerus'],
            'Y'=>['Yaks'],
            'Z'=>['Zilpzalp', 'Zebra']
        ],
        'en' => [
            'A'=>['Ant', 'Anteater'],
            'B'=>['Bees', 'Beaver', 'Bear'],
            'C'=>['Chameleon', 'Chicken'],
            'D'=>['Deer', 'Dog'],
            'E'=>['Elephant', 'Emu'],
            'F'=>['Fish', 'Frog'],
            'G'=>['Giraffes', 'Goats'],
            'H'=>['Horse', 'Hedgehog'],
            'I'=>['Insects', 'Iguanas'],
            'J'=>['Jaguars', 'Jellyfish'],
            'K'=>['Kangaroos'],
            'L'=>['Lions', 'Lizard'],
            'M'=>['Monkey', 'Mandril'],
            'N'=>['Narwhals'],
            'O'=>['Oxen'],
            'P'=>['Parrot', 'Penguin'],
            'Q'=>['Quails'],
            'R'=>['Rats', 'Raccoons'],
            'S'=>['Snake', 'Spider'],
            'T'=>['Tigers', 'Tupans', 'Turtles'],
            'U'=>['Uguis'],
            'V'=>['Vultures'],
            'W'=>['Whales'],
            'X'=>['Xerus'],
            'Y'=>['Yaks'],
            'Z'=>['Zebra']
        ]
    ];

    /**
     * Generate a shuffled animal names sequence for a schedule
     * 
     * @param string $locale Locale code (de, en)
     * @return array Shuffled animal names by letter
     */
    public function generateAnimalNamesSequence(string $locale = 'de'): array
    {
        $names = self::ANIMAL_NAMES[$locale] ?? self::ANIMAL_NAMES['de'];
        $shuffled = [];
        
        foreach ($names as $letter => $animals) {
            // Shuffle animals for this letter
            $shuffledAnimals = $animals;
            shuffle($shuffledAnimals);
            $shuffled[$letter] = $shuffledAnimals;
        }
        
        return $shuffled;
    }

    /**
     * Get animal name for a specific day index
     * 
     * @param array $sequence Animal names sequence
     * @param int $dayIndex Day index (0-based)
     * @return string Animal name
     */
    public function getAnimalNameForDay(array $sequence, int $dayIndex): string
    {
        $letters = array_keys($sequence);
        $letterCount = count($letters);
        
        // Determine which letter and which animal within that letter
        $letterIndex = $dayIndex % $letterCount;
        $animalIndex = (int)floor($dayIndex / $letterCount);
        
        $letter = $letters[$letterIndex];
        $animals = $sequence[$letter];
        
        // If we need more animals than available, cycle through
        $animalIndex = $animalIndex % count($animals);
        
        return $animals[$animalIndex];
    }

    /**
     * Generate report data for a schedule
     *
     * @param int $scheduleId Schedule ID
     * @param int $daysCount Number of days to generate
     * @return array Report data structure
     */
    public function generateReportData(int $scheduleId, int $daysCount, string $locale = 'de'): array
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

        // Get animal names sequence from schedule, or generate default
        $animalNamesSequence = null;
        
        if ($schedule->animal_names_sequence) {
            $sequences = @unserialize($schedule->animal_names_sequence);
            if (is_array($sequences)) {
                // New format: ['de' => [...], 'en' => [...]]
                if (isset($sequences[$locale])) {
                    $animalNamesSequence = $sequences[$locale];
                } elseif (isset($sequences['de'])) {
                    // Fallback to German if preferred locale not available
                    $animalNamesSequence = $sequences['de'];
                }
            } else {
                // Old format: direct array (backward compatibility)
                $animalNamesSequence = $sequences;
            }
        }
        
        if (!$animalNamesSequence) {
            // Fallback to default for specified locale
            $animalNamesSequence = self::ANIMAL_NAMES[$locale] ?? self::ANIMAL_NAMES['de'];
        }

        // Generate day boxes using sorted children with sibling logic
        $days = $this->generateDaysWithSiblings($daysCount, $sortedChildren, $schedule->capacity_per_day ?? 9, $animalNamesSequence);

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

                // Add sibling group as ONE unit in round-robin
                // All siblings come together on same days
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
     * 
     * @param int $daysCount Number of days to generate
     * @param array $childUnits Child units (singles or sibling groups)
     * @param int $capacity Capacity per day
     * @param array $animalNamesSequence Animal names sequence by letter
     * @return array Days with children and animal names
     */
    private function generateDaysWithSiblings(int $daysCount, array $childUnits, int $capacity, array $animalNamesSequence): array
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
        // Use proper round-robin with overflow queue
        $overflowQueue = []; // Units that didn't fit in previous day
        
        for ($i = 0; $i < $daysCount; $i++) {
            // Get animal name from sequence
            $animalName = $this->getAnimalNameForDay($animalNamesSequence, $i);
            
            $dayChildren = [];
            $countingSum = 0;
            $newOverflowQueue = [];
            
            // First, try to add units from overflow queue
            foreach ($overflowQueue as $unit) {
                if ($this->isUnitInDay($unit, $dayChildren)) {
                    continue; // Already in day
                }
                
                $unitCapacity = $this->getUnitCapacity($unit);
                if ($countingSum + $unitCapacity <= $capacity) {
                    $dayChildren = array_merge($dayChildren, $this->expandUnitToChildren($unit));
                    $countingSum += $unitCapacity;
                } else {
                    $newOverflowQueue[] = $unit; // Still doesn't fit, keep in overflow
                }
            }
            
            // Then, continue with normal round-robin
            $attempts = 0;
            $maxAttempts = count($childUnits) * 2;
            $triedUnitsThisDay = []; // Track which units we already tried this day
            
            while ($countingSum < $capacity && $attempts < $maxAttempts && !empty($childUnits)) {
                $unitIndex = $currentIndex % count($childUnits);
                $unit = $childUnits[$unitIndex];
                $currentIndex++;
                $attempts++;
                
                // Skip if already tried this unit today
                $unitKey = $this->getUnitKey($unit);
                if (isset($triedUnitsThisDay[$unitKey])) {
                    continue;
                }
                $triedUnitsThisDay[$unitKey] = true;
                
                // Skip if already in day
                if ($this->isUnitInDay($unit, $dayChildren)) {
                    continue;
                }
                
                $unitCapacity = $this->getUnitCapacity($unit);
                
                if ($countingSum + $unitCapacity <= $capacity) {
                    $dayChildren = array_merge($dayChildren, $this->expandUnitToChildren($unit));
                    $countingSum += $unitCapacity;
                } else {
                    // Doesn't fit - add to overflow queue for next day
                    if (!in_array($unit, $newOverflowQueue, true)) {
                        $newOverflowQueue[] = $unit;
                    }
                }
            }
            
            // FILL UP: If day is not full yet, continue with more children
            // Reset tried units and continue filling until capacity is reached
            if ($countingSum < $capacity && !empty($childUnits)) {
                $fillAttempts = 0;
                $maxFillAttempts = count($childUnits) * 10; // Allow more attempts for filling
                
                while ($countingSum < $capacity && $fillAttempts < $maxFillAttempts) {
                    $unitIndex = $currentIndex % count($childUnits);
                    $unit = $childUnits[$unitIndex];
                    $currentIndex++;
                    $fillAttempts++;
                    
                    $unitCapacity = $this->getUnitCapacity($unit);
                    
                    // Try to add if it fits (allow duplicates now to fill up)
                    if ($countingSum + $unitCapacity <= $capacity) {
                        $dayChildren = array_merge($dayChildren, $this->expandUnitToChildren($unit));
                        $countingSum += $unitCapacity;
                    }
                }
            }
            
            $overflowQueue = $newOverflowQueue;
            
            $days[] = [
                'number' => $i + 1,
                'animalName' => $animalName,
                'title' => sprintf('%s-'.__('Day') . ' %d', $animalName, $i + 1),
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
     * Get unique key for a unit (for tracking)
     */
    private function getUnitKey(array $unit): string
    {
        if ($unit['type'] === 'sibling_group') {
            return 'sibling_' . $unit['sibling_group_id'];
        } else {
            return 'single_' . $unit['child']->id;
        }
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
     * Balance sibling GROUP appearances in firstOnWaitlist
     * If one sibling GROUP appears much more than another GROUP, swap first children between groups
     * 
     * @param array $days Array of days with assignments
     * @param array $waitlistChildren Flat list of waitlist children
     * @param array $consecutiveSiblings Map of consecutive sibling groups
     * @return array Modified days array
     */
    private function balanceSiblingAppearances(array $days, array $waitlistChildren, array $consecutiveSiblings): array
    {
        // Count appearances per SIBLING GROUP (not per child!)
        $groupAppearances = [];
        $childToGroup = []; // Map child ID to group ID
        
        foreach ($consecutiveSiblings as $groupId => $siblingIds) {
            $groupAppearances[$groupId] = 0;
            foreach ($siblingIds as $sibId) {
                $childToGroup[$sibId] = $groupId;
            }
        }
        
        // Count how often each GROUP appears
        foreach ($days as $day) {
            if ($day['firstOnWaitlistChild']) {
                $childId = $day['firstOnWaitlistChild']['child']->id;
                if (isset($childToGroup[$childId])) {
                    $groupId = $childToGroup[$childId];
                    $groupAppearances[$groupId]++;
                }
            }
        }
        
        // Find groups with unbalanced appearances
        if (count($groupAppearances) < 2) {
            return $days; // Need at least 2 groups to balance
        }
        
        $maxGroupId = array_keys($groupAppearances, max($groupAppearances))[0];
        $minGroupId = array_keys($groupAppearances, min($groupAppearances))[0];
        $maxCount = $groupAppearances[$maxGroupId];
        $minCount = $groupAppearances[$minGroupId];
        
        // If difference > 1, try to swap first child of each group
        if ($maxCount - $minCount > 1) {
            $maxGroupFirstChild = $consecutiveSiblings[$maxGroupId][0]; // First child of max group
            $minGroupFirstChild = $consecutiveSiblings[$minGroupId][0]; // First child of min group
            
            // Find a day where maxGroup's first child is firstOnWaitlist and minGroup's first child is NOT in day
            foreach ($days as $dayIdx => $day) {
                if (!$day['firstOnWaitlistChild']) continue;
                
                $currentChildId = $day['firstOnWaitlistChild']['child']->id;
                
                // Check if current child is from max group
                if (!isset($childToGroup[$currentChildId]) || $childToGroup[$currentChildId] != $maxGroupId) {
                    continue;
                }
                
                // Check if minGroup's first child is in this day
                $minGroupFirstChildInDay = false;
                foreach ($day['children'] as $dc) {
                    if ($dc['child']->id == $minGroupFirstChild) {
                        $minGroupFirstChildInDay = true;
                        break;
                    }
                }
                
                if (!$minGroupFirstChildInDay) {
                    // Swap! Replace current child with minGroup's first child
                    $oldName = $day['firstOnWaitlistChild']['child']->name;
                    foreach ($waitlistChildren as $wc) {
                        if ($wc['child']->id == $minGroupFirstChild) {
                            $days[$dayIdx]['firstOnWaitlistChild'] = $wc;
                            $days[$dayIdx]['swappedSibling'] = [
                                'from' => $oldName,
                                'to' => $wc['child']->name,
                                'reason' => 'balance_groups'
                            ];
                            if (self::DEBUG_WAITLIST) {
                                error_log("Balanced sibling GROUPS: Replaced {$oldName} (group {$maxGroupId}) with {$wc['child']->name} (group {$minGroupId}) on day " . ($dayIdx + 1));
                            }
                            break 2; // Exit both loops, one swap is enough
                        }
                    }
                }
            }
        }
        
        return $days;
    }
}
