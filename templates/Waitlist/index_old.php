<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Schedule> $schedules
 * @var \App\Model\Entity\Schedule|null $selectedSchedule
 * @var iterable<\App\Model\Entity\Child> $waitlistChildren
 * @var iterable<\App\Model\Entity\Child> $availableChildren
 */
$this->assign('title', __('Waitlist'));
?>

<!-- Include Sortable.js for drag & drop (local copy) -->
<script src="<?= $this->Url->build('/js/Sortable.min.js') ?>"></script>

<style>
    @media (max-width: 768px) {
        /* Mobile: Stack columns vertically and reorder */
        .waitlist-row {
            grid-template-columns: 1fr !important;
        }
        
        /* Waitlist first, Available Children second */
        .waitlist-children {
            order: 1;
        }
        
        .available-children {
            order: 2;
        }
        
        /* Mobile: Stack buttons vertically */
        .schedule-selector {
            flex-direction: column !important;
            align-items: stretch !important;
        }
        
        .schedule-selector > div {
            flex-direction: column !important;
            width: 100%;
        }
        
        .schedule-selector select {
            margin-left: 0 !important;
            margin-top: 0.5rem;
            width: 100%;
        }
        
        .schedule-selector .button {
            margin-left: 0 !important;
            margin-top: 0.5rem !important;
            width: 100%;
            text-align: center;
        }
    }
</style>

