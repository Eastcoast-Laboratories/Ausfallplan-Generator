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
        <?= $this->Html->link(__('Bearbeiten'), ['action' => 'edit', $organization->id], ['class' => 'button']) ?>
        <?= $this->Html->link(__('Zurück zur Liste'), ['action' => 'index'], ['class' => 'button']) ?>
        <?= $this->Form->postLink(
            $organization->is_active ? __('Deaktivieren') : __('Aktivieren'), 
            ['action' => 'toggleActive', $organization->id],
            ['class' => 'button']
        ) ?>
        <?php if ($organization->name !== 'keine organisation'): ?>
            <?= $this->Form->postLink(
                __('Delete Organization'), 
                ['action' => 'delete', $organization->id],
                [
                    'confirm' => __('WARNUNG: Dies löscht die Organisation und ALLE zugehörigen Daten (Benutzer, Kinder, Dienstpläne). Fortfahren?'),
                    'class' => 'button button-danger'
                ]
            ) ?>
        <?php endif; ?>
    </div>

    <div class="row">
        <div class="column">
            <table>
                <tr>
                    <th><?= __('Status') ?></th>
                    <td>
                        <?php if ($organization->is_active): ?>
                            <span style="color: green; font-weight: bold;">● <?= __('Aktiv') ?></span>
                        <?php else: ?>
                            <span style="color: red; font-weight: bold;">● <?= __('Inaktiv') ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?= __('Verschlüsselung') ?></th>
                    <td>
                        <?php if ($organization->encryption_enabled): ?>
                            <span style="color: green; font-weight: bold;">🔒 <?= __('Aktiv') ?></span>
                            <p style="font-size: 0.9em; color: #666; margin-top: 0.5rem;">
                                <?= __('Sensible Daten (z.B. Kindernamen) werden clientseitig verschlüsselt') ?>
                            </p>
                        <?php else: ?>
                            <span style="color: #f39c12; font-weight: bold;">🔓 <?= __('Inaktiv') ?></span>
                            <p style="font-size: 0.9em; color: #666; margin-top: 0.5rem;">
                                <?= __('Sensible Daten werden unverschlüsselt gespeichert') ?>
                            </p>
                        <?php endif; ?>
                        <small style="display: block; margin-top: 0.5rem; color: #999;">
                            <?= __('Änderbar unter') ?> <?= $this->Html->link(__('Bearbeiten'), ['action' => 'edit', $organization->id]) ?>
                        </small>
                    </td>
                </tr>
                <tr>
                    <th><?= __('Kontakt E-Mail') ?></th>
                    <td><?= h($organization->contact_email ?? '-') ?></td>
                </tr>
                <tr>
                    <th><?= __('Telefon') ?></th>
                    <td><?= h($organization->contact_phone ?? '-') ?></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="related">
        <h4><?= __('Mitglieder') ?> (<?= count($organization->organization_users ?? []) ?>)</h4>
        <?php if (!empty($organization->organization_users)): ?>
        <div class="table-responsive">
            <table>
                <tr>
                    <th><?= __('E-Mail') ?></th>
                    <th><?= __('Rolle in Organisation') ?></th>
                    <th style="white-space: nowrap;">Haupt&nbsp;organisation</th>
                    <th><?= __('Beigetreten') ?></th>
                    <th><?= __('Aktionen') ?></th>
                </tr>
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
                    <td style="white-space: nowrap;"><?= $orgUser->is_primary ? '⭐' : '-' ?></td>
                    <td><?= $orgUser->joined_at ? $orgUser->joined_at->format('d.m.Y') : '-' ?></td>
                    <td>
                        <?= $this->Form->postLink(
                            __('Entfernen'),
                            ['action' => 'removeUser', $organization->id, $orgUser->user_id],
                            [
                                'confirm' => __('Benutzer aus Organisation entfernen?'),
                                'class' => 'button button-small button-danger'
                            ]
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

