<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="users form content">
    <?= $this->Form->create() ?>
    <fieldset>
        <legend><?= __('Login') ?></legend>
        <p><?= __('Please enter your email and password to access your account.') ?></p>
        
        <?php
            echo $this->Form->control('email', [
                'type' => 'email',
                'label' => __('Email'),
                'required' => true,
                'placeholder' => 'email@example.com',
                'autofocus' => true,
                'id' => 'email-field'
            ]);
            echo $this->Form->control('password', [
                'type' => 'password',
                'label' => __('Password'),
                'required' => true,
                'id' => 'password-field'
            ]);
        ?>
    </fieldset>
    
    <div class="form-actions">
        <?= $this->Form->button(__('Login'), ['class' => 'button-primary', 'id' => 'login-button']) ?>
        <?= $this->Html->link(__('Create new account'), ['action' => 'register'], ['class' => 'button']) ?>
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
</style>

<?= $this->Html->script('crypto/orgEncryption', ['block' => true]) ?>
<script>
// Capture password before login for automatic key unwrapping
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.querySelector('form');
    const passwordField = document.getElementById('password-field');
    
    if (loginForm && passwordField) {
        loginForm.addEventListener('submit', function() {
            // Store password temporarily in sessionStorage for key unwrapping after redirect
            try {
                sessionStorage.setItem('_temp_login_password', passwordField.value);
                console.log('Password stored in sessionStorage for key unwrapping');
            } catch (e) {
                console.error('Failed to store password:', e);
            }
        });
    }
});

// Auto-unwrap keys after successful login
<?php
$encryptionData = $this->request->getSession()->read('encryption');
if ($encryptionData): ?>
(async function() {
    if (!window.OrgEncryption) {
        console.warn('Encryption module not available');
        return;
    }
    
    const encryptionData = <?= json_encode($encryptionData) ?>;
    
    if (!encryptionData || !encryptionData.encrypted_private_key || !encryptionData.key_salt) {
        console.log('No encryption data available for this user');
        return;
    }
    
    // Get password from sessionStorage
    let password = null;
    try {
        password = sessionStorage.getItem('_temp_login_password');
    } catch (e) {
        console.error('Failed to read password from sessionStorage:', e);
    }
    
    if (!password) {
        console.log('No password available for automatic key unwrapping');
        return;
    }
    
    console.log('Password retrieved from sessionStorage');
    
    try {
        console.log('Automatically unwrapping private key...');
        const privateKey = await window.OrgEncryption.unwrapPrivateKeyWithPassword(
            encryptionData.encrypted_private_key,
            password,
            encryptionData.key_salt
        );
        
        console.log('Private key unwrapped successfully');
        
        // Unwrap DEKs for each organization
        if (encryptionData.wrapped_deks && encryptionData.wrapped_deks.length > 0) {
            for (const wrappedDekData of encryptionData.wrapped_deks) {
                try {
                    console.log(`Unwrapping DEK for organization ${wrappedDekData.organization_id}...`);
                    
                    // Convert base64 wrapped DEK to ArrayBuffer
                    const wrappedDekArrayBuffer = window.OrgEncryption.base64ToArrayBuffer(wrappedDekData.wrapped_dek);
                    
                    const dek = await window.OrgEncryption.unwrapDEK(
                        wrappedDekArrayBuffer,
                        privateKey
                    );
                    
                    // Store DEK in session storage
                    window.OrgEncryption.storeDEK(wrappedDekData.organization_id, dek);
                    console.log(`✅ DEK stored for organization ${wrappedDekData.organization_id}`);
                } catch (error) {
                    console.error(`Failed to unwrap DEK for organization ${wrappedDekData.organization_id}:`, error);
                }
            }
        }
        
        console.log('✅ Encryption keys loaded successfully - encryption active!');
        
        // Clear temp password from sessionStorage
        try {
            sessionStorage.removeItem('_temp_login_password');
            console.log('Temp password cleared from sessionStorage');
        } catch (e) {
            console.error('Failed to clear temp password:', e);
        }
        
        // DON'T redirect - user is already on login page after successful login
        // The login controller redirects us, so we don't need to do it here
    } catch (error) {
        console.error('Key unwrapping error:', error);
        console.log('⚠️ Encryption not available - falling back to plaintext');
    }
})();
<?php endif; ?>
</script>
