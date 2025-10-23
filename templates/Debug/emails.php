<?php
/**
 * Debug Emails View
 * Shows all "sent" emails in local development
 */
?>

<div class="debug-emails">
    <div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1>📧 Debug Emails (Localhost)</h1>
            <div>
                <?= $this->Html->link('🔄 Refresh', ['action' => 'emails'], ['class' => 'button']) ?>
                <?= $this->Form->postLink('🗑️ Clear All', ['action' => 'clearEmails'], [
                    'confirm' => 'Clear all debug emails?',
                    'class' => 'button button-danger'
                ]) ?>
                <?= $this->Html->link('← Back to App', ['controller' => 'Dashboard', 'action' => 'index'], ['class' => 'button']) ?>
            </div>
        </div>

        <?php if (empty($emails)): ?>
            <div style="text-align: center; padding: 60px 20px; background: #f5f5f5; border-radius: 8px;">
                <p style="font-size: 48px; margin: 0;">📭</p>
                <h3>No emails yet</h3>
                <p>Emails sent in local development will appear here.</p>
                <p style="color: #666; font-size: 14px;">Try registering a new user or requesting a password reset.</p>
            </div>
        <?php else: ?>
            <div style="color: #666; margin-bottom: 20px;">
                <strong><?= count($emails) ?></strong> email(s) captured
            </div>

            <?php foreach (array_reverse($emails) as $index => $email): ?>
                <div class="email-card" style="
                    background: white;
                    border: 2px solid #e5e7eb;
                    border-radius: 8px;
                    padding: 24px;
                    margin-bottom: 20px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                ">
                    <!-- Email Header -->
                    <div style="border-bottom: 2px solid #e5e7eb; padding-bottom: 15px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div>
                                <h3 style="margin: 0 0 8px 0; color: #1f2937;">
                                    📧 <?= h($email['subject'] ?? 'No Subject') ?>
                                </h3>
                                <div style="font-size: 14px; color: #6b7280;">
                                    <strong>To:</strong> <?= h($email['to'] ?? 'unknown') ?>
                                </div>
                                <?php if (!empty($email['timestamp'])): ?>
                                    <div style="font-size: 12px; color: #9ca3af; margin-top: 4px;">
                                        <?= $email['timestamp']->format('Y-m-d H:i:s') ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <span style="
                                background: #10b981;
                                color: white;
                                padding: 4px 12px;
                                border-radius: 12px;
                                font-size: 12px;
                                font-weight: 600;
                            ">
                                SENT
                            </span>
                        </div>
                    </div>

                    <!-- Email Body -->
                    <div style="
                        background: #f9fafb;
                        border: 1px solid #e5e7eb;
                        border-radius: 6px;
                        padding: 20px;
                        margin-bottom: 20px;
                        font-family: monospace;
                        font-size: 14px;
                        line-height: 1.6;
                        white-space: pre-wrap;
                        word-wrap: break-word;
                    ">
<?= h($email['body'] ?? 'No body') ?>
                    </div>

                    <!-- Action Links -->
                    <?php if (!empty($email['links'])): ?>
                        <div style="
                            background: #eff6ff;
                            border: 2px solid #3b82f6;
                            border-radius: 6px;
                            padding: 16px;
                        ">
                            <h4 style="margin: 0 0 12px 0; color: #1e40af; font-size: 14px;">
                                🔗 Action Links (Click to test):
                            </h4>
                            <?php foreach ($email['links'] as $label => $url): ?>
                                <div style="margin-bottom: 8px;">
                                    <a href="<?= h($url) ?>" 
                                       target="_blank"
                                       style="
                                           display: inline-block;
                                           background: #3b82f6;
                                           color: white;
                                           padding: 10px 20px;
                                           border-radius: 6px;
                                           text-decoration: none;
                                           font-weight: 600;
                                           font-size: 14px;
                                       ">
                                        <?= h($label) ?> →
                                    </a>
                                    <code style="
                                        margin-left: 10px;
                                        font-size: 12px;
                                        color: #6b7280;
                                        background: white;
                                        padding: 4px 8px;
                                        border-radius: 4px;
                                    "><?= h($url) ?></code>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Raw Data (for debugging) -->
                    <?php if (!empty($email['data'])): ?>
                        <details style="margin-top: 16px;">
                            <summary style="cursor: pointer; color: #6b7280; font-size: 12px;">
                                Show raw data
                            </summary>
                            <pre style="
                                background: #1f2937;
                                color: #10b981;
                                padding: 12px;
                                border-radius: 4px;
                                font-size: 11px;
                                overflow-x: auto;
                                margin-top: 8px;
                            "><?= h(json_encode($email['data'], JSON_PRETTY_PRINT)) ?></pre>
                        </details>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.button {
    display: inline-block;
    padding: 8px 16px;
    background: #3b82f6;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    margin-left: 8px;
}

.button:hover {
    background: #2563eb;
}

.button-danger {
    background: #ef4444;
}

.button-danger:hover {
    background: #dc2626;
}

/* Make it look good on mobile */
@media (max-width: 768px) {
    .debug-emails > div {
        padding: 10px !important;
    }
    
    .email-card {
        padding: 16px !important;
    }
}
</style>
