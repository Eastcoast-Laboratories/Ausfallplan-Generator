<?php
/**
 * @var \App\View\AppView $this
 * @var int $totalOrganizations
 * @var int $activeOrganizations
 * @var int $totalUsers
 * @var int $activeUsers
 * @var int $systemAdmins
 * @var int $totalChildren
 * @var int $activeChildren
 * @var int $integrativeChildren
 * @var int $totalSchedules
 * @var int $activeSchedules
 * @var int $totalSiblingGroups
 * @var array $recentChildren
 * @var array $recentSchedules
 * @var array $orgActivity
 */
$this->assign('title', __('System Admin Dashboard'));
?>

<div class="admin-dashboard content">
    <h3>🎛️ <?= __('System Administration Dashboard') ?></h3>
    
    <!-- Statistics Cards -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        
        <!-- Organizations Card -->
        <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="font-size: 0.9rem; opacity: 0.9;">🏢 <?= __('Organizations') ?></div>
            <div style="font-size: 2.5rem; font-weight: bold; margin: 0.5rem 0;"><?= h($totalOrganizations) ?></div>
            <div style="font-size: 0.85rem; opacity: 0.8;">
                <?= h($activeOrganizations) ?> <?= __('Active') ?> • 
                <?= h($totalOrganizations - $activeOrganizations) ?> <?= __('Inactive') ?>
            </div>
        </div>
        
        <!-- Users Card -->
        <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="font-size: 0.9rem; opacity: 0.9;">👥 <?= __('Users') ?></div>
            <div style="font-size: 2.5rem; font-weight: bold; margin: 0.5rem 0;"><?= h($totalUsers) ?></div>
            <div style="font-size: 0.85rem; opacity: 0.8;">
                <?= h($activeUsers) ?> <?= __('Active') ?> • 
                <?= h($systemAdmins) ?> <?= __('System Admins') ?>
            </div>
        </div>
        
        <!-- Children Card -->
        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="font-size: 0.9rem; opacity: 0.9;">👶 <?= __('Children') ?></div>
            <div style="font-size: 2.5rem; font-weight: bold; margin: 0.5rem 0;"><?= h($totalChildren) ?></div>
            <div style="font-size: 0.85rem; opacity: 0.8;">
                <?= h($activeChildren) ?> <?= __('Active') ?> • 
                <?= h($integrativeChildren) ?> <?= __('Integrative') ?>
            </div>
        </div>
        
        <!-- Schedules Card -->
        <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="font-size: 0.9rem; opacity: 0.9;">📅 <?= __('Schedules') ?></div>
            <div style="font-size: 2.5rem; font-weight: bold; margin: 0.5rem 0;"><?= h($totalSchedules) ?></div>
            <div style="font-size: 0.85rem; opacity: 0.8;">
                <?= h($activeSchedules) ?> <?= __('Active') ?> • 
                <?= h($totalSiblingGroups) ?> <?= __('Sibling Groups') ?>
            </div>
        </div>
        
    </div>
    
    <!-- Organization Activity Table -->
    <div class="organization-activity" style="margin-bottom: 2rem;">
        <h4><?= __('Organization Activity Overview') ?></h4>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th><?= __('Organization') ?></th>
                        <th><?= __('Status') ?></th>
                        <th><?= __('Children') ?></th>
                        <th><?= __('Schedules') ?></th>
                        <th><?= __('Contact') ?></th>
                        <th><?= __('Created') ?></th>
                        <th class="actions"><?= __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orgActivity as $activity): ?>
                    <tr>
                        <td><strong><?= h($activity['organization']->name) ?></strong></td>
                        <td>
                            <?php if ($activity['organization']->is_active): ?>
                                <span style="color: green;">● <?= __('Active') ?></span>
                            <?php else: ?>
                                <span style="color: red;">● <?= __('Inactive') ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <strong><?= h($activity['children_count']) ?></strong>
                        </td>
                        <td style="text-align: center;">
                            <strong><?= h($activity['schedules_count']) ?></strong>
                        </td>
                        <td><?= h($activity['organization']->contact_email ?? '-') ?></td>
                        <td style="white-space: nowrap;">
                            <?= $activity['organization']->created ? $activity['organization']->created->format('d.m.Y') : '-' ?>
                        </td>
                        <td class="actions">
                            <?= $this->Html->link(__('View'), ['controller' => 'Organizations', 'action' => 'view', $activity['organization']->id]) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Recent Activity Section -->
    <div class="recent-activity" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        
        <!-- Recent Children -->
        <div>
            <h4><?= __('Recently Added Children') ?></h4>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th><?= __('Name') ?></th>
                            <th><?= __('Organization') ?></th>
                            <th><?= __('Added') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentChildren as $child): ?>
                        <tr>
                            <td><?= h($child->name) ?> <?= h($child->last_name ?? '') ?></td>
                            <td><?= h($child->organization->name ?? '-') ?></td>
                            <td style="white-space: nowrap;">
                                <?= $child->created ? $child->created->format('d.m.Y H:i') : '-' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Recent Schedules -->
        <div>
            <h4><?= __('Recently Created Schedules') ?></h4>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th><?= __('Title') ?></th>
                            <th><?= __('Organization') ?></th>
                            <th><?= __('Created') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentSchedules as $schedule): ?>
                        <tr>
                            <td><?= h($schedule->title ?? '-') ?></td>
                            <td><?= h($schedule->organization->name ?? '-') ?></td>
                            <td style="white-space: nowrap;">
                                <?= $schedule->created ? $schedule->created->format('d.m.Y H:i') : '-' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
    
</div>

<style>
.stat-card {
    transition: transform 0.2s;
}
.stat-card:hover {
    transform: translateY(-4px);
}
@media (max-width: 768px) {
    .recent-activity {
        grid-template-columns: 1fr;
    }
}
</style>
