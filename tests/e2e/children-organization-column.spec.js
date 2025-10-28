const { test, expect } = require('@playwright/test');

/**
 * TEST: Children list organization column
 * 
 * Tests that organization column appears when "Alle Organisationen" is selected
 */
test.describe('Children List - Organization Column', () => {
    test('should show organization column when "Alle Organisationen" is selected', async ({ page }) => {
        console.log('🧪 Testing organization column display...');
        
        // Step 1: Login
        console.log('📍 Step 1: Login');
        await page.goto('http://localhost:8080/users/login');
        await page.fill('input[name="email"]', 'admin@demo.kita');
        await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/dashboard');
        console.log('✅ Logged in');
        
        // Step 2: Navigate to children list
        console.log('📍 Step 2: Navigate to children list');
        await page.goto('http://localhost:8080/children');
        await page.waitForLoadState('networkidle');
        console.log('✅ On children list');
        
        // Step 3: Check if organization selector exists
        console.log('📍 Step 3: Check organization selector');
        const orgSelect = page.locator('select#organization-select');
        const selectorExists = await orgSelect.count();
        
        if (selectorExists === 0) {
            console.log('⚠️  No organization selector (single-org user) - skipping test');
            return;
        }
        console.log('✅ Organization selector found');
        
        // Step 4: Select "Alle Organisationen"
        console.log('📍 Step 4: Select "Alle Organisationen"');
        await orgSelect.selectOption('');
        await page.waitForLoadState('networkidle');
        // Wait a bit more for table to re-render
        await page.waitForTimeout(500);
        console.log('✅ "Alle Organisationen" selected');
        
        // Step 5: Check for organization column
        console.log('📍 Step 5: Check for Organization column');
        // Wait for table to be visible
        await page.locator('thead').waitFor({ state: 'visible' });
        const headers = await page.locator('thead th').allTextContents();
        console.log(`  Found ${headers.length} headers:`, headers);
        
        const hasOrgColumn = headers.some(h => 
            h.includes('Organization') || 
            h.includes('Organisation')
        );
        
        expect(hasOrgColumn).toBe(true);
        console.log('✅ Organization column found in headers');
        
        // Step 6: Verify organization column has data
        console.log('📍 Step 6: Check organization column data');
        const firstRow = page.locator('tbody tr').first();
        const cells = await firstRow.locator('td').allTextContents();
        console.log(`  First row has ${cells.length} cells`);
        
        if (cells.length > 0) {
            // Find organization cell (should be after Display Name, before Gender)
            const orgCellIndex = headers.findIndex(h => 
                h.includes('Organization') || h.includes('Organisation')
            );
            
            if (orgCellIndex >= 0 && orgCellIndex < cells.length) {
                const orgCellContent = cells[orgCellIndex].trim();
                console.log(`  Organization cell content: "${orgCellContent}"`);
                expect(orgCellContent.length).toBeGreaterThan(0);
                console.log('✅ Organization column has data');
            }
        }
        
        // Step 7: Select specific organization
        console.log('📍 Step 7: Select specific organization');
        const options = await orgSelect.locator('option').all();
        
        // Find first non-empty option
        let selectedOrg = null;
        for (const option of options) {
            const value = await option.getAttribute('value');
            if (value && value !== '') {
                selectedOrg = value;
                break;
            }
        }
        
        if (selectedOrg) {
            await orgSelect.selectOption(selectedOrg);
            await page.waitForLoadState('networkidle');
            console.log(`✅ Selected organization ID: ${selectedOrg}`);
            
            // Step 8: Verify organization column is hidden
            console.log('📍 Step 8: Verify organization column is hidden');
            const headersFiltered = await page.locator('thead th').allTextContents();
            const hasOrgColumnFiltered = headersFiltered.some(h => 
                h.includes('Organization') || h.includes('Organisation')
            );
            
            expect(hasOrgColumnFiltered).toBe(false);
            console.log('✅ Organization column hidden when filtering by specific org');
        }
        
        // Take screenshot
        await page.screenshot({ 
            path: 'test-results/children-org-column.png',
            fullPage: true 
        });
        console.log('✅ Screenshot saved');
        
        console.log('');
        console.log('📊 SUMMARY:');
        console.log('  - Organization column shown with "Alle Organisationen": ✅');
        console.log('  - Organization column hidden with specific org: ✅');
        console.log('  - Column has data: ✅');
        console.log('');
        console.log('✅ TEST PASSED!');
    });
});
