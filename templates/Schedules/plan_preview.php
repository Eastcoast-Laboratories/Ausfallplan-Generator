<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Schedule> $schedules
 */
$this->assign('title', __('Plan Preview'));
?>

<div class="schedules plan-preview content">
    <h3><?= __('Plan Preview') ?></h3>
    
    <p><?= __('Select a schedule to view its plan preview:') ?></p>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
        <?php foreach ($schedules as $schedule): ?>
            <div style="border: 1px solid #e1e8ed; border-radius: 8px; padding: 1.5rem; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: box-shadow 0.2s;">
                <h4 style="margin: 0 0 0.5rem 0;"><?= h($schedule->title) ?></h4>
                
                <div style="font-size: 0.9rem; color: #666; margin-bottom: 1rem;">
                    <p style="margin: 0.25rem 0;">
                        <strong><?= __('From') ?>:</strong> <?= h($schedule->starts_on) ?>
                    </p>
                    <p style="margin: 0.25rem 0;">
                        <strong><?= __('To') ?>:</strong> <?= h($schedule->ends_on) ?>
                    </p>
                    <p style="margin: 0.25rem 0;">
                        <strong><?= __('Max Children per Day') ?>:</strong> <?= h($schedule->capacity_per_day) ?>
                    </p>
                </div>
                
                <div style="display: flex; gap: 0.5rem;">
                    <?= $this->Html->link(
                        "📄 " . __('Plan Preview'),
                        ['action' => 'generate-report', $schedule->id],
                        ['class' => 'button', 'style' => 'background: #2196F3; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; font-weight: bold; flex: 1; text-align: center;']
                    ) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
