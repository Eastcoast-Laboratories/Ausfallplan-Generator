<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Schedule $schedule
 */
$this->assign('title', __('Create Schedule'));
?>
<div class="schedules form content">
    <?= $this->Form->create($schedule) ?>
    <fieldset>
        <legend><?= __('Add Schedule') ?></legend>
        <?php
            // Organization selector (only for system admins or users with multiple orgs)
            if ($canSelectOrganization) {
                echo $this->Form->control('organization_id', [
                    'label' => __('Organization'),
                    'options' => $organizations,
                    'required' => true,
                    'empty' => false,
                    'class' => 'organization-selector'
                ]);
            }
            
            echo $this->Form->control('title', [
                'label' => __('Title'),
                'required' => true,
                'placeholder' => __('e.g., January 2024'),
                'autofocus' => true
            ]);
            echo $this->Form->control('starts_on', [
                'type' => 'date',
                'required' => true,
                'value' => date('Y-m-d')
            ]);
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
                'default' => 'draft',
            ]);
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
