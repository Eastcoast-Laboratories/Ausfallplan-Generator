<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Schedule $schedule
 * @var iterable<\App\Model\Entity\Schedule> $schedules
 * @var iterable<\App\Model\Entity\Child> $childrenInOrder
 * @var iterable<\App\Model\Entity\Child> $childrenNotInOrder
 * @var array $missingSiblings
 */
$this->assign("title", __("Manage Children") . " - " . h($schedule->title));
?>

<!-- Include Sortable.js for drag & drop (local copy) -->
<script src="<?= $this->Url->build('/js/Sortable.min.js') ?>"></script>

<style>
    @media (max-width: 768px) {
        /* Mobile: Stack columns and reorder */
        .manage-children-row {
            grid-template-columns: 1fr !important;
        }
        
        /* Order on Schedule first, Not on schedule second */
        .in-order-children {
            order: 1;
        }
        
        .not-in-order-children {
            order: 2;
        }
        
        /* Mobile: Stack header section vertically */
        .manage-children-header {
            flex-direction: column !important;
            align-items: stretch !important;
        }
        
        .manage-children-header > div {
            width: 100%;
        }
        
        /* Mobile: Stack header buttons vertically */
        .manage-children .button {
            display: block;
            width: 100%;
            margin: 0.5rem 0 !important;
            text-align: center;
        }
        
        /* Mobile: Schedule selector full width */
        .schedule-selector select {
            min-width: 100% !important;
            width: 100%;
        }
    }
</style>

