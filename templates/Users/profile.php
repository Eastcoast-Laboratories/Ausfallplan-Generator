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
                    'label' => __('Account Holder Name'),
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
            <?= $this->Form->button(__('Save Changes'), [
                'class' => 'btn-primary'
            ]) ?>
            <?= $this->Html->link(__('Cancel'), ['controller' => 'Dashboard', 'action' => 'index'], [
                'class' => 'btn-secondary'
            ]) ?>
        </div>

        <?= $this->Form->end() ?>
    </div>

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

    @media (max-width: 600px) {
        .settings-content {
            padding: 1.5rem;
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
