<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Schedule> $schedules
 * @var \App\Model\Entity\Schedule|null $selectedSchedule
 * @var iterable<\App\Model\Entity\WaitlistEntry> $waitlistEntries
 * @var iterable<\App\Model\Entity\Child> $availableChildren
 */
$this->assign('title', __('Waitlist'));
?>

<!-- Include Sortable.js for drag & drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<div class="waitlist index content">
    <h3><?= __('Waitlist') ?></h3>
    
    <!-- Schedule Selector -->
    <div class="schedule-selector" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <label for="schedule-select"><?= __('Select Schedule') ?>:</label>
            <select id="schedule-select" onchange="window.location.href='<?= $this->Url->build(['action' => 'index']) ?>?schedule_id=' + this.value" style="margin-left: 1rem; padding: 0.5rem;">
                <option value=""><?= __('-- Select Schedule --') ?></option>
                <?php foreach ($schedules as $schedule): ?>
                    <option value="<?= $schedule->id ?>" <?= $selectedSchedule && $selectedSchedule->id == $schedule->id ? 'selected' : '' ?>>
                        <?= h($schedule->title) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($selectedSchedule && !empty($availableChildren)): ?>
            <?= $this->Form->postLink(
                '+ ' . __('Add All Children'),
                ['action' => 'add-all', $selectedSchedule->id],
                [
                    'class' => 'button',
                    'style' => 'background: #4caf50; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; font-weight: bold;'
                ]
            ) ?>
        <?php endif; ?>
    </div>
    
    <?php if ($selectedSchedule): ?>
    <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        
        <!-- Available Children -->
        <div class="available-children">
            <h4><?= __('Available Children') ?></h4>
            <div style="background: #f5f7fa; padding: 1rem; border-radius: 8px; min-height: 300px;">
                <?php if (!empty($availableChildren) && (is_countable($availableChildren) ? count($availableChildren) : $availableChildren->count()) > 0): ?>
                    <?php foreach ($availableChildren as $child): ?>
                        <div style="background: white; padding: 1rem; margin-bottom: 0.5rem; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong><?= h($child->name) ?></strong>
                                <?php if ($child->is_integrative): ?>
                                    <span style="background: #e3f2fd; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem; margin-left: 0.5rem;">
                                        <?= __('Integrative') ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?= $this->Form->postLink(
                                '+ ' . __('Add'),
                                ['action' => 'add'],
                                [
                                    'data' => [
                                        'schedule_id' => $selectedSchedule->id,
                                        'child_id' => $child->id,
                                    ],
                                    'class' => 'button button-small',
                                    'style' => 'background: #4caf50; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;'
                                ]
                            ) ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #666; text-align: center; padding: 2rem;">
                        <?= __('All children are on the waitlist.') ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Waitlist -->
        <div class="waitlist-children">
            <h4><?= __('Children on Waitlist') ?> <span style="font-size: 0.9rem; color: #666;">(<?= __('Drag to reorder') ?>)</span></h4>
            <div id="waitlist-sortable" style="background: #fff3e0; padding: 1rem; border-radius: 8px; min-height: 300px;">
                <?php if (!empty($waitlistEntries) && (is_countable($waitlistEntries) ? count($waitlistEntries) : $waitlistEntries->count()) > 0): ?>
                    <?php foreach ($waitlistEntries as $entry): ?>
                        <div class="waitlist-item" data-id="<?= $entry->id ?>" style="background: white; padding: 1rem; margin-bottom: 0.5rem; border-radius: 4px; cursor: move; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid #ff9800;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <span style="background: #ff9800; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                    <?= $entry->priority ?>
                                </span>
                                <div>
                                    <strong><?= h($entry->child->name) ?></strong>
                                    <?php if ($entry->child->is_integrative): ?>
                                        <span style="background: #e3f2fd; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem; margin-left: 0.5rem;">
                                            <?= __('Integrative') ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?= $this->Form->postLink(
                                '✕',
                                ['action' => 'delete', $entry->id],
                                [
                                    'class' => 'button button-small',
                                    'style' => 'background: #f44336; color: white; padding: 0.5rem 0.75rem; text-decoration: none; border-radius: 4px;'
                                ]
                            ) ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #666; text-align: center; padding: 2rem;">
                        <?= __('No children on waitlist.') ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
    <?php else: ?>
        <div style="background: #fff3cd; padding: 2rem; border-radius: 8px; text-align: center;">
            <p><?= __('Please select a schedule to manage the waitlist.') ?></p>
            <?= $this->Html->link(__('Create Schedule'), ['controller' => 'Schedules', 'action' => 'add'], ['class' => 'button']) ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($selectedSchedule && $waitlistEntries->count() > 0): ?>
<script>
// Initialize Sortable.js for drag & drop
const el = document.getElementById('waitlist-sortable');
const sortable = Sortable.create(el, {
    animation: 150,
    ghostClass: 'sortable-ghost',
    handle: '.waitlist-item',
    onEnd: function (evt) {
        // Get new order
        const items = el.querySelectorAll('.waitlist-item');
        const order = Array.from(items).map(item => item.dataset.id);
        
        // Send AJAX request to update order
        fetch('<?= $this->Url->build(['action' => 'reorder']) ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= $this->request->getAttribute('csrfToken') ?>'
            },
            body: JSON.stringify({
                schedule_id: <?= $selectedSchedule->id ?>,
                order: order
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update priority numbers
                items.forEach((item, index) => {
                    const prioritySpan = item.querySelector('span[style*="background: #ff9800"]');
                    if (prioritySpan) {
                        prioritySpan.textContent = index + 1;
                    }
                });
                
                // Show success message (optional)
                console.log('Order updated successfully');
            }
        })
        .catch(error => {
            console.error('Error updating order:', error);
            // Reload page on error
            location.reload();
        });
    }
});

// Add ghost class style
const style = document.createElement('style');
style.textContent = `
    .sortable-ghost {
        opacity: 0.4;
        background: #e0e0e0 !important;
    }
`;
document.head.appendChild(style);
</script>
<?php endif; ?>
