<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Organization> $organizations
 */
$this->assign('title', __('Organization Management'));
?>
<div class="organizations index content">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3 style="margin: 0;"><?= __('Organizations') ?></h3>
        <?php
        // All users can create organizations
        $canCreate = true;
        ?>
        <?php if ($canCreate): ?>
            <?= $this->Html->link('+ ' . __('Add Organization'), ['action' => 'add'], ['class' => 'button']) ?>
        <?php endif; ?>
    </div>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= __('Name') ?></th>
                    <th><?= __('Encryption') ?></th>
                    <th><?= __('Status') ?></th>
                    <th><?= __('Benutzer') ?></th>
                    <th><?= __('Kinder') ?></th>
                    <th><?= __('Kontakt E-Mail') ?></th>
                    <th><?= __('Telefon') ?></th>
                    <th><?= __('Erstellt') ?></th>
                    <th class="actions"><?= __('Aktionen') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($organizations as $organization): ?>
                <tr>
                    <td>
                        <?= h($organization->name) ?>
                        <?php if ($organization->encryption_enabled ?? false): ?>
                            <span style="background: #4CAF50; color: white; padding: 0.2rem 0.4rem; border-radius: 3px; font-size: 0.75rem; margin-left: 0.5rem;" 
                                  title="<?= __('End-to-end encryption enabled') ?>">🔒 <?= __('Encrypted') ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center; font-size: 1.3rem;" title="<?= ($organization->encryption_enabled ?? false) ? __('Encryption enabled') : __('Encryption disabled') ?>">
                        <?php if ($organization->encryption_enabled ?? false): ?>
                            🔒<span style="color: green;">✅</span>
                        <?php else: ?>
                            🔓<span style="color: red;">❌</span>
                        <?php endif; ?>
                    </td>
                    <td style="white-space: nowrap;">
                        <?php if ($organization->is_active): ?>
                            <span style="color: green;">● <?= __('Aktiv') ?></span>
                        <?php else: ?>
                            <span style="color: red;">● <?= __('Inaktiv') ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= h($organization->user_count ?? 0) ?></td>
                    <td><?= h($organization->children_count ?? 0) ?></td>
                    <td><?= h($organization->contact_email ?? '-') ?></td>
                    <td><?= h($organization->contact_phone ?? '-') ?></td>
                    <td><?= h($organization->created->format('Y-m-d H:i')) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('Ansehen'), ['action' => 'view', $organization->id]) ?>
                        <?php 
                        // Check permissions for edit and delete based on user role in this org
                        $canEdit = false;
                        $canDelete = false;
                        $identity = $this->request->getAttribute('identity');
                        if ($identity) {
                            if ($identity->is_system_admin) {
                                // System admin can edit/delete all orgs
                                $canEdit = true;
                                $canDelete = true;
                            } elseif (isset($organization->user_role)) {
                                // Regular users: only org_admin can edit and delete
                                if ($organization->user_role === 'org_admin') {
                                    $canEdit = true;
                                    $canDelete = true;
                                }
                                // editor and viewer: no edit, no delete (canEdit and canDelete stay false)
                            }
                        }
                        ?>
                        <?php if ($canEdit): ?>
                            <?= $this->Html->link(__('Bearbeiten'), ['action' => 'edit', $organization->id]) ?>
                        <?php endif; ?>
                        <?php if ($canDelete && $organization->name !== 'keine organisation'): ?>
                            <?= $this->Form->postLink(
                                __('Löschen'), 
                                ['action' => 'delete', $organization->id], 
                                [
                                    'confirm' => __('WARNUNG: Dies löscht die Organisation und ALLE zugehörigen Daten (Benutzer, Kinder, Dienstpläne). Fortfahren?'),
                                    'class' => 'button-danger'
                                ]
                            ) ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
