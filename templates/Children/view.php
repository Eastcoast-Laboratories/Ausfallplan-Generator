<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Child $child
 */
?>
<div class="children view content">
    <h3>
        <span class="child-name" 
              data-encrypted="<?= h($child->name_encrypted ?? '') ?>"
              data-iv="<?= h($child->name_iv ?? '') ?>"
              data-tag="<?= h($child->name_tag ?? '') ?>"
              data-org-id="<?= $child->organization_id ?>"><?= h($child->name) ?></span>
        <?php if ($child->sibling_group_id && isset($siblingNames[$child->id])): ?>
            <?= $this->Html->link(
                '👨‍👩‍👧 ' . __("Siblings"),
                ['controller' => 'SiblingGroups', 'action' => 'view', $child->sibling_group_id],
                [
                    'style' => 'background: #fff3cd; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem; margin-left: 0.5rem; text-decoration: none; color: #856404; display: inline-block;',
                    'title' => __('Siblings') . ': ' . h($siblingNames[$child->id]),
                    'escape' => false
                ]
            ) ?>
        <?php endif; ?>
    </h3>
    <table>
        <tr>
            <th><?= __('Name') ?></th>
            <td>
                <span class="child-name" 
                      data-encrypted="<?= h($child->name_encrypted ?? '') ?>"
                      data-iv="<?= h($child->name_iv ?? '') ?>"
                      data-tag="<?= h($child->name_tag ?? '') ?>"
                      data-org-id="<?= $child->organization_id ?>"><?= h($child->name) ?></span>
            </td>
        </tr>
        <tr>
            <th><?= __('Last Name') ?></th>
            <td>
                <span class="child-last-name" 
                      data-encrypted="<?= h($child->last_name_encrypted ?? '') ?>"
                      data-iv="<?= h($child->last_name_iv ?? '') ?>"
                      data-tag="<?= h($child->last_name_tag ?? '') ?>"
                      data-org-id="<?= $child->organization_id ?>"><?= h($child->last_name) ?></span>
            </td>
        </tr>
        <tr>
            <th><?= __('Display Name (for Reports)') ?></th>
            <td><strong><?= h($child->display_name ?? ($child->name . ' ' . $child->last_name)) ?></strong></td>
        </tr>
        <tr>
            <th><?= __('Status') ?></th>
            <td><?= $child->is_active ? __('Active') : __('Inactive') ?></td>
        </tr>
        <tr>
            <th><?= __('Integrative') ?></th>
            <td><?= $child->is_integrative ? __('Yes') : __('No') ?></td>
        </tr>
        <tr>
            <th><?= __('Sibling Group') ?></th>
            <td><?= $child->has('sibling_group') && $child->sibling_group ? $this->Html->link($child->sibling_group->name, ['controller' => 'SiblingGroups', 'action' => 'view', $child->sibling_group->id]) : __('None') ?></td>
        </tr>        <tr>
            <th><?= __('Created') ?></th>
            <td><?= h($child->created) ?></td>
        </tr>
        <tr>
            <th><?= __('Modified') ?></th>
            <td><?= h($child->modified) ?></td>
        </tr>
    </table>
    
    <div class="related">
        <h4><?= __('Assignments') ?></h4>
        <?php if (!empty($child->assignments)) : ?>
        <div class="table-responsive">
            <table>
                <tr>
                    <th><?= __('Schedule Day') ?></th>
                    <th><?= __('Created') ?></th>
                </tr>
                <?php foreach ($child->assignments as $assignment) : ?>
                <tr>
                    <td><?= h($assignment->schedule_day_id) ?></td>
                    <td><?= h($assignment->created) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="actions">
        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $child->id], ['class' => 'button']) ?>
        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $child->id], ['confirm' => __('Are you sure?'), 'class' => 'button']) ?>
        <?= $this->Html->link(__('List Children'), ['action' => 'index'], ['class' => 'button']) ?>
    </div>
</div>
