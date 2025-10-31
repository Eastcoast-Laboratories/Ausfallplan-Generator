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
    
    <div class="form-actions">
        <?= $this->Form->button(__('Create Account'), ['class' => 'button-primary']) ?>
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
</style>
