<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Schedule> $schedules
 */
$this->assign('title', __('Schedules'));
?>
<div class="schedules index content">
    <h3><?= __('Schedules') ?></h3>
    <div class="actions">
        <?= $this->Html->link(__('New Schedule'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= __('Title') ?></th>
                    <?php if (isset($user) && $user->is_system_admin): ?>
                        <th><?= __('User') ?></th>
                        <th><?= __('Organization') ?></th>
                    <?php endif; ?>
                    <th><?= __('Days') ?></th>
                    <th><?= __('Children') ?></th>
                    <th><?= __('Max Children per Day') ?></th>
                    <th><?= __('Created') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($schedules as $schedule): 
                    $isActive = isset($activeScheduleId) && $activeScheduleId == $schedule->id;
                    $rowStyle = $isActive ? 'background: #e8f5e9; border-left: 4px solid #4caf50;' : '';
                ?>
                <tr class="schedule-row" data-schedule-id="<?= $schedule->id ?>" style="<?= $rowStyle ?> cursor: pointer;">
                    <td>
                        <?= h($schedule->title) ?>
                        <?php if ($isActive): ?>
                            <span style="background: #4caf50; color: white; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.75rem; margin-left: 0.5rem;">
                                ⭐ <?= __('Active') ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <?php if (isset($user) && $user->is_system_admin): ?>
                        <td><?= h($schedule->user->email ?? $schedule->organization->name ?? '-') ?></td>
                        <td>
                            <?php if ($schedule->has('organization') && isset($schedule->organization->id)): ?>
                                <?= $this->Html->link(
                                    h($schedule->organization->name), 
                                    '/admin/organizations/view/' . $schedule->organization->id
                                ) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                    <td><?= h($schedule->days_count) ?></td>
                    <td>
                        <strong><?= isset($childrenCounts[$schedule->id]) ? h($childrenCounts[$schedule->id]) : 0 ?></strong>
                        <?php if (isset($childrenCounts[$schedule->id]) && $childrenCounts[$schedule->id] > 0): ?>
                            <span style="color: #4caf50;">✓</span>
                        <?php endif; ?>
                    </td>
                    <td><?= h($schedule->capacity_per_day) ?></td>
                    <td><?= h($schedule->created->format('Y-m-d H:i')) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('Generate List'), ['action' => 'generateReport', $schedule->id], ['class' => 'button', 'style' => 'background: #2196F3; color: white;']) ?>
                        <?= $this->Html->link(__('Export CSV'), ['action' => 'exportCsv', $schedule->id], ['class' => 'button', 'style' => 'background: #4CAF50; color: white;']) ?>
                        <?= $this->Html->link(__('Manage Children'), ['action' => 'manageChildren', $schedule->id], ['class' => 'button']) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $schedule->id]) ?>
                        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $schedule->id], ['confirm' => __('Are you sure you want to delete # {0}?', $schedule->id)]) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.schedule-row {
    transition: background-color 0.2s ease;
}

.schedule-row:hover {
    background-color: #f5f5f5 !important;
}

.schedule-row:hover td {
    font-weight: 500;
}

/* Don't override active schedule hover */
.schedule-row[style*="background: #e8f5e9"]:hover {
    background-color: #c8e6c9 !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const scheduleRows = document.querySelectorAll('.schedule-row');
    
    scheduleRows.forEach(row => {
        row.addEventListener('click', function(e) {
            // Don't trigger if clicking on action buttons
            if (e.target.closest('.actions') || e.target.closest('a') || e.target.closest('form')) {
                return;
            }
            
            const scheduleId = this.dataset.scheduleId;
            
            // Send AJAX request to set active schedule
            fetch('<?= $this->Url->build(['action' => 'setActive']) ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?= $this->request->getAttribute('csrfToken') ?>'
                },
                body: JSON.stringify({
                    schedule_id: scheduleId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload page to show updated highlighting
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error setting active schedule:', error);
            });
        });
    });
});
</script>
