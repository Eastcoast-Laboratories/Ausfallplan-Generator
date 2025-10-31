<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Organization $organization
 * @var array $organizationsList
 */
$this->assign('title', __('Add Organization'));
?>
<div class="organizations add content">
    <h3><?= __('Organisation erstellen oder beitreten') ?></h3>
    <p><?= __('Erstellen Sie eine neue Organisation oder treten Sie einer bestehenden bei.') ?></p>
    
    <?= $this->Form->create($organization) ?>
    <fieldset>
        <?php
        // Use reusable organization selector element
        echo $this->element('organization_selector', [
            'organizationsList' => $organizationsList,
            'showRoleSelector' => true,
            'defaultRole' => 'editor'
        ]);
        ?>
    </fieldset>
    
    <div class="form-actions">
        <?= $this->Form->button(__('Speichern'), ['class' => 'button-primary']) ?>
        <?= $this->Html->link(__('Abbrechen'), ['action' => 'index'], ['class' => 'button']) ?>
    </div>
    <?= $this->Form->end() ?>
    
    <div class="info-box" style="margin-top: 2rem; padding: 1rem; background: #e3f2fd; border-left: 4px solid #2196f3;">
        <h4 style="margin-top: 0;"><?= __('Hinweis') ?></h4>
        <p style="margin-bottom: 0;">
            <?= __('Wenn Sie eine neue Organisation erstellen, werden Sie automatisch als Administrator hinzugefügt.') ?><br>
            <?= __('Wenn Sie einer bestehenden Organisation beitreten, muss ein Administrator Ihre Anfrage genehmigen.') ?>
        </p>
    </div>
</div>

<style>
.organizations.add {
    max-width: 600px;
    margin: 2rem auto;
    padding: 2rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.organizations.add h3 {
    margin-top: 0;
    color: #333;
}

.organizations.add p {
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
