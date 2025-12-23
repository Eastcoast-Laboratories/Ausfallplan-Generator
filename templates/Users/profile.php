<?php
/**
 * User Profile/Settings
 * 
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $userEntity
 */

$this->assign('title', __('Profile Settings'));
?>

<div class="profile-settings">
    <div class="settings-header">
        <h1><?= __('Profile Settings') ?></h1>
        <p><?= __('Manage your account information and preferences.') ?></p>
    </div>

    <div class="settings-content">
        <?= $this->Form->create($userEntity, ['class' => 'settings-form']) ?>
        
        <div class="form-section">
            <h2><?= __('Account Information') ?></h2>
            
            <div class="form-row">
                <?= $this->Form->control('email', [
                    'label' => __('Email Address'),
                    'type' => 'email',
                    'required' => true,
                    'class' => 'form-input'
                ]) ?>
            </div>

            <div class="form-row">
                <div class="form-info">
                    <label><?= __('Subscription Plan') ?></label>
                    <div class="role-badge role-<?= h($userEntity->subscription_plan) ?>">
                        <?php
                        $planNames = [
                            'test' => __('Test (Free)'),
                            'pro' => __('Pro'),
                            'enterprise' => __('Enterprise')
                        ];
                        echo h($planNames[$userEntity->subscription_plan] ?? ucfirst($userEntity->subscription_plan));
                        ?>
                    </div>
                    <small>
                        <?= $this->Html->link(__('Manage Subscription'), ['controller' => 'Subscriptions', 'action' => 'index']) ?>
                    </small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-info">
                    <label><?= __('Role') ?></label>
                    <div class="role-badge role-<?= h($userEntity->role) ?>">
                        <?= h(ucfirst($userEntity->role)) ?>
                    </div>
                    <small><?= __('Your role is managed by administrators.') ?></small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-info">
                    <label><?= __('Member Since') ?></label>
                    <div class="info-value">
                        <?= $userEntity->created ? $userEntity->created->format('d.m.Y') : __('N/A') ?>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-info">
                    <label><?= __('Organizations') ?></label>
                    <div class="organizations-list">
                        <?php if (!empty($userOrganizations)): ?>
                            <ul style="list-style: none; padding: 0; margin: 0;">
                                <?php foreach ($userOrganizations as $orgUser): ?>
                                    <li style="padding: 0.5rem 0; border-bottom: 1px solid #e1e8ed;">
                                        <strong><?= h($orgUser->organization->name ?? 'Unknown') ?></strong>
                                        <span style="color: #95a5a6; font-size: 0.85rem; margin-left: 0.5rem;">
                                            (<?= h($orgUser->role ?? 'unknown') ?>)
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p style="color: #95a5a6;"><?= __('You are not yet assigned to any organization.') ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>


        <div class="form-section">
            <h2><?= __('Personal Information') ?></h2>
            
            <div class="form-row">
                <?= $this->Form->control('first_name', [
                    'label' => __('First Name'),
                    'type' => 'text',
                    'required' => false,
                    'class' => 'form-input'
                ]) ?>
            </div>

            <div class="form-row">
                <?= $this->Form->control('last_name', [
                    'label' => __('Last Name'),
                    'type' => 'text',
                    'required' => false,
                    'class' => 'form-input'
                ]) ?>
            </div>

            <div class="form-row">
                <?= $this->Form->control('info', [
                    'label' => __('Additional Information'),
                    'type' => 'textarea',
                    'required' => false,
                    'class' => 'form-input',
                    'rows' => 4
                ]) ?>
            </div>
        </div>

        <div class="form-section">
            <h2><?= __('Bank Details for Direct Debit') ?></h2>
            <p class="section-hint"><?= __('Required for bank transfer subscription payments.') ?></p>
            
            <div class="form-row">
                <?= $this->Form->control('bank_account_holder', [
                    'label' => __('Account Holder Name') . ' ' . __('(if different)'),
                    'type' => 'text',
                    'required' => false,
                    'class' => 'form-input'
                ]) ?>
            </div>

            <div class="form-row">
                <?= $this->Form->control('bank_iban', [
                    'label' => __('IBAN'),
                    'type' => 'text',
                    'required' => false,
                    'class' => 'form-input',
                    'placeholder' => 'DE89 3704 0044 0532 0130 00'
                ]) ?>
            </div>

            <div class="form-row">
                <?= $this->Form->control('bank_bic', [
                    'label' => __('BIC/SWIFT'),
                    'type' => 'text',
                    'required' => false,
                    'class' => 'form-input',
                    'placeholder' => 'COBADEFFXXX'
                ]) ?>
            </div>
        </div>

        <div class="form-section">
            <h2><?= __('Change Password') ?></h2>
            <p class="section-hint"><?= __('Leave blank if you don\'t want to change your password.') ?></p>
            
            <div class="form-row">
                <?= $this->Form->control('new_password', [
                    'label' => __('New Password'),
                    'type' => 'password',
                    'required' => false,
                    'value' => '',
                    'class' => 'form-input'
                ]) ?>
            </div>

            <div class="form-row">
                <?= $this->Form->control('confirm_password', [
                    'label' => __('Confirm Password'),
                    'type' => 'password',
                    'required' => false,
                    'value' => '',
                    'class' => 'form-input'
                ]) ?>
            </div>
        </div>

        <div class="form-actions">
            <?= $this->Form->button(__('Update Profile'), ['class' => 'btn-primary', 'type' => 'submit']) ?>
            <?= $this->Html->link(__('Cancel'), ['controller' => 'Dashboard', 'action' => 'index'], ['class' => 'btn-secondary']) ?>
        </div>
        <?= $this->Form->end() ?>
        
        <!-- Encryption & Security Section (Collapsible) -->
        <?php
        $hasPublicKey = !empty($userEntity->public_key) && strlen($userEntity->public_key) > 100;
        $hasPrivateKey = !empty($userEntity->encrypted_private_key) && strlen($userEntity->encrypted_private_key) > 100;
        $hasSalt = !empty($userEntity->key_salt) && strlen($userEntity->key_salt) > 10;
        $hasFullEncryption = $hasPublicKey && $hasPrivateKey && $hasSalt;
        $hasError = false; // Will be set by JavaScript
        ?>
        
        <div class="encryption-section" id="encryption-section">
            <div class="encryption-header" id="encryption-header" style="cursor: pointer;">
                <h2><?= __('Encryption & Security') ?> 🔐</h2>
                <button type="button" class="toggle-btn" id="encryption-toggle-btn">
                    <span id="toggle-text"><?= __('Show Details') ?></span>
                    <span id="toggle-icon">▼</span>
                </button>
            </div>
            
            <div class="encryption-content" id="encryption-content" style="display: none;">
                <?php if ($hasFullEncryption): ?>
                    <div class="encryption-status enabled" id="encryption-status-container">
                        <span class="status-icon">✅</span>
                        <span class="status-text"><?= __('Encryption Enabled') ?></span>
                    </div>
                    
                    <div id="encryption-error-warning" style="display: none; margin-top: 15px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px; color: #856404;">
                        <strong>⚠️ <?= __('Encryption Key Error') ?></strong><br>
                        <span id="encryption-error-message"></span><br>
                        <small><?= __('Your encryption keys may be corrupted or incompatible. Please regenerate your encryption keys.') ?></small>
                        
                        <div style="margin-top: 15px;">
                            <div class="password-input-group" style="margin-bottom: 10px;">
                                <label for="reenter-password-input"><?= __('Re-enter Password') ?>:</label>
                                <input type="password" id="reenter-password-input" class="form-input" placeholder="<?= __('Your password') ?>" style="margin-top: 5px;">
                            </div>
                            <button type="button" id="re-enter-password-btn" class="button-secondary" style="margin-right: 10px;">
                                🔑 <?= __('Unlock Keys') ?>
                            </button>
                            <button type="button" id="regenerate-keys-error-btn" class="button-danger">
                                🔄 <?= __('Regenerate Keys') ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="encryption-info" style="margin-top: 15px;">
                        <p>
                            <?= __('Your sensitive data is encrypted using client-side encryption. Your encryption keys were generated on {0}.', [
                                $userEntity->created ? $userEntity->created->format('d.m.Y') : __('registration')
                            ]) ?>
                        </p>
                        
                        <div class="encryption-details" style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                            <strong><?= __('Technical Info:') ?></strong><br>
                            • <?= __('Public Key Length:') ?> <?= strlen($userEntity->public_key) ?> <?= __('chars') ?><br>
                            • <?= __('Encrypted Private Key Length:') ?> <?= strlen($userEntity->encrypted_private_key) ?> <?= __('chars') ?><br>
                            • <?= __('Key Salt Length:') ?> <?= strlen($userEntity->key_salt) ?> <?= __('chars') ?><br>
                            • <?= __('Encryption: RSA-OAEP-2048 + AES-GCM-256') ?>
                        </div>
                        
                        <div style="margin-top: 15px;">
                            <div class="password-input-group" id="regenerate-password-group" style="margin-bottom: 10px; display: none;">
                                <label for="regenerate-password-input"><?= __('Password') ?>:</label>
                                <input type="password" id="regenerate-password-input" class="form-input" placeholder="<?= __('Enter password to regenerate keys') ?>" style="margin-top: 5px;">
                            </div>
                            <button type="button" id="regenerate-keys-enabled-btn" class="button-secondary">
                                🔄 <?= __('Regenerate Encryption Keys') ?>
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="encryption-status disabled">
                        <span class="status-icon">⚠️</span>
                        <span class="status-text"><?= __('Encryption Not Set Up') ?></span>
                    </div>
                    
                    <p style="margin-top: 10px;">
                        <?= __('Your account does not have encryption keys configured. Set up encryption to protect your sensitive data.') ?>
                    </p>
                    
                    <?php if (!$hasPublicKey && !$hasPrivateKey && !$hasSalt): ?>
                        <small style="display: block; margin-top: 5px; color: #999;">
                            <?= __('Status: No encryption keys found in database.') ?>
                        </small>
                    <?php else: ?>
                        <small style="display: block; margin-top: 5px; color: #d9534f;">
                            <?= __('Warning: Partial encryption data detected. Keys may be corrupted.') ?><br>
                            • <?= __('Public Key:') ?> <?= $hasPublicKey ? '✓' : '✗' ?><br>
                            • <?= __('Private Key:') ?> <?= $hasPrivateKey ? '✓' : '✗' ?><br>
                            • <?= __('Salt:') ?> <?= $hasSalt ? '✓' : '✗' ?>
                        </small>
                    <?php endif; ?>
                    
                    <div style="margin-top: 15px;">
                        <div class="password-input-group" style="margin-bottom: 10px;">
                            <label for="setup-password-input"><?= __('Password') ?>:</label>
                            <input type="password" id="setup-password-input" class="form-input" placeholder="<?= __('Enter password to setup encryption') ?>" style="margin-top: 5px;">
                        </div>
                        <button type="button" id="setup-encryption-btn" class="button-primary">
                            <?= $hasPublicKey || $hasPrivateKey || $hasSalt ? __('Regenerate Encryption Keys') : __('Set Up Encryption Now') ?> 🔒
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Encryption Setup Script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle functionality
        const encryptionHeader = document.getElementById('encryption-header');
        const encryptionContent = document.getElementById('encryption-content');
        const toggleBtn = document.getElementById('encryption-toggle-btn');
        const toggleText = document.getElementById('toggle-text');
        const toggleIcon = document.getElementById('toggle-icon');
        
        let isExpanded = false;
        
        function toggleEncryption() {
            isExpanded = !isExpanded;
            encryptionContent.style.display = isExpanded ? 'block' : 'none';
            toggleText.textContent = isExpanded ? '<?= __('Hide Details') ?>' : '<?= __('Show Details') ?>';
            toggleIcon.textContent = isExpanded ? '▲' : '▼';
        }
        
        if (encryptionHeader && toggleBtn) {
            encryptionHeader.addEventListener('click', toggleEncryption);
            toggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleEncryption();
            });
        }
        
        // Check for encryption errors from sessionStorage
        const encryptionError = sessionStorage.getItem('encryption_error');
        const passwordMissing = !sessionStorage.getItem('_temp_login_password');
        
        // Auto-expand if there's an error
        if (encryptionError || passwordMissing) {
            const warningDiv = document.getElementById('encryption-error-warning');
            const messageSpan = document.getElementById('encryption-error-message');
            
            if (warningDiv && messageSpan) {
                if (passwordMissing && !encryptionError) {
                    messageSpan.textContent = '<?= __('Password not available for key decryption. Please re-enter your password.') ?>';
                } else if (encryptionError) {
                    const errorData = JSON.parse(encryptionError);
                    messageSpan.textContent = errorData.error;
                }
                
                warningDiv.style.display = 'block';
                
                // Update status icon to warning
                const statusContainer = document.getElementById('encryption-status-container');
                if (statusContainer) {
                    statusContainer.querySelector('.status-icon').textContent = '⚠️';
                    statusContainer.style.borderLeft = '4px solid #ffc107';
                }
                
                // Auto-expand when there's an error
                if (!isExpanded) {
                    toggleEncryption();
                }
            }
        }
        
        const setupButton = document.getElementById('setup-encryption-btn');
        const setupPasswordInput = document.getElementById('setup-password-input');
        
        if (setupButton && setupPasswordInput && window.OrgEncryption) {
            setupButton.addEventListener('click', async function() {
                if (!confirm('<?= __('This will generate encryption keys for your account. You will need to enter your current password to encrypt your private key. Continue?') ?>')) {
                    return;
                }
                
                const password = setupPasswordInput.value.trim();
                if (!password) {
                    alert('<?= __('Password is required to set up encryption.') ?>');
                    setupPasswordInput.focus();
                    return;
                }
                
                setupButton.disabled = true;
                setupButton.textContent = '<?= __('Generating keys...') ?>';
                
                try {
                    console.log('Generating RSA key pair...');
                    const keyPair = await window.OrgEncryption.generateKeyPair();
                    
                    console.log('Wrapping private key with password...');
                    const result = await window.OrgEncryption.wrapPrivateKeyWithPassword(
                        keyPair.privateKey,
                        password
                    );
                    
                    console.log('Exporting public key...');
                    const publicKeyPem = await window.OrgEncryption.exportPublicKey(keyPair.publicKey);
                    
                    // Send to server
                    const response = await fetch('/users/setup-encryption', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': '<?= $this->request->getAttribute('csrfToken') ?>'
                        },
                        body: JSON.stringify({
                            public_key: publicKeyPem,
                            encrypted_private_key: result.wrappedKey,
                            key_salt: result.salt
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        alert('<?= __('Encryption has been successfully set up for your account!') ?>');
                        window.location.reload();
                    } else {
                        alert('<?= __('Error setting up encryption:') ?> ' + (data.message || 'Unknown error'));
                        setupButton.disabled = false;
                        setupButton.textContent = '<?= __('Set Up Encryption Now') ?> 🔒';
                    }
                } catch (error) {
                    console.error('Encryption setup error:', error);
                    alert('<?= __('Error generating encryption keys. Please try again.') ?>');
                    setupButton.disabled = false;
                    setupButton.textContent = '<?= __('Set Up Encryption Now') ?> 🔒';
                }
            });
        }
        
        // Re-enter Password Button
        const reEnterPasswordBtn = document.getElementById('re-enter-password-btn');
        const reenterPasswordInput = document.getElementById('reenter-password-input');
        if (reEnterPasswordBtn && reenterPasswordInput) {
            reEnterPasswordBtn.addEventListener('click', async function() {
                const password = reenterPasswordInput.value.trim();
                if (!password) {
                    alert('<?= __('Password is required to regenerate encryption keys.') ?>');
                    reenterPasswordInput.focus();
                    return;
                }
                
                // Store password in sessionStorage for encryption
                sessionStorage.setItem('_temp_login_password', password);
                
                // Clear encryption error
                sessionStorage.removeItem('encryption_error');
                
                alert('<?= __('Password stored successfully. Encryption should now work. Reloading page...') ?>');
                window.location.reload();
            });
        }
        
        // Regenerate Keys Button (when error)
        const regenerateKeysErrorBtn = document.getElementById('regenerate-keys-error-btn');
        if (regenerateKeysErrorBtn && reenterPasswordInput && window.OrgEncryption) {
            regenerateKeysErrorBtn.addEventListener('click', async function() {
                if (!confirm('<?= __('This will regenerate your encryption keys. All existing encrypted data will become inaccessible. Continue?') ?>')) {
                    return;
                }
                
                const password = reenterPasswordInput.value.trim();
                if (!password) {
                    alert('<?= __('Password is required to regenerate encryption keys.') ?>');
                    reenterPasswordInput.focus();
                    return;
                }
                
                regenerateKeysErrorBtn.disabled = true;
                regenerateKeysErrorBtn.textContent = '<?= __('Generating keys...') ?>';
                
                try {
                    console.log('Regenerating RSA key pair...');
                    const keyPair = await window.OrgEncryption.generateKeyPair();
                    
                    console.log('Wrapping private key with password...');
                    const result = await window.OrgEncryption.wrapPrivateKeyWithPassword(
                        keyPair.privateKey,
                        password
                    );
                    
                    console.log('Exporting public key...');
                    const publicKeyPem = await window.OrgEncryption.exportPublicKey(keyPair.publicKey);
                    
                    // Send to server
                    const response = await fetch('/users/setup-encryption', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': '<?= $this->request->getAttribute('csrfToken') ?>'
                        },
                        body: JSON.stringify({
                            public_key: publicKeyPem,
                            encrypted_private_key: result.wrappedKey,
                            key_salt: result.salt
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Store password for immediate use
                        sessionStorage.setItem('_temp_login_password', password);
                        sessionStorage.removeItem('encryption_error');
                        
                        alert('<?= __('Encryption keys have been successfully regenerated!') ?>');
                        window.location.reload();
                    } else {
                        alert('<?= __('Error regenerating encryption keys:') ?> ' + (data.message || 'Unknown error'));
                        regenerateKeysErrorBtn.disabled = false;
                        regenerateKeysErrorBtn.textContent = '🔄 <?= __('Regenerate Keys') ?>';
                    }
                } catch (error) {
                    console.error('Key regeneration error:', error);
                    alert('<?= __('Error generating encryption keys. Please try again.') ?>');
                    regenerateKeysErrorBtn.disabled = false;
                    regenerateKeysErrorBtn.textContent = '🔄 <?= __('Regenerate Keys') ?>';
                }
            });
        }
        
        // Regenerate Keys Button (when encryption is enabled and working)
        const regenerateKeysEnabledBtn = document.getElementById('regenerate-keys-enabled-btn');
        const regeneratePasswordInput = document.getElementById('regenerate-password-input');
        const regeneratePasswordGroup = document.getElementById('regenerate-password-group');
        
        console.log('🔐 SCRIPT LOADED: Looking for regenerate button...');
        console.log('🔐 Button element:', regenerateKeysEnabledBtn);
        console.log('🔐 Password input:', regeneratePasswordInput);
        console.log('🔐 Password group:', regeneratePasswordGroup);
        
        if (regenerateKeysEnabledBtn) {
            console.log('✅ Regenerate button found! Adding event listener...');
            regenerateKeysEnabledBtn.addEventListener('click', async function(event) {
                console.log('🔥 REGENERATE BUTTON CLICKED!', event);
                console.log('🔐 Event type:', event.type);
                console.log('🔐 Target:', event.target);
                console.log('🔐 Button disabled?', regenerateKeysEnabledBtn.disabled);
                
                // Show password field if hidden
                if (regeneratePasswordGroup && regeneratePasswordGroup.style.display === 'none') {
                    console.log('🔐 Showing password field');
                    regeneratePasswordGroup.style.display = 'block';
                    if (regeneratePasswordInput) {
                        regeneratePasswordInput.focus();
                    }
                    return;
                }
                
                // Check if OrgEncryption is available
                if (!window.OrgEncryption) {
                    alert('Encryption module not loaded. Please reload the page.');
                    return;
                }
                
                if (!confirm('<?= __('⚠️ WARNING: This will regenerate your encryption keys. All existing encrypted data will become inaccessible and must be re-encrypted. This action cannot be undone. Continue?') ?>')) {
                    return;
                }
                
                if (!regeneratePasswordInput) {
                    alert('Password input field not found.');
                    return;
                }
                
                const password = regeneratePasswordInput.value.trim();
                if (!password) {
                    alert('<?= __('Password is required to regenerate encryption keys.') ?>');
                    regeneratePasswordInput.focus();
                    return;
                }
                
                regenerateKeysEnabledBtn.disabled = true;
                regenerateKeysEnabledBtn.textContent = '<?= __('Generating keys...') ?>';
                
                try {
                    console.log('Regenerating RSA key pair...');
                    const keyPair = await window.OrgEncryption.generateKeyPair();
                    
                    console.log('Wrapping private key with password...');
                    const result = await window.OrgEncryption.wrapPrivateKeyWithPassword(
                        keyPair.privateKey,
                        password
                    );
                    
                    console.log('Exporting public key...');
                    const publicKeyPem = await window.OrgEncryption.exportPublicKey(keyPair.publicKey);
                    
                    // Send to server
                    const response = await fetch('/users/setup-encryption', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': '<?= $this->request->getAttribute('csrfToken') ?>'
                        },
                        body: JSON.stringify({
                            public_key: publicKeyPem,
                            encrypted_private_key: result.wrappedKey,
                            key_salt: result.salt
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Store password for immediate use
                        sessionStorage.setItem('_temp_login_password', password);
                        sessionStorage.removeItem('encryption_error');
                        
                        alert('<?= __('Encryption keys have been successfully regenerated! All children names must be re-encrypted.') ?>');
                        window.location.reload();
                    } else {
                        alert('<?= __('Error regenerating encryption keys:') ?> ' + (data.message || 'Unknown error'));
                        regenerateKeysEnabledBtn.disabled = false;
                        regenerateKeysEnabledBtn.textContent = '🔄 <?= __('Regenerate Encryption Keys') ?>';
                    }
                } catch (error) {
                    console.error('Key regeneration error:', error);
                    alert('<?= __('Error generating encryption keys. Please try again.') ?>');
                    regenerateKeysEnabledBtn.disabled = false;
                    regenerateKeysEnabledBtn.textContent = '🔄 <?= __('Regenerate Encryption Keys') ?>';
                }
            });
        } else {
            console.error('❌ REGENERATE BUTTON NOT FOUND! Button with id "regenerate-keys-enabled-btn" does not exist in the DOM.');
            console.log('🔍 All buttons on page:', document.querySelectorAll('button'));
            console.log('🔍 All elements with class "button-secondary":', document.querySelectorAll('.button-secondary'));
        }
    });
    </script>

    <!-- Danger Zone -->
    <div class="danger-zone">
        <h2><?= __('Danger Zone') ?></h2>
        <p><?= __('Once you delete your account, there is no going back. Please be certain.') ?></p>
        
        <?= $this->Form->postLink(
            __('Delete My Account'),
            ['action' => 'delete', $userEntity->id],
            [
                'class' => 'btn-danger',
                'confirm' => __('Are you sure you want to delete your account? This action cannot be undone.')
            ]
        ) ?>
    </div>
</div>

<style>
    .profile-settings {
        max-width: 800px;
    }

    .settings-header {
        margin-bottom: 2rem;
    }

    .settings-header h1 {
        font-size: 2rem;
        color: #2c3e50;
        margin-bottom: 0.5rem;
    }

    .settings-header p {
        color: #7f8c8d;
    }

    .settings-content {
        background: white;
        border-radius: 8px;
        padding: 2rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .settings-form {
        max-width: 600px;
    }

    .form-section {
        margin-bottom: 2.5rem;
        padding-bottom: 2rem;
        border-bottom: 1px solid #e1e8ed;
    }

    .form-section:last-of-type {
        border-bottom: none;
    }

    .form-section h2 {
        font-size: 1.3rem;
        color: #2c3e50;
        margin-bottom: 0.5rem;
    }

    .section-hint {
        color: #95a5a6;
        font-size: 0.9rem;
        margin-bottom: 1.5rem;
    }

    .form-row {
        margin-bottom: 1.5rem;
    }

    .form-input {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
    }

    .form-input:focus {
        outline: none;
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }

    .form-info label {
        font-weight: 600;
        color: #2c3e50;
        display: block;
        margin-bottom: 0.5rem;
    }

    .role-badge {
        display: inline-block;
        padding: 0.5rem 1rem;
        border-radius: 4px;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .role-admin {
        background: #fee;
        color: #e74c3c;
    }

    .role-editor {
        background: #fef3e0;
        color: #f39c12;
    }

    .role-viewer {
        background: #e3f2fd;
        color: #3498db;
    }

    .info-value {
        color: #7f8c8d;
        font-size: 1rem;
        margin-bottom: 0.25rem;
    }

    .form-info small {
        color: #95a5a6;
        font-size: 0.85rem;
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
    }

    .btn-primary {
        background: #3498db;
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 4px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: background 0.2s;
    }

    .btn-primary:hover {
        background: #2980b9;
    }

    .btn-secondary {
        background: #ecf0f1;
        color: #2c3e50;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 4px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: background 0.2s;
    }

    .btn-secondary:hover {
        background: #d5dbdb;
    }

    .danger-zone {
        margin-top: 3rem;
        padding: 2rem;
        background: #fff5f5;
        border: 2px solid #fee;
        border-radius: 8px;
    }

    .danger-zone h2 {
        color: #e74c3c;
        font-size: 1.3rem;
        margin-bottom: 0.5rem;
    }

    .danger-zone p {
        color: #c0392b;
        margin-bottom: 1.5rem;
    }

    .btn-danger {
        background: #e74c3c;
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 4px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: background 0.2s;
    }

    .btn-danger:hover {
        background: #c0392b;
    }

    /* Encryption Section Styles */
    .encryption-section {
        margin-top: 2rem;
        padding: 1.5rem;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
    }

    .encryption-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 0.5rem;
    }

    .encryption-header h2 {
        margin: 0;
        font-size: 1.3rem;
        color: #2c3e50;
    }

    .toggle-btn {
        background: transparent;
        border: 1px solid #dee2e6;
        padding: 0.5rem 1rem;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9rem;
        color: #6c757d;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: background 0.2s;
    }

    .toggle-btn:hover {
        background: #e9ecef;
    }

    .encryption-content {
        padding-top: 1rem;
    }

    .encryption-status {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        border-radius: 4px;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .encryption-status.enabled {
        background: #d4edda;
        color: #155724;
        border-left: 4px solid #28a745;
    }

    .encryption-status.disabled {
        background: #fff3cd;
        color: #856404;
        border-left: 4px solid #ffc107;
    }

    .password-input-group {
        margin-bottom: 1rem;
    }

    .password-input-group label {
        display: block;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.5rem;
    }

    .button-primary, .button-secondary, .button-danger {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 4px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: background 0.2s;
        font-size: 1rem;
    }

    .button-primary {
        background: #3498db;
        color: white;
    }

    .button-primary:hover {
        background: #2980b9;
    }

    .button-secondary {
        background: #6c757d;
        color: white;
    }

    .button-secondary:hover {
        background: #5a6268;
    }

    .button-danger {
        background: #e74c3c;
        color: white;
    }

    .button-danger:hover {
        background: #c0392b;
    }

    @media (max-width: 600px) {
        .settings-content {
            padding: 1.5rem;
        }

        .encryption-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .toggle-btn {
            width: 100%;
            justify-content: center;
        }

        .form-actions {
            flex-direction: column;
        }

        .btn-primary,
        .btn-secondary {
            width: 100%;
            text-align: center;
        }
    }
</style>
