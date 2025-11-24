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
            <h4><?= __('Basis-Information') ?></h4>
            <?= $this->Form->create($organization) ?>
            <fieldset>
                <?= $this->Form->control('name', ['required' => true]) ?>
                <?= $this->Form->control('is_active', ['type' => 'checkbox', 'label' => __('Aktive')]) ?>
                <?= $this->Form->control('encryption_enabled', [
                    'type' => 'checkbox',
                    'label' => __('Client-Side Encryption enabled'),
                    'help' => __('Wenn deaktiviert, werden verschlüsselte Kindernamen automatisch entschlüsselt und als Klartext in der Datenbank gespeichert.'),
                    'checked' => (bool)$organization->encryption_enabled  // Explicitly set from DB
                ]) ?>
                <?= $this->Form->control('contact_email', ['type' => 'email', 'label' => __('Kontakt E-Mail')]) ?>
                <?= $this->Form->control('contact_phone', ['label' => __('Telefon')]) ?>
            </fieldset>
            <?= $this->Form->button(__('Save'), ['id' => 'save-org-btn']) ?>
            <?= $this->Html->link(__('Cancel'), ['action' => 'view', $organization->id], ['class' => 'button']) ?>
            <?= $this->Form->end() ?>
            
            <script>
            document.addEventListener('DOMContentLoaded', async function() {
                const encryptionCheckbox = document.querySelector('input[name="encryption_enabled"]');
                const form = document.querySelector('form');
                const organizationId = <?= $organization->id ?>;
                // Use DB value, not checkbox state (checkbox might be manipulated by browser)
                const originalEncryptionState = <?= json_encode((bool)$organization->encryption_enabled) ?>;
                
                // Force checkbox to match DB value (fix browser autocomplete issues)
                if (encryptionCheckbox) {
                    encryptionCheckbox.checked = originalEncryptionState;
                }
                
                console.log('🔐 Org Edit: Original encryption_enabled from DB:', originalEncryptionState);
                console.log('🔐 Org Edit: Checkbox forced to:', encryptionCheckbox ? encryptionCheckbox.checked : 'N/A');
                console.log('🔐 Org Edit: Children data received from PHP:', <?= json_encode($children) ?>);
                console.log('🔐 Org Edit: Number of children:', <?= count($children) ?>);
                
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
                                
                                console.log(`🔓 Total children loaded: ${children.length}`);
                                console.log('🔓 Children data:', JSON.stringify(children, null, 2));
                                
                                const decryptedNames = {};
                                let decryptCount = 0;
                                
                                for (const child of children) {
                                    console.log(`🔍 Checking child ${child.id}:`, {
                                        has_encrypted: !!child.name_encrypted,
                                        has_iv: !!child.name_iv,
                                        has_tag: !!child.name_tag,
                                        encrypted_length: child.name_encrypted ? child.name_encrypted.length : 0
                                    });
                                    
                                    if (child.name_encrypted && child.name_iv && child.name_tag) {
                                        try {
                                            // Convert base64 to Uint8Array (not ArrayBuffer)
                                            const ciphertextBuf = window.OrgEncryption.base64ToArrayBuffer(child.name_encrypted);
                                            const ivBuf = window.OrgEncryption.base64ToArrayBuffer(child.name_iv);
                                            const tagBuf = window.OrgEncryption.base64ToArrayBuffer(child.name_tag);
                                            
                                            const ciphertext = new Uint8Array(ciphertextBuf);
                                            const iv = new Uint8Array(ivBuf);
                                            const tag = new Uint8Array(tagBuf);
                                            
                                            // Decrypt the field
                                            const decryptedName = await window.OrgEncryption.decryptField(ciphertext, iv, tag, dek);
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
            <h4><?= __('Manage Members') ?> (<?= count($organization->organization_users ?? []) ?>)</h4>
            
            <?php if (!empty($organization->organization_users)): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th><?= __('Email') ?></th>
                            <th><?= __('Role in Organization') ?></th>
                            <th><?= __('Primary Organization') ?></th>
                            <th><?= __('Joined') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
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
                                    __('Remove'),
                                    ['action' => 'removeUser', $organization->id, $orgUser->user_id],
                                    ['confirm' => __('Remove member from organization?'), 'class' => 'button button-small button-danger']
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p><?= __('No members in this organization.') ?></p>
            <?php endif; ?>
            
            <div style="margin-top: 2rem;">
                <h5><?= __('Add Member') ?></h5>
                <?= $this->Form->create(null, ['url' => ['action' => 'addUser', $organization->id]]) ?>
                <div class="input">
                    <?= $this->Form->control('user_id', [
                        'options' => $allUsers,
                        'empty' => __('-- Select User --'),
                        'label' => __('User')
                    ]) ?>
                    <?= $this->Form->control('role', [
                        'options' => [
                            'org_admin' => __('Organization Admin'),
                            'editor' => __('Editor'),
                            'viewer' => __('Viewer')
                        ],
                        'default' => 'viewer',
                        'label' => __('Role')
                    ]) ?>
                </div>
                <?= $this->Form->button(__('Add'), ['class' => 'button']) ?>
                <?= $this->Form->end() ?>
            </div>
        </div>
    </div>
</div>
