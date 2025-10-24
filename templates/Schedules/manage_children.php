<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Schedule $schedule
 * @var iterable<\App\Model\Entity\Child> $assignedChildren
 * @var iterable<\App\Model\Entity\Child> $availableChildren
 */
$this->assign("title", __("Manage Children") . " - " . h($schedule->title));
?>

<!-- Include Sortable.js for drag & drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<div class="manage-children content">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <div>
            <h3 style="margin: 0;"><?= __("Manage Children") ?> - <?= h($schedule->title) ?></h3>
            <p style="margin: 0.5rem 0 0 0;"><?= $this->Html->link("← " . __("Back to Schedules"), ["action" => "index"]) ?></p>
        </div>
        <?= $this->Html->link(
            "+ " . __("Add Child"),
            ["controller" => "Children", "action" => "add"],
            ["class" => "button", "style" => "background: #4caf50; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; font-weight: bold;"]
        ) ?>
    </div>
    
    <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 1rem;">
        
        <!-- Available Children -->
        <div class="available-children">
            <h4><?= __("Available Children") ?></h4>
            <div style="background: #f5f7fa; padding: 1rem; border-radius: 8px; min-height: 300px;">
                <?php if (!empty($availableChildren) && (is_countable($availableChildren) ? count($availableChildren) : $availableChildren->count()) > 0): ?>
                    <?php foreach ($availableChildren as $child): ?>
                        <div style="background: white; padding: 1rem; margin-bottom: 0.5rem; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong><?= h($child->name) ?></strong>
                                <?php if ($child->is_integrative): ?>
                                    <span style="background: #e3f2fd; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem; margin-left: 0.5rem;">
                                        <?= __("Integrative") ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($child->sibling_group_id): ?>
                                    <span style="background: #fff3cd; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem; margin-left: 0.5rem;">
                                        👨‍👩‍👧 <?= __("Geschwister") ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?= $this->Form->postLink(
                                "+ " . __("Add"),
                                ["action" => "assignChild", "?" => ["schedule_id" => $schedule->id, "child_id" => $child->id]],
                                [
                                    "class" => "button button-small",
                                    "style" => "background: #4caf50; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;"
                                ]
                            ) ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #666; text-align: center; padding: 2rem;">
                        <?= __("All children are assigned to this schedule.") ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Assigned Children (Sortable) -->
        <div class="assigned-children">
            <h4><?= __("Assigned Children") ?> 
                <span style="font-size: 0.85rem; font-weight: normal; color: #666;">
                    (<?= __("Drag to reorder") ?>)
                </span>
            </h4>
            <div id="children-sortable" style="background: #e8f5e9; padding: 1rem; border-radius: 8px; min-height: 300px;">
                <?php if (!empty($assignedChildren) && (is_countable($assignedChildren) ? count($assignedChildren) : $assignedChildren->count()) > 0): ?>
                    <?php 
                    // Group children by sibling_group_id
                    $groups = [];
                    $singles = [];
                    foreach ($assignedChildren as $child) {
                        if ($child->sibling_group_id) {
                            $groups[$child->sibling_group_id][] = $child;
                        } else {
                            $singles[] = $child;
                        }
                    }
                    
                    // Display singles first, then groups
                    foreach ($singles as $child): ?>
                        <div class="child-item" data-child-id="<?= $child->id ?>" style="background: white; padding: 1rem; margin-bottom: 0.5rem; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid #4caf50; cursor: move;">
                            <div>
                                <strong><?= h($child->name) ?></strong>
                                <?php if ($child->is_integrative): ?>
                                    <span style="background: #e3f2fd; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem; margin-left: 0.5rem;">
                                        <?= __("Integrative") ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?= $this->Form->postLink(
                                "✕",
                                ["action" => "removeChild", "?" => ["schedule_id" => $schedule->id, "child_id" => $child->id]],
                                [
                                    "class" => "button button-small",
                                    "style" => "background: #f44336; color: white; padding: 0.5rem 0.75rem; text-decoration: none; border-radius: 4px;"
                                ]
                            ) ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php foreach ($groups as $groupId => $siblings): ?>
                        <div class="sibling-group" data-child-ids="<?= implode(",", array_map(fn($c) => $c->id, $siblings)) ?>" style="background: #fff9c4; padding: 0.5rem; margin-bottom: 0.5rem; border-radius: 4px; border-left: 4px solid #ffc107; cursor: move;">
                            <div style="font-size: 0.85rem; color: #f57c00; font-weight: bold; margin-bottom: 0.5rem;">
                                👨‍👩‍👧 <?= __("Sibling Group") ?>
                            </div>
                            <?php foreach ($siblings as $child): ?>
                                <div style="background: white; padding: 0.75rem; margin-bottom: 0.25rem; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong><?= h($child->name) ?></strong>
                                        <?php if ($child->is_integrative): ?>
                                            <span style="background: #e3f2fd; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem; margin-left: 0.5rem;">
                                                <?= __("Integrative") ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?= $this->Form->postLink(
                                        "✕",
                                        ["action" => "removeChild", "?" => ["schedule_id" => $schedule->id, "child_id" => $child->id]],
                                        [
                                            "class" => "button button-small",
                                            "style" => "background: #f44336; color: white; padding: 0.5rem 0.75rem; text-decoration: none; border-radius: 4px;"
                                        ]
                                    ) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #666; text-align: center; padding: 2rem;">
                        <?= __("No children assigned to this schedule.") ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

<?php if (!empty($assignedChildren) && (is_countable($assignedChildren) ? count($assignedChildren) : $assignedChildren->count()) > 0): ?>
<script>
// Initialize Sortable.js for drag & drop
const el = document.getElementById("children-sortable");
const sortable = Sortable.create(el, {
    animation: 150,
    ghostClass: "sortable-ghost",
    handle: ".child-item, .sibling-group",
    onEnd: function (evt) {
        // Get new order - extract child IDs (singles and groups)
        const items = el.querySelectorAll(".child-item, .sibling-group");
        const order = [];
        
        items.forEach(item => {
            if (item.classList.contains("sibling-group")) {
                // For sibling groups, add all child IDs
                const childIds = item.dataset.childIds.split(",").map(id => parseInt(id));
                order.push(...childIds);
            } else {
                // Single child
                order.push(parseInt(item.dataset.childId));
            }
        });
        
        // Send AJAX request to update order
        fetch("<?= $this->Url->build(["action" => "reorderChildren"]) ?>", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-Token": "<?= $this->request->getAttribute("csrfToken") ?>"
            },
            body: JSON.stringify({
                schedule_id: <?= $schedule->id ?>,
                order: order
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log("Order updated successfully");
            } else {
                console.error("Failed to update order:", data.error);
                location.reload();
            }
        })
        .catch(error => {
            console.error("Error updating order:", error);
            location.reload();
        });
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
</script>
<?php endif; ?>
