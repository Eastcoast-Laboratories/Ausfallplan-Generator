<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Schedule $schedule
 */
?>
<div class="schedules view content">
    <h3><?= h($schedule->title) ?></h3>
    <table>
        <tr>
            <th><?= __('Title') ?></th>
            <td><?= h($schedule->title) ?></td>
        </tr>
        <tr>
            <th><?= __('State') ?></th>
            <td><?= h($schedule->state) ?></td>
        </tr>
        <tr>
            <th><?= __('Starts On') ?></th>
            <td><?= h($schedule->starts_on) ?></td>
        </tr>
        <tr>
            <th><?= __('Ends On') ?></th>
            <td><?= h($schedule->ends_on) ?></td>
        </tr>
        <tr>
            <th><?= __('Created') ?></th>
            <td><?= h($schedule->created) ?></td>
        </tr>
    </table>
    <div class="actions">
        <?= $this->Html->link(__('Manage Children'), ['action' => 'manage-children', $schedule->id], ['class' => 'button button-primary']) ?>
        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $schedule->id], ['class' => 'button']) ?>
        <?= $this->Html->link(__('Generate Report'), ['action' => 'generate-report', $schedule->id], ['class' => 'button']) ?>
        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $schedule->id], ['confirm' => __('Are you sure?'), 'class' => 'button']) ?>
        <?= $this->Html->link(__('List Schedules'), ['action' => 'index'], ['class' => 'button']) ?>
    </div>
</div>
