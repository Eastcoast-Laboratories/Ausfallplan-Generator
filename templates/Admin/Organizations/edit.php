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
                <?= $this->Form->control('encryption_enabled', [
                    'type' => 'checkbox',
                    'label' => __('Client-Side Encryption aktiviert'),
                    'help' => __('Wenn deaktiviert, werden verschlüsselte Kindernamen automatisch entschlüsselt und als Klartext in der Datenbank gespeichert.')
                ]) ?>
                <?= $this->Form->control('contact_email', ['type' => 'email', 'label' => __('Kontakt E-Mail')]) ?>
                <?= $this->Form->control('contact_phone', ['label' => __('Telefon')]) ?>
            </fieldset>
            <?= $this->Form->button(__('Speichern'), ['id' => 'save-org-btn']) ?>
            <?= $this->Html->link(__('Abbrechen'), ['action' => 'view', $organization->id], ['class' => 'button']) ?>
            <?= $this->Form->end() ?>
            
            <script>
            document.addEventListener('DOMContentLoaded', async function() {
                const encryptionCheckbox = document.querySelector('input[name="encryption_enabled"]');
                const form = document.querySelector('form');
                const organizationId = <?= $organization->id ?>;
                const originalEncryptionState = encryptionCheckbox ? encryptionCheckbox.checked : false;
                
                console.log('🔐 Org Edit: Original encryption_enabled from DB:', <?= json_encode($organization->encryption_enabled) ?>);
                console.log('🔐 Org Edit: Checkbox checked state:', encryptionCheckbox ? encryptionCheckbox.checked : 'N/A');
                
                // Flag to track if decryption is complete
                let decryptionComplete = false;
                
                if (form && encryptionCheckbox) {
                    form.addEventListener('submit', async function(e) {
                        console.log('📝 Form submit triggered. DecryptionComplete:', decryptionComplete);
                        
                        // Check if encryption was disabled AND we haven't decrypted yet
                        if (originalEncryptionState && !encryptionCheckbox.checked && !decryptionComplete) {
                            e.preventDefault();
                            console.log('🔐 Encryption is being disabled - need to decrypt children first');
                            
                            if (!confirm('<?= __('⚠️ WARNUNG: Verschlüsselung deaktivieren?') ?>\n\n<?= __('Alle verschlüsselten Kindernamen werden automatisch entschlüsselt und als Klartext in der Datenbank gespeichert. Dieser Vorgang kann nicht rückgängig gemacht werden.') ?>\n\n<?= __('Fortfahren?') ?>')) {
                                return;
                            }
                            
                            // User confirmed - now decrypt all children names client-side
                            try {
                                console.log('🔓 Starting client-side decryption of children names...');
                                
                                // Check if OrgEncryption module is available
                                if (!window.OrgEncryption) {
                                    alert('Encryption module not loaded. Please reload the page.');
                                    return;
                                }
                                
                                // Get DEK for this organization
                                const dek = await window.OrgEncryption.getDEK(organizationId);
                                if (!dek) {
                                    alert('No encryption key available. Cannot decrypt children names.');
                                    return;
                                }
                                
                                // Use children data from PHP
                                const children = <?= json_encode($children) ?>;
                                
                                console.log(`🔓 Found ${children.length} children to decrypt`);
                                
                                const decryptedNames = {};
                                let decryptCount = 0;
                                
                                for (const child of children) {
                                    if (child.name_encrypted) {
                                        try {
                                            const decryptedName = await window.OrgEncryption.decryptData(child.name_encrypted, dek);
                                            decryptedNames[child.id] = decryptedName;
                                            decryptCount++;
                                            console.log(`✅ Decrypted child ${child.id}: ${decryptedName}`);
                                        } catch (err) {
                                            console.error(`❌ Failed to decrypt child ${child.id}:`, err);
                                            // Use existing name as fallback
                                            decryptedNames[child.id] = child.name || 'Decryption failed';
                                        }
                                    }
                                }
                                
                                console.log(`🔓 Successfully decrypted ${decryptCount} children names`);
                                
                                // Check if we actually decrypted any children
                                if (decryptCount === 0) {
                                    console.warn('⚠️ No encrypted children found to decrypt');
                                    if (!confirm('<?= __('Keine verschlüsselten Kindernamen gefunden. Möchten Sie die Verschlüsselung trotzdem deaktivieren?') ?>')) {
                                        return;
                                    }
                                }
                                
                                // Add decrypted names as hidden fields to form
                                for (const [childId, decryptedName] of Object.entries(decryptedNames)) {
                                    const hiddenField = document.createElement('input');
                                    hiddenField.type = 'hidden';
                                    hiddenField.name = `decrypted_children_names[${childId}]`;
                                    hiddenField.value = decryptedName;
                                    form.appendChild(hiddenField);
                                    console.log(`➕ Added hidden field: child ${childId} = ${decryptedName}`);
                                }
                                
                                console.log('✅ All hidden fields added. Setting decryptionComplete flag and resubmitting...');
                                
                                // Mark decryption as complete
                                decryptionComplete = true;
                                
                                // Add success count as hidden field for server-side message
                                const countField = document.createElement('input');
                                countField.type = 'hidden';
                                countField.name = 'decrypted_count';
                                countField.value = decryptCount;
                                form.appendChild(countField);
                                
                                // Now submit the form - this will trigger the submit event again but with the flag set
                                form.requestSubmit();
                                
                            } catch (error) {
                                console.error('❌ Error during decryption:', error);
                                alert('Error decrypting children names: ' + error.message);
                            }
                        }
                        // If encryption is being enabled or unchanged, form submits normally
                    });
                }
            });
            </script>
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
