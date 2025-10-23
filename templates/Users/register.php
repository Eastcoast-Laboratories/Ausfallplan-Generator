<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 * @var \Cake\Collection\CollectionInterface|string[] $organizations
 */
?>
<div class="users form content">
    <?= $this->Form->create($user) ?>
    <fieldset>
        <legend><?= __('Register New Account') ?></legend>
        <p><?= __('Create your account to start managing your Kita schedules.') ?></p>
        
        <div class="form-group">
            <?= $this->Form->control('organization_name', [
                'type' => 'text',
                'label' => __('Organization (optional)'),
                'placeholder' => __('Start typing to search...'),
                'required' => false,
                'autofocus' => true,
                'id' => 'organization-input',
                'autocomplete' => 'off',
                'list' => 'organization-list'
            ]) ?>
            <datalist id="organization-list"></datalist>
            <small id="org-hint" style="display:none; color: #27ae60; margin-top: 0.5rem;">
                ✓ <?= __('Existing organization selected') ?>
            </small>
            <small id="org-new" style="display:none; color: #f39c12; margin-top: 0.5rem;">
                ⚠ <?= __('New organization will be created') ?>
            </small>
        </div>
        <?php
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
            echo $this->Form->control('role', [
                'options' => [
                    'viewer' => __('Viewer (Read-only)'),
                    'editor' => __('Editor (Create & Edit)'),
                    'admin' => __('Administrator (Full Access)')
                ],
                'label' => __('Role'),
                'default' => 'viewer',
                'help' => __('Your role can be changed later by an administrator')
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
// Organization autocomplete
document.addEventListener('DOMContentLoaded', function() {
    const orgInput = document.getElementById('organization-input');
    const orgList = document.getElementById('organization-list');
    const hintExisting = document.getElementById('org-hint');
    const hintNew = document.getElementById('org-new');
    let organizations = [];
    
    // Fetch organizations from API
    fetch('/api/organizations/search.json')
        .then(res => res.json())
        .then(data => {
            organizations = data.organizations || [];
            updateDatalist();
        });
    
    function updateDatalist() {
        orgList.innerHTML = '';
        organizations.forEach(org => {
            const option = document.createElement('option');
            option.value = org.name;
            orgList.appendChild(option);
        });
    }
    
    // Check if input matches existing organization
    orgInput.addEventListener('input', function() {
        const value = orgInput.value.trim();
        hintExisting.style.display = 'none';
        hintNew.style.display = 'none';
        
        if (!value) return;
        
        const exists = organizations.some(org => org.name.toLowerCase() === value.toLowerCase());
        
        if (exists) {
            hintExisting.style.display = 'block';
            orgInput.style.borderColor = '#27ae60';
        } else if (value.length > 2) {
            hintNew.style.display = 'block';
            orgInput.style.borderColor = '#f39c12';
        }
    });
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

.autocomplete-container {
    position: relative;
}

.autocomplete-suggestions {
    position: absolute;
    border: 1px solid #ddd;
    border-top: none;
    z-index: 99;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    max-height: 200px;
    overflow-y: auto;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.autocomplete-suggestion {
    padding: 10px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
}

.autocomplete-suggestion:hover {
    background-color: #f0f0f0;
}

.autocomplete-suggestion.existing {
    background-color: #e8f5e9;
}

.autocomplete-suggestion.new {
    background-color: #fff3e0;
}

.autocomplete-suggestion .label {
    font-size: 0.85em;
    color: #666;
    margin-top: 2px;
}

#organization-input.has-suggestions {
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;
}

#organization-input.new-org {
    border-color: #ff9800;
    background-color: #fff8e1;
}
</style>

<script>
(function() {
    const input = document.getElementById('organization-input');
    if (!input) return;
    
    let currentFocus = -1;
    let suggestionsDiv = null;
    
    function createSuggestionsDiv() {
        const container = input.parentElement;
        if (!suggestionsDiv) {
            suggestionsDiv = document.createElement('div');
            suggestionsDiv.className = 'autocomplete-suggestions';
            suggestionsDiv.style.display = 'none';
            container.appendChild(container);
            container.style.position = 'relative';
            container.appendChild(suggestionsDiv);
        }
        return suggestionsDiv;
    }
    
    function closeSuggestions() {
        if (suggestionsDiv) {
            suggestionsDiv.style.display = 'none';
            suggestionsDiv.innerHTML = '';
        }
        input.classList.remove('has-suggestions');
        currentFocus = -1;
    }
    
    function showSuggestions(organizations, query) {
        closeSuggestions();
        
        const div = createSuggestionsDiv();
        
        if (organizations.length === 0) {
            // No existing organizations found
            div.innerHTML = `
                <div class="autocomplete-suggestion new">
                    <strong>${query}</strong>
                    <div class="label">✨ <?= __('Create new organization') ?></div>
                </div>
            `;
            input.classList.add('new-org');
        } else {
            // Show existing organizations
            organizations.forEach((org, index) => {
                const item = document.createElement('div');
                item.className = 'autocomplete-suggestion existing';
                item.innerHTML = `
                    <strong>${org.name}</strong>
                    <div class="label">✓ <?= __('Join existing organization') ?></div>
                `;
                item.addEventListener('click', () => {
                    input.value = org.name;
                    input.classList.remove('new-org');
                    closeSuggestions();
                });
                div.appendChild(item);
            });
            
            // Add "Create new" option at the end
            const newItem = document.createElement('div');
            newItem.className = 'autocomplete-suggestion new';
            newItem.innerHTML = `
                <strong>${query}</strong>
                <div class="label">✨ <?= __('Create new organization') ?></div>
            `;
            newItem.addEventListener('click', () => {
                input.value = query;
                input.classList.add('new-org');
                closeSuggestions();
            });
            div.appendChild(newItem);
            
            input.classList.remove('new-org');
        }
        
        div.style.display = 'block';
        input.classList.add('has-suggestions');
    }
    
    let debounceTimer = null;
    input.addEventListener('input', function() {
        const query = this.value.trim();
        
        if (query.length < 2) {
            closeSuggestions();
            input.classList.remove('new-org');
            return;
        }
        
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            fetch(`/api/organizations/search?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    showSuggestions(data.organizations || [], query);
                })
                .catch(error => {
                    console.error('Autocomplete error:', error);
                });
        }, 300);
    });
    
    // Close suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target !== input) {
            closeSuggestions();
        }
    });
})();
</script>
