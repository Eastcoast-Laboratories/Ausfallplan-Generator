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
                <?php 
                    $isError = isset($errorGroups) && in_array($siblingGroup->id, $errorGroups);
                    $rowStyle = $isError ? 'background: #ffe5e5; border-left: 4px solid #d32f2f;' : '';
                ?>
                <tr style="<?= $rowStyle ?>">
                    <td>
                        <?= h($siblingGroup->label) ?>
                        <?php if ($isError): ?>
                            <span style="color: #d32f2f; font-weight: bold; margin-left: 0.5rem;" title="<?= __('Diese Gruppe hat nur 1 Kind - das ist ein Datenfehler!') ?>">
                                ⚠️ FEHLER
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= count($siblingGroup->children) ?> <?= __('Children') ?>
                        <?php if ($isError): ?>
                            <span style="color: #d32f2f; font-weight: bold;">
                                (zu wenig!)
                            </span>
                        <?php endif; ?>
                    </td>
                    <td><?= h($siblingGroup->created) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $siblingGroup->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $siblingGroup->id]) ?>
                        <?php if (count($siblingGroup->children) == 0): ?>
                            <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $siblingGroup->id], ['confirm' => __('Are you sure you want to delete this sibling group?'), 'class' => 'button-delete']) ?>
                        <?php else: ?>
                            <span style="color: #999;" title="<?= __('Cannot delete group with children') ?>"><?= __('Delete') ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
