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
    
    <div class="row" style="margin-top: 1rem;">
        
        <!-- Children (Sortable) -->
        <div class="organization-children">
            <h4><?= __("Organization Children") ?> 
                <span style="font-size: 0.85rem; font-weight: normal; color: #666;">
                    (<?= __("Drag to reorder") ?>)
                </span>
            </h4>
            <div id="children-sortable" style="background: #e8f5e9; padding: 1rem; border-radius: 8px; min-height: 300px;">
                <?php if (!empty($children) && (is_countable($children) ? count($children) : $children->count()) > 0): ?>
                    <?php foreach ($children as $child): ?>
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
                                <?php if ($child->schedule_id): ?>
                                    <span style="background: #c8e6c9; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem; margin-left: 0.5rem;">
                                        📅 <?= __("In Schedule") ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <span style="color: #666; font-size: 0.9rem;">
                                ⋮⋮
                            </span>
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
</script>
<?php endif; ?>
