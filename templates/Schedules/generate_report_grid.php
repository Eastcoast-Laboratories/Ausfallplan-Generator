<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Schedule $schedule
 * @var array $grid 2D array with cell types
 * @var array $gridMetadata Metadata about the grid
 */

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
            line-height: 1;
        }

        .header {
            text-align: center;
            margin-top: -10px;
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

        .report-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        .cell-header {
            background: #4CAF50;
            color: white;
            font-weight: bold;
            text-align: center;
            font-size: 12px;
        }

        .cell-child {
            background: #fff;
        }

        .cell-waitlist {
            background: #fff3e0;
        }

        .cell-firstOnWaitlist {
            background: #ffebee;
            font-style: italic;
        }

        .cell-checksum {
            background: #e3f2fd;
            font-weight: bold;
            text-align: center;
        }

        .cell-label {
            background: #f5f5f5;
            font-weight: bold;
        }

        .cell-stats {
            background: #fafafa;
            text-align: center;
        }

        .cell-empty {
            background: #fff;
            border-color: #ddd;
        }

        .integrative-badge {
            background: #e3f2fd;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 9px;
            margin-left: 4px;
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
     <div class="header">
        <img src="<?= $this->Url->build('/img/fairnestplan_logo_w.png') ?>" alt="FairNestPlan">
        <div style="margin-left: 10px; font-size: 16px; font-weight: bold; "><?= h($schedule->title) ?></div>
    </div>

    <div class="no-print" style="margin-bottom: 20px; text-align: center;">
        <?= $this->Html->link('← ' . __('Back'), ['action' => 'view', $schedule->id], ['class' => 'button']) ?>
        <button onclick="window.print()" class="button">🖨️ <?= __('Print') ?></button>
        <?= $this->Html->link('📄 ' . __('Export CSV'), ['action' => 'exportCsv', $schedule->id], ['class' => 'button']) ?>
    </div>

    <table class="report-table">
        <?php foreach ($grid as $rowIndex => $row): ?>
            <tr>
                <?php foreach ($row as $cellIndex => $cell): 
                    $type = $cell['type'];
                    $value = $cell['value'];
                    $metadata = $cell['metadata'] ?? [];
                    
                    $cellClass = 'cell-' . $type;
                    $cellContent = h($value);
                    
                    // Special formatting for integrative children
                    if ($type === 'child' && isset($metadata['is_integrative']) && $metadata['is_integrative']) {
                        $cellContent .= ' <span class="integrative-badge">I</span>';
                    }
                    
                    // Special formatting for firstOnWaitlist children
                    if ($type === 'firstOnWaitlist') {
                        $cellContent = '→ ' . $cellContent;
                    }
                ?>
                    <td class="<?= $cellClass ?>" data-type="<?= $type ?>">
                        <?= $cellContent ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </table>

    <div class="no-print" style="margin-top: 20px; padding: 10px; background: #f5f5f5; border-radius: 4px; font-size: 10px;">
        <strong><?= __('Grid Info') ?>:</strong>
        <?= __('Rows') ?>: <?= $gridMetadata['total_rows'] ?? 0 ?> |
        <?= __('Columns') ?>: <?= $gridMetadata['total_cols'] ?? 0 ?> |
        <?= __('Days') ?>: <?= $gridMetadata['days_count'] ?? 0 ?>
    </div>
</body>
</html>
