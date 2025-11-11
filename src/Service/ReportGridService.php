<?php
declare(strict_types=1);

namespace App\Service;

/**
 * Report Grid Service (Punkt 5)
 * 
 * Generates reports as 2D arrays with cell types for flexible export:
 * - HTML display
 * - CSV export
 * - Excel export with formulas
 */
class ReportGridService
{
    // Cell types
    public const CELL_EMPTY = 'empty';
    public const CELL_HEADER = 'header';
    public const CELL_CHILD = 'child';
    public const CELL_WAITLIST = 'waitlist';
    public const CELL_CHECKSUM = 'checksum';
    public const CELL_LABEL = 'label';
    public const CELL_FIRSTONWAITLIST = 'firstOnWaitlist';
    public const CELL_STATS = 'stats';

    /**
     * Generate 2D grid from report data
     *
     * @param array $reportData Report data from ReportService
     * @return array 2D grid with cell types
     */
    public function generateGrid(array $reportData): array
    {
        $days = $reportData['days'];
        $waitlist = $reportData['waitlist'];
        $alwaysAtEnd = $reportData['alwaysAtEnd'];
        $childStats = $reportData['childStats'];
        $schedule = $reportData['schedule'];

        $grid = [];
        
        // Calculate grid dimensions
        $maxChildrenPerDay = 0;
        foreach ($days as $day) {
            $childCount = count($day['children'] ?? []);
            if ($childCount > $maxChildrenPerDay) {
                $maxChildrenPerDay = $childCount;
            }
        }
        
        // Calculate minimum rows needed
        $minRowsForWaitlist = count($waitlist) + 2; // waitlist children + header + 1 empty row
        $minRowsForAlwaysAtEnd = !empty($alwaysAtEnd) ? count($alwaysAtEnd) + 1 : 0; // always at end children + label
        $minRowsForRightColumn = $minRowsForWaitlist + $minRowsForAlwaysAtEnd;
        
        $rowsPerDay = max($maxChildrenPerDay + 3, 10, $minRowsForRightColumn); // Ensure enough rows for right column
        $waitlistWidth = 5; // Waitlist columns on right
        
        // Build grid row by row
        $rowIndex = 0;
        
        // Header row
        $grid[] = $this->buildHeaderRow($days, $waitlistWidth);
        $rowIndex++;
        
        // Content rows - iterate through days
        for ($dayRow = 0; $dayRow < $rowsPerDay; $dayRow++) {
            $row = [];
            
            foreach ($days as $dayIndex => $day) {
                $dayChildren = $day['children'] ?? [];
                
                if ($dayRow < count($dayChildren)) {
                    // Child row
                    $childData = $dayChildren[$dayRow];
                    $row[] = $this->createCell(
                        self::CELL_CHILD,
                        $childData['child']->name,
                        [
                            'child' => $childData['child'], // Pass full child entity for encryption
                            'child_id' => $childData['child']->id,
                            'is_integrative' => $childData['is_integrative'],
                            'weight' => $childData['is_integrative'] ? 2 : 1
                        ]
                    );
                } elseif ($dayRow == count($dayChildren)) {
                    // Leaving child row
                    $firstOnWaitlistChild = $day['firstOnWaitlistChild'] ?? null;
                    if ($firstOnWaitlistChild) {
                        $row[] = $this->createCell(
                            self::CELL_FIRSTONWAITLIST,
                            '→ ' . $firstOnWaitlistChild['child']->name,
                            [
                                'child' => $firstOnWaitlistChild['child'], // Pass full child entity for encryption
                                'child_id' => $firstOnWaitlistChild['child']->id,
                                'is_integrative' => $firstOnWaitlistChild['is_integrative']
                            ]
                        );
                    } else {
                        $row[] = $this->createCell(self::CELL_EMPTY, '');
                    }
                } elseif ($dayRow == count($dayChildren) + 1) {
                    // Checksum row
                    $row[] = $this->createCell(
                        self::CELL_CHECKSUM,
                        $day['countingChildrenSum'] ?? 0,
                        [
                            'day_index' => $dayIndex,
                            'formula_range_start' => $rowIndex - count($dayChildren),
                            'formula_range_end' => $rowIndex - 1,
                            'column' => $dayIndex
                        ]
                    );
                } else {
                    // Empty cell
                    $row[] = $this->createCell(self::CELL_EMPTY, '');
                }
            }
            
            // Add waitlist column (right side)
            if ($dayRow == 0) {
                // Header
                $row[] = $this->createCell(self::CELL_LABEL, 'Nachrückliste', ['style' => 'bold']);
            } elseif ($dayRow > 0 && $dayRow - 1 < count($waitlist)) {
                // Waitlist children
                $waitlistChild = $waitlist[$dayRow - 1];
                $row[] = $this->createCell(
                    self::CELL_WAITLIST,
                    $waitlistChild->name,
                    [
                        'child' => $waitlistChild, // Pass full child entity for encryption
                        'child_id' => $waitlistChild->id,
                        'order' => $waitlistChild->waitlist_order,
                        'is_integrative' => $waitlistChild->is_integrative
                    ]
                );
            } elseif ($dayRow == count($waitlist) + 1 && !empty($alwaysAtEnd)) {
                // "Always at end" label (after waitlist children + 1 empty row)
                $row[] = $this->createCell(self::CELL_LABEL, __('Always at end'), ['style' => 'bold']);
            } elseif ($dayRow > count($waitlist) + 1 && !empty($alwaysAtEnd)) {
                // "Always at end" children
                $alwaysAtEndIndex = $dayRow - count($waitlist) - 2;
                if ($alwaysAtEndIndex < count($alwaysAtEnd)) {
                    $alwaysAtEndChild = $alwaysAtEnd[$alwaysAtEndIndex];
                    $row[] = $this->createCell(
                        self::CELL_CHILD,
                        $alwaysAtEndChild['child']->name,
                        [
                            'child' => $alwaysAtEndChild['child'], // Pass full child entity for encryption
                            'child_id' => $alwaysAtEndChild['child']->id,
                            'is_integrative' => $alwaysAtEndChild['weight'] == 2,
                            'always_at_end' => true
                        ]
                    );
                } else {
                    $row[] = $this->createCell(self::CELL_EMPTY, '');
                }
            } else {
                $row[] = $this->createCell(self::CELL_EMPTY, '');
            }
            
            $grid[] = $row;
            $rowIndex++;
        }
        
        // Statistics section (always at end is now in right column)
        $grid[] = $this->buildSeparatorRow(count($days) + $waitlistWidth);
        $grid = array_merge($grid, $this->buildStatsSection($childStats, $days, $waitlist, count($days), $waitlistWidth));
        
        return [
            'grid' => $grid,
            'metadata' => [
                'days_count' => count($days),
                'waitlist_width' => $waitlistWidth,
                'total_rows' => count($grid),
                'total_cols' => count($days) + $waitlistWidth,
                'schedule_title' => $schedule->title,
                'schedule_id' => $schedule->id
            ]
        ];
    }

