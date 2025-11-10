/**
 * Waitlist Drag & Drop Add Test
 * 
 * Tests:
 * - Drag child from available children (left) to waitlist (right)
 * - Child is added to waitlist
 * - Page reloads automatically
 * - Child appears in waitlist after reload
 * 
 * Run command:
 * timeout 120 npx playwright test tests/e2e/waitlist-drag-add.spec.js --project=chromium --headed
 */

const { test, expect } = require('@playwright/test');

test.describe('Waitlist Drag & Drop Add', () => {
    test('should add child to waitlist by dragging from left to right', async ({ page }) => {
        console.log('1. Login...');
        
        // Login
        await page.goto('http://localhost:8080/users/login');
        await page.fill('input[name="email"]', 'admin@demo.kita');
        await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
        await page.click('button:has-text("Anmelden")');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);
        
        console.log('2. Navigate to waitlist...');
        
        // Navigate to waitlist
        await page.goto('http://localhost:8080/waitlist');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000);
        
        console.log('3. Select schedule...');
        
        // Select first schedule from dropdown
        const scheduleSelect = page.locator('select[name="schedule_id"]');
        const scheduleOptions = await scheduleSelect.locator('option').count();
        console.log(`  Found ${scheduleOptions} schedule options`);
        
        if (scheduleOptions > 1) {
            // Select second option (first is "Select Schedule")
            await scheduleSelect.selectOption({ index: 1 });
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(1000);
        }
        
        console.log('4. Check for available children...');
        
        // Check if there are available children
        const availableChildren = page.locator('.available-child-item');
        const availableCount = await availableChildren.count();
        console.log(`  Found ${availableCount} available children`);
        
        if (availableCount === 0) {
            console.log('⚠️  No available children to test with - skipping test');
            return;
        }
        
        // Get first available child
        const firstChild = availableChildren.first();
        const childName = await firstChild.locator('strong').textContent();
        const childId = await firstChild.getAttribute('data-id');
        console.log(`  First child: ${childName} (ID: ${childId})`);
        
        console.log('5. Check waitlist before drag...');
        
        // Count children in waitlist before
        const waitlistBefore = page.locator('.waitlist-item');
        const waitlistCountBefore = await waitlistBefore.count();
        console.log(`  Waitlist count before: ${waitlistCountBefore}`);
        
        console.log('6. Drag child from left to right...');
        
        // Get waitlist container
        const waitlistContainer = page.locator('#waitlist-sortable');
        
        // Drag from available children to waitlist
        await firstChild.dragTo(waitlistContainer);
        
        console.log('7. Wait for reload...');
        
        // Wait for page reload (the script does location.reload())
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);
        
        console.log('8. Verify child is in waitlist...');
        
        // Check if child is now in waitlist
        const waitlistAfter = page.locator('.waitlist-item');
        const waitlistCountAfter = await waitlistAfter.count();
        console.log(`  Waitlist count after: ${waitlistCountAfter}`);
        
        // Verify count increased
        expect(waitlistCountAfter).toBe(waitlistCountBefore + 1);
        
        // Verify child name appears in waitlist
        const childInWaitlist = await page.locator('.waitlist-item').filter({ hasText: childName }).count();
        console.log(`  Child "${childName}" found in waitlist: ${childInWaitlist > 0}`);
        expect(childInWaitlist).toBeGreaterThan(0);
        
        console.log('✅ Drag & drop add to waitlist successful!');
    });
});
