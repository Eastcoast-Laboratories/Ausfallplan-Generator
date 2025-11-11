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
            // Organization dropdown
            if (!empty($userOrgs) && count($userOrgs) > 1) {
                $organizations = collection($userOrgs)->combine('id', 'name')->toArray();
                echo $this->Form->control('organization_id', [
                    'type' => 'select',
                    'options' => $organizations,
                    'value' => $selectedOrgId,
                    'label' => __('Organization'),
                    'required' => true,
                    'disabled' => $hasChildren ?? false,
                    'help' => ($hasChildren ?? false) ? __('Organization cannot be changed because this group has children assigned') : null
                ]);
            }
            
            echo $this->Form->control('label', ['label' => __('Name'), 'required' => true]);
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
