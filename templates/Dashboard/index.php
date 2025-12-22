<?php
/**
 * Dashboard Index
 * 
 * @var \App\View\AppView $this
 * @var array $stats
 * @var object $user
 */

$this->assign('title', __('Dashboard'));
?>

<div class="dashboard">
    <div class="welcome-section">
        <h1><?= __('Welcome back, {0}!', h($user->name ?? $user->email)) ?></h1>
        <p><?= __('You are logged in.') ?></p>
    </div>

    <div class="stats-grid">
        <?= $this->Html->link(
            '<div class="stat-card">
                <div class="stat-icon">👶</div>
                <div class="stat-content">
                    <div class="stat-value">' . h($stats['children']) . '</div>
                    <div class="stat-label">' . __('Children') . '</div>
                </div>
            </div>',
            ['controller' => 'Children', 'action' => 'index'],
            ['escape' => false, 'style' => 'text-decoration: none; color: inherit;']
        ) ?>

        <?= $this->Html->link(
            '<div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-content">
                    <div class="stat-value">' . h($stats['schedules']) . '</div>
                    <div class="stat-label">' . __('Total Schedules') . '</div>
                </div>
            </div>',
            ['controller' => 'Schedules', 'action' => 'index'],
            ['escape' => false, 'style' => 'text-decoration: none; color: inherit;']
        ) ?>

        <?= $this->Html->link(
            '<div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <div class="stat-value">' . h($stats['active_schedules']) . '</div>
                    <div class="stat-label">' . __('Active Schedules') . '</div>
                </div>
            </div>',
            ['controller' => 'Schedules', 'action' => 'index'],
            ['escape' => false, 'style' => 'text-decoration: none; color: inherit;']
        ) ?>

        <?= $this->Html->link(
            '<div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-content">
                    <div class="stat-value">' . h($stats['waitlist_entries']) . '</div>
                    <div class="stat-label">' . __('Waitlist Entries') . '</div>
                </div>
            </div>',
            ['controller' => 'Waitlist', 'action' => 'index'],
            ['escape' => false, 'style' => 'text-decoration: none; color: inherit;']
        ) ?>
    </div>

    <div class="quick-actions">
        <h2><?= __('Quick Actions') ?></h2>
        <div class="action-buttons">
            <a href="<?= $this->Url->build(['controller' => 'Children', 'action' => 'add']) ?>" class="action-button">
                <span class="action-icon">➕</span>
                <span class="action-text"><?= __('Add Child') ?></span>
            </a>
            <a href="<?= $this->Url->build(['controller' => 'Schedules', 'action' => 'add']) ?>" class="action-button">
                <span class="action-icon">📅</span>
                <span class="action-text"><?= __('Create Schedule') ?></span>
            </a>
            <a href="<?= $this->Url->build(['controller' => 'Children', 'action' => 'import']) ?>" class="action-button">
                <span class="action-icon">📁</span>
                <span class="action-text"><?= __('Import CSV') ?></span>
            </a>
        </div>
    </div>

    <div class="recent-activity">
        <h2><?= __('Recent Activity') ?></h2>
        <?php if (!empty($recentActivities)): ?>
            <div class="activity-list">
                <?php foreach ($recentActivities as $activity): ?>
                    <div class="activity-item" data-org-id="<?= h($activity['organization_id'] ?? '') ?>">
                        <div class="activity-icon"><?= $activity['icon'] ?></div>
                        <div class="activity-content">
                            <div class="activity-title">
                                <?php if ($activity['type'] === 'child'): ?>
                                    <?= h($activity['title_prefix']) ?>
                                    <?= $this->Html->link(
                                        '<span class="child-name" 
                                              data-encrypted="' . h($activity['name_encrypted'] ?? '') . '"
                                              data-iv="' . h($activity['name_iv'] ?? '') . '"
                                              data-tag="' . h($activity['name_tag'] ?? '') . '">' . h($activity['name']) . '</span>',
                                        $activity['url'],
                                        ['class' => 'activity-link', 'escape' => false]
                                    ) ?>
                                <?php else: ?>
                                    <?= $this->Html->link(
                                        h($activity['title']),
                                        $activity['url'],
                                        ['class' => 'activity-link']
                                    ) ?>
                                <?php endif; ?>
                            </div>
                            <?php if (isset($user) && $user->is_system_admin): ?>
                                <div class="activity-meta">
                                    <?= h($activity['organization']) ?> • 
                                    <?= $activity['time']->timeAgoInWords() ?>
                                </div>
                            <?php else: ?>
                                <div class="activity-meta">
                                    <?= $activity['time']->timeAgoInWords() ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="activity-placeholder">
                <p><?= __('No recent activity to display.') ?></p>
                <p class="placeholder-hint"><?= __('Start by adding children or creating a schedule.') ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .dashboard {
        max-width: 1200px;
    }

    .welcome-section {
        margin-bottom: 2rem;
    }

    .welcome-section h1 {
        font-size: 2rem;
        color: #2c3e50;
        margin-bottom: 0.5rem;
    }

    .welcome-section p {
        color: #7f8c8d;
        font-size: 1.1rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
        margin-bottom: 3rem;
    }

    .stat-card {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    .stat-icon {
        font-size: 3rem;
    }

    .stat-content {
        flex: 1;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: bold;
        color: #2c3e50;
    }

    .stat-label {
        color: #7f8c8d;
        font-size: 0.9rem;
        margin-top: 0.25rem;
    }

    .quick-actions {
        background: white;
        border-radius: 8px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .quick-actions h2 {
        font-size: 1.5rem;
        color: #2c3e50;
        margin-bottom: 1.5rem;
    }

    .action-buttons {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }

    .action-button {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem;
        background: #f8f9fa;
        border: 2px solid #e1e8ed;
        border-radius: 8px;
        text-decoration: none;
        color: #2c3e50;
        transition: all 0.2s;
    }

    .action-button:hover {
        background: #e3f2fd;
        border-color: #3498db;
        transform: translateX(4px);
    }

    .action-icon {
        font-size: 1.5rem;
    }

    .action-text {
        font-weight: 500;
    }

    .recent-activity {
        background: white;
        border-radius: 8px;
        padding: 2rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .recent-activity h2 {
        font-size: 1.5rem;
        color: #2c3e50;
        margin-bottom: 1.5rem;
    }

    .activity-placeholder {
        text-align: center;
        padding: 3rem 1rem;
        color: #95a5a6;
    }

    .activity-placeholder p {
        margin: 0.5rem 0;
    }

    .placeholder-hint {
        font-size: 0.9rem;
        font-style: italic;
    }

    .activity-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .activity-item {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #3498db;
        transition: all 0.2s;
    }

    .activity-item:hover {
        background: #e3f2fd;
        transform: translateX(4px);
    }

    .activity-icon {
        font-size: 1.5rem;
        flex-shrink: 0;
    }

    .activity-content {
        flex: 1;
        min-width: 0;
    }

    .activity-title {
        font-weight: 500;
        color: #2c3e50;
        margin-bottom: 0.25rem;
    }

    .activity-link {
        color: #2c3e50;
        text-decoration: none;
    }

    .activity-link:hover {
        color: #3498db;
        text-decoration: underline;
    }

    .activity-meta {
        font-size: 0.85rem;
        color: #7f8c8d;
    }

    @media (max-width: 600px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .action-buttons {
            grid-template-columns: 1fr;
        }
    }
</style>
