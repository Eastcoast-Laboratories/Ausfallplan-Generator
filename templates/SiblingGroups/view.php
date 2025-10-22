<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SiblingGroup $siblingGroup
 */
?>
<div class="sibling-groups view content">
    <h3><?= h($siblingGroup->label) ?></h3>
    <table>
        <tr>
            <th><?= __('Name') ?></th>
            <td><?= h($siblingGroup->label) ?></td>
        </tr>
        <tr>
            <th><?= __('Created') ?></th>
            <td><?= h($siblingGroup->created) ?></td>
        </tr>
        <tr>
            <th><?= __('Modified') ?></th>
            <td><?= h($siblingGroup->modified) ?></td>
        </tr>
    </table>
    
    <div class="related">
        <h4><?= __('Children') ?></h4>
        <?php if (!empty($siblingGroup->children)) : ?>
        <div class="table-responsive">
            <table>
                <tr>
                    <th><?= __('Name') ?></th>
                    <th><?= __('Status') ?></th>
                    <th><?= __('Integrative') ?></th>
                    <th><?= __('Created') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
                <?php foreach ($siblingGroup->children as $child) : ?>
                <tr>
                    <td><?= h($child->name) ?></td>
                    <td><?= $child->is_active ? __('Active') : __('Inactive') ?></td>
                    <td><?= $child->is_integrative ? __('Yes') : __('No') ?></td>
                    <td><?= h($child->created) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['controller' => 'Children', 'action' => 'view', $child->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['controller' => 'Children', 'action' => 'edit', $child->id]) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php else: ?>
        <p><?= __('No children in this sibling group.') ?></p>
        <?php endif; ?>
    </div>
    
    <div class="actions">
        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $siblingGroup->id], ['class' => 'button']) ?>
        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $siblingGroup->id], ['confirm' => __('Are you sure?'), 'class' => 'button']) ?>
        <?= $this->Html->link(__('List Sibling Groups'), ['action' => 'index'], ['class' => 'button']) ?>
    </div>
</div>
