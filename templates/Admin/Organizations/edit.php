<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Organization $organization
 * @var array $allUsers
 */
$this->assign('title', __('Organisation bearbeiten'));
?>
<div class="organization form content">
    <h3><?= __('Organisation bearbeiten') ?></h3>
    
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
            <h4><?= __('Benutzer verwalten') ?> (<?= count($organization->users) ?>)</h4>
            
            <?php if (!empty($organization->users)): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th><?= __('E-Mail') ?></th>
                            <th><?= __('Rolle') ?></th>
                            <th><?= __('Status') ?></th>
                            <th class="actions"><?= __('Aktionen') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($organization->users as $user): ?>
                        <tr>
                            <td><?= h($user->email) ?></td>
                            <td><?= h($user->role) ?></td>
                            <td><?= h($user->status) ?></td>
                            <td class="actions">
                                <?= $this->Form->postLink(
                                    __('Entfernen'),
                                    ['action' => 'removeUser', $organization->id, $user->id],
                                    ['confirm' => __('User aus dieser Organisation entfernen?'), 'class' => 'button button-small']
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p><?= __('Keine Benutzer in dieser Organisation.') ?></p>
            <?php endif; ?>
            
            <div style="margin-top: 2rem;">
                <h5><?= __('Benutzer hinzufügen') ?></h5>
                <?= $this->Form->create(null, ['url' => ['action' => 'addUser', $organization->id]]) ?>
                <div class="input">
                    <?= $this->Form->control('user_id', [
                        'options' => $allUsers,
                        'empty' => __('-- Benutzer wählen --'),
                        'label' => __('Benutzer')
                    ]) ?>
                </div>
                <?= $this->Form->button(__('Hinzufügen'), ['class' => 'button']) ?>
                <?= $this->Form->end() ?>
            </div>
        </div>
    </div>
</div>
