<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Schedule $schedule
 */
$this->assign('title', __('Edit Schedule'));
?>
<div class="schedules form content">
    <?= $this->Form->create($schedule) ?>
    <fieldset>
        <legend><?= __('Edit Schedule') ?></legend>
        <?php
            echo $this->Form->control('title', ['required' => true]);
            echo $this->Form->control('starts_on', ['type' => 'date', 'required' => true]);
            echo $this->Form->control('ends_on', [
                'type' => 'date',
                'required' => false,
                'empty' => true,
                'help' => __('Leave empty for schedules that never end')
            ]);
            echo $this->Form->control('capacity_per_day', [
                'label' => __('Max Children per Day'),
                'type' => 'number',
                'min' => 1,
                'help' => __('Maximum number of children that can be assigned per day')
            ]);
            echo $this->Form->control('days_count', [
                'label' => __('Number of Days'),
                'type' => 'number',
                'min' => 1,
                'help' => __('Number of days for the schedule (default: number of assigned children)')
            ]);
            echo $this->Form->control('state', [
                'options' => ['draft' => __('Draft'), 'final' => __('Final')],
            ]);
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
