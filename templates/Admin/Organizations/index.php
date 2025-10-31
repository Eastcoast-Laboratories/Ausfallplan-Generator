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
        <?= $this->Html->link('+ ' . __('Add Organization'), ['action' => 'add'], ['class' => 'button']) ?>
    </div>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= __('Name') ?></th>
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
                    <td><?= h($organization->name) ?></td>
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
                        // Check permissions for edit and delete
                        $canEdit = false;
                        $canDelete = false;
                        $identity = $this->request->getAttribute('identity');
                        if ($identity) {
                            if ($identity->is_system_admin) {
                                $canEdit = true;
                                $canDelete = true;
                            } else {
                                // Check if user is org_admin of this organization
                                $orgUsersTable = \Cake\ORM\TableRegistry::getTableLocator()->get('OrganizationUsers');
                                $isOrgAdmin = $orgUsersTable->find()
                                    ->where([
                                        'user_id' => $identity->id,
                                        'organization_id' => $organization->id,
                                        'role' => 'org_admin'
                                    ])
                                    ->count() > 0;
                                
                                if ($isOrgAdmin) {
                                    $canEdit = true;
                                    $canDelete = true;
                                }
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
