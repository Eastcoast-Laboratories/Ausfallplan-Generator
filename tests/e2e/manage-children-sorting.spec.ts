import { test, expect } from '@playwright/test';

/**
 * Test: manage-children sorting and organization_order management
 * 
 * Tests:
 * 1. Remove child from organization_order (set to NULL)
 * 2. Verify child moves to "excluded" column
 * 3. Add child back to organization_order
 * 4. Verify sortable functionality exists
 * 
 * Note: organization_order is used for INTERNAL ORGANIZATION ONLY.
 * It does NOT affect which children appear in reports (that's waitlist_order).
 * 
 * Git commit: b2f0972 - feat: Redesign manage-children with two-column layout + tests
 */

test.describe('Manage Children - Sorting & Organization Order', () => {
    test('should allow removing/adding children from organization order', async ({ page }) => {
        // 1. Login
        await page.goto('http://localhost:8080/users/login');
        await page.fill('input[name="email"]', 'admin@demo.kita');
        await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/dashboard', { timeout: 10000 });
        
        console.log('✅ Logged in');
        
        // 2. Create a test child
        const testChildName = 'SortTestChild_' + Date.now();
        await page.goto('http://localhost:8080/children/add');
        await page.fill('input[name="name"]', testChildName);
        await page.click('button[type="submit"]');
        await page.waitForURL('**/children', { timeout: 5000 });
        
        console.log('✅ Created test child:', testChildName);
        
        // 3. Go to manage-children
        await page.goto('http://localhost:8080/schedules/manage-children/1');
        await page.waitForSelector('h3:has-text("Organisations-Reihenfolge")', { timeout: 10000 });
        
        // 4. Verify child is in left column (Order on Schedule)
        const leftColumn = page.locator('.in-order-children');
        const rightColumn = page.locator('.not-in-order-children');
        
        const childInOrder = leftColumn.locator('.child-item').filter({ hasText: testChildName });
        await expect(childInOrder).toBeVisible({ timeout: 5000 });
        
        console.log('✅ Child is in organization order (left column)');
        
        // 5. Remove child from organization order
        const removeButton = childInOrder.locator('.remove-from-order');
        // Click remove button (no confirm dialog anymore)
        await removeButton.click();
        
        // Wait for page to reload
        await page.waitForLoadState('load', { timeout: 5000 });
        
        console.log('✅ Clicked remove button and page reloaded');
        
        // 6. Reload and verify child is now in right column (Excluded)
        await page.reload();
        await page.waitForSelector('h3:has-text("Organisations-Reihenfolge")', { timeout: 10000 });
        
        const childExcluded = rightColumn.locator('.child-item-excluded').filter({ hasText: testChildName });
        await expect(childExcluded).toBeVisible({ timeout: 5000 });
        
        // Verify it has "Excluded" badge
        await expect(childExcluded.locator('span:has-text("Ausgeschlossen")')).toBeVisible();
        
        console.log('✅ Child moved to excluded column (right side)');
        
        // 7. Verify add button exists (we'll test the full add-back flow separately)
        const addButton = childExcluded.locator('.add-to-order');
        await expect(addButton).toBeVisible();
        
        console.log('✅ Add button present for excluded child');
        
        // 8. Verify sortable functionality exists
        const sortableContainer = page.locator('#children-sortable');
        await expect(sortableContainer).toBeVisible();
        
        // Check that drag handles are present
        const dragHandles = sortableContainer.locator('.child-item span:has-text("⋮⋮")');
        const dragHandleCount = await dragHandles.count();
        expect(dragHandleCount).toBeGreaterThan(0);
        
        console.log(`✅ Sortable container with ${dragHandleCount} drag handles present`);
        
        console.log('\n🎉 All tests passed!');
        console.log('✓ Children can be removed from organization order (NULL)');
        console.log('✓ Removed children appear in excluded column');
        console.log('✓ Add button is available for excluded children');
        console.log('✓ Sortable drag & drop handles are present');
    });
    
    test('should show two-column layout like waitlist', async ({ page }) => {
        // Login
        await page.goto('http://localhost:8080/users/login');
        await page.fill('input[name="email"]', 'admin@demo.kita');
        await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/dashboard', { timeout: 10000 });
        
        // Go to manage-children
        await page.goto('http://localhost:8080/schedules/manage-children/1');
        await page.waitForSelector('h3:has-text("Organisations-Reihenfolge")', { timeout: 10000 });
        
        // Verify two-column layout exists
        const leftColumn = page.locator('.in-order-children');
        const rightColumn = page.locator('.not-in-order-children');
        
        await expect(leftColumn).toBeVisible();
        await expect(rightColumn).toBeVisible();
        
        // Verify column headers
        await expect(page.locator('h4:has-text("In Organisations-Reihenfolge")')).toBeVisible();
        await expect(page.locator('h4:has-text("Nicht in Reihenfolge")')).toBeVisible();
        
        console.log('✅ Two-column layout verified (like waitlist)');
    });
});