<div class="manage-children content">
    <div class="manage-children-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <div>
            <h3 style="margin: 0;"><?= __("Children on Schedule") ?> - <?= h($schedule->organization->name) ?></h3>
            <p style="margin: 0.5rem 0 0 0;">
                <?= $this->Html->link("← " . __("Back to Schedules"), ["action" => "index"]) ?>
                <span style="color: #3f3e3eff; margin-left: 1rem;">
                    <?= __("This order is used on the shchedule.") ?>
                </span>
            </p>
        </div>
        <?php
        echo $this->Html->link("📄 " . __('Plan Preview'),
            ["controller" => "Schedules", "action" => "generate-report", $schedule->id],
            ["class" => "button", "style" => "background: #4caf50; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; font-weight: bold; margin-left: 0.5rem;"]
        );
        echo $this->Html->link( __("Waitlist"),
            ["controller" => "Waitlist", "action" => "index", "?" => ["schedule_id" => $schedule->id]],
            ["class" => "button", "style" => "background: #4caf50; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; font-weight: bold; margin-left: 0.5rem;"]
        );
        ?>
        <?= $this->Html->link(
            "+ " . __("Add Child"),
            ["controller" => "Children", "action" => "add"],
            ["class" => "button", "style" => "background: #4caf50; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; font-weight: bold; margin-left: 0.5rem;"]
        ) ?>
    </div>
    
    <!-- Schedule Selector -->
    <div class="schedule-selector" style="margin-bottom: 2rem; padding: 1rem; background: #f5f5f5; border-radius: 8px;">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <label for="schedule-select" style="font-weight: bold;"><?= __('Select Schedule') ?>:</label>
            <select id="schedule-select" onchange="window.location.href='/schedules/manage-children/' + this.value" style="padding: 0.5rem; border-radius: 4px; border: 1px solid #ccc; min-width: 300px;">
                <?php foreach ($schedules as $scheduleOption): ?>
                    <option value="<?= $scheduleOption->id ?>" <?= $schedule->id == $scheduleOption->id ? 'selected' : '' ?>>
                        <?= h($scheduleOption->title) ?>
                        <?= $scheduleOption->days_count ? ' (' . $scheduleOption->days_count . ' ' . __('Days') . ')' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
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
    
    <div class="row manage-children-row" style="margin-top: 1rem; display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        
        <!-- Children NOT in Organization Order (NULL) - LEFT SIDE -->
        <div class="not-in-order-children">
            <h4><?= __("Not on schedule") ?></h4>
            <div id="children-not-in-order" style="background: #ffebee; padding: 1rem; border-radius: 8px; min-height: 400px;">
                <?php if (!empty($childrenNotInOrder)): ?>
                    <?php foreach ($childrenNotInOrder as $child): ?>
                        <div class="child-item-excluded" data-child-id="<?= $child->id ?>" data-org-id="<?= $child->organization_id ?>" style="background: white; padding: 1rem; margin-bottom: 0.5rem; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid #f44336; cursor: move;">
                            <div>
                                <strong class="child-name" style="color: #999;"
                                    data-encrypted="<?= h($child->name_encrypted ?? '') ?>"
                                    data-iv="<?= h($child->name_iv ?? '') ?>"
                                    data-tag="<?= h($child->name_tag ?? '') ?>"><?= h($child->name) ?></strong>
                                <?php if ($child->is_integrative): ?>
                                    <span style="background: #e3f2fd; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem; margin-left: 0.5rem;">
                                        <?= __("Integrative") ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($child->sibling_group_id): ?>
                                    <span style="background: #fff3cd; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem; margin-left: 0.5rem;">
                                        👨‍👩‍👧 <?= $this->Html->link(__("Sibling Group"), ["controller" => "SiblingGroups", "action" => "view", $child->sibling_group_id]) ?>
                                    </span>
                                <?php endif; ?>
                                <span style="background: #ffcdd2; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.75rem; margin-left: 0.5rem; color: #c62828;">
                                    <?= __("Excluded") ?>
                                </span>
                            </div>
                            <button 
                                class="add-to-order" 
                                data-child-id="<?= $child->id ?>"
                                style="background: #4caf50; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1.2rem; font-weight: bold;"
                                title="<?= __("Add to organization order") ?>">
                                +
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #666; text-align: center; padding: 2rem;">
                        <?= __("All children are in organization order.") ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Children in Organization Order (Sortable) - RIGHT SIDE -->
        <div class="in-order-children">
            <h4><?= __("Order on Schedule") ?> 
                <span style="font-size: 0.85rem; font-weight: normal; color: #666;">
                    (<?= __("Drag to reorder") ?>)
                </span>
            </h4>
            <div id="children-sortable" style="background: #e8f5e9; padding: 1rem; border-radius: 8px; min-height: 400px;">
                <?php 
                // Group children by sibling_group_id
                $siblingGroups = [];
                foreach ($childrenInOrder as $child) {
                    if ($child->sibling_group_id) {
                        if (!isset($siblingGroups[$child->sibling_group_id])) {
                            $siblingGroups[$child->sibling_group_id] = [];
                        }
                        $siblingGroups[$child->sibling_group_id][] = $child;
                    }
                }
                
                // Build ordered list - iterate through childrenInOrder (already sorted by organization_order)
                $processedGroups = [];
                $orderedItems = [];
                
                foreach ($childrenInOrder as $child) {
                    if ($child->sibling_group_id) {
                        // If first sibling in group, add whole group
                        if (!in_array($child->sibling_group_id, $processedGroups)) {
                            $orderedItems[] = ['type' => 'group', 'group_id' => $child->sibling_group_id, 'siblings' => $siblingGroups[$child->sibling_group_id]];
                            $processedGroups[] = $child->sibling_group_id;
                        }
                        // Skip other siblings (already added in group)
                    } else {
                        // Single child (no siblings)
                        $orderedItems[] = ['type' => 'single', 'child' => $child];
                    }
                }
                ?>
                <?php if (!empty($orderedItems)): ?>
                    <?php foreach ($orderedItems as $item): ?>
                        <?php if ($item['type'] === 'single'): 
                            $child = $item['child'];
                        ?>
                            <div class="child-item" data-child-id="<?= $child->id ?>" data-org-id="<?= $child->organization_id ?>" style="background: white; padding: 1rem; margin-bottom: 0.5rem; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid #4caf50; cursor: move; transition: all 0.3s ease;">
                                <div>
                                    <strong class="child-name"
                                        data-encrypted="<?= h($child->name_encrypted ?? '') ?>"
                                        data-iv="<?= h($child->name_iv ?? '') ?>"
                                        data-tag="<?= h($child->name_tag ?? '') ?>"><?= h($child->name) ?></strong>
                                    <?php if ($child->is_integrative): ?>
                                        <span style="background: #e3f2fd; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem; margin-left: 0.5rem;">
                                            <?= __("Integrative") ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <button 
                                        class="remove-from-order" 
                                        data-child-id="<?= $child->id ?>"
                                        style="background: #f44336; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1.2rem; font-weight: bold;"
                                        title="<?= __("Remove from organization order") ?>">
                                        ✕
                                    </button>
                                    <span style="color: #666; font-size: 0.9rem; cursor: move;">
                                        ⋮⋮
                                    </span>
                                </div>
                            </div>
                        <?php else: // group
                            $siblings = $item['siblings'];
                            $siblingNames = array_map(fn($s) => $s->name, $siblings);
                        ?>
                            <div class="sibling-group" data-child-ids="<?= implode(',', array_map(fn($c) => $c->id, $siblings)) ?>" style="background: #fff9c4; padding: 0.5rem; margin-bottom: 0.5rem; border-radius: 4px; border-left: 4px solid #ffc107; cursor: move;">
                                <div style="font-size: 0.85rem; color: #f57c00; font-weight: bold; margin-bottom: 0.5rem;">
                                    👨‍👩‍👧 <?= $this->Html->link(__("Sibling Group"), ["controller" => "SiblingGroups", "action" => "view", $siblings[0]->sibling_group_id]) ?>
                                </div>
                                <?php foreach ($siblings as $child): 
                                    $otherSiblings = array_filter($siblingNames, fn($n) => $n !== $child->name);
                                ?>
                                    <div style="background: white; padding: 0.75rem; margin-bottom: 0.25rem; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;" data-org-id="<?= $child->organization_id ?>">
                                        <div>
                                            <strong class="child-name" title="<?= __("Siblings") ?>: <?= implode(', ', $otherSiblings) ?>"
                                                data-encrypted="<?= h($child->name_encrypted ?? '') ?>"
                                                data-iv="<?= h($child->name_iv ?? '') ?>"
                                                data-tag="<?= h($child->name_tag ?? '') ?>"><?= h($child->name) ?></strong>
                                            <?php if ($child->is_integrative): ?>
                                                <span style="background: #e3f2fd; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem; margin-left: 0.5rem;">
                                                    <?= __("Integrative") ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <button 
                                            class="remove-sibling-group" 
                                            data-child-ids="<?= implode(',', array_map(fn($c) => $c->id, $siblings)) ?>"
                                            style="background: #f44336; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1.2rem; font-weight: bold;"
                                            title="<?= __("Remove sibling group from organization order") ?>">
                                            ✕
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #666; text-align: center; padding: 2rem;">
                        <?= __("No children in organization order.") ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

