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
        <h4><?= __('Benutzer') ?> (<?= count($organization->users) ?>)</h4>
        <?php if (\!empty($organization->users)): ?>
        <div class="table-responsive">
            <table>
                <tr>
                    <th><?= __('E-Mail') ?></th>
                    <th><?= __('Rolle') ?></th>
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
