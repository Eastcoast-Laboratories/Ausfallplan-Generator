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
                    <?php if (isset($user) && $user->role === 'admin'): ?>
                        <th><?= __('User') ?></th>
                        <th><?= __('Organization') ?></th>
                    <?php endif; ?>
                    <th><?= __('Days') ?></th>
                    <th><?= __('Max Children per Day') ?></th>
                    <th><?= __('Created') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($schedules as $schedule): ?>
                <tr>
                    <td><?= h($schedule->title) ?></td>
                    <?php if (isset($user) && $user->role === 'admin'): ?>
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
                    <td><?= h($schedule->capacity_per_day) ?></td>
                    <td><?= h($schedule->created->format('Y-m-d H:i')) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('Generate List'), ['action' => 'generateReport', $schedule->id], ['class' => 'button', 'style' => 'background: #2196F3; color: white;']) ?>
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
