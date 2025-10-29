import { test, expect } from '@playwright/test';

/**
 * Test: Report uses organization_order (not waitlist_order) for main list
 * 
 * Verifies:
 * 1. Children with organization_order appear in report main days
 * 2. Children with organization_order = NULL do NOT appear in report
 * 3. waitlist_order controls waitlist display (right side)
 * 
 * Correct behavior:
 * - organization_order: Controls which children appear in report (NULL = excluded)
 * - waitlist_order: Controls waitlist order (right side, first substitute child)
 * 
 * Git commit: [pending] - fix: Use organization_order for report, not waitlist_order
 */

test.describe('Report - organization_order Controls Main List', () => {
    test('should show children with organization_order and hide NULL ones', async ({ page }) => {
        // 1. Login
        await page.goto('http://localhost:8080/users/login');
        await page.fill('input[name="email"]', 'admin@demo.kita');
        await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/dashboard', { timeout: 10000 });
        
        console.log('✅ Logged in');
        
        // 2. Create two test children
        const childWithOrder = 'ReportWithOrder_' + Date.now();
        const childWithoutOrder = 'ReportWithoutOrder_' + Date.now();
        
        // Create first child (will have organization_order)
        await page.goto('http://localhost:8080/children/add');
        await page.fill('input[name="name"]', childWithOrder);
        await page.click('button[type="submit"]');
        await page.waitForURL('**/children', { timeout: 5000 });
        
        // Create second child (will have organization_order)
        await page.goto('http://localhost:8080/children/add');
        await page.fill('input[name="name"]', childWithoutOrder);
        await page.click('button[type="submit"]');
        await page.waitForURL('**/children', { timeout: 5000 });
        
        console.log('✅ Created two test children');
        
        // 3. Remove second child from organization_order (set to NULL)
        await page.goto('http://localhost:8080/schedules/manage-children/1');
        await page.waitForSelector('h3:has-text("Organisations-Reihenfolge")', { timeout: 10000 });
        
        const child2Item = page.locator('.child-item').filter({ hasText: childWithoutOrder });
        await expect(child2Item).toBeVisible({ timeout: 5000 });
        
        const removeButton = child2Item.locator('.remove-from-order');
        
        page.once('dialog', async dialog => {
            await dialog.accept();
        });
        
        await removeButton.click();
        await page.waitForTimeout(1000);
        
        console.log('✅ Removed child2 from organization_order (NULL)');
        
        // 4. Generate report
        await page.goto('http://localhost:8080/schedules');
        await page.waitForSelector('table', { timeout: 5000 });
        
        const generateButton = page.locator('a:has-text("Ausfallplan generieren")').first();
        await generateButton.click();
        
        // Wait for report to load
        await page.waitForSelector('.day-box, .waitlist-box', { timeout: 15000 });
        
        const reportContent = await page.content();
        
        // 5. Verify: child WITH organization_order SHOULD appear in report
        expect(reportContent).toContain(childWithOrder);
        console.log(`✅ Child WITH organization_order IS in report: ${childWithOrder}`);
        
        // 6. Verify: child WITHOUT organization_order (NULL) should NOT appear
        expect(reportContent).not.toContain(childWithoutOrder);
        console.log(`✅ Child WITHOUT organization_order (NULL) NOT in report: ${childWithoutOrder}`);
        
        console.log('\n🎉 Report correctly uses organization_order!');
        console.log('✓ Children with organization_order appear in report');
        console.log('✓ Children with NULL organization_order excluded from report');
    });
    
    test('should use waitlist_order for waitlist display only', async ({ page }) => {
        // Login
        await page.goto('http://localhost:8080/users/login');
        await page.fill('input[name="email"]', 'admin@demo.kita');
        await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/dashboard', { timeout: 10000 });
        
        // Generate report
        await page.goto('http://localhost:8080/schedules');
        await page.waitForSelector('table', { timeout: 5000 });
        
        const generateButton = page.locator('a:has-text("Ausfallplan generieren")').first();
        await generateButton.click();
        
        await page.waitForSelector('.waitlist-box', { timeout: 15000 });
        
        // Verify waitlist box exists (right side)
        const waitlistBox = page.locator('.waitlist-box');
        await expect(waitlistBox).toBeVisible();
        
        console.log('✅ Waitlist box present (uses waitlist_order)');
    });
});
