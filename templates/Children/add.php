<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Child $child
 * @var array $siblingGroups
 * @var array $schedules
 */
$this->assign('title', __('Add Child'));
?>

<!-- Encryption Warning Banner -->
<div id="encryption-warning" class="alert alert-warning" style="display: none; margin-bottom: 20px;">
    <strong>⚠️ <?= __('Encryption Warning') ?></strong><br>
    <?= __('No encryption key available for this organization. Data will be stored unencrypted.') ?><br>
    <small><?= __('Please log out and log in again to load encryption keys.') ?></small>
</div>

<div class="children form content">
    <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-bottom: 1rem;">
        <?= $this->Html->link('📥 ' . __('CSV Import'), ['action' => 'import'], ['class' => 'button', 'style' => 'background: #2196f3; color: white;']) ?>
    </div>
    <?= $this->Form->create($child) ?>
    <fieldset>
        <legend><?= __('Add Child') ?></legend>
        <?php
            // Organization selector - show first for editors/admins
            if (!empty($userOrgs)):
                $orgOptions = [];
                foreach ($userOrgs as $org) {
                    $orgOptions[$org->id] = $org->name;
                }
                echo $this->Form->control('organization_id', [
                    'type' => 'select',
                    'options' => $orgOptions,
                    'label' => __('Organization'),
                    'required' => true,
                    'default' => $selectedOrgId ?? null,
                    'help' => __('Select the organization this child belongs to')
                ]);
            endif;
            
            echo $this->Form->control('name', ['label' => __('Name'), 'required' => true, 'autofocus' => false]);
            echo $this->Form->control('last_name', ['label' => __('Last Name'), 'required' => false]);
            echo $this->Form->control('display_name', [
                'label' => __('Display Name (for Reports)'),
                'required' => false,
                'help' => __('How the child\'s name should appear in reports. Leave empty to auto-generate from name + last name.')
            ]);
            echo $this->Form->control('gender', [
                'type' => 'select',
                'options' => [
                    'm' => __('Male'),
                    'f' => __('Female'),
                    'd' => __('Diverse'),
                ],
                'empty' => __('(Not specified)'),
                'label' => __('Gender')
            ]);
            echo $this->Form->control('birthdate', [
                'type' => 'date',
                'label' => __('Birthdate'),
                'empty' => true,
            ]);
            echo $this->Form->control('postal_code', ['label' => __('Postal Code'), 'required' => false]);
            echo $this->Form->control('is_active', ['type' => 'checkbox', 'label' => __('Active'), 'checked' => true]);
            echo $this->Form->control('is_integrative', ['type' => 'checkbox', 'label' => __('Integrative Child')]);
            echo $this->Form->control('sibling_group_id', [
                'options' => $siblingGroups,
                'empty' => __('(No Sibling Group)'),
                'label' => __('Sibling Group'),
            ]);
            echo $this->Form->control('schedule_id', [
                'options' => $schedules,
                'empty' => __('(Select Schedule)'),
                'label' => __('Schedule (Ausfallplan)'),
                'help' => __('Select the schedule this child will be assigned to'),
                'default' => $defaultScheduleId ?? null, // Auto-select if only one exists
            ]);
        ?>
    </fieldset>
    
    <!-- Hidden fields for encrypted name data -->
    <?= $this->Form->hidden('name_encrypted', ['id' => 'name-encrypted-field']) ?>
    <?= $this->Form->hidden('name_iv', ['id' => 'name-iv-field']) ?>
    <?= $this->Form->hidden('name_tag', ['id' => 'name-tag-field']) ?>
    
    <!-- Hidden fields for encrypted last_name data -->
    <?= $this->Form->hidden('last_name_encrypted', ['id' => 'last-name-encrypted-field']) ?>
    <?= $this->Form->hidden('last_name_iv', ['id' => 'last-name-iv-field']) ?>
    <?= $this->Form->hidden('last_name_tag', ['id' => 'last-name-tag-field']) ?>
    
    <?= $this->Form->button(__('Submit'), ['id' => 'submit-button', 'type' => 'button']) ?>
    <?= $this->Form->end() ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    const form = document.querySelector('.children.form form');
    const submitButton = document.getElementById('submit-button');
    const nameField = document.querySelector('input[name="name"]');
    const nameEncryptedField = document.getElementById('name-encrypted-field');
    const nameIvField = document.getElementById('name-iv-field');
    const nameTagField = document.getElementById('name-tag-field');
    const lastNameField = document.querySelector('input[name="last_name"]');
    const lastNameEncryptedField = document.getElementById('last-name-encrypted-field');
    const lastNameIvField = document.getElementById('last-name-iv-field');
    const lastNameTagField = document.getElementById('last-name-tag-field');
    const warningBanner = document.getElementById('encryption-warning');
    
    if (!form) {
        console.log('Form not found');
        return;
    }
    
    // Wait for OrgEncryption module to load
    let attempts = 0;
    while (!window.OrgEncryption && attempts < 50) {
        await new Promise(resolve => setTimeout(resolve, 100));
        attempts++;
    }
    
    if (!window.OrgEncryption) {
        console.log('⚠️ Encryption module not available after waiting');
        return;
    }
    
    console.log('✅ OrgEncryption module loaded in add.php');
    
    // Check encryption status on page load with retry mechanism
    async function checkEncryptionStatus() {
        const selectedOrgId = <?= json_encode($selectedOrgId ?? 0) ?>;
        const encryptionEnabled = <?= json_encode($encryptionEnabled ?? false) ?>;
        
        console.log('🔐 Checking encryption status...');
        console.log('🔐 selectedOrgId =', selectedOrgId);
        console.log('🔐 encryptionEnabled =', encryptionEnabled);
        
        if (!selectedOrgId || !encryptionEnabled) {
            console.log('✅ Encryption not required');
            return true; // Encryption not required
        }
        
        // Debug: Check what's in sessionStorage
        console.log('🔍 SessionStorage keys:', Object.keys(sessionStorage));
        const storageKey = 'orgcrypt_dek_' + selectedOrgId;
        const storedValue = sessionStorage.getItem(storageKey);
        console.log(`🔍 Looking for key: ${storageKey}`);
        console.log(`🔍 Value in storage: ${storedValue ? storedValue.substring(0, 50) + '...' : 'NULL'}`);
        
        // Wait for DEK to be unwrapped (from layout script) - retry up to 5 times
        let dek = null;
        for (let attempt = 1; attempt <= 5; attempt++) {
            console.log(`🔐 Attempt ${attempt}/5 to find DEK...`);
            
            // Wait before checking (longer on first attempt to let layout script run)
            await new Promise(resolve => setTimeout(resolve, attempt === 1 ? 1000 : 500));
            
            // Debug: Check again after waiting
            const storedValueAfter = sessionStorage.getItem(storageKey);
            console.log(`🔍 Value after wait: ${storedValueAfter ? storedValueAfter.substring(0, 50) + '...' : 'NULL'}`);
            
            // Try to get DEK from session storage
            dek = await window.OrgEncryption.getDEK(selectedOrgId);
            
            console.log(`🔐 DEK found = ${dek ? 'YES' : 'NO'}`);
            console.log(`🔐 DEK type = ${typeof dek} ${dek instanceof CryptoKey ? 'CryptoKey' : 'Not CryptoKey'}`);
            
            if (dek) {
                console.log('✅ DEK available, encryption ready');
                warningBanner.style.display = 'none';
                return true;
            }
        }
        
        // After 5 attempts, no DEK found
        console.log('⚠️ No DEK available after 5 attempts');
        warningBanner.style.display = 'block';
        return false;
    }
    
    // Check on page load
    checkEncryptionStatus();
    
    // Handle button click to encrypt and submit
    submitButton.addEventListener('click', async function(e) {
        e.preventDefault();
        
        const name = nameField.value.trim();
        if (!name) {
            alert('<?= __('Please enter a name') ?>');
            return;
        }
        
        // Check if organization has encryption enabled
        const selectedOrgId = <?= json_encode($selectedOrgId ?? 0) ?>;
        const encryptionEnabled = <?= json_encode($encryptionEnabled ?? false) ?>;
        
        if (!selectedOrgId || !encryptionEnabled) {
            console.log('✅ No encryption required, submitting');
            form.submit();
            return;
        }
        
        // Try to get DEK from session storage
        const dek = await window.OrgEncryption.getDEK(selectedOrgId);
        
        if (!dek) {
            console.log('⚠️ No DEK available - asking user');
            
            // WARN user that data will be stored unencrypted
            const proceed = confirm('⚠️ WARNING: No encryption key available for this organization!\n\nThe child\'s data will be stored UNENCRYPTED in the database.\n\nThis may happen if:\n- You need to log out and log in again\n- Your encryption keys are not properly configured\n\nDo you want to proceed anyway?');
            
            if (!proceed) {
                return;
            }
            
            form.submit();
            return;
        }
        
        // Disable button and show loading state
        submitButton.disabled = true;
        const originalText = submitButton.textContent;
        submitButton.textContent = '<?= __('Encrypting...') ?>';
        
        try {
            console.log('Encrypting name field...');
            const encryptedName = await window.OrgEncryption.encryptField(name, dek);
            
            // Convert ArrayBuffer/Uint8Array to base64 strings for form submission
            nameEncryptedField.value = window.OrgEncryption.arrayBufferToBase64(encryptedName.ciphertext);
            nameIvField.value = window.OrgEncryption.arrayBufferToBase64(encryptedName.iv);
            nameTagField.value = window.OrgEncryption.arrayBufferToBase64(encryptedName.tag);
            
            console.log('Name encrypted successfully');
            
            // Encrypt last_name if provided
            const lastName = lastNameField.value.trim();
            if (lastName) {
                console.log('Encrypting last_name field...');
                const encryptedLastName = await window.OrgEncryption.encryptField(lastName, dek);
                
                lastNameEncryptedField.value = window.OrgEncryption.arrayBufferToBase64(encryptedLastName.ciphertext);
                lastNameIvField.value = window.OrgEncryption.arrayBufferToBase64(encryptedLastName.iv);
                lastNameTagField.value = window.OrgEncryption.arrayBufferToBase64(encryptedLastName.tag);
                
                console.log('Last name encrypted successfully');
            }
            
            console.log('All fields encrypted, submitting form...');
            
            // Submit form
            form.submit();
        } catch (error) {
            console.error('Encryption error:', error);
            alert('<?= __('Error encrypting data. Proceeding with plaintext.') ?>');
            submitButton.disabled = false;
            submitButton.textContent = originalText;
            form.submit(); // Submit without encryption as fallback
        }
    });
});
</script>
