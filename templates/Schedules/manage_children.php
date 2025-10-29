<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Schedule $schedule
 * @var iterable<\App\Model\Entity\Child> $children
 */
$this->assign("title", __("Manage Children") . " - " . h($schedule->title));
?>

<!-- Include Sortable.js for drag & drop (local copy) -->
<script src="<?= $this->Url->build('/js/Sortable.min.js') ?>"></script>

<div class="manage-children content">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <div>
            <h3 style="margin: 0;"><?= __("Manage Children Organization Order") ?> - <?= h($schedule->organization->name) ?></h3>
            <p style="margin: 0.5rem 0 0 0;">
                <?= $this->Html->link("← " . __("Back to Schedules"), ["action" => "index"]) ?>
                <span style="color: #666; margin-left: 1rem;">
                    <?= __("This order is used in reports. Use Waitlist for schedule assignments.") ?>
                </span>
            </p>
        </div>
        <?= $this->Html->link(
            "+ " . __("Add Child"),
            ["controller" => "Children", "action" => "add"],
            ["class" => "button", "style" => "background: #4caf50; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; font-weight: bold;"]
        ) ?>
    </div>
    
    <div class="row" style="margin-top: 1rem; display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        
        <!-- Children in Organization Order (Sortable) -->
        <div class="in-order-children">
            <h4><?= __("In Organization Order") ?> 
                <span style="font-size: 0.85rem; font-weight: normal; color: #666;">
                    (<?= __("Drag to reorder") ?>)
                </span>
            </h4>
            <div id="children-sortable" style="background: #e8f5e9; padding: 1rem; border-radius: 8px; min-height: 400px;">
                <?php 
                $inOrderChildren = [];
                foreach ($children as $child) {
                    if ($child->organization_order !== null) {
                        $inOrderChildren[] = $child;
                    }
                }
                ?>
                <?php if (!empty($inOrderChildren)): ?>
                    <?php foreach ($inOrderChildren as $child): ?>
                        <div class="child-item" data-child-id="<?= $child->id ?>" style="background: white; padding: 1rem; margin-bottom: 0.5rem; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid #4caf50; cursor: move;">
                            <div>
                                <strong><?= h($child->name) ?></strong>
                                <?php if ($child->is_integrative): ?>
                                    <span style="background: #e3f2fd; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem; margin-left: 0.5rem;">
                                        <?= __("Integrative") ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($child->sibling_group_id): ?>
                                    <span style="background: #fff3cd; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem; margin-left: 0.5rem;">
                                        👨‍👩‍👧 <?= __("Sibling Group") ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <button 
                                    class="remove-from-order" 
                                    data-child-id="<?= $child->id ?>"
                                    style="background: #f44336; color: white; border: none; padding: 0.25rem 0.5rem; border-radius: 3px; cursor: pointer; font-size: 0.75rem;"
                                    title="<?= __("Remove from organization order") ?>">
                                    ✕
                                </button>
                                <span style="color: #666; font-size: 0.9rem; cursor: move;">
                                    ⋮⋮
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #666; text-align: center; padding: 2rem;">
                        <?= __("No children in organization order.") ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Children NOT in Organization Order (NULL) -->
        <div class="not-in-order-children">
            <h4><?= __("Not in Order (Excluded from Reports)") ?></h4>
            <div style="background: #ffebee; padding: 1rem; border-radius: 8px; min-height: 400px;">
                <?php 
                $notInOrderChildren = [];
                foreach ($children as $child) {
                    if ($child->organization_order === null) {
                        $notInOrderChildren[] = $child;
                    }
                }
                ?>
                <?php if (!empty($notInOrderChildren)): ?>
                    <?php foreach ($notInOrderChildren as $child): ?>
                        <div class="child-item-excluded" data-child-id="<?= $child->id ?>" style="background: white; padding: 1rem; margin-bottom: 0.5rem; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid #f44336;">
                            <div>
                                <strong style="color: #999;"><?= h($child->name) ?></strong>
                                <?php if ($child->is_integrative): ?>
                                    <span style="background: #e3f2fd; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem; margin-left: 0.5rem;">
                                        <?= __("Integrative") ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($child->sibling_group_id): ?>
                                    <span style="background: #fff3cd; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem; margin-left: 0.5rem;">
                                        👨‍👩‍👧 <?= __("Sibling Group") ?>
                                    </span>
                                <?php endif; ?>
                                <span style="background: #ffcdd2; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.75rem; margin-left: 0.5rem; color: #c62828;">
                                    <?= __("Excluded") ?>
                                </span>
                            </div>
                            <button 
                                class="add-to-order" 
                                data-child-id="<?= $child->id ?>"
                                style="background: #4caf50; color: white; border: none; padding: 0.25rem 0.5rem; border-radius: 3px; cursor: pointer; font-size: 0.75rem;"
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
        
    </div>
</div>

<?php if (!empty($children) && (is_countable($children) ? count($children) : $children->count()) > 0): ?>
<script>
// Initialize Sortable.js for drag & drop
const el = document.getElementById("children-sortable");
const sortable = Sortable.create(el, {
    animation: 150,
    ghostClass: "sortable-ghost",
    handle: ".child-item",
    onEnd: function (evt) {
        // Get new order - extract child IDs
        const items = el.querySelectorAll(".child-item");
        const childrenIds = [];
        
        items.forEach(item => {
            childrenIds.push(parseInt(item.dataset.childId));
        });
        
        // Send AJAX request to update organization_order
        fetch("<?= $this->Url->build(["action" => "manageChildren", $schedule->id]) ?>", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-Token": "<?= $this->request->getAttribute("csrfToken") ?>"
            },
            body: JSON.stringify({
                children: childrenIds
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log("Organization order updated successfully");
            } else {
                console.error("Failed to update order");
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

// Handle remove from order buttons
document.querySelectorAll(".remove-from-order").forEach(button => {
    button.addEventListener("click", function(e) {
        e.stopPropagation();
        const childId = this.dataset.childId;
        const childName = this.closest(".child-item").querySelector("strong").textContent;
        
        if (confirm("<?= __("Remove {0} from organization order?", "") ?>" + childName)) {
            // Send AJAX request to remove from order
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
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove item from DOM
                    this.closest(".child-item").style.transition = "opacity 0.3s";
                    this.closest(".child-item").style.opacity = "0";
                    setTimeout(() => {
                        this.closest(".child-item").remove();
                    }, 300);
                } else {
                    alert("<?= __("Failed to remove from order") ?>");
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("<?= __("An error occurred") ?>");
            });
        }
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
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload page to show updated order
                location.reload();
            } else {
                alert("<?= __("Failed to add to order") ?>");
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("<?= __("An error occurred") ?>");
        });
    });
});
</script>
<?php endif; ?>
