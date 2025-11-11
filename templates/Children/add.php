<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Child $child
 * @var array $siblingGroups
 * @var array $schedules
 */
$this->assign('title', __('Add Child'));
?>
<div class="children form content">
    <?= $this->Form->create($child) ?>
    <fieldset>
        <legend><?= __('Add Child') ?></legend>
        <?php
            echo $this->Form->control('name', ['label' => __('Name'), 'required' => true, 'autofocus' => true]);
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
            ]);
        ?>
    </fieldset>
    
    <!-- Hidden fields for encrypted name data -->
    <?= $this->Form->hidden('name_encrypted', ['id' => 'name-encrypted-field']) ?>
    <?= $this->Form->hidden('name_iv', ['id' => 'name-iv-field']) ?>
    <?= $this->Form->hidden('name_tag', ['id' => 'name-tag-field']) ?>
    
    <?= $this->Form->button(__('Submit'), ['id' => 'submit-button']) ?>
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
    
    if (!form || !window.OrgEncryption) {
        console.log('Encryption module not available or form not found');
        return;
    }
    
    // Intercept form submission to encrypt name if needed
    form.addEventListener('submit', async function(e) {
        // Only encrypt if fields are empty (first submission)
        if (nameEncryptedField.value) {
            return; // Already encrypted, proceed with submission
        }
        
        e.preventDefault();
        
        const name = nameField.value.trim();
        if (!name) {
            alert('<?= __('Please enter a name') ?>');
            return;
        }
        
        // Check if organization has encryption enabled
        const selectedOrgId = <?= json_encode($selectedOrgId ?? 0) ?>;
        
        console.log('ENCRYPTION_CHECK: selectedOrgId =', selectedOrgId);
        
        if (!selectedOrgId) {
            console.log('ENCRYPTION_CHECK: No organization selected, proceeding without encryption');
            form.submit();
            return;
        }
        
        // Try to get DEK from session storage (async!)
        const dek = await window.OrgEncryption.getDEK(selectedOrgId);
        
        console.log(`ENCRYPTION_CHECK: DEK found = ${dek ? 'YES' : 'NO'}`);
        console.log(`ENCRYPTION_CHECK: DEK type = ${typeof dek} ${dek instanceof CryptoKey ? 'CryptoKey' : 'Not CryptoKey'}`);
        
        if (!dek) {
            console.log('ENCRYPTION_CHECK: No DEK available for organization, proceeding without encryption');
            
            // WARN user that data will be stored unencrypted
            const proceed = confirm('⚠️ WARNING: No encryption key available for this organization!\n\nThe child\'s data will be stored UNENCRYPTED in the database.\n\nThis may happen if:\n- Encryption is not set up for your organization\n- You need to log out and log in again\n- Your encryption keys are not properly configured\n\nDo you want to proceed anyway?');
            
            if (!proceed) {
                submitButton.disabled = false;
                submitButton.textContent = '<?= __('Submit') ?>';
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
            const encrypted = await window.OrgEncryption.encryptField(name, dek);
            
            // Set encrypted values
            nameEncryptedField.value = encrypted.ciphertext;
            nameIvField.value = encrypted.iv;
            nameTagField.value = encrypted.tag;
            
            console.log('Name encrypted successfully, submitting form...');
            
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
