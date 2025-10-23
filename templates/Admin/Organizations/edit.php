<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Organization $organization
 */
$this->assign('title', __('Organisation bearbeiten'));
?>
<div class="organization form content">
    <h3><?= __('Organisation bearbeiten') ?></h3>
    <?= $this->Form->create($organization) ?>
    <fieldset>
        <?= $this->Form->control('name', ['required' => true]) ?>
        <?= $this->Form->control('is_active', ['type' => 'checkbox']) ?>
        <?= $this->Form->control('contact_email', ['type' => 'email']) ?>
        <?= $this->Form->control('contact_phone') ?>
    </fieldset>
    <?= $this->Form->button(__('Speichern')) ?>
    <?= $this->Html->link(__('Abbrechen'), ['action' => 'view', $organization->id], ['class' => 'button']) ?>
    <?= $this->Form->end() ?>
</div>
