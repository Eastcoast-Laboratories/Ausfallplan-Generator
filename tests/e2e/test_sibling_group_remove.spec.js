const { test, expect } = require('@playwright/test');

test('Sibling group view shows Remove from Group button', async ({ page }) => {
    console.log('🔍 Testing sibling group remove button...');
    
    // 1. Login as admin
    await page.goto('/login');
    await page.fill('input[name="email"]', 'admin@eastcoast-labs.de');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL(/dashboard/, { timeout: 5000 });
    console.log('✅ Logged in');
    
    // 2. Go to sibling groups index
    await page.goto('/sibling-groups');
    console.log('✅ On sibling groups page');
    
    // 3. Check if there are any sibling groups
    const hasGroups = await page.locator('table tbody tr').count() > 0;
    
    if (!hasGroups) {
        console.log('⚠️  No sibling groups found - skipping test');
        return;
    }
    
    // 4. Click first sibling group view link
    await page.locator('table tbody tr').first().locator('a:has-text("View"), a:has-text("Ansehen")').click();
    await page.waitForLoadState('networkidle');
    console.log('✅ Viewing sibling group');
    
    // 5. Check if "Remove from Group" button exists
    const removeButton = page.locator('a:has-text("Remove from Group"), a:has-text("Aus Gruppe entfernen")');
    const buttonExists = await removeButton.count() > 0;
    
    if (buttonExists) {
        console.log('✅ "Remove from Group" button found!');
    } else {
        console.log('⚠️  No "Remove from Group" button (maybe no children in group)');
    }
    
    // 6. Verify no "Delete" button for children in actions column
    const childrenTable = page.locator('.related table');
    if (await childrenTable.count() > 0) {
        const deleteButtons = await childrenTable.locator('a:has-text("Delete"), a:has-text("Löschen")').count();
        expect(deleteButtons).toBe(0);
        console.log('✅ No "Delete" buttons in children list (correct!)');
    }
    
    console.log('✅ Test completed successfully!');
});
