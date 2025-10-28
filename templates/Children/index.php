<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Child> $children
 * @var array $organizations
 * @var int|null $selectedOrgId
 * @var bool $canSelectOrganization
 */
$this->assign('title', __('Children'));
?>
<div class="children index content">
    <h3><?= __('Children') ?></h3>
    
    <?php if ($canSelectOrganization ?? false): ?>
    <!-- Organization Selector -->
    <div class="organization-selector" style="margin-bottom: 2rem;">
        <label for="organization-select"><?= __('Filter by Organization') ?>:</label>
        <select id="organization-select" onchange="window.location.href='<?= $this->Url->build(['action' => 'index']) ?>?organization_id=' + this.value" style="margin-left: 1rem; padding: 0.5rem;">
            <option value=""><?= __('-- Alle Organisationen --') ?></option>
            <?php foreach ($organizations as $orgId => $orgName): ?>
                <option value="<?= $orgId ?>" <?= $selectedOrgId == $orgId ? 'selected' : '' ?>>
                    <?= h($orgName) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    
    <div class="actions" style="display: flex; gap: 1rem; justify-content: flex-end;">
        <?= $this->Html->link('📥 ' . __('CSV Import'), ['action' => 'import'], ['class' => 'button', 'style' => 'background: #2196f3; color: white;']) ?>
        <?= $this->Html->link(__('New Child'), ['action' => 'add'], ['class' => 'button', 'id' => 'new-child-button']) ?>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= __('Name') ?></th>
                    <th><?= __('Last Name') ?></th>
                    <th><?= __('Display Name') ?></th>
                    <?php if (!$selectedOrgId && $canSelectOrganization): ?>
                    <th><?= __('Organization') ?></th>
                    <?php endif; ?>
                    <th><?= __('Gender') ?></th>
                    <th><?= __('Birthdate') ?></th>
                    <th><?= __('Status') ?></th>
                    <th><?= __('Integrative') ?></th>
                    <th><?= __('Sibling Group') ?></th>
                    <th><?= __('Created') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($children as $child): ?>
                <tr>
                    <td>
                        <?= h($child->name) ?>
                        <?php if ($child->sibling_group_id && isset($siblingNames[$child->id])): ?>
                            <?= $this->Html->link(
                                '👨‍👩‍👧',
                                ['controller' => 'SiblingGroups', 'action' => 'view', $child->sibling_group_id],
                                [
                                    'style' => 'background: #fff3cd; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem; margin-left: 0.5rem; text-decoration: none; color: #856404; display: inline-block;',
                                    'title' => 'Geschwister: ' . h($siblingNames[$child->id]),
                                    'escape' => false
                                ]
                            ) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= h($child->last_name) ?></td>
                    <td><strong><?= h($child->display_name ?? ($child->name . ' ' . $child->last_name)) ?></strong></td>
                    <?php if (!$selectedOrgId && $canSelectOrganization): ?>
                    <td><?= h($organizations[$child->organization_id] ?? '-') ?></td>
                    <?php endif; ?>
                    <td style="text-align: center; font-size: 1.2rem;">
                        <?php
                        if ($child->gender === 'male') {
                            echo '<span title="Junge">♂️</span>';
                        } elseif ($child->gender === 'female') {
                            echo '<span title="Mädchen">♀️</span>';
                        } elseif ($child->gender === 'neutral') {
                            echo '<span title="Neutral">⚪</span>';
                        } else {
                            echo '<span title="Unbekannt">❓</span>';
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        if ($child->birthdate) {
                            $birthdate = $child->birthdate instanceof \DateTime ? $child->birthdate : new \DateTime($child->birthdate);
                            echo $birthdate->format('d.m.Y');
                            
                            // Calculate age
                            $now = new \DateTime();
                            $age = $now->diff($birthdate)->y;
                            echo ' <span style="color: #666; font-size: 0.9rem;">(' . $age . ')</span>';
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                    <td><?= $child->is_active ? __('Active') : __('Inactive') ?></td>
                    <td><?= $child->is_integrative ? __('Yes') : __('No') ?></td>
                    <td><?= $child->has('sibling_group') && $child->sibling_group ? h($child->sibling_group->label) : '' ?></td>
                    <td><?= h($child->created) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $child->id]) ?>
                        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $child->id], ['confirm' => __('Are you sure you want to delete # {0}?', $child->id)]) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Auto-focus "New Child" button after saving a child (when success message is present)
document.addEventListener('DOMContentLoaded', function() {
    const successMessage = document.querySelector('.message.success');
    const newChildButton = document.getElementById('new-child-button');
    
    if (successMessage && newChildButton) {
        // Focus the button so user can press Enter to add another child
        newChildButton.focus();
    }
});
</script>
