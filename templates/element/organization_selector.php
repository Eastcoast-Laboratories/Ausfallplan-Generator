<?php
/**
 * Organization Selector Element
 * 
 * Reusable organization selection form with:
 * - Create new organization option
 * - Join existing organization option
 * - Role selector (for existing orgs)
 * 
 * Usage:
 * <?= $this->element('organization_selector', [
 *     'organizationsList' => $organizationsList,
 *     'showRoleSelector' => true, // optional, default true
 *     'defaultRole' => 'editor' // optional, default 'editor'
 * ]) ?>
 * 
 * @var \App\View\AppView $this
 * @var array $organizationsList List of organizations [id => name]
 * @var bool $showRoleSelector Whether to show role selector (default: true)
 * @var string $defaultRole Default role selection (default: 'editor')
 */

$showRoleSelector = $showRoleSelector ?? true;
$defaultRole = $defaultRole ?? 'editor';
?>

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

// Role selector (only for existing orgs)
if ($showRoleSelector) {
    echo $this->Form->control('requested_role', [
        'options' => [
            'viewer' => __('Viewer') . ' - ' . __('Read-only access'),
            'editor' => __('Editor') . ' - ' . __('Create and edit schedules'),
            'org_admin' => __('Organization Admin') . ' - ' . __('Full access (approval required)')
        ],
        'label' => __('Desired role in the organization'),
        'default' => $defaultRole,
        'id' => 'role-selector',
        'help' => __('For existing organizations, administrators will review your request')
    ]);
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const orgChoice = document.getElementById('organization-choice');
    const orgNameInput = document.getElementById('organization-name-input');
    const roleSelector = document.getElementById('role-selector');
    const roleContainer = roleSelector ? roleSelector.closest('.input') : null;
    
    function updateFormBasedOnChoice() {
        const choice = orgChoice.value;
        
        if (choice === 'new') {
            // New organization
            orgNameInput.style.display = 'block';
            orgNameInput.required = true;
            orgNameInput.closest('.input').style.display = 'block';
            
            // Hide role selector
            if (roleContainer) {
                roleContainer.style.display = 'none';
                roleSelector.required = false;
                roleSelector.value = 'org_admin'; // Auto-select org_admin
            }
            
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
            if (roleContainer) {
                roleContainer.style.display = 'block';
                roleSelector.required = true;
            }
            
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
/* Disable divider option */
#organization-choice option[value="divider"] {
    color: #999;
    background: #f5f5f5;
    font-weight: bold;
    cursor: default;
}
</style>
