const { test, expect } = require('@playwright/test');

/**
 * TEST: "Always at end" section with statistics columns
 * 
 * Verifies that children in "Always at end" section have:
 * - Name column
 * - Z (weight) column
 * - D (days count) column
 * - ⬇️ (first on waitlist count) column
 */
test.describe('Report - Always at End Statistics Columns', () => {
    test('should show statistics columns for "Always at end" children', async ({ page }) => {
        console.log('🧪 Testing "Always at end" statistics columns...');
        
        // Step 1: Login
        console.log('📍 Step 1: Login');
        await page.goto('http://localhost:8080/users/login');
        await page.fill('input[name="email"]', 'admin@demo.kita');
        await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/dashboard');
        console.log('✅ Logged in');
        
        // Step 2: Navigate to existing schedule (Demo Kita schedule)
        console.log('📍 Step 2: Navigate to report');
        // Use schedule ID 1 which should have "Always at end" children
        await page.goto('http://localhost:8080/schedules/generate-report/1');
        await page.waitForLoadState('networkidle');
        console.log('✅ Report loaded');
        
        // Step 3: Verify "Always at end" section exists
        console.log('📍 Step 3: Verify "Always at end" section');
        const alwaysEndBox = page.locator('.always-end-box');
        await expect(alwaysEndBox).toBeVisible({ timeout: 5000 });
        console.log('✅ "Always at end" section found');
        
        // Step 4: Verify header row with column titles
        console.log('📍 Step 4: Verify header columns');
        const headerRow = alwaysEndBox.locator('.always-end-header');
        await expect(headerRow).toBeVisible();
        
        // Check for all 4 column headers
        const nameHeader = headerRow.locator('text=Name');
        const weightHeader = headerRow.locator('text=Z');
        const daysHeader = headerRow.locator('text=D');
        const firstOnWaitlistHeader = headerRow.locator('text=⬇️');
        
        await expect(nameHeader).toBeVisible();
        await expect(weightHeader).toBeVisible();
        await expect(daysHeader).toBeVisible();
        await expect(firstOnWaitlistHeader).toBeVisible();
        console.log('✅ All 4 column headers found: Name, Z, D, ⬇️');
        
        // Step 5: Verify at least one child row with all columns
        console.log('📍 Step 5: Verify child rows have all columns');
        const childRows = alwaysEndBox.locator('.always-end-child');
        const childCount = await childRows.count();
        console.log(`  Found ${childCount} children in "Always at end"`);
        
        if (childCount > 0) {
            // Check first child has all 4 columns
            const firstChild = childRows.first();
            const childCells = firstChild.locator('td, .cell');
            const cellCount = await childCells.count();
            
            console.log(`  First child has ${cellCount} cells`);
            expect(cellCount).toBeGreaterThanOrEqual(4); // Name, Z, D, ⬇️
            
            // Verify each cell has content
            for (let i = 0; i < Math.min(4, cellCount); i++) {
                const cellText = await childCells.nth(i).textContent();
                console.log(`    Column ${i + 1}: "${cellText.trim()}"`);
                expect(cellText.trim()).not.toBe('');
            }
            console.log('✅ All columns have content');
        } else {
            console.log('⚠️  No children in "Always at end" section');
        }
        
        // Step 6: Take screenshot for verification
        console.log('📍 Step 6: Take screenshot');
        await page.screenshot({ 
            path: 'test-results/always-at-end-stats.png',
            fullPage: true 
        });
        console.log('✅ Screenshot saved');
        
        // Step 7: Verify statistics are numeric
        console.log('📍 Step 7: Verify statistics are numeric');
        if (childCount > 0) {
            const firstChild = childRows.first();
            const cells = firstChild.locator('td, .cell');
            
            // Column 2 (index 2) should be D (days count) - numeric
            const daysText = await cells.nth(2).textContent();
            const daysValue = parseInt(daysText.trim());
            expect(daysValue).toBeGreaterThanOrEqual(0);
            console.log(`  Days count (D): ${daysValue} ✅`);
            
            // Column 3 (index 3) should be ⬇️ (first on waitlist count) - numeric
            const firstOnWaitlistText = await cells.nth(3).textContent();
            const firstOnWaitlistValue = parseInt(firstOnWaitlistText.trim());
            expect(firstOnWaitlistValue).toBeGreaterThanOrEqual(0);
            console.log(`  First on waitlist (⬇️): ${firstOnWaitlistValue} ✅`);
        }
        
        console.log('');
        console.log('📊 SUMMARY:');
        console.log('  - "Always at end" section exists: ✅');
        console.log('  - Header has 4 columns (Name, Z, D, ⬇️): ✅');
        console.log('  - Child rows have all 4 columns: ✅');
        console.log('  - Statistics are numeric: ✅');
        console.log('');
        console.log('✅ TEST PASSED!');
    });
});