    /**
     * Create a cell structure
     */
    private function createCell(string $type, $value, array $metadata = []): array
    {
        return [
            'type' => $type,
            'value' => $value,
            'metadata' => $metadata
        ];
    }

    /**
     * Build header row
     */
    private function buildHeaderRow(array $days, int $waitlistWidth): array
    {
        $row = [];
        
        foreach ($days as $day) {
            $row[] = $this->createCell(
                self::CELL_HEADER,
                $day['animalName'] . '-Tag ' . $day['number'],
                ['day_number' => $day['number'], 'animal' => $day['animalName']]
            );
        }
        
        // Waitlist header
        $row[] = $this->createCell(self::CELL_HEADER, 'Warteliste', ['style' => 'bold']);
        
        // Fill remaining waitlist columns
        for ($i = 1; $i < $waitlistWidth; $i++) {
            $row[] = $this->createCell(self::CELL_EMPTY, '');
        }
        
        return $row;
    }

    /**
     * Build separator row
     */
    private function buildSeparatorRow(int $width): array
    {
        $row = [];
        for ($i = 0; $i < $width; $i++) {
            $row[] = $this->createCell(self::CELL_EMPTY, '---');
        }
        return $row;
    }

    /**
     * Build "Always at End" section
     */
    private function buildAlwaysAtEndSection(array $alwaysAtEnd, int $daysCount, int $waitlistWidth): array
    {
        $row = [];
        
        // Label in first column
        $row[] = $this->createCell(self::CELL_LABEL, __('Always at end'), ['style' => 'bold']);
        
        // Children names
        $names = [];
        foreach ($alwaysAtEnd as $childData) {
            $names[] = $childData['child']->name;
        }
        $row[] = $this->createCell(self::CELL_CHILD, implode(', ', $names));
        
        // Fill rest
        for ($i = 2; $i < $daysCount + $waitlistWidth; $i++) {
            $row[] = $this->createCell(self::CELL_EMPTY, '');
        }
        
        return $row;
    }

    /**
     * Build statistics section
     */
    private function buildStatsSection(array $childStats, array $days, array $waitlist, int $daysCount, int $waitlistWidth): array
    {
        $rows = [];
        
        // Header row
        $headerRow = [];
        $headerRow[] = $this->createCell(self::CELL_LABEL, 'Kind', ['style' => 'bold']);
        $headerRow[] = $this->createCell(self::CELL_LABEL, 'Tage', ['style' => 'bold']);
        $headerRow[] = $this->createCell(self::CELL_LABEL, 'Nachrücken', ['style' => 'bold']);
        for ($i = 3; $i < $daysCount + $waitlistWidth; $i++) {
            $headerRow[] = $this->createCell(self::CELL_EMPTY, '');
        }
        $rows[] = $headerRow;
        
        // Stats for each child in waitlist
        foreach ($waitlist as $child) {
            if (isset($childStats[$child->id])) {
                $stats = $childStats[$child->id];
                $row = [];
                $row[] = $this->createCell(self::CELL_STATS, $child->name, ['child_id' => $child->id]);
                $row[] = $this->createCell(self::CELL_STATS, $stats['daysCount']);
                $row[] = $this->createCell(self::CELL_STATS, $stats['firstOnWaitlistCount']);
                for ($i = 3; $i < $daysCount + $waitlistWidth; $i++) {
                    $row[] = $this->createCell(self::CELL_EMPTY, '');
                }
                $rows[] = $row;
            }
        }
        
        return $rows;
    }
}
