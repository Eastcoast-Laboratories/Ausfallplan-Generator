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
            // Organization selector
            echo $this->Form->control('organization_choice', [
                'type' => 'select',
                'options' => [
                    'new' => '➕ ' . __('Neue Organisation anlegen'),
                    'divider' => '─────────────────────',
                ] + $organizationsList,
                'empty' => false,
                'label' => __('Organization'),
                'id' => 'organization-choice',
                'required' => true
            ]);
            
            // Organization name input (only for new orgs)
            echo $this->Form->control('organization_name', [
                'type' => 'text',
                'label' => __('Name der neuen Organisation'),
                'id' => 'organization-name-input',
                'required' => false,
                'style' => 'display:none;'
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
                    'viewer' => __('Betrachter') . ' - ' . __('Nur Lesezugriff'),
                    'editor' => __('Redakteur') . ' - ' . __('Pläne erstellen und bearbeiten'),
                    'org_admin' => __('Organisations-Admin') . ' - ' . __('Voller Zugriff (Genehmigung erforderlich)')
                ],
                'label' => __('Gewünschte Rolle in der Organisation'),
                'default' => 'editor',
                'id' => 'role-selector',
                'help' => __('Bei einer existierenden Organisation prüfen Administratoren Ihre Anfrage')
            ]);
        ?>
    </fieldset>
    
    <div class="form-actions">
        <?= $this->Form->button(__('Create Account'), ['class' => 'button-primary']) ?>
        <?= $this->Html->link(__('Already have an account? Login'), ['action' => 'login'], ['class' => 'button']) ?>
    </div>
    <?= $this->Form->end() ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const orgChoice = document.getElementById('organization-choice');
    const orgNameInput = document.getElementById('organization-name-input');
    const roleSelector = document.getElementById('role-selector');
    const roleContainer = roleSelector.closest('.input');
    
    function updateFormBasedOnChoice() {
        const choice = orgChoice.value;
        
        if (choice === 'new') {
            // New organization
            orgNameInput.style.display = 'block';
            orgNameInput.required = true;
            orgNameInput.closest('.input').style.display = 'block';
            
            // Hide role selector
            roleContainer.style.display = 'none';
            roleSelector.required = false;
            roleSelector.value = 'org_admin'; // Auto-select org_admin
            
        } else if (choice === 'divider') {
            // Divider selected - reset to "new"
            orgChoice.value = 'new';
            updateFormBasedOnChoice();
            
        } else {
            // Existing organization
            orgNameInput.style.display = 'none';
            orgNameInput.required = false;
            orgNameInput.closest('.input').style.display = 'none';
            orgNameInput.value = ''; // Clear the name input
            
            // Show role selector
            roleContainer.style.display = 'block';
            roleSelector.required = true;
            
            // Set organization_name to the selected org's name for controller
            orgNameInput.value = orgChoice.options[orgChoice.selectedIndex].text;
        }
    }
    
    // Initial setup
    updateFormBasedOnChoice();
    
    // Listen for changes
    orgChoice.addEventListener('change', updateFormBasedOnChoice);
});
</script>

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

/* Disable divider option */
#organization-choice option[value="divider"] {
    color: #999;
    background: #f5f5f5;
    font-weight: bold;
    cursor: default;
}
</style>
