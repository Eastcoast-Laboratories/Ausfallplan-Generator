<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Organization $organization
 */
$this->assign('title', __('Edit Organization'));
?>
<div class="organization form content">
    <h3><?= __('Edit Organization') ?></h3>
    <?= $this->Form->create($organization) ?>
    <fieldset>
        <?= $this->Form->control('name', ['required' => true]) ?>
        <?= $this->Form->control('is_active', ['type' => 'checkbox']) ?>
        <?= $this->Form->control('contact_email', ['type' => 'email']) ?>
        <?= $this->Form->control('contact_phone') ?>
    </fieldset>
    <?= $this->Form->button(__('Save')) ?>
    <?= $this->Html->link(__('Cancel'), ['action' => 'view', $organization->id], ['class' => 'button']) ?>
    <?= $this->Form->end() ?>
</div>
