<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Schedule $schedule
 * @var iterable<\App\Model\Entity\Child> $assignedChildren
 * @var iterable<\App\Model\Entity\Child> $availableChildren
 */
$this->assign('title', __('Manage Children') . ' - ' . h($schedule->title));
?>

<div class="manage-children content">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <div>
            <h3 style="margin: 0;"><?= __('Manage Children') ?> - <?= h($schedule->title) ?></h3>
            <p style="margin: 0.5rem 0 0 0;"><?= $this->Html->link('← ' . __('Back to Schedules'), ['action' => 'index']) ?></p>
        </div>
        <?= $this->Html->link(
            '+ ' . __('Add Child'),
            ['controller' => 'Children', 'action' => 'add'],
            ['class' => 'button', 'style' => 'background: #4caf50; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; font-weight: bold;']
        ) ?>
    </div>
    
    <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 1rem;">
        
        <!-- Available Children -->
        <div class="available-children">
            <h4><?= __('Available Children') ?></h4>
            <div style="background: #f5f7fa; padding: 1rem; border-radius: 8px; min-height: 300px;">
                <?php if (!empty($availableChildren) && (is_countable($availableChildren) ? count($availableChildren) : $availableChildren->count()) > 0): ?>
                    <?php foreach ($availableChildren as $child): ?>
                        <div style="background: white; padding: 1rem; margin-bottom: 0.5rem; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong><?= h($child->name) ?></strong>
                                <?php if ($child->is_integrative): ?>
                                    <span style="background: #e3f2fd; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem; margin-left: 0.5rem;">
                                        <?= __('Integrative') ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?= $this->Form->postLink(
                                '+ ' . __('Add'),
                                ['action' => 'assignChild'],
                                [
                                    'data' => [
                                        'schedule_id' => $schedule->id,
                                        'child_id' => $child->id,
                                    ],
                                    'class' => 'button button-small',
                                    'style' => 'background: #4caf50; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;'
                                ]
                            ) ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #666; text-align: center; padding: 2rem;">
                        <?= __('All children are assigned to this schedule.') ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Assigned Children -->
        <div class="assigned-children">
            <h4><?= __('Assigned Children') ?></h4>
            <div style="background: #e8f5e9; padding: 1rem; border-radius: 8px; min-height: 300px;">
                <?php if (!empty($assignedChildren) && (is_countable($assignedChildren) ? count($assignedChildren) : $assignedChildren->count()) > 0): ?>
                    <?php foreach ($assignedChildren as $child): ?>
                        <div style="background: white; padding: 1rem; margin-bottom: 0.5rem; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid #4caf50;">
                            <div>
                                <strong><?= h($child->name) ?></strong>
                                <?php if ($child->is_integrative): ?>
                                    <span style="background: #e3f2fd; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem; margin-left: 0.5rem;">
                                        <?= __('Integrative') ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?= $this->Form->postLink(
                                '✕',
                                ['action' => 'removeChild'],
                                [
                                    'confirm' => __('Remove {0} from this schedule?', $child->name),
                                    'data' => [
                                        'schedule_id' => $schedule->id,
                                        'child_id' => $child->id,
                                    ],
                                    'class' => 'button button-small',
                                    'style' => 'background: #f44336; color: white; padding: 0.5rem 0.75rem; text-decoration: none; border-radius: 4px;'
                                ]
                            ) ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #666; text-align: center; padding: 2rem;">
                        <?= __('No children assigned to this schedule.') ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>
