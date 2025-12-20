<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SiblingGroup $siblingGroup
 * @var \App\Model\Entity\Child|null $pendingChild
 */
$this->assign('title', __('Add Sibling Group'));
?>
<div class="sibling-groups form content">
    <?php if (!empty($pendingChild)): ?>
        <div style="background: #e3f2fd; border-left: 4px solid #2196F3; padding: 1rem; margin-bottom: 1rem; border-radius: 4px;">
            <strong>ℹ️ <?= __('Info') ?>:</strong> 
            <?= __('This group will be assigned to child: {0}', '<strong>' . h($pendingChild->name) . '</strong>') ?>
        </div>
    <?php endif; ?>
    
    <?= $this->Form->create($siblingGroup) ?>
    <fieldset>
        <legend><?= __('Add Sibling Group') ?></legend>
        <?php
            // Organization dropdown
            if (!empty($userOrgs) && count($userOrgs) > 1) {
                $organizations = collection($userOrgs)->combine('id', 'name')->toArray();
                echo $this->Form->control('organization_id', [
                    'type' => 'select',
                    'options' => $organizations,
                    'value' => $selectedOrgId,
                    'label' => __('Organization'),
                    'required' => true
                ]);
            } else {
                // Hidden field for single org
                echo $this->Form->hidden('organization_id', ['value' => $selectedOrgId]);
            }
            
            echo $this->Form->control('label', [
                'label' => __('Group Name'),
                'required' => false,
                'autofocus' => true
            ]);?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
