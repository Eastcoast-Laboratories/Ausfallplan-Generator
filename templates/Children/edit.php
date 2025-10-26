<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Child $child
 * @var array $siblingGroups
 */
$this->assign('title', __('Edit Child'));
?>
<div class="children form content">
    <h3>
        <?= h($child->name) ?>
        <?php if ($child->sibling_group_id): ?>
            <?= $this->Html->link(
                '👨‍👩‍👧 ' . __("Geschwister"),
                ['controller' => 'SiblingGroups', 'action' => 'view', $child->sibling_group_id],
                [
                    'style' => 'background: #fff3cd; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem; margin-left: 0.5rem; text-decoration: none; color: #856404; display: inline-block;',
                    'title' => 'Geschwister: ' . (isset($siblingNames[$child->id]) ? h($siblingNames[$child->id]) : ''),
                    'escape' => false
                ]
            ) ?>
        <?php endif; ?>
    </h3>
    <?= $this->Form->create($child) ?>
    <fieldset>
        <legend><?= __('Edit Child') ?></legend>
        <?php
            echo $this->Form->control('name', ['label' => __('Name'), 'required' => true]);
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
