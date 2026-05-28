<div class="admin-users index content">
    <h2><?= __('User Management') ?></h2>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= __('ID') ?></th>
                    <th><?= __('Email') ?></th>
                    <th><?= __('Organization') ?></th>
                    <th><?= __('Role') ?></th>
                    <th><?= __('Status') ?></th>
                    <th><?= __('Email Verified') ?></th>
                    <th><?= __('Created') ?></th>
                    <th><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <?php
                // Get primary organization name
                $primaryOrgName = '-';
                if (!empty($user->organizations)) {
                    foreach ($user->organizations as $org) {
                        if ($org->_joinData->is_primary ?? false) {
                            $primaryOrgName = $org->name;
                            break;
                        }
                    }
                    // Fallback to first organization if no primary found
                    if ($primaryOrgName === '-' && !empty($user->organizations[0])) {
                        $primaryOrgName = $user->organizations[0]->name;
                    }
                }
                // Get primary role
                $primaryRole = '-';
                if (!empty($user->organization_users)) {
                    foreach ($user->organization_users as $orgUser) {
                        if ($orgUser->is_primary) {
                            $primaryRole = $orgUser->role;
                            break;
                        }
                    }
                }
                ?>
                <tr>
                    <td><?= $user->id ?></td>
                    <td><?= h($user->email) ?></td>
                    <td><?= h($primaryOrgName) ?></td>
                    <td>
                        <span class="badge badge-<?= $primaryRole ?>">
                            <?= h($primaryRole) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-<?= $user->status ?>">
                            <?= h($user->status) ?>
                        </span>
                    </td>
                    <td>
                        <?= $user->email_verified ? '✓' : '✗' ?>
                    </td>
                    <td><?= $user->created->format('Y-m-d H:i') ?></td>
                    <td class="actions">
                        <?php if ($user->status === 'pending'): ?>
                            <?= $this->Form->postLink(
                                __('Approve'),
                                ['action' => 'approve', $user->id],
                                ['confirm' => __('Approve this user?'), 'class' => 'button-success']
                            ) ?>
                        <?php endif; ?>
                        
                        <?php if ($user->status === 'active'): ?>
                            <?= $this->Form->postLink(
                                __('Deactivate'),
                                ['action' => 'deactivate', $user->id],
                                ['confirm' => __('Deactivate this user?'), 'class' => 'button-danger']
                            ) ?>
                        <?php endif; ?>

                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $user->id],
                            ['confirm' => __('Delete this user and their organization? This cannot be undone!'), 'class' => 'button-danger']
                        ) ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.85em;
    font-weight: 600;
}

.badge-admin { background: #4caf50; color: white; }
.badge-editor { background: #2196f3; color: white; }
.badge-viewer { background: #9e9e9e; color: white; }

.badge-active { background: #4caf50; color: white; }
.badge-pending { background: #ff9800; color: white; }
.badge-inactive { background: #f44336; color: white; }

.button-success {
    background: #4caf50;
    color: white;
    padding: 6px 12px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 0.9em;
}

.button-danger {
    background: #f44336;
    color: white;
    padding: 6px 12px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 0.9em;
}

.actions {
    display: flex;
    gap: 8px;
}
</style>
