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
                    
                    // Check if this cell contains a child name (types: child, waitlist, firstOnWaitlist)
                    $isChildCell = in_array($type, ['child', 'waitlist', 'firstOnWaitlist']);
                    
                    // Special formatting for firstOnWaitlist children
                    $prefix = '';
                    if ($type === 'firstOnWaitlist') {
                        $prefix = '→ ';
                    }
                ?>
                    <td class="<?= $cellClass ?>" data-type="<?= $type ?>">
                        <?php if ($isChildCell && isset($metadata['child'])): ?>
                            <?= $prefix ?>
                            <span class="child-name"
                                data-encrypted="<?= h($metadata['child']->name_encrypted ?? '') ?>"
                                data-iv="<?= h($metadata['child']->name_iv ?? '') ?>"
                                data-tag="<?= h($metadata['child']->name_tag ?? '') ?>"><?= h($value) ?></span>
                            <?php if (isset($metadata['is_integrative']) && $metadata['is_integrative']): ?>
                                <span class="integrative-badge">I</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <?= h($value) ?>
                        <?php endif; ?>
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
    
    <!-- Encryption Warning (if password missing) -->
    <div id="encryption-warning" class="no-print" style="display: none; position: fixed; top: 20px; right: 20px; background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.2); max-width: 400px; z-index: 10000;">
        <strong>⚠️ <?= __('Encryption Key Missing') ?></strong><br>
        <span id="encryption-warning-message"></span><br>
        <small><?= __('Child names cannot be decrypted without your password.') ?></small><br>
        <div style="margin-top: 10px;">
            <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'profile']) ?>" class="button-secondary" style="text-decoration: none; padding: 8px 15px; background: #3498db; color: white; border-radius: 4px; display: inline-block;">
                🔑 <?= __('Go to Settings') ?>
            </a>
        </div>
    </div>
    
    <!-- Encryption Scripts -->
    <script src="<?= $this->Url->build('/js/crypto/orgEncryption.js') ?>"></script>
    <script>
    // IMPORTANT: Set organization ID BEFORE childDecryption.js loads
    // This ensures org IDs are available when decryption starts
    document.addEventListener('DOMContentLoaded', function() {
        const orgId = <?= (int)$schedule->organization_id ?>;
        
        // Add org-id data attribute to all child-name elements
        const childNameElements = document.querySelectorAll('.child-name');
        childNameElements.forEach(el => {
            el.dataset.orgId = orgId;
        });
        
        console.log('[ReportGrid] Set organization ID:', orgId, 'for', childNameElements.length, 'child names');
        
        // Trigger decryption manually after setting org IDs
        if (window.ChildDecryption) {
            console.log('[ReportGrid] Triggering manual decryption after org IDs set');
            window.ChildDecryption.decryptAll();
        }
    });
    </script>
    <script src="<?= $this->Url->build('/js/childDecryption.js') ?>"></script>
    <script>
    // Continue with password check and status logging
    document.addEventListener('DOMContentLoaded', function() {
        // Check if encryption is enabled for this organization
        const encryptionEnabled = <?= json_encode($schedule->organization->encryption_enabled ?? false) ?>;
        
        if (!encryptionEnabled) {
            console.log('[ReportGrid] ℹ️ Encryption disabled for this organization - skipping encryption checks');
            return; // Don't show warnings if encryption is disabled
        }
        
        // Check if password is available
        setTimeout(function() {
            const password = sessionStorage.getItem('_temp_login_password');
            
            if (!password) {
                console.warn('[ReportGrid] ⚠️ Password not available in sessionStorage - child names cannot be decrypted');
                
                const warningDiv = document.getElementById('encryption-warning');
                const messageSpan = document.getElementById('encryption-warning-message');
                
                if (warningDiv && messageSpan) {
                    messageSpan.textContent = '<?= __('Password not available for key decryption. Please re-enter your password in settings.') ?>';
                    warningDiv.style.display = 'block';
                }
            } else {
                console.log('[ReportGrid] ✅ Password available in sessionStorage');
            }
            
            // Check if decryption succeeded after 2 seconds
            setTimeout(function() {
                const decryptedElements = document.querySelectorAll('.child-name[data-decrypted="true"]');
                const encryptedElements = document.querySelectorAll('.child-name[data-encrypted]');
                
                console.log('[ReportGrid] Decryption Status:', {
                    total: encryptedElements.length,
                    decrypted: decryptedElements.length,
                    failed: encryptedElements.length - decryptedElements.length
                });
                
                if (encryptedElements.length > 0 && decryptedElements.length === 0) {
                    console.error('[ReportGrid] ❌ No child names were decrypted! Check encryption keys and DEK.');
                    
                    const warningDiv = document.getElementById('encryption-warning');
                    const messageSpan = document.getElementById('encryption-warning-message');
                    
                    if (warningDiv && messageSpan) {
                        messageSpan.textContent = '<?= __('Decryption failed. Please check your encryption keys in settings.') ?>';
                        warningDiv.style.display = 'block';
                    }
                }
            }, 2000);
        }, 500);
    });
    </script>
</body>
</html>
