<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Schedule> $schedules
 * @var array $missingSiblingsPerSchedule
 */
$this->assign('title', __('Schedules'));
?>
<div class="schedules index content">
    <h3><?= __('Schedules') ?></h3>
    <div class="actions">
        <?= $this->Html->link(__('New Schedule'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    </div>
    
    <?php if ($hasMultipleOrgs || ($user && $user->is_system_admin)): ?>
        <div style="margin-bottom: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 4px;">
            <form method="get" action="<?= $this->Url->build(['action' => 'index']) ?>" style="display: flex; align-items: center; gap: 1rem;">
                <label for="organization-filter" style="margin: 0; font-weight: bold;">
                    <?= __('Organization') ?>:
                </label>
                <select name="organization_id" id="organization-filter" onchange="this.form.submit()" style="flex: 1; max-width: 300px;">
                    <option value=""><?= __('Alle Organisationen') ?></option>
                    <?php foreach ($userOrgs as $org): ?>
                        <option value="<?= $org->id ?>" <?= $selectedOrgId == $org->id ? 'selected' : '' ?>>
                            <?= h($org->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($missingSiblingsPerSchedule)): ?>
        <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 1rem; margin-bottom: 1rem; border-radius: 4px;">
            <strong>⚠️ <?= __('Warning') ?>:</strong> 
            <?= __('Siblings in different schedules detected') ?>:
            <?php foreach ($missingSiblingsPerSchedule as $scheduleId => $missingSiblings): ?>
                <div style="margin-top: 0.5rem;">
                    <strong>Schedule #<?= $scheduleId ?>:</strong>
                    <ul style="margin: 0.5rem 0 0 1.5rem;">
                        <?php foreach ($missingSiblings as $missing): ?>
                            <li>
                                <strong><?= $this->Html->link(h($missing['name']), '/schedules/manage-children/' . $missing['schedule_id']) ?></strong> 
                                (<?= __('Sibling of') ?> <?= h($missing['sibling_of']) ?>)
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= __('Title') ?></th>
                    <?php if (isset($user) && $user->is_system_admin): ?>
                        <th><?= __('User') ?></th>
                    <?php endif; ?>
                    <?php if ($hasMultipleOrgs || (isset($user) && $user->is_system_admin)): ?>
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
                            <span style="background: #4caf50; color: white; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.75rem; margin-left: 0.5rem; display: inline-block;">
                                ⭐ <?= __('Active') ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <?php if (isset($user) && $user->is_system_admin): ?>
                        <td><?= h($schedule->user->email ?? $schedule->organization->name ?? '-') ?></td>
                    <?php endif; ?>
                    <?php if ($hasMultipleOrgs || (isset($user) && $user->is_system_admin)): ?>
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
                        <strong><?php
                        if(isset($childrenCounts[$schedule->id]) and $childrenCounts[$schedule->id] > 0) {
                            echo h($childrenCounts[$schedule->id]);
                        } ?></strong>
                        <?php if (isset($childrenCounts[$schedule->id]) && $childrenCounts[$schedule->id] > 0): ?>
                            <span title="has children" style="color: #4caf50;">✓</span>
                        <?php else: ?>
                            <?php
                            echo $this->Html->link(
                                __('Add Child'), 
                                '/schedules/manage-children/' . $schedule->id,
                                ['class' => 'button', 'style' => 'background: #2196F3; color: white;']
                            );
                            ?>
                        <?php endif; ?>
                    </td>
                    <td><?= h($schedule->capacity_per_day) ?></td>
                    <td><?= h($schedule->created->format('Y-m-d H:i')) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('Generate List'), ['action' => 'generateReport', $schedule->id], ['class' => 'button', 'style' => 'background: #2196F3; color: white;']) ?>
                        <?= $this->Html->link(__('Manage Children'), '/schedules/manage-children/' . $schedule->id, ['class' => 'button']) ?>
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