<?php if (!empty($childrenInOrder) || !empty($childrenNotInOrder)): ?>
<script>
// Initialize Sortable.js for drag & drop - BIDIRECTIONAL
const inOrderEl = document.getElementById("children-sortable");
const notInOrderEl = document.getElementById("children-not-in-order");

// Sortable for "In Order" list (right side)
const sortableInOrder = Sortable.create(inOrderEl, {
    animation: 150,
    ghostClass: "sortable-ghost",
    group: "children-manage", // Same group for bidirectional drag
    onEnd: function (evt) {
        // Check if dragged TO "Not in Order" list
        if (evt.to === notInOrderEl) {
            console.log('[ManageChildren] Dragged from In Order TO Not in Order');
            const childId = evt.item.dataset.childId;
            
            // If it's a sibling group, get all child IDs
            let childIds = [];
            if (evt.item.classList.contains('sibling-group')) {
                childIds = evt.item.dataset.childIds.split(',').map(id => parseInt(id));
            } else {
                childIds = [parseInt(childId)];
            }
            
            // Remove all children from order
            const removePromises = childIds.map(id => {
                return fetch("<?= $this->Url->build(["action" => "removeFromOrder", $schedule->id]) ?>", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-Token": "<?= $this->request->getAttribute("csrfToken") ?>"
                    },
                    body: JSON.stringify({ child_id: id })
                });
            });
            
            Promise.all(removePromises)
                .then(() => {
                    console.log('[ManageChildren] ✅ Removed from order, reloading...');
                    location.reload();
                })
                .catch(error => {
                    console.error('[ManageChildren] ❌ Error removing:', error);
                    location.reload();
                });
            return;
        }
        // Reordering within "In Order" list
        console.log('[ManageChildren] Reordered within In Order list');
        const items = inOrderEl.querySelectorAll(".child-item, .sibling-group");
        const childrenIds = [];
        
        items.forEach(item => {
            if (item.classList.contains('sibling-group')) {
                // Sibling group - add all child IDs in group
                const ids = item.dataset.childIds.split(',').map(id => parseInt(id));
                childrenIds.push(...ids);
            } else {
                // Single child
                childrenIds.push(parseInt(item.dataset.childId));
            }
        });
        
        // Send AJAX request to update organization_order
        fetch("<?= $this->Url->build(["action" => "updateChildrenOrder", $schedule->id]) ?>", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-Token": "<?= $this->request->getAttribute("csrfToken") ?>"
            },
            body: JSON.stringify({
                children: childrenIds
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.log("Organization order updated successfully");
            } else {
                console.error("Failed to update order:", data.message);
                location.reload(); // Reload to show correct state
            }
        })
        .catch(error => {
            console.error("Error updating order:", error);
            location.reload(); // Reload to show correct state
        });
    }
});

