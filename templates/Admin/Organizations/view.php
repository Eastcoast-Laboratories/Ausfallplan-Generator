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
                        <label class="toggle-switch" style="display: inline-flex; align-items: center; cursor: pointer;">
                            <input type="checkbox" 
                                   id="encryption-toggle" 
                                   <?= $organization->encryption_enabled ? 'checked' : '' ?>
                                   data-org-id="<?= $organization->id ?>">
                            <span class="toggle-slider"></span>
                            <span style="margin-left: 10px;" id="encryption-status">
                                <?= $organization->encryption_enabled ? '🔒 ' . __('Aktiv') : '🔓 ' . __('Inaktiv') ?>
                            </span>
                        </label>
                        <p style="font-size: 0.9em; color: #666; margin-top: 0.5rem;">
                            <?= __('Verschlüsselt sensible Daten (z.B. Kindernamen) clientseitig vor dem Speichern') ?>
                        </p>
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

<style>
/* Toggle Switch Styling */
.toggle-switch {
    position: relative;
    display: inline-block;
}

.toggle-switch input[type="checkbox"] {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 26px;
    background-color: #ccc;
    border-radius: 34px;
    transition: background-color 0.3s;
}

.toggle-slider:before {
    content: "";
    position: absolute;
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    border-radius: 50%;
    transition: transform 0.3s;
}

.toggle-switch input:checked + .toggle-slider {
    background-color: #4CAF50;
}

.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(24px);
}

.toggle-switch input:disabled + .toggle-slider {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('encryption-toggle');
    const statusText = document.getElementById('encryption-status');
    
    if (!toggle) return;
    
    toggle.addEventListener('change', async function() {
        const orgId = this.dataset.orgId;
        const isEnabled = this.checked;
        
        // Show confirmation dialog when disabling
        if (!isEnabled) {
            if (!confirm('<?= __('WARNUNG: Das Deaktivieren der Verschlüsselung macht alle verschlüsselten Daten unlesbar! Fortfahren?') ?>')) {
                this.checked = true;
                return;
            }
        }
        
        // Disable toggle during request
        toggle.disabled = true;
        statusText.textContent = '⏳ <?= __('Wird aktualisiert...') ?>';
        
        try {
            const response = await fetch(`/api/organizations/${orgId}/toggle-encryption`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?= $this->request->getAttribute('csrfToken') ?>'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Update status text
                if (data.encryption_enabled) {
                    statusText.textContent = '🔒 <?= __('Aktiv') ?>';
                } else {
                    statusText.textContent = '🔓 <?= __('Inaktiv') ?>';
                }
                
                // Show success message
                alert(data.message || '<?= __('Verschlüsselungseinstellung wurde aktualisiert') ?>');
            } else {
                // Revert toggle
                toggle.checked = !isEnabled;
                statusText.textContent = isEnabled ? '🔓 <?= __('Inaktiv') ?>' : '🔒 <?= __('Aktiv') ?>';
                alert(data.message || '<?= __('Fehler beim Aktualisieren der Verschlüsselung') ?>');
            }
        } catch (error) {
            console.error('Encryption toggle error:', error);
            // Revert toggle
            toggle.checked = !isEnabled;
            statusText.textContent = isEnabled ? '🔓 <?= __('Inaktiv') ?>' : '🔒 <?= __('Aktiv') ?>';
            alert('<?= __('Netzwerkfehler beim Aktualisieren der Verschlüsselung') ?>');
        } finally {
            toggle.disabled = false;
        }
    });
});
</script>
