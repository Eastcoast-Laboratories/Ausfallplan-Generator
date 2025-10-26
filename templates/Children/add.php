<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Child $child
 * @var array $siblingGroups
 */
$this->assign('title', __('Add Child'));
?>
<div class="children form content">
    <?= $this->Form->create($child) ?>
    <fieldset>
        <legend><?= __('Add Child') ?></legend>
        <?php
            echo $this->Form->control('name', ['label' => __('Name'), 'required' => true, 'autofocus' => true]);
            echo $this->Form->control('last_name', ['label' => __('Last Name'), 'required' => false]);
            echo $this->Form->control('gender', [
                'type' => 'select',
                'options' => [
                    'm' => __('Male'),
                    'f' => __('Female'),
                    'd' => __('Diverse'),
                ],
                'empty' => __('(Not specified)'),
                'label' => __('Gender')
            ]);
            echo $this->Form->control('birth_date', [
                'type' => 'date',
                'label' => __('Birthdate'),
                'empty' => true,
            ]);
            echo $this->Form->control('postal_code', ['label' => __('Postal Code'), 'required' => false]);
            echo $this->Form->control('is_active', ['type' => 'checkbox', 'label' => __('Active'), 'checked' => true]);
            echo $this->Form->control('is_integrative', ['type' => 'checkbox', 'label' => __('Integrative Child')]);
            echo $this->Form->control('sibling_group_id', [
                'options' => $siblingGroups,
                'empty' => __('(No Sibling Group)'),
                'label' => __('Sibling Group'),
            ]);
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
