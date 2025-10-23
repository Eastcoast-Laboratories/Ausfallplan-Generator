<?php
/**
 * @var \App\View\AppView $this
 * @var array $reportData
 */
$schedule = $reportData['schedule'];
$days = $reportData['days'];
$waitlist = $reportData['waitlist'];
$alwaysAtEnd = $reportData['alwaysAtEnd'];

$this->assign('title', __('Ausfallplan') . ' - ' . h($schedule->title));
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= h($schedule->title) ?> - <?= __('Ausfallplan') ?></title>
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
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            font-size: 16px;
            font-weight: bold;
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
            min-height: 180px;
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
            flex: 1;
            list-style: none;
        }

        .child-item {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
        }

        .child-name {
            flex: 1;
        }

        .child-weight {
            margin-left: 8px;
            font-weight: bold;
        }

        .leaving-child {
            margin-top: auto;
            padding-top: 8px;
            border-top: 1px solid #ccc;
            text-align: center;
            font-size: 10px;
        }

        .sidebar {
            width: 200px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            position: sticky;
            top: 20px;
            align-self: flex-start;
        }

        .waitlist-box, .always-end-box {
            border: 2px solid #000;
            padding: 12px;
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
            margin-top: 20px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 8px;
            font-size: 10px;
            line-height: 1.5;
        }

        .flag-icon {
            font-size: 14px;
        }

        @media print {
            body {
                padding: 10px;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <?= $this->Html->link('← ' . __('Back to Schedules'), ['action' => 'index'], ['class' => 'button']) ?>
        <button onclick="window.print()" class="button" style="float: right; background: #2196F3; color: white;">
            🖨️ <?= __('Print') ?>
        </button>
    </div>

    <div class="header">
        <?= __('Ausfallplan') ?> <?= h($schedule->title) ?>
    </div>

    <div class="container">
        <div class="days-grid">
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
                    <div class="day-sum" style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #ccc; font-size: 11px; font-weight: bold; text-align: right;">
                        Σ <?= h($day['countingChildrenSum']) ?> / 9
                    </div>
                    <?php if ($day['leavingChild']): ?>
                        <div class="leaving-child">
                            <?= h($day['leavingChild']['child']->name) ?> <span class="flag-icon">⬇️</span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="sidebar">
            <div class="waitlist-box">
                <div class="box-title"><?= __('Nachrückliste') ?></div>
                <?php if (!empty($waitlist)): ?>
                    <?php foreach ($waitlist as $entry): 
                        $count = $entry->child->is_integrative ? 2 : 1;
                    ?>
                        <div class="waitlist-item">
                            <span><?= h($entry->child->name) ?></span>
                            <span class="priority-badge"><?= h($count) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #666; font-size: 10px;"><?= __('No entries') ?></p>
                <?php endif; ?>
            </div>

            <div class="always-end-box">
                <div class="box-title"><?= __('Immer am Ende') ?></div>
                <?php if (!empty($alwaysAtEnd)): ?>
                    <?php foreach ($alwaysAtEnd as $childData): ?>
                        <div class="waitlist-item">
                            <span><?= h($childData['child']->name) ?></span>
                            <span class="priority-badge"><?= h($childData['weight']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #666; font-size: 10px;"><?= __('None') ?></p>
                <?php endif; ?>
            </div>
            
            <?php
            // Calculate total counting children (ALL children, not just in waitlist)
            $totalCountingChildren = 0;
            
            // Count from waitlist
            foreach ($waitlist as $entry) {
                $totalCountingChildren += $entry->child->is_integrative ? 2 : 1;
            }
            
            // Count from "always at end"
            foreach ($alwaysAtEnd as $childData) {
                $totalCountingChildren += $childData['child']->is_integrative ? 2 : 1;
            }
            ?>
            <div class="total-counting-children" style="text-align: right;">
                <?= __('Summe aller Zählkinder') ?>: <?= h($totalCountingChildren) ?>
            </div>
        </div>
    </div>

    <div class="explanation">
        <p><strong><?= __('Note for parents:') ?></strong></p>
        <p>
            <?= __('If places become available due to illness, appointments, etc., the parents can fill these spots by consulting the substitute list in order. The integrative children count double here.') ?>
        </p>
    </div>
</body>
</html>
