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
            echo $this->Form->control('ends_on', ['type' => 'date', 'required' => true]);
            echo $this->Form->control('state', [
                'options' => ['draft' => __('Draft'), 'final' => __('Final')],
            ]);
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