// Sortable for "Not in Order" list (left side)
const sortableNotInOrder = Sortable.create(notInOrderEl, {
    animation: 150,
    ghostClass: "sortable-ghost",
    group: "children-manage", // Same group for bidirectional drag
    onEnd: function (evt) {
        // Check if dragged TO "In Order" list
        if (evt.to === inOrderEl) {
            console.log('[ManageChildren] Dragged from Not in Order TO In Order');
            const childId = evt.item.dataset.childId;
            
            if (!childId) {
                console.error('[ManageChildren] No child ID found');
                location.reload();
                return;
            }
            
            // Add to order
            fetch("<?= $this->Url->build(["action" => "addToOrder", $schedule->id]) ?>", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": "<?= $this->request->getAttribute("csrfToken") ?>"
                },
                body: JSON.stringify({ child_id: parseInt(childId) })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('[ManageChildren] ✅ Added to order, reloading...');
                    location.reload();
                } else {
                    console.error('[ManageChildren] Failed to add:', data.message);
                    location.reload();
                }
            })
            .catch(error => {
                console.error('[ManageChildren] ❌ Error adding:', error);
                location.reload();
            });
            return;
        }
        
        // If staying within "Not in Order", do nothing
        console.log('[ManageChildren] Reordered within Not in Order list (no action)');
    }
});

// Add ghost class style
const style = document.createElement("style");
style.textContent = `
    .sortable-ghost {
        opacity: 0.4;
        background: #e0e0e0 !important;
    }
`;
document.head.appendChild(style);

