<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Schedule $schedule
 */
$this->assign('title', __('Edit Schedule'));
?>
<div class="schedules form content">
    <?= $this->Form->create($schedule) ?>
    <fieldset>
        <legend><?= __('Edit Schedule') ?></legend>
        <?php
            // Show organization (read-only) if user has multiple orgs or is system_admin
            $identity = $this->request->getAttribute('identity');
            $userOrgs = $this->request->getAttribute('userOrgs') ?? [];
            $hasMultipleOrgs = count($userOrgs) > 1;
            
            if ($hasMultipleOrgs || ($identity && $identity->is_system_admin)) {
                if ($schedule->has('organization') && $schedule->organization) {
                    echo '<div class="input text"><label>' . __('Organization') . '</label>';
                    echo '<div style="padding: 0.5rem; background: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;">';
                    echo h($schedule->organization->name);
                    echo '</div></div>';
                }
            }
            
            echo $this->Form->control('title', ['required' => true]);
            echo $this->Form->control('starts_on', ['type' => 'date', 'required' => true]);
            echo $this->Form->control('ends_on', [
                'type' => 'date',
                'required' => false,
                'empty' => true,
                'help' => __('Leave empty for schedules that never end')
            ]);
            echo $this->Form->control('capacity_per_day', [
                'label' => __('Max Children per Day'),
                'type' => 'number',
                'min' => 1,
                'help' => __('Maximum number of children that can be assigned per day')
            ]);
            echo $this->Form->control('days_count', [
                'label' => __('Number of Days'),
                'type' => 'number',
                'min' => 1,
                'help' => __('Number of days for the schedule (default: number of assigned children)')
            ]);
            echo $this->Form->control('state', [
                'options' => ['draft' => __('Draft'), 'final' => __('Final')],
            ]);
        ?>
    </fieldset>
    
    <details id="animal-names-section" <?= isset($_GET['shuffled']) ? 'open' : '' ?>>
        <summary style="cursor: pointer; padding: 1rem; background: #f5f5f5; border: 1px solid #ddd; border-radius: 4px; margin: 1rem 0; font-weight: bold;">
            <span class="toggle-icon">▶</span> <?= __('Day Names (Animals)') ?>
        </summary>
        
        <fieldset style="margin-top: 1rem;">
            <p class="help"><?= __('These animal names will be used as day labels in reports. Only shows as many names as days in the schedule.') ?></p>
            
            <div style="margin-bottom: 1rem;">
                <button type="button" id="shuffle-animal-names-btn" class="button button-outline">
                    <?= __('🎲 Shuffle Names') ?>
                </button>
            </div>
            
            <div id="animal-names-editor">
                <?php if ($animalNamesDE || $animalNamesEN): ?>
                    <?php 
                    $daysCount = $schedule->days_count ?? 26;
                    $lettersDE = $animalNamesDE ? array_keys($animalNamesDE) : [];
                    $lettersEN = $animalNamesEN ? array_keys($animalNamesEN) : [];
                    
                    for ($day = 0; $day < $daysCount; $day++):
                        $letterIndex = $day % 26;
                        $animalIndex = (int)floor($day / 26);
                        
                        $letterDE = $lettersDE[$letterIndex] ?? 'A';
                        $letterEN = $lettersEN[$letterIndex] ?? 'A';
                        
                        $animalsDE = $animalNamesDE[$letterDE] ?? [];
                        $animalsEN = $animalNamesEN[$letterEN] ?? [];
                        
                        $animalNameDE = $animalsDE[$animalIndex] ?? ($animalsDE[0] ?? '');
                        $animalNameEN = $animalsEN[$animalIndex] ?? ($animalsEN[0] ?? '');
                    ?>
                        <div class="input text" style="border-left: 3px solid #2196f3; padding-left: 1rem; margin-bottom: 1.5rem;">
                            <label style="font-weight: bold; color: #333;"><?= __('Day {0}', $day + 1) ?> (<?= h($letterDE) ?>)</label>
                            
                            <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                                <div style="flex: 1;">
                                    <label style="font-size: 0.85rem; color: #666;">🇩🇪 Deutsch:</label>
                                    <input 
                                        type="text" 
                                        class="animal-names-input" 
                                        data-day="<?= $day ?>"
                                        data-letter="<?= h($letterDE) ?>"
                                        data-animal-index="<?= $animalIndex ?>"
                                        data-locale="de"
                                        value="<?= h($animalNameDE) ?>"
                                        placeholder="<?= __('Enter animal name') ?>"
                                        style="width: 100%;"
                                    />
                                </div>
                                <div style="flex: 1;">
                                    <label style="font-size: 0.85rem; color: #666;">🇬🇧 English:</label>
                                    <input 
                                        type="text" 
                                        class="animal-names-input" 
                                        data-day="<?= $day ?>"
                                        data-letter="<?= h($letterEN) ?>"
                                        data-animal-index="<?= $animalIndex ?>"
                                        data-locale="en"
                                        value="<?= h($animalNameEN) ?>"
                                        placeholder="<?= __('Enter animal name') ?>"
                                        style="width: 100%;"
                                    />
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                <?php else: ?>
                    <p><?= __('No animal names sequence generated yet. Click "Shuffle Names" to generate.') ?></p>
                <?php endif; ?>
            </div>
            
            <input type="hidden" id="animal-names-json" name="animal_names_json" value="" />
        </fieldset>
    </details>
    
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const shuffleBtn = document.getElementById('shuffle-animal-names-btn');
    const form = document.querySelector('form');
    const hiddenInput = document.getElementById('animal-names-json');
    
    // Shuffle button click
    if (shuffleBtn) {
        shuffleBtn.addEventListener('click', async function() {
            if (!confirm('<?= __('This will generate a new random sequence of animal names. Continue?') ?>')) {
                return;
            }
            
            try {
                const response = await fetch('<?= $this->Url->build(['action' => 'shuffleAnimalNames', $schedule->id]) ?>', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': '<?= $this->request->getAttribute('csrfToken') ?>'
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.animalNames) {
                        // Reload page with shuffled parameter to auto-open details
                        window.location.href = window.location.pathname + '/' + <?= $schedule->id ?> + '?shuffled=1';
                    }
                } else {
                    alert('<?= __('Failed to shuffle animal names.') ?>');
                }
            } catch (error) {
                console.error('Shuffle error:', error);
                alert('<?= __('An error occurred while shuffling.') ?>');
            }
        });
    }
    
    // Before form submit, collect all animal names into hidden field (both locales)
    if (form) {
        form.addEventListener('submit', function() {
            const animalNamesData = {
                de: {},
                en: {}
            };
            const inputs = document.querySelectorAll('.animal-names-input');
            
            inputs.forEach(input => {
                const letter = input.dataset.letter;
                const animalIndex = parseInt(input.dataset.animalIndex) || 0;
                const locale = input.dataset.locale || 'de';
                const value = input.value.trim();
                
                if (value) {
                    if (!animalNamesData[locale][letter]) {
                        animalNamesData[locale][letter] = [];
                    }
                    // Ensure array has enough slots
                    while (animalNamesData[locale][letter].length <= animalIndex) {
                        animalNamesData[locale][letter].push('');
                    }
                    animalNamesData[locale][letter][animalIndex] = value;
                }
            });
            
            hiddenInput.value = JSON.stringify(animalNamesData);
        });
    }
    
    function updateAnimalNamesInputs(animalNames) {
        const inputs = document.querySelectorAll('.animal-names-input');
        inputs.forEach(input => {
            const letter = input.dataset.letter;
            const animalIndex = parseInt(input.dataset.animalIndex) || 0;
            if (animalNames[letter] && animalNames[letter][animalIndex]) {
                input.value = animalNames[letter][animalIndex];
            }
        });
    }
    
    // Toggle summary icon on open/close
    const detailsElement = document.getElementById('animal-names-section');
    if (detailsElement) {
        detailsElement.addEventListener('toggle', function() {
            const icon = this.querySelector('.toggle-icon');
            if (icon) {
                icon.textContent = this.open ? '▼' : '▶';
            }
        });
    }
});
</script>
