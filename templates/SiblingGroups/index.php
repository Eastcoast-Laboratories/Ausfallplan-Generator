<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\SiblingGroup> $siblingGroups
 */
$this->assign('title', __('Sibling Groups'));
?>
<div class="sibling-groups index content">
    <h3><?= __('Sibling Groups') ?></h3>
    <div class="actions">
        <?= $this->Html->link(__('New Sibling Group'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    </div>
    
    <?php if ($hasMultipleOrgs || ($this->request->getAttribute('identity') && $this->request->getAttribute('identity')->is_system_admin)): ?>
        <div style="margin-bottom: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 4px;">
            <form method="get" action="<?= $this->Url->build(['action' => 'index']) ?>" style="display: flex; align-items: center; gap: 1rem;">
                <label for="organization-filter" style="margin: 0; font-weight: bold;">
                    <?= __('Organization') ?>:
                </label>
                <select name="organization_id" id="organization-filter" onchange="this.form.submit()" style="flex: 1; max-width: 300px;">
                    <option value=""><?= __('Alle Organisationen') ?></option>
                    <?php foreach ($userOrgs as $org): ?>
                        <option value="<?= $org->id ?>" <?= $selectedOrgId == $org->id ? 'selected' : '' ?>>
                            <?= h($org->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    <?php endif; ?>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= __('Name') ?></th>
                    <th><?= __('Children') ?></th>
                    <th><?= __('Created') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($siblingGroups as $siblingGroup): ?>
                <?php 
                    $isError = isset($errorGroups) && in_array($siblingGroup->id, $errorGroups);
                    $rowStyle = $isError ? 'background: #ffe5e5; border-left: 4px solid #d32f2f;' : '';
                ?>
                <tr style="<?= $rowStyle ?>">
                    <td>
                        <?= h($siblingGroup->label) ?>
                        <?php if ($isError): ?>
                            <span style="color: #d32f2f; font-weight: bold; margin-left: 0.5rem;" title="<?= __('Diese Gruppe hat nur 1 Kind - das ist ein Datenfehler!') ?>">
                                ⚠️ FEHLER
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= count($siblingGroup->children) ?> <?= __('Children') ?>
                        <?php if ($isError): ?>
                            <span style="color: #d32f2f; font-weight: bold;">
                                (zu wenig!)
                            </span>
                        <?php endif; ?>
                    </td>
                    <td><?= h($siblingGroup->created) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $siblingGroup->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $siblingGroup->id]) ?>
                        <?php if (count($siblingGroup->children) == 0): ?>
                            <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $siblingGroup->id], ['confirm' => __('Are you sure you want to delete this sibling group?'), 'class' => 'button-delete']) ?>
                        <?php else: ?>
                            <span style="color: #999;" title="<?= __('Cannot delete group with children') ?>"><?= __('Delete') ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
