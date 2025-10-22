<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Child $child
 * @var array $siblingGroups
 */
$this->assign('title', __('Edit Child'));
?>
<div class="children form content">
    <?= $this->Form->create($child) ?>
    <fieldset>
        <legend><?= __('Edit Child') ?></legend>
        <?php
            echo $this->Form->control('name', ['label' => __('Name'), 'required' => true]);
            echo $this->Form->control('is_active', ['type' => 'checkbox', 'label' => __('Active')]);
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
