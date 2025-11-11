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
                'autofocus' => true
            ]);
            echo $this->Form->control('password', [
                'type' => 'password',
                'label' => __('Password'),
                'required' => true
            ]);
        ?>
    </fieldset>
    
    <div class="form-actions">
        <?= $this->Form->button(__('Login'), ['class' => 'button-primary']) ?>
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

<?php
// Check if encryption data is available in session (after successful login)
$encryptionData = $this->request->getSession()->read('encryption');
if ($encryptionData): ?>
<?= $this->Html->script('crypto/orgEncryption', ['block' => true]) ?>
<script>
document.addEventListener('DOMContentLoaded', async function() {
    if (!window.OrgEncryption) {
        console.warn('Encryption module not available');
        return;
    }
    
    const encryptionData = <?= json_encode($encryptionData) ?>;
    
    if (!encryptionData || !encryptionData.encrypted_private_key || !encryptionData.key_salt) {
        console.log('No encryption data available for this user');
        return;
    }
    
    // Prompt for password to unwrap private key
    const password = prompt('<?= __('Enter your password to unlock encrypted data:') ?>');
    
    if (!password) {
        console.log('Password not provided, encrypted data will not be available');
        return;
    }
    
    try {
        console.log('Unwrapping private key...');
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
                    const dek = await window.OrgEncryption.unwrapDEK(
                        wrappedDekData.wrapped_dek,
                        privateKey
                    );
                    
                    // Store DEK in session storage
                    window.OrgEncryption.storeDEK(wrappedDekData.organization_id, dek);
                    console.log(`DEK stored for organization ${wrappedDekData.organization_id}`);
                } catch (error) {
                    console.error(`Failed to unwrap DEK for organization ${wrappedDekData.organization_id}:`, error);
                }
            }
        }
        
        console.log('Encryption keys loaded successfully');
        alert('<?= __('Encrypted data unlocked successfully!') ?>');
        
        // Redirect to dashboard
        window.location.href = '/dashboard';
    } catch (error) {
        console.error('Key unwrapping error:', error);
        alert('<?= __('Failed to unlock encrypted data. Please check your password.') ?>');
    }
});
</script>
<?php endif; ?>