<div class="waitlist index content">
    <h3><?= __('Waitlist') ?></h3>
    
    <!-- Schedule Selector -->
    <div class="schedule-selector" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center; gap: 1rem;">
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
            <?php if ($selectedSchedule): ?>
                <?= $this->Html->link(
                    '📊 ' . __('Generate Schedule'),
                    ['controller' => 'Schedules', 'action' => 'generateReport', $selectedSchedule->id],
                    [
                        'class' => 'button',
                        'style' => 'background: #2196F3; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; font-weight: bold; margin-left: 1rem; margin-top: 2.5rem;'
                    ]
                ) ?>
            <?php endif; ?>
        </div>
        <?php if ($selectedSchedule && isset($countNotOnWaitlist) && $countNotOnWaitlist > 0): ?>
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
    
    <?php if (!empty($missingSiblings)): ?>
        <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 1rem; margin-bottom: 1rem; border-radius: 4px;">
            <strong>⚠️ <?= __('Warning') ?>:</strong> 
            <?= __('The following siblings are not assigned to this waitlist') ?>:
            <ul style="margin: 0.5rem 0 0 1.5rem;">
                <?php foreach ($missingSiblings as $missing): ?>
                    <li>
                        <strong><?= $this->Html->link(h($missing['name']), '/schedules/manage-children/' . $missing['schedule_id']) ?></strong> 
                        (<?= __('Sibling of') ?> <?= h($missing['sibling_of']) ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="row waitlist-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        
        <!-- Available Children -->
        <div class="available-children">
            <h4><?= __('Available Children') ?></h4>
            <div style="background: #f5f7fa; padding: 1rem; border-radius: 8px; min-height: 300px;">
                <?php if (!empty($availableChildren)): ?>
                    <?php foreach ($availableChildren as $child): ?>
                        <div class="available-child-item" data-id="<?= $child->id ?>" data-org-id="<?= $child->organization_id ?>" data-sibling-group="<?= $child->sibling_group_id ?? '' ?>" style="background: white; padding: 1rem; margin-bottom: 0.5rem; border-radius: 4px; cursor: move; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid #4caf50;">
                            <div>
                                <strong class="child-name"
                                    data-encrypted="<?= h($child->name_encrypted ?? '') ?>"
                                    data-iv="<?= h($child->name_iv ?? '') ?>"
                                    data-tag="<?= h($child->name_tag ?? '') ?>"><?= h($child->name) ?></strong>
                                <?php if ($child->is_integrative): ?>
                                    <span style="background: #e3f2fd; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem; margin-left: 0.5rem;">
                                        <?= __('Integrative') ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($child->sibling_group_id): ?>
                                    <?= $this->Html->link(
                                        '👨‍👩‍👧 ' . __("Geschwister"),
                                        ['controller' => 'SiblingGroups', 'action' => 'view', $child->sibling_group_id],
                                        [
                                            'style' => 'background: #fff3cd; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem; margin-left: 0.5rem; text-decoration: none; color: #856404; display: inline-block;',
                                            'title' => __('View Sibling Group'),
                                            'escape' => false
                                        ]
                                    ) ?>
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
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h4 style="margin: 0;"><?= __('Children on Waitlist') ?> <span style="font-size: 0.9rem; color: #666;">(<?= __('Drag to reorder') ?>)</span></h4>
                <div style="display: flex; gap: 0.5rem;">
                    <?= $this->Form->postLink(
                        '📅 ' . __('Sort by Birthdate'),
                        ['action' => 'sortBy', '?' => ['schedule_id' => $selectedSchedule->id, 'field' => 'birthdate']],
                        [
                            'class' => 'button button-small',
                            'style' => 'background: #9C27B0; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; font-size: 0.85rem;',
                            'title' => __('Sort waitlist by birthdate (oldest first)')
                        ]
                    ) ?>
                    <?= $this->Form->postLink(
                        '📍 ' . __('Sort by Postal Code'),
                        ['action' => 'sortBy', '?' => ['schedule_id' => $selectedSchedule->id, 'field' => 'postal_code']],
                        [
                            'class' => 'button button-small',
                            'style' => 'background: #00BCD4; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; font-size: 0.85rem;',
                            'title' => __('Sort waitlist by postal code (ascending)')
                        ]
                    ) ?>
                </div>
            </div>
            <div id="waitlist-sortable" style="background: #fff3e0; padding: 1rem; border-radius: 8px; min-height: 300px;">
                <?php if (!empty($waitlistChildren) && (is_countable($waitlistChildren) ? count($waitlistChildren) : $waitlistChildren->count()) > 0): ?>
                    <?php 
                    // Group children by sibling_group_id (same as manage_children)
                    $siblingGroupsWaitlist = [];
                    foreach ($waitlistChildren as $child) {
                        if ($child->sibling_group_id) {
                            if (!isset($siblingGroupsWaitlist[$child->sibling_group_id])) {
                                $siblingGroupsWaitlist[$child->sibling_group_id] = [];
                            }
                            $siblingGroupsWaitlist[$child->sibling_group_id][] = $child;
                        }
                    }
                    
                    // Build ordered list - iterate through waitlistChildren (already sorted by waitlist_order)
                    $processedGroupsWaitlist = [];
                    $orderedWaitlistItems = [];
                    
                    foreach ($waitlistChildren as $child) {
                        if ($child->sibling_group_id) {
                            // If first sibling in group, add whole group
                            if (!in_array($child->sibling_group_id, $processedGroupsWaitlist)) {
                                $orderedWaitlistItems[] = ['type' => 'group', 'group_id' => $child->sibling_group_id, 'siblings' => $siblingGroupsWaitlist[$child->sibling_group_id]];
                                $processedGroupsWaitlist[] = $child->sibling_group_id;
                            }
                            // Skip other siblings (already added in group)
                        } else {
                            // Single child (no siblings)
                            $orderedWaitlistItems[] = ['type' => 'single', 'child' => $child];
                        }
                    }
                    ?>
                    <?php foreach ($orderedWaitlistItems as $item): ?>
                        <?php if ($item['type'] === 'single'): 
                            $child = $item['child'];
                        ?>
                        <div class="waitlist-item" data-id="<?= $child->id ?>" data-org-id="<?= $child->organization_id ?>" data-sibling-group="<?= $child->sibling_group_id ?? '' ?>" style="background: white; padding: 1rem; margin-bottom: 0.5rem; border-radius: 4px; cursor: move; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid #ff9800;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <span style="background: #ff9800; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                    <?= $child->waitlist_order ?>
                                </span>
                                <div>
                                    <strong class="child-name"
                                        data-encrypted="<?= h($child->name_encrypted ?? '') ?>"
                                        data-iv="<?= h($child->name_iv ?? '') ?>"
                                        data-tag="<?= h($child->name_tag ?? '') ?>"><?= h($child->name) ?></strong>
                                    <?php if ($child->sibling_group_id): ?>
                                        <?= $this->Html->link(
                                            '👨‍👩‍👧 ' . __('Geschwister'),
                                            ['controller' => 'SiblingGroups', 'action' => 'view', $child->sibling_group_id],
                                            [
                                                'style' => 'background: #fff3cd; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem; margin-left: 0.5rem; text-decoration: none; color: #856404; display: inline-block;',
                                                'title' => __('View Sibling Group'),
                                                'escape' => false
                                            ]
                                        ) ?>
                                    <?php endif; ?>
                                    <?php if ($child->is_integrative): ?>
                                        <span style="background: #e3f2fd; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem; margin-left: 0.5rem;">
                                            <?= __('Integrative') ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?= $this->Form->postLink(
                                '✕',
                                ['action' => 'delete', $child->id],
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
        <!-- No schedule selected -->
        <div style="background: #f5f7fa; padding: 3rem; border-radius: 8px; text-align: center;">
            <p style="font-size: 1.2rem; color: #666; margin-bottom: 1rem;">
                <?= __('Bitte wählen Sie einen Ausfallplan aus, um die Nachrückliste zu sehen.') ?>
            </p>
            <?= $this->Html->link(
                __('Zu den Ausfallplänen'),
                ['controller' => 'Schedules', 'action' => 'index'],
                ['class' => 'button', 'style' => 'background: #667eea; color: white;']
            ) ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($selectedSchedule && $waitlistChildren && $waitlistChildren->count() > 0): ?>
<script>
// Initialize Sortable.js for drag & drop
const waitlistEl = document.getElementById('waitlist-sortable');
const availableEl = document.querySelector('.available-children > div');

const sortable = Sortable.create(waitlistEl, {
    animation: 150,
    ghostClass: 'sortable-ghost',
    handle: '.waitlist-item',
    group: 'waitlist-group',
    onEnd: function (evt) {
        // Check if item was moved to available children
        if (evt.to === availableEl) {
            // Remove from waitlist
            const childId = evt.item.dataset.id;
            fetch('<?= $this->Url->build(['action' => 'delete']) ?>/' + childId, {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': '<?= $this->request->getAttribute('csrfToken') ?>'
                }
            })
            .then(() => {
                location.reload();
            })
            .catch(error => {
                console.error('Error removing from waitlist:', error);
                location.reload();
            });
            return;
        }
        
        // Get new order
        const items = waitlistEl.querySelectorAll('.waitlist-item');
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

// Make available children droppable and draggable
Sortable.create(availableEl, {
    group: 'waitlist-group',
    sort: false,
    handle: '.available-child-item',
    onEnd: function(evt) {
        // Check if item was moved to waitlist
        if (evt.to === waitlistEl) {
            // Add to waitlist
            const childId = evt.item.dataset.id;
            fetch('<?= $this->Url->build(['action' => 'add']) ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?= $this->request->getAttribute('csrfToken') ?>'
                },
                body: JSON.stringify({
                    schedule_id: <?= $selectedSchedule->id ?>,
                    child_id: childId
                })
            })
            .then(() => {
                location.reload();
            })
            .catch(error => {
                console.error('Error adding to waitlist:', error);
                location.reload();
            });
            return;
        }
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

// Create warning div for sibling order check
const warningDiv = document.createElement('div');
warningDiv.id = 'sibling-warning';
warningDiv.style.cssText = 'display:none; background: #fff3cd; border: 2px solid #ffc107; padding: 1rem; margin-bottom: 1rem; border-radius: 4px; color: #856404; font-weight: bold;';
warningDiv.innerHTML = '⚠️ Hinweis: Geschwister sollten auch in der Nachrückliste hintereinander angeordnet werden';
el.parentElement.insertBefore(warningDiv, el);

// Function to check if siblings are consecutive
function checkSiblingOrder() {
    const items = Array.from(el.querySelectorAll('.waitlist-item'));
    const siblingGroups = {};
    
    // Build sibling groups
    items.forEach((item, index) => {
        const groupId = item.dataset.siblingGroup;
        if (groupId && groupId !== '') {
            if (!siblingGroups[groupId]) {
                siblingGroups[groupId] = [];
            }
            siblingGroups[groupId].push(index);
        }
    });
    
    // Check if any group has non-consecutive members
    let hasSeparatedSiblings = false;
    for (const groupId in siblingGroups) {
        const positions = siblingGroups[groupId];
        if (positions.length < 2) continue;
        
        // Check if positions are consecutive
        for (let i = 1; i < positions.length; i++) {
            if (positions[i] !== positions[i-1] + 1) {
                hasSeparatedSiblings = true;
                break;
            }
        }
        if (hasSeparatedSiblings) break;
    }
    
    // Show/hide warning
    warningDiv.style.display = hasSeparatedSiblings ? 'block' : 'none';
}

// Check on load
checkSiblingOrder();

// Also add to the existing onEnd callback
const originalOnEnd = sortable.option('onEnd');
sortable.option('onEnd', function(evt) {
    if (originalOnEnd) originalOnEnd.call(this, evt);
    setTimeout(checkSiblingOrder, 100);
});
</script>
<?php endif; ?>
