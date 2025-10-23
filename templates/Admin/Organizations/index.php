<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Organization> $organizations
 */
$this->assign('title', __('Organizations Management'));
?>
<div class="organizations index content">
    <h3><?= __('Organizations') ?></h3>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= __('Name') ?></th>
                    <th><?= __('Status') ?></th>
                    <th><?= __('Users') ?></th>
                    <th><?= __('Children') ?></th>
                    <th><?= __('Contact Email') ?></th>
                    <th><?= __('Contact Phone') ?></th>
                    <th><?= __('Created') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($organizations as $organization): ?>
                <tr>
                    <td><?= h($organization->name) ?></td>
                    <td>
                        <?php if ($organization->is_active): ?>
                            <span style="color: green;">● <?= __('Active') ?></span>
                        <?php else: ?>
                            <span style="color: red;">● <?= __('Inactive') ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= h($organization->user_count ?? 0) ?></td>
                    <td><?= h($organization->children_count ?? 0) ?></td>
                    <td><?= h($organization->contact_email ?? '-') ?></td>
                    <td><?= h($organization->contact_phone ?? '-') ?></td>
                    <td><?= h($organization->created->format('Y-m-d')) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $organization->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $organization->id]) ?>
                        <?php if (($organization->user_count ?? 0) == 0): ?>
                            <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $organization->id], ['confirm' => __('Are you sure?')]) ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
