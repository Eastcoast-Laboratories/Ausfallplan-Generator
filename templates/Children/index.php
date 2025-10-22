<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Child> $children
 */
$this->assign('title', __('Children'));
?>
<div class="children index content">
    <h3><?= __('Children') ?></h3>
    <div class="actions">
        <?= $this->Html->link(__('New Child'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= __('Name') ?></th>
                    <th><?= __('Status') ?></th>
                    <th><?= __('Integrative') ?></th>
                    <th><?= __('Sibling Group') ?></th>
                    <th><?= __('Created') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($children as $child): ?>
                <tr>
                    <td><?= h($child->name) ?></td>
                    <td><?= $child->is_active ? __('Active') : __('Inactive') ?></td>
                    <td><?= $child->is_integrative ? __('Yes') : __('No') ?></td>
                    <td><?= $child->has('sibling_group') && $child->sibling_group ? h($child->sibling_group->name) : '' ?></td>
                    <td><?= h($child->created) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $child->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $child->id]) ?>
                        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $child->id], ['confirm' => __('Are you sure you want to delete # {0}?', $child->id)]) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
