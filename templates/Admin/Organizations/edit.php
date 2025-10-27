<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Organization $organization
 * @var array $allUsers
 */
$this->assign('title', __('Edit Organization'));
?>
<div class="organization form content">
    <h3><?= __('Edit Organization') ?></h3>
    
    <div class="row">
        <div class="column">
            <h4><?= __('Basis-Informationen') ?></h4>
            <?= $this->Form->create($organization) ?>
            <fieldset>
                <?= $this->Form->control('name', ['required' => true]) ?>
                <?= $this->Form->control('is_active', ['type' => 'checkbox', 'label' => __('Aktiv')]) ?>
                <?= $this->Form->control('contact_email', ['type' => 'email', 'label' => __('Kontakt E-Mail')]) ?>
                <?= $this->Form->control('contact_phone', ['label' => __('Telefon')]) ?>
            </fieldset>
            <?= $this->Form->button(__('Speichern')) ?>
            <?= $this->Html->link(__('Abbrechen'), ['action' => 'view', $organization->id], ['class' => 'button']) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>

    <div class="row" style="margin-top: 2rem;">
        <div class="column">
            <h4><?= __('Mitglieder verwalten') ?> (<?= count($organization->organization_users ?? []) ?>)</h4>
            
            <?php if (!empty($organization->organization_users)): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th><?= __('E-Mail') ?></th>
                            <th><?= __('Rolle in Organisation') ?></th>
                            <th><?= __('Hauptorganisation') ?></th>
                            <th><?= __('Beigetreten') ?></th>
                            <th class="actions"><?= __('Aktionen') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($organization->organization_users as $orgUser): ?>
                        <tr>
                            <td><?= h($orgUser->user->email ?? '-') ?></td>
                            <td>
                                <?php
                                $roleLabels = [
                                    'org_admin' => __('Organization Admin'),
                                    'editor' => __('Editor'),
                                    'viewer' => __('Viewer')
                                ];
                                echo h($roleLabels[$orgUser->role] ?? $orgUser->role);
                                ?>
                            </td>
                            <td><?= $orgUser->is_primary ? '⭐' : '-' ?></td>
                            <td><?= $orgUser->joined_at ? $orgUser->joined_at->format('d.m.Y') : '-' ?></td>
                            <td class="actions">
                                <?= $this->Form->postLink(
                                    __('Entfernen'),
                                    ['action' => 'removeUser', $organization->id, $orgUser->user_id],
                                    ['confirm' => __('Mitglied aus Organisation entfernen?'), 'class' => 'button button-small button-danger']
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p><?= __('Keine Mitglieder in dieser Organisation.') ?></p>
            <?php endif; ?>
            
            <div style="margin-top: 2rem;">
                <h5><?= __('Mitglied hinzufügen') ?></h5>
                <?= $this->Form->create(null, ['url' => ['action' => 'addUser', $organization->id]]) ?>
                <div class="input">
                    <?= $this->Form->control('user_id', [
                        'options' => $allUsers,
                        'empty' => __('-- Benutzer wählen --'),
                        'label' => __('Benutzer')
                    ]) ?>
                    <?= $this->Form->control('role', [
                        'options' => [
                            'org_admin' => __('Organization Admin'),
                            'editor' => __('Editor'),
                            'viewer' => __('Viewer')
                        ],
                        'default' => 'viewer',
                        'label' => __('Rolle')
                    ]) ?>
                </div>
                <?= $this->Form->button(__('Hinzufügen'), ['class' => 'button']) ?>
                <?= $this->Form->end() ?>
            </div>
        </div>
    </div>
</div>
