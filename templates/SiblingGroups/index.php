<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\SiblingGroup> $siblingGroups
 */
$this->assign('title', __('Sibling Groups'));
?>
<div class="sibling-groups index content">
    <h3><?= __('Sibling Groups') ?></h3>
    <div class="actions">
        <?= $this->Html->link(__('New Sibling Group'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= __('Name') ?></th>
                    <th><?= __('Children') ?></th>
                    <th><?= __('Created') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($siblingGroups as $siblingGroup): ?>
                <tr>
                    <td><?= h($siblingGroup->label) ?></td>
                    <td><?= count($siblingGroup->children) ?> <?= __('Children') ?></td>
                    <td><?= h($siblingGroup->created) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $siblingGroup->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $siblingGroup->id]) ?>
                        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $siblingGroup->id], ['confirm' => __('Are you sure you want to delete # {0}?', $siblingGroup->id)]) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
