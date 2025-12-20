<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Schedule> $schedules
 * @var array $missingSiblingsPerSchedule
 */
$this->assign('title', __('Schedules'));
?>

<style>
    .schedules-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
        margin-top: 1rem;
    }
    
    .schedule-card {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 1.5rem;
        background: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        transition: box-shadow 0.2s ease;
    }
    
    .schedule-card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .schedule-card.active {
        border-left: 4px solid #4caf50;
        background: #e8f5e9;
    }
    
    .card-boxes {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    
    .card-box {
        flex: 1;
        min-width: 200px;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 4px;
        border: 1px solid #e0e0e0;
    }
    
    .card-box h4 {
        margin: 0 0 0.5rem 0;
        font-size: 0.9rem;
        color: #666;
        font-weight: normal;
    }
    
    .card-box .value {
        font-size: 1.1rem;
        font-weight: bold;
        color: #333;
    }
    
    .card-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 1rem;
    }
    
    @media (max-width: 768px) {
        .card-boxes {
            flex-direction: column;
        }
        
        .card-box {
            min-width: 100%;
        }
    }
</style>

<div class="schedules index content">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3 style="margin: 0;"><?= __('Schedules') ?></h3>
        <?= $this->Html->link(__('New Schedule'), ['action' => 'add'], ['class' => 'button']) ?>
    </div>
    
    <?php if ($hasMultipleOrgs || ($user && $user->is_system_admin)): ?>
        <div style="margin-bottom: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 4px;">
            <form method="get" action="<?= $this->Url->build(['action' => 'index']) ?>" style="display: flex; align-items: center; gap: 1rem;">
                <label for="organization-filter" style="margin: 0; font-weight: bold;">
                    <?= __('Organization') ?>:
                </label>
                <select name="organization_id" id="organization-filter" onchange="this.form.submit()" style="flex: 1; max-width: 300px;">
                    <option value="all"><?= __('All Organizations') ?></option>
                    <?php foreach ($userOrgs as $org): ?>
                        <option value="<?= $org->id ?>" <?= $selectedOrgId == $org->id ? 'selected' : '' ?>>
                            <?= h($org->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($missingSiblingsPerSchedule)): ?>
        <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 1rem; margin-bottom: 1rem; border-radius: 4px;">
            <strong>⚠️ <?= __('Warning') ?>:</strong> 
            <?= __('Siblings in different schedules detected') ?>:
            <?php foreach ($missingSiblingsPerSchedule as $scheduleId => $missingSiblings): ?>
                <div style="margin-top: 0.5rem;">
                    <strong>Schedule #<?= $scheduleId ?>:</strong>
                    <ul style="margin: 0.5rem 0 0 1.5rem;">
                        <?php foreach ($missingSiblings as $missing): ?>
                            <li>
                                <strong><?= $this->Html->link(h($missing['name']), '/schedules/manage-children/' . $missing['schedule_id']) ?></strong> 
                                (<?= __('Sibling of') ?> <?= h($missing['sibling_of']) ?>)
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="schedules-grid">
        <?php foreach ($schedules as $schedule): 
            $isActive = isset($activeScheduleId) && $activeScheduleId == $schedule->id;
        ?>
        <div class="schedule-card <?= $isActive ? 'active' : '' ?>" data-schedule-id="<?= $schedule->id ?>">
            <!-- First row: 3 boxes -->
            <div class="card-boxes">
                <!-- Box 1: Title, User, Organization -->
                <div class="card-box">
                    <h4><?= __('Title') ?></h4>
                    <div class="value">
                        <?= h($schedule->title) ?>
                        <?php if ($isActive): ?>
                            <span style="background: #4caf50; color: white; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.75rem; margin-left: 0.5rem; display: inline-block;">
                                ⭐ <?= __('Active') ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if (isset($user) && $user->is_system_admin): ?>
                        <div style="margin-top: 0.5rem; font-size: 0.9rem; color: #666;">
                            <strong><?= __('User') ?>:</strong> <?= h($schedule->user->email ?? $schedule->organization->name ?? '-') ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($hasMultipleOrgs || (isset($user) && $user->is_system_admin)): ?>
                        <div style="margin-top: 0.5rem; font-size: 0.9rem;">
                            <strong><?= __('Organization') ?>:</strong>
                            <?php if ($schedule->has('organization') && isset($schedule->organization->id)): ?>
                                <?= $this->Html->link(
                                    h($schedule->organization->name), 
                                    '/admin/organizations/view/' . $schedule->organization->id
                                ) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Box 2: Days, Children, Max. per Day -->
                <div class="card-box">
                    <h4><?= __('Days') ?></h4>
                    <div class="value"><?= h($schedule->days_count) ?></div>
                    <div style="margin-top: 0.5rem;">
                        <strong><?= __('Children') ?>:</strong> 
                        <?php if(isset($childrenCounts[$schedule->id]) and $childrenCounts[$schedule->id] > 0): ?>
                            <?= h($childrenCounts[$schedule->id]) ?> <span style="color: #4caf50;">✓</span>
                        <?php else: ?>
                            <span style="color: #999;">0</span>
                        <?php endif; ?>
                    </div>
                    <div style="margin-top: 0.5rem;">
                        <strong><?= __('Max Children per Day') ?>:</strong> <?= h($schedule->capacity_per_day) ?>
                    </div>
                </div>
                
                <!-- Box 3: Actions -->
                <div class="card-box">
                    <h4><?= __('Actions') ?></h4>
                    <div class="card-actions">
                        <?= $this->Html->link(__('Generate List'), ['action' => 'generateReport', $schedule->id], ['class' => 'button', 'style' => 'background: #2196F3; color: white;']) ?>
                        <?php if (!($isViewer ?? false)): ?>
                            <?= $this->Html->link(__('Manage Children'), '/schedules/manage-children/' . $schedule->id, ['class' => 'button']) ?>
                            <?= $this->Html->link(__('Edit'), ['action' => 'edit', $schedule->id], ['class' => 'button']) ?>
                            <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $schedule->id], ['confirm' => __('Are you sure you want to delete # {0}?', $schedule->id), 'class' => 'button']) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const scheduleCards = document.querySelectorAll('.schedule-card');
    
    scheduleCards.forEach(card => {
        card.addEventListener('click', function(e) {
            // Don't trigger if clicking on action buttons
            if (e.target.closest('.card-actions') || e.target.closest('a') || e.target.closest('form') || e.target.closest('button')) {
                return;
            }
            
            const scheduleId = this.dataset.scheduleId;
            
            // Send AJAX request to set active schedule
            fetch('<?= $this->Url->build(['action' => 'setActive']) ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?= $this->request->getAttribute('csrfToken') ?>'
                },
                body: JSON.stringify({
                    schedule_id: scheduleId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload page to show updated highlighting
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error setting active schedule:', error);
            });
        });
    });
});
</script>
