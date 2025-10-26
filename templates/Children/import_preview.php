<?php
/**
 * @var \App\View\AppView $this
 * @var array $parsedChildren
 * @var array $siblingGroups
 */
$this->assign('title', __('Import-Vorschau'));
?>

<div class="children import-preview content">
    <h3><?= __('Import-Vorschau') ?></h3>
    
    <div class="info-box" style="background: #e8f5e9; border-left: 4px solid #4caf50; padding: 1rem; margin-bottom: 2rem;">
        <h4 style="margin-top: 0;">✅ <?= __('CSV erfolgreich geparst') ?></h4>
        <p><strong><?= count($parsedChildren) ?></strong> <?= __('Kinder wurden erkannt') ?></p>
        <?php if (!empty($siblingGroups)): ?>
            <p><strong><?= count($siblingGroups) ?></strong> <?= __('Geschwistergruppen wurden anhand der Adresse erkannt') ?></p>
        <?php endif; ?>
    </div>

    <?= $this->Form->create(null, ['url' => ['action' => 'importConfirm']]) ?>
    
    <!-- Anonymization Options -->
    <fieldset style="margin-bottom: 2rem; background: #fff3cd; padding: 1.5rem; border-radius: 4px; border: 2px solid #ffc107;">
        <legend style="font-weight: bold; color: #856404;"><?= __('🔒 Anonymisierungs-Optionen') ?></legend>
        <p style="margin-bottom: 1rem;"><?= __('Wählen Sie, wie die Kinder-Namen gespeichert werden sollen:') ?></p>
        
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <label style="display: flex; align-items: start; padding: 1rem; background: white; border-radius: 4px; cursor: pointer; border: 2px solid #e0e0e0;">
                <input type="radio" name="anonymization_mode" value="full" checked style="margin-top: 4px; margin-right: 1rem;">
                <div>
                    <strong><?= __('Voller Name (Vor- und Nachname)') ?></strong>
                    <div style="color: #666; font-size: 0.9rem; margin-top: 0.25rem;">
                        <?= __('Beispiel: Valentina Brühl, Amadeus Kuder') ?>
                    </div>
                </div>
            </label>
            
            <label style="display: flex; align-items: start; padding: 1rem; background: white; border-radius: 4px; cursor: pointer; border: 2px solid #e0e0e0;">
                <input type="radio" name="anonymization_mode" value="first_name" style="margin-top: 4px; margin-right: 1rem;">
                <div>
                    <strong><?= __('Nur Vorname') ?></strong>
                    <div style="color: #666; font-size: 0.9rem; margin-top: 0.25rem;">
                        <?= __('Beispiel: Hans, Andi, Noah') ?>
                    </div>
                </div>
            </label>
            
            <label style="display: flex; align-items: start; padding: 1rem; background: white; border-radius: 4px; cursor: pointer; border: 2px solid #e0e0e0;">
                <input type="radio" name="anonymization_mode" value="initial_animal" style="margin-top: 4px; margin-right: 1rem;">
                <div>
                    <strong><?= __('Anfangsbuchstabe + Tiername') ?></strong>
                    <div style="color: #666; font-size: 0.9rem; margin-top: 0.25rem;">
                        <?= __('Beispiel: V. Bär, A. Fuchs, N. Eule') ?>
                    </div>
                </div>
            </label>
        </div>
    </fieldset>

    <!-- Preview Table -->
    <h4><?= __('Erkannte Kinder:') ?></h4>
    <div style="overflow-x: auto; margin-bottom: 2rem;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f5f5f5;">
                    <th style="padding: 0.75rem; border: 1px solid #ddd; text-align: left;">#</th>
                    <th style="padding: 0.75rem; border: 1px solid #ddd; text-align: left;"><?= __('Vorname') ?></th>
                    <th style="padding: 0.75rem; border: 1px solid #ddd; text-align: left;"><?= __('Nachname') ?></th>
                    <th style="padding: 0.75rem; border: 1px solid #ddd; text-align: left;"><?= __('Geburtstag') ?></th>
                    <th style="padding: 0.75rem; border: 1px solid #ddd; text-align: left;"><?= __('Geschlecht') ?></th>
                    <th style="padding: 0.75rem; border: 1px solid #ddd; text-align: left;"><?= __('PLZ') ?></th>
                    <th style="padding: 0.75rem; border: 1px solid #ddd; text-align: left;"><?= __('Integrativ') ?></th>
                    <th style="padding: 0.75rem; border: 1px solid #ddd; text-align: left;"><?= __('Geschwister') ?></th>
                    <th style="padding: 0.75rem; border: 1px solid #ddd; text-align: left;"><?= __('Tiername') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($parsedChildren as $index => $child): ?>
                <tr style="<?= $child['sibling_group_id'] ? 'background: #e3f2fd;' : '' ?>">
                    <td style="padding: 0.75rem; border: 1px solid #ddd;"><?= $index + 1 ?></td>
                    <td style="padding: 0.75rem; border: 1px solid #ddd;"><strong><?= h($child['first_name']) ?></strong></td>
                    <td style="padding: 0.75rem; border: 1px solid #ddd;"><?= h($child['last_name']) ?></td>
                    <td style="padding: 0.75rem; border: 1px solid #ddd;">
                        <?php if ($child['birth_date']): ?>
                            <?= $child['birth_date']->format('d.m.Y') ?>
                        <?php else: ?>
                            <span style="color: #999;">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 0.75rem; border: 1px solid #ddd;">
                        <?php
                        $genderIcons = ['male' => '♂️', 'female' => '♀️', 'unknown' => '❓'];
                        $genderLabels = ['male' => 'Männlich', 'female' => 'Weiblich', 'unknown' => 'Unbekannt'];
                        ?>
                        <span title="<?= $genderLabels[$child['gender']] ?>">
                            <?= $genderIcons[$child['gender']] ?> <?= __($genderLabels[$child['gender']]) ?>
                        </span>
                    </td>
                    <td style="padding: 0.75rem; border: 1px solid #ddd;"><?= h($child['postal_code']) ?></td>
                    <td style="padding: 0.75rem; border: 1px solid #ddd; text-align: center;">
                        <?= $child['is_integrative'] ? '✅' : '—' ?>
                    </td>
                    <td style="padding: 0.75rem; border: 1px solid #ddd; text-align: center;">
                        <?php if ($child['sibling_group_id']): ?>
                            <span style="background: #2196f3; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem;">
                                👥 Gruppe <?= $child['sibling_group_id'] ?>
                            </span>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td style="padding: 0.75rem; border: 1px solid #ddd;">
                        <span style="color: #666;"><?= h($child['animal_name']) ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Sibling Groups Summary -->
    <?php if (!empty($siblingGroups)): ?>
    <div style="background: #e3f2fd; padding: 1rem; border-radius: 4px; margin-bottom: 2rem;">
        <h4 style="margin-top: 0;">👥 <?= __('Erkannte Geschwistergruppen') ?>:</h4>
        <?php foreach ($siblingGroups as $groupId => $siblings): ?>
            <div style="margin-bottom: 0.5rem;">
                <strong><?= __('Gruppe {0}', $groupId) ?>:</strong>
                <?php
                $names = array_map(function($s) { return $s['first_name']; }, $siblings);
                echo implode(', ', $names);
                ?>
                <span style="color: #666; font-size: 0.9rem;">
                    (<?= h($siblings[0]['last_name']) ?>)
                </span>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Action Buttons -->
    <div class="form-actions" style="display: flex; gap: 1rem; justify-content: space-between; align-items: center;">
        <div>
            <?= $this->Html->link('← ' . __('Zurück'), ['action' => 'import'], [
                'class' => 'button',
                'style' => 'background: #757575; color: white;'
            ]) ?>
        </div>
        <div style="display: flex; gap: 1rem;">
            <?= $this->Form->button('✅ ' . __('Jetzt importieren'), [
                'class' => 'button',
                'style' => 'background: #4caf50; color: white; font-weight: bold; font-size: 1.1rem; padding: 1rem 2rem;'
            ]) ?>
        </div>
    </div>
    <?= $this->Form->end() ?>
</div>

<style>
input[type="radio"] {
    width: 20px;
    height: 20px;
}
label:has(input[type="radio"]:checked) {
    border-color: #4caf50 !important;
    background: #e8f5e9 !important;
}
</style>

<script>
// Preview name changes based on anonymization mode
document.querySelectorAll('input[name="anonymization_mode"]').forEach(radio => {
    radio.addEventListener('change', function() {
        // Could add live preview here if desired
        console.log('Anonymization mode changed to:', this.value);
    });
});
</script>
