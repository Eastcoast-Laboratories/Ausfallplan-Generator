import { test, expect } from '@playwright/test';

/**
 * Test: Report excludes children with organization_order = NULL
 * 
 * Verifies that children removed from organization order (NULL)
 * do not appear in generated reports.
 * 
 * Steps:
 * 1. Login as admin
 * 2. Create a child and add to organization
 * 3. Remove child from organization order (set to NULL)
 * 4. Generate report
 * 5. Verify child does NOT appear in report
 * 
 * Git commit: f77626b - feat: Add remove from organization order functionality
 */

test.describe('Report Excludes NULL Organization Order', () => {
    test('should not show children with NULL organization_order in report', async ({ page }) => {
        // 1. Login as admin
        await page.goto('http://localhost:8080/users/login');
        await page.fill('input[name="email"]', 'admin@demo.kita');
        await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
        await page.click('button[type="submit"]');
        
        await page.waitForURL('**/dashboard', { timeout: 10000 });
        
        // 2. Create a test child with unique name
        const uniqueName = 'TestChildNull' + Date.now();
        await page.goto('http://localhost:8080/children/add');
        await page.fill('input[name="name"]', uniqueName);
        await page.click('button[type="submit"]');
        await page.waitForURL('**/children', { timeout: 5000 });
        
        // 3. Go to manage-children and remove child from organization order
        await page.goto('http://localhost:8080/schedules/manage-children/1');
        await page.waitForSelector('h3:has-text("Organisations-Reihenfolge")', { timeout: 10000 });
        
        // Find the child and click remove button
        const childItem = page.locator('.child-item').filter({ hasText: uniqueName });
        await expect(childItem).toBeVisible({ timeout: 5000 });
        
        // Click remove button
        const removeButton = childItem.locator('.remove-from-order');
        await removeButton.click();
        
        // Confirm dialog
        page.on('dialog', async dialog => {
            await dialog.accept();
        });
        
        // Wait for child to fade out
        await page.waitForTimeout(500);
        
        // 4. Generate report
        await page.goto('http://localhost:8080/schedules');
        await page.waitForSelector('table', { timeout: 5000 });
        
        // Click first "Generate List" button
        const generateButton = page.locator('a:has-text("Ausfallplan generieren")').first();
        await generateButton.click();
        
        // Wait for report to load
        await page.waitForSelector('.day-box, .report-table', { timeout: 10000 });
        
        // 5. Verify child does NOT appear in report
        const reportContent = await page.content();
        expect(reportContent).not.toContain(uniqueName);
        
        console.log(`✅ Child "${uniqueName}" with NULL organization_order correctly excluded from report`);
    });
    
    test('should show children with organization_order in report', async ({ page }) => {
        // Login
        await page.goto('http://localhost:8080/users/login');
        await page.fill('input[name="email"]', 'admin@demo.kita');
        await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
        await page.click('button[type="submit"]');
        
        await page.waitForURL('**/dashboard', { timeout: 10000 });
        
        // Create a child (will have organization_order automatically)
        const uniqueName = 'TestChildWithOrder' + Date.now();
        await page.goto('http://localhost:8080/children/add');
        await page.fill('input[name="name"]', uniqueName);
        await page.click('button[type="submit"]');
        await page.waitForURL('**/children', { timeout: 5000 });
        
        // Generate report
        await page.goto('http://localhost:8080/schedules');
        await page.waitForSelector('table', { timeout: 5000 });
        
        const generateButton = page.locator('a:has-text("Ausfallplan generieren")').first();
        await generateButton.click();
        
        await page.waitForSelector('.day-box, .report-table', { timeout: 10000 });
        
        // Verify child DOES appear (since it has organization_order)
        const reportContent = await page.content();
        expect(reportContent).toContain(uniqueName);
        
        console.log(`✅ Child "${uniqueName}" with organization_order correctly shown in report`);
    });
});
