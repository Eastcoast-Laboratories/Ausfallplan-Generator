<?php
/**
 * @var \App\View\AppView $this
 * @var array $organizations
 * @var int|null $selectedOrgId
 * @var bool $canSelectOrganization
 */
$this->assign('title', __('Kinder importieren'));
?>

<div class="children import content">
    <h3><?= __('Kinder aus CSV importieren') ?></h3>
    
    <div class="info-box" style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 1rem; margin-bottom: 2rem;">
        <h4 style="margin-top: 0;">ℹ️ <?= __('Hinweise zum Import') ?>:</h4>
        <ul>
            <li><?= __('CSV-Format: Semikolon-separiert (;)') ?></li>
            <li><?= __('Erwartete Spalten: Vorname, Nachname, ..., Geburtstag (DD.MM.YY), ..., i (Integrativ-Status)') ?></li>
            <li><?= __('Das Geschlecht wird automatisch aus dem Vornamen erkannt') ?></li>
            <li><?= __('Bereits vorhandene Kinder werden übersprungen') ?></li>
            <li><?= __('Integrative Kinder: Wert "2" in der letzten Spalte') ?></li>
        </ul>
    </div>

    <?= $this->Form->create(null, ['type' => 'file']) ?>
    <fieldset>
        <legend><?= __('Import-Optionen') ?></legend>
        
        <?php if ($canSelectOrganization ?? false): ?>
        <?= $this->Form->control('organization_id', [
            'type' => 'select',
            'label' => __('Organization'),
            'options' => $organizations,
            'value' => $selectedOrgId,
            'required' => true,
            'empty' => __('-- Select Organization --'),
        ]) ?>
        <?php else: ?>
        <?= $this->Form->hidden('organization_id', ['value' => $selectedOrgId]) ?>
        <p><strong><?= __('Import in Organisation') ?>:</strong> <?= h($organizations[$selectedOrgId] ?? __('Unknown')) ?></p>
        <?php endif; ?>
        
        <?= $this->Form->control('csv_file', [
            'type' => 'file',
            'label' => __('CSV-Datei'),
            'accept' => '.csv',
            'required' => true,
        ]) ?>
    </fieldset>

    <div class="form-actions">
        <?= $this->Form->button(__('Importieren'), [
            'class' => 'button',
            'style' => 'background: #4caf50; color: white;'
        ]) ?>
        <?= $this->Html->link(__('Abbrechen'), ['action' => 'index'], [
            'class' => 'button',
            'style' => 'background: #757575; color: white; margin-left: 1rem;'
        ]) ?>
    </div>
    <?= $this->Form->end() ?>

    <div class="help-section" style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid #e0e0e0;">
        <h4><?= __('Beispiel CSV-Struktur') ?>:</h4>
        <pre style="background: #f5f5f5; padding: 1rem; border-radius: 4px; overflow-x: auto;">
;;Mama;Mama Handy;Mama E-Mail;Papa;Papa Handy;Papa E-Mail;Geburtstag;Geschwister;WhatsApp;Signal;Straße;Plz;i
Valentina;Brühl;Vicky;01577 9116456;vicky@example.com;Richard;;;14.02.19;;Malla;x;Straße 1;24143;2
Amadeus;Kuder;Anna;01590 130 1983;anna@example.com;Ruben;;;05.08.19;Noah;Gruppe;x;Straße 2;24106;1
        </pre>
        
        <h4><?= __('Automatische Geschlechtserkennung') ?>:</h4>
        <p><?= __('Das System erkennt automatisch das Geschlecht anhand typischer deutscher Vornamen:') ?></p>
        <ul style="columns: 3; -webkit-columns: 3; -moz-columns: 3;">
            <li><strong><?= __('Männlich') ?>:</strong> Aaron, Amadeus, Bo, Ezra, Jannis, Levin, Nael, Noah, Timotheus, etc.</li>
            <li><strong><?= __('Weiblich') ?>:</strong> Clara, Johanna, Lene, Tina, Valentina, Zoe, etc.</li>
            <li><?= __('Bei unbekannten Namen: "unknown"') ?></li>
        </ul>
    </div>
</div>

<style>
.info-box {
    border-radius: 4px;
}
.info-box h4 {
    color: #1976d2;
}
.form-actions {
    margin-top: 1.5rem;
}
</style>
