<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SiblingGroup $siblingGroup
 */
$this->assign('title', __('Edit Sibling Group'));
?>
<div class="sibling-groups form content">
    <?= $this->Form->create($siblingGroup) ?>
    <fieldset>
        <legend><?= __('Edit Sibling Group') ?></legend>
        <?php
            echo $this->Form->control('label', ['label' => __('Name'), 'required' => true]);
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
