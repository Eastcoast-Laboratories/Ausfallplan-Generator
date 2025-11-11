<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 * @var array $organizationsList
 */
?>
<div class="users form content">
    <?= $this->Form->create($user) ?>
    <fieldset>
        <legend><?= __('Register New Account') ?></legend>
        <p><?= __('Create your account to start managing your Kita schedules.') ?></p>
        
        <?php
            // Organization selector (reusable element)
            echo $this->element('organization_selector', [
                'organizationsList' => $organizationsList,
                'showRoleSelector' => false // Will be added separately below
            ]);
            
            echo $this->Form->control('email', [
                'type' => 'email',
                'label' => __('Email'),
                'required' => true,
                'placeholder' => 'email@example.com'
            ]);
            
            echo $this->Form->control('password', [
                'type' => 'password',
                'label' => __('Password'),
                'required' => true,
                'minlength' => 8,
                'placeholder' => __('Minimum 8 characters')
            ]);
            
            echo $this->Form->control('password_confirm', [
                'type' => 'password',
                'label' => __('Confirm Password'),
                'required' => true,
                'minlength' => 8
            ]);
            
            // Role selector (only for existing orgs)
            echo $this->Form->control('requested_role', [
                'options' => [
                    'viewer' => __('Viewer') . ' - ' . __('Read-only access'),
                    'editor' => __('Editor') . ' - ' . __('Create and edit schedules'),
                    'org_admin' => __('Organization Admin') . ' - ' . __('Full access (approval required)')
                ],
                'label' => __('Desired role in the organization'),
                'default' => 'editor',
                'id' => 'role-selector',
                'help' => __('For existing organizations, administrators will review your request')
            ]);
        ?>
    </fieldset>
    
    <!-- Hidden fields for encryption keys -->
    <?= $this->Form->hidden('public_key', ['id' => 'public-key-field']) ?>
    <?= $this->Form->hidden('encrypted_private_key', ['id' => 'encrypted-private-key-field']) ?>
    <?= $this->Form->hidden('key_salt', ['id' => 'key-salt-field']) ?>
    
    <div class="form-actions">
        <?= $this->Form->button(__('Create Account'), ['class' => 'button-primary', 'id' => 'register-button']) ?>
        <?= $this->Html->link(__('Already have an account? Login'), ['action' => 'login'], ['class' => 'button']) ?>
    </div>
    <?= $this->Form->end() ?>
</div>


<style>
.users.form {
    max-width: 500px;
    margin: 2rem auto;
    padding: 2rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.users.form fieldset {
    border: none;
    padding: 0;
}

.users.form legend {
    font-size: 1.8rem;
    color: #333;
    margin-bottom: 0.5rem;
}

.users.form p {
    color: #666;
    margin-bottom: 1.5rem;
}

.form-actions {
    margin-top: 1.5rem;
    display: flex;
    gap: 1rem;
}

.form-actions .button {
    flex: 1;
}

#register-button.generating {
    opacity: 0.6;
    cursor: wait;
}
</style>

<?= $this->Html->script('crypto/orgEncryption', ['block' => true]) ?>
<script>
document.addEventListener('DOMContentLoaded', async function() {
    const form = document.querySelector('.users.form form');
    const registerButton = document.getElementById('register-button');
    const passwordField = document.querySelector('input[name="password"]');
    const publicKeyField = document.getElementById('public-key-field');
    const encryptedPrivateKeyField = document.getElementById('encrypted-private-key-field');
    const keySaltField = document.getElementById('key-salt-field');
    
    if (!form || !window.OrgEncryption) {
        console.warn('Encryption module not available or form not found');
        return;
    }
    
    // Intercept form submission to generate keys
    form.addEventListener('submit', async function(e) {
        // Only generate keys if fields are empty (first submission)
        if (publicKeyField.value) {
            return; // Keys already generated, proceed with submission
        }
        
        e.preventDefault();
        
        // Check password match
        const password = passwordField.value;
        const passwordConfirm = document.querySelector('input[name="password_confirm"]').value;
        
        if (password !== passwordConfirm) {
            alert('<?= __('Passwords do not match') ?>');
            return;
        }
        
        if (password.length < 8) {
            alert('<?= __('Password must be at least 8 characters') ?>');
            return;
        }
        
        // Disable button and show loading state
        registerButton.disabled = true;
        registerButton.classList.add('generating');
        const originalText = registerButton.textContent;
        registerButton.textContent = '<?= __('Generating encryption keys...') ?>';
        
        try {
            // Generate encryption keys
            console.log('Generating RSA key pair...');
            const keyPair = await window.OrgEncryption.generateKeyPair();
            
            console.log('Wrapping private key with password...');
            const result = await window.OrgEncryption.wrapPrivateKeyWithPassword(
                keyPair.privateKey,
                password
            );
            
            console.log('Exporting public key...');
            const publicKeyPem = await window.OrgEncryption.exportPublicKey(keyPair.publicKey);
            
            // Generate DEK for new organization
            console.log('Generating DEK for organization...');
            const dek = await window.OrgEncryption.generateDEK();
            
            // Wrap DEK with user's public key
            console.log('Wrapping DEK with public key...');
            const wrappedDek = await window.OrgEncryption.wrapDEK(dek, keyPair.publicKey);
            
            // Convert to base64
            const wrappedDekBase64 = window.OrgEncryption.arrayBufferToBase64(wrappedDek);
            
            // Set hidden field values
            publicKeyField.value = publicKeyPem;
            encryptedPrivateKeyField.value = result.wrappedKey;
            keySaltField.value = result.salt;
            
            // Add DEK field if it doesn't exist
            let dekField = document.getElementById('wrapped-dek-field');
            if (!dekField) {
                dekField = document.createElement('input');
                dekField.type = 'hidden';
                dekField.name = 'wrapped_dek';
                dekField.id = 'wrapped-dek-field';
                form.appendChild(dekField);
            }
            dekField.value = wrappedDekBase64;
            
            console.log('Wrapped key length:', result.wrappedKey.length);
            console.log('Salt length:', result.salt.length);
            console.log('Wrapped DEK length:', wrappedDekBase64.length);
            
            console.log('Keys generated successfully, submitting form...');
            
            // Submit form
            form.submit();
        } catch (error) {
            console.error('Key generation error:', error);
            alert('<?= __('Error generating encryption keys. Please try again.') ?>');
            registerButton.disabled = false;
            registerButton.classList.remove('generating');
            registerButton.textContent = originalText;
        }
    });
});
</script>