// Handle delete child buttons with AJAX and transition
document.querySelectorAll(".delete-child").forEach(button => {
    button.addEventListener("click", function(e) {
        e.preventDefault();
        e.stopPropagation();
        const childId = this.dataset.childId;
        const childItem = document.querySelector(`.child-item[data-child-id="${childId}"]`);
        
        if (!confirm("<?= __('Are you sure you want to delete this child?') ?>")) {
            return;
        }
        
        // Send AJAX request to delete child
        fetch("<?= $this->Url->build(["controller" => "Children", "action" => "delete"]) ?>/" + childId, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-Token": "<?= $this->request->getAttribute("csrfToken") ?>"
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Animate removal with transition
                if (childItem) {
                    childItem.style.opacity = "0";
                    childItem.style.transform = "translateX(-100%)";
                    childItem.style.marginBottom = "0";
                    childItem.style.height = childItem.offsetHeight + "px";
                    
                    setTimeout(() => {
                        childItem.style.height = "0";
                        childItem.style.padding = "0";
                        childItem.style.margin = "0";
                    }, 50);
                    
                    // Remove from DOM after animation
                    setTimeout(() => {
                        childItem.remove();
                        
                        // Check if list is now empty
                        const sortable = document.getElementById("children-sortable");
                        if (sortable && sortable.querySelectorAll(".child-item, .sibling-group").length === 0) {
                            sortable.innerHTML = '<p style="color: #666; text-align: center; padding: 2rem;"><?= __("No children in organization order.") ?></p>';
                        }
                    }, 300);
                }
            } else {
                console.error('Failed to delete:', data.message);
                alert("<?= __('Error deleting child') ?>");
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("<?= __('Error deleting child') ?>");
        });
    });
});

// Handle remove from order buttons
document.querySelectorAll(".remove-from-order").forEach(button => {
    button.addEventListener("click", function(e) {
        e.stopPropagation();
        const childId = this.dataset.childId;
        
        // Send AJAX request to remove from order (no confirm dialog)
        fetch("<?= $this->Url->build(["action" => "removeFromOrder", $schedule->id]) ?>", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-Token": "<?= $this->request->getAttribute("csrfToken") ?>"
            },
            body: JSON.stringify({
                child_id: parseInt(childId)
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Reload page to show updated state
                location.reload();
            } else {
                console.error('Failed to remove:', data.message);
                location.reload(); // Reload anyway to show correct state
            }
        })
        .catch(error => {
            console.error("Error:", error);
            location.reload(); // Reload to show correct state
        });
    });
});

// Handle remove sibling group from order buttons
document.querySelectorAll(".remove-sibling-group").forEach(button => {
    button.addEventListener("click", function(e) {
        e.stopPropagation();
        const childIds = this.dataset.childIds.split(',').map(id => parseInt(id));
        
        // Send AJAX request to remove all siblings from order
        const removePromises = childIds.map(childId => {
            return fetch("<?= $this->Url->build(["action" => "removeFromOrder", $schedule->id]) ?>", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": "<?= $this->request->getAttribute("csrfToken") ?>"
                },
                body: JSON.stringify({
                    child_id: childId
                })
            });
        });
        
        Promise.all(removePromises)
            .then(() => {
                location.reload();
            })
            .catch(error => {
                console.error("Error:", error);
                location.reload();
            });
    });
});

// Handle add to order buttons
document.querySelectorAll(".add-to-order").forEach(button => {
    button.addEventListener("click", function(e) {
        e.stopPropagation();
        const childId = this.dataset.childId;
        
        // Send AJAX request to add to order
        fetch("<?= $this->Url->build(["action" => "addToOrder", $schedule->id]) ?>", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-Token": "<?= $this->request->getAttribute("csrfToken") ?>"
            },
            body: JSON.stringify({
                child_id: parseInt(childId)
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Reload page to show updated order
                location.reload();
            } else {
                console.error('Failed to add:', data.message);
                location.reload(); // Reload anyway to show correct state
            }
        })
        .catch(error => {
            console.error("Error:", error);
            location.reload(); // Reload to show correct state
        });
    });
});
</script>
<?php endif; ?>
