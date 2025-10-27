<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Organization $organization
 */
$this->assign('title', __('Add Organization'));
?>
<div class="organizations add content">
    <h3><?= __('Add Organization') ?></h3>
    
    <?= $this->Form->create($organization) ?>
    <fieldset>
        <legend><?= __('Organization Information') ?></legend>
        
        <?= $this->Form->control('name', [
            'label' => __('Organization Name'),
            'required' => true,
            'maxlength' => 255
        ]) ?>
        
        <?= $this->Form->control('contact_email', [
            'label' => __('Contact Email'),
            'type' => 'email',
            'required' => false
        ]) ?>
        
        <?= $this->Form->control('contact_phone', [
            'label' => __('Contact Phone'),
            'type' => 'text',
            'required' => false
        ]) ?>
        
        <?= $this->Form->control('is_active', [
            'label' => __('Active'),
            'type' => 'checkbox',
            'checked' => true
        ]) ?>
    </fieldset>
    
    <div class="form-actions">
        <?= $this->Form->button(__('Save Organization'), ['class' => 'button']) ?>
        <?= $this->Html->link(__('Cancel'), ['action' => 'index'], ['class' => 'button']) ?>
    </div>
    <?= $this->Form->end() ?>
    
    <div class="info-box" style="margin-top: 2rem; padding: 1rem; background: #e3f2fd; border-left: 4px solid #2196f3;">
        <h4 style="margin-top: 0;"><?= __('After Creating') ?></h4>
        <p style="margin-bottom: 0;">
            <?= __('After creating the organization, you can add users to it from the organization view page.') ?>
        </p>
    </div>
</div>
