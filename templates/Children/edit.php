<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Child $child
 * @var array $siblingGroups
 */
$this->assign('title', __('Edit Child'));
?>
<div class="children form content">
    <h3>
        <?= h($child->name) ?>
        <?php if ($child->sibling_group_id && isset($siblingNames[$child->id])): ?>
            <?= $this->Html->link(
                '👨‍👩‍👧 ' . __("Geschwister"),
                ['controller' => 'SiblingGroups', 'action' => 'view', $child->sibling_group_id],
                [
                    'style' => 'background: #fff3cd; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem; margin-left: 0.5rem; text-decoration: none; color: #856404; display: inline-block;',
                    'title' => 'Geschwister: ' . h($siblingNames[$child->id]),
                    'escape' => false
                ]
            ) ?>
        <?php endif; ?>
    </h3>
    <?= $this->Form->create($child) ?>
    <fieldset>
        <legend><?= __('Edit Child') ?></legend>
        <?php
            echo $this->Form->control('name', ['label' => __('Name'), 'required' => true]);
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
            echo $this->Form->control('is_active', ['type' => 'checkbox', 'label' => __('Active')]);
            echo $this->Form->control('is_integrative', ['type' => 'checkbox', 'label' => __('Integrative Child')]);
            echo $this->Form->control('sibling_group_id', [
                'options' => $siblingGroups,
                'empty' => __('(No Sibling Group)'),
                'label' => __('Sibling Group'),
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

<?= $this->Html->script('crypto/orgEncryption', ['block' => true]) ?>
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
    
    // Get organization ID from child entity
    const orgId = <?= json_encode($child->organization_id) ?>;
    
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
        
        if (!orgId) {
            console.log('No organization ID, proceeding without encryption');
            form.submit();
            return;
        }
        
        // Try to get DEK from session storage
        const dek = window.OrgEncryption.getDEK(orgId);
        
        if (!dek) {
            console.log('No DEK available for organization, proceeding without encryption');
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
