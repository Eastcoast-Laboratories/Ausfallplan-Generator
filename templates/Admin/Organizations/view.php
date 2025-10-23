<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Organization $organization
 */
$this->assign('title', h($organization->name));
?>
<div class="organization view content">
    <h3><?= h($organization->name) ?></h3>
    
    <div class="actions" style="margin-bottom: 2rem;">
        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $organization->id], ['class' => 'button']) ?>
        <?= $this->Html->link(__('Back to List'), ['action' => 'index'], ['class' => 'button']) ?>
        <?= $this->Form->postLink(
            $organization->is_active ? __('Deactivate') : __('Activate'), 
            ['action' => 'toggleActive', $organization->id],
            ['class' => 'button']
        ) ?>
    </div>

    <div class="row">
        <div class="column">
            <table>
                <tr>
                    <th><?= __('Status') ?></th>
                    <td>
                        <?php if ($organization->is_active): ?>
                            <span style="color: green; font-weight: bold;">● <?= __('Active') ?></span>
                        <?php else: ?>
                            <span style="color: red; font-weight: bold;">● <?= __('Inactive') ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?= __('Contact Email') ?></th>
                    <td><?= h($organization->contact_email ?? '-') ?></td>
                </tr>
                <tr>
                    <th><?= __('Contact Phone') ?></th>
                    <td><?= h($organization->contact_phone ?? '-') ?></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="related">
        <h4><?= __('Users') ?> (<?= count($organization->users) ?>)</h4>
        <?php if (\!empty($organization->users)): ?>
        <div class="table-responsive">
            <table>
                <tr>
                    <th><?= __('Email') ?></th>
                    <th><?= __('Role') ?></th>
                </tr>
                <?php foreach ($organization->users as $user): ?>
                <tr>
                    <td><?= h($user->email) ?></td>
                    <td><?= h($user->role) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
