import { test, expect } from '@playwright/test';

/**
 * Quick Test: Children on Schedule
 * 
 * Tests the /schedules/manage-children/:id endpoint:
 * - Login as admin
 * - Create children
 * - Access manage-children page for existing schedule
 * - Verify page loads correctly
 * - Verify children are displayed
 * 
 * Git commit: a6a8649 - fix: Fix manage-children permission check and birthdate field
 */

test.describe('Quick: Manage Children', () => {
    test('should access manage-children and display children', async ({ page }) => {
        // 1. Login as admin
        await page.goto('http://localhost:8080/users/login');
        await page.fill('input[name="email"]', 'admin@demo.kita');
        await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
        await page.click('button[type="submit"]');
        
        // Wait for dashboard
        await page.waitForURL('**/dashboard', { timeout: 10000 });
        
        // 2. Create test children
        await page.goto('http://localhost:8080/children/add');
        await page.fill('input[name="name"]', 'Test Kind ManageA');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/children', { timeout: 5000 });
        
        await page.goto('http://localhost:8080/children/add');
        await page.fill('input[name="name"]', 'Test Kind ManageB');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/children', { timeout: 5000 });
        
        // 3. Access manage-children directly (using schedule ID 1 which exists from default data)
        await page.goto('http://localhost:8080/schedules/manage-children/1');
        
        // 4. Verify page loads
        await expect(page.locator('h3')).toContainText('Organisations-Reihenfolge', { timeout: 10000 });
        
        // 6. Verify children are visible
        const childrenContainer = page.locator('#children-sortable');
        await expect(childrenContainer).toBeVisible();
        
        // Check if at least one child is visible
        const childItems = page.locator('.child-item');
        await expect(childItems.first()).toBeVisible({ timeout: 5000 });
        
        console.log('✅ manage-children page loads and displays children!');
    });
});
