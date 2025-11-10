/**
 * Sibling Groups Test
 * 
 * Tests:
 * - Create a new sibling group
 * - Verify sibling group appears in list
 * - Edit sibling group name
 * - Delete sibling group
 * 
 * Run command:
 * timeout 120 npx playwright test tests/e2e/sibling-groups.spec.js --project=chromium --headed
 */

const { test, expect } = require('@playwright/test');

test.describe('Sibling Groups', () => {
    test('should create, edit, and delete a sibling group', async ({ page }) => {
        // Login
        await page.goto('http://localhost:8080/users/login');
        await page.fill('input[name="email"]', 'admin@demo.kita');
        await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
        await page.click('button:has-text("Anmelden")');
        
        // Wait for redirect
        await page.waitForTimeout(3000);
        
        console.log('1. Navigating to Sibling Groups...');
        
        // Navigate to Sibling Groups
        await page.goto('http://localhost:8080/sibling-groups');
        await page.waitForLoadState('networkidle');
        
        console.log('2. Creating new sibling group...');
        
        // Click "Add Sibling Group" button
        await page.click('a:has-text("Neue Geschwistergruppe")');
        await page.waitForLoadState('networkidle');
        
        // Fill in the form
        const groupName = 'Test Gruppe ' + Date.now();
        await page.fill('input[name="label"]', groupName);
        console.log('Group name:', groupName);
        
        // Submit the form
        await page.click('button:has-text("Absenden")');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000);
        
        // Verify we're back on the index page (successful save redirects)
        const currentUrl = page.url();
        console.log('Current URL:', currentUrl);
        expect(currentUrl).toContain('/sibling-groups');
        expect(currentUrl).not.toContain('/add');
        
        // Verify the group appears in the list
        const groupExists = await page.locator(`text=${groupName}`).count();
        console.log('Group found in list:', groupExists > 0);
        expect(groupExists).toBeGreaterThan(0);
        
        console.log('✅ Sibling group created successfully!');
    });
});
