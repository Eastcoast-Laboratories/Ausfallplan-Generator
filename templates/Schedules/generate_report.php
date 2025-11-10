<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Schedule $schedule
 * @var array $days
 * @var array $waitlist
 * @var array $alwaysAtEnd
 * @var int $daysCount
 * @var array $childStats
 */
// Variables are passed directly from controller

$dayMinHeight = "180px";

$this->assign('title', __('FairNestPlan') . ' - ' . h($schedule->title));
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= h($schedule->title) ?> - <?= __('FairNestPlan') ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            padding: 20px;
            background: white;
            line-height: 1;
        }

        .header {
            text-align: center;
            margin-top: -30px;
            font-size: 16px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .header img {
            height: 40px;
            margin-bottom: 10px;
        }

        .container {
            display: flex;
            gap: 20px;
        }

        .days-grid {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        .day-box {
            border: 2px solid #000;
            padding: 8px;
            min-height: <?= $dayMinHeight ?>;
            display: flex;
            flex-direction: column;
        }

        .day-title {
            font-weight: bold;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #000;
            font-size: 12px;
        }

        .children-list {
            list-style: none;
            flex: 1;
            margin-bottom: 0;
        }

        .child-item {
            display: flex;
            justify-content: space-between;
            padding: 1px 0;
            line-height: 0.4;
        }

        .child-name {
            flex: 1;
        }

        .child-weight {
            margin-left: 8px;
            font-weight: bold;
        }

        .day-footer {
            margin-top: auto;
        }

        .day-sum {
            margin-top: 0;
            padding-top: 5px;
            margin-bottom: -15px;
            border-top: 1px solid #ccc;
            font-size: 7px;
            text-align: right;
            color: #666;
        }

        .firstOnWaitlist-child {
            padding-top: 8px;
            border-top: none;
            text-align: left;
            font-size: 10px;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 10px;
            position: sticky;
            top: 20px;
            align-self: flex-start;
        }

        .waitlist-box, .always-end-box {
            border: 2px solid #000;
            padding: 12px;
            min-height: <?= $dayMinHeight ?>;
        }

        .box-title {
            font-weight: bold;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #000;
            font-size: 12px;
        }

        .waitlist-item {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            align-items: center;
        }

        .priority-badge {
            background: #e3f2fd;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }

        .total-counting-children {
            margin-top: 8px;
            font-size: 8px;
            color: #999;
            text-align: center;
        }

        .explanation {
            padding: 15px;
            background: #f5f5f5;
            border-radius: 8px;
            font-size: 10px;
            line-height: 1.5;
            border: 2px solid #000;
        }

        .flag-icon {
            font-size: 14px;
        }

        .waitlist-header {
           font-weight: bold; padding-bottom: 4px; border-bottom: 1px solid #ccc;
        }

        .checksums {
            color: #aaa;
            font-size: 7px;
            margin-top:4px; 
        }

        .waitlist-header.checksums {
            margin-top: 2px;
        }

        @media print {
            body {
                padding: 10px;
            }
            .no-print {
                display: none;
            }
            .header {
               margin-top: -10px;
            }
            .not-on-print {
                opacity: 0;
                width: 0;
                overflow: hidden;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <?= $this->Html->link('← ' . __('Back to Schedules'), ['action' => 'index'], ['class' => 'button']) ?>
        <div style="float: right; display: flex; gap: 10px;">
            <?= $this->Html->link('📊 CSV', ['action' => 'export-csv', $schedule->id], ['class' => 'button', 'style' => 'background: #4caf50; color: white;']) ?>
            <?= $this->Html->link('📈 Excel', ['action' => 'export-xls', $schedule->id], ['class' => 'button', 'style' => 'background: #2e7d32; color: white;']) ?>
            <button onclick="window.print()" class="button" style="background: #2196F3; color: white;">
                🖨️ <?= __('Print') ?>
            </button>
        </div>
    </div>

    <div class="header">
        <img src="<?= $this->Url->build('/img/fairnestplan_logo_w.png') ?>" alt="FairNestPlan">
        <div style="margin-left: 10px; font-size: 16px; font-weight: bold; "><?= h($schedule->title) ?></div>
    </div>

    <div class="container">
        <div class="days-grid">
            <?php 
            // Calculate dynamic min-height based on capacity_per_day (15px per child)
            $capacityPerDay = $schedule->capacity_per_day ?? 9;
            $minHeight = $capacityPerDay * 15;
            
            // Calculate how many columns the explanation should span
            $totalDays = count($days);
            $daysInLastRow = $totalDays % 4; // 0 means full row (4 days)
            $explanationColumns = $daysInLastRow === 0 ? 4 : (4 - $daysInLastRow);
            ?>
            <?php foreach ($days as $day): ?>
                <div class="day-box">
                    <div class="day-title"><?= h($day['title']) ?></div>
                    <ul class="children-list">
                        <?php foreach ($day['children'] as $childData): ?>
                            <li class="child-item">
                                <span class="child-name"><?= h($childData['child']->name) ?></span>
                                <span class="child-weight"><?= $childData['is_integrative'] ? '2' : '1' ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="day-footer">
                        <div class="day-sum">
                            <?= h($day['countingChildrenSum']) ?>
                        </div>
                        <?php if ($day['firstOnWaitlistChild']): ?>
                            <div class="firstOnWaitlist-child">
                                <span class="flag-icon">⬇️</span>
                                <?= h($day['firstOnWaitlistChild']['child']->name) ?> 
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($day['debugLog'])): ?>
                        <div style="margin-top: 8px; padding: 4px; background: #fffacd; border: 1px solid #ffd700; font-size: 8px; font-family: monospace;">
                            <?php foreach ($day['debugLog'] as $logLine): ?>
                                <?= h($logLine) ?><br>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <!-- Explanation box - fills remaining columns in last row -->
            <div class="explanation" style="grid-column: span <?= $explanationColumns ?>;">
                <p>
                    <?= __('If places become available due to illness, appointments, etc., parents can fill them via a chatgroup. Work through the waitlist from top to bottom, starting with the child at the bottom of the current day. When you reach the end, start again at the top. Important: The maximum number of counting children must not be exceeded – integrative children count double. If only one spot becomes available but the next child on the list requires two spots, the child after that moves up temporarily. If additional spots become available later, the order will be adjusted accordingly.') ?>
                </p>
            </div>
        </div>

        <div class="sidebar">
            <div class="waitlist-box">
                <div class="box-title">⬇️ <?= __('Waitlist') ?></div>
                <div style="display: grid; grid-template-columns: 1fr auto auto auto; gap: 4px; font-size: 10px;">
                    <div class="waitlist-header">Name</div>
                    <div class="waitlist-header" style="text-align: center;">Z</div>
                    <div class="waitlist-header not-on-print checksums" style="text-align: center;">D</div>
                    <div class="waitlist-header not-on-print checksums" style="text-align: center;">⬇️</div>
                    
                    <?php if (!empty($waitlist)): ?>
                        <?php foreach ($waitlist as $child): 
                            if (!$child) continue;
                            
                            $count = $child->is_integrative ? 2 : 1;
                            $childId = $child->id;
                            $stats = isset($childStats[$childId]) ? $childStats[$childId] : ['daysCount' => 0, 'firstOnWaitlistCount' => 0];
                        ?>
                            <div style="padding: 2px 0;"><?= h($child->name) ?></div>
                            <div style="background: #e3f2fd; padding: 2px 6px; border-radius: 3px; font-weight: bold; text-align: center;"><?= h($count) ?></div>
                            <div class="not-on-print checksums" style="text-align: center;"><?= h($stats['daysCount']) ?></div>
                            <div class="not-on-print checksums" style="text-align: center;"><?= h($stats['firstOnWaitlistCount']) ?></div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="checksums" style="grid-column: 1 / -1; text-align: center; padding: 2rem;">
                            <?= __('No entries') ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($alwaysAtEnd)): ?>
            <div class="always-end-box">
                <div class="box-title"><?= __('Always at end') ?></div>
                <div style="display: grid; grid-template-columns: 1fr auto auto auto; gap: 4px; font-size: 10px;">
                    <div class="waitlist-header">Name</div>
                    <div class="waitlist-header" style="text-align: center;">i</div>

                    <div class="waitlist-header not-on-print checksums" style="text-align: center;">D</div>
                    <div class="waitlist-header not-on-print checksums" style="text-align: center;">⬇️</div>
                    
                    <?php if (!empty($alwaysAtEnd)): ?>
                        <?php foreach ($alwaysAtEnd as $childData): ?>
                            <?php 
                            $childId = $childData['child']->id;
                            $stats = isset($childStats[$childId]) ? $childStats[$childId] : ['daysCount' => 0, 'firstOnWaitlistCount' => 0];
                            ?>
                            <div style="padding: 2px 0;"><?= h($childData['child']->name) ?></div>
                            <div style="background: #e3f2fd; padding: 2px 6px; border-radius: 3px; font-weight: bold; text-align: center;"><?= h($childData['weight']) ?></div>
                            <div style="color: #999; text-align: center;" class="not-on-print"><?= h($stats['daysCount']) ?></div>
                            <div style="color: #999; text-align: center;" class="not-on-print"><?= h($stats['firstOnWaitlistCount']) ?></div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="grid-column: 1 / -1; color: #666; text-align: center; padding: 2rem;">
                            <?= __('None') ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php
            // Calculate total counting children (ALL children, not just in waitlist)
            $totalCountingChildren = 0;
            
            // Count from waitlist
            if (!empty($waitlist)) {
                foreach ($waitlist as $child) {
                    $totalCountingChildren += $child->is_integrative ? 2 : 1;
                }
            }
            
            // Count from "always at end"
            if (!empty($alwaysAtEnd)) {
                foreach ($alwaysAtEnd as $childData) {
                    $totalCountingChildren += $childData['child']->is_integrative ? 2 : 1;
                }
            }
            ?>
            <div class="total-counting-children not-on-print" style="text-align: right;">
                <?= __('Counting sum of all children') ?>: <?= h($totalCountingChildren) ?>
            </div>
        </div>
    </div>
</body>
</html>
