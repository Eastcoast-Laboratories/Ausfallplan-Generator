const { test, expect } = require('@playwright/test');

/**
 * TEST: Children import with preselected organization
 * 
 * Tests that the import form always has an organization preselected
 * (no "-- Select Organization --" empty option)
 */
test.describe('Children Import - Preselected Organization', () => {
    test('should have organization preselected on import form', async ({ page }) => {
        console.log('🧪 Testing organization preselection on import...');
        
        // Step 1: Login
        console.log('📍 Step 1: Login');
        await page.goto('http://localhost:8080/users/login');
        await page.fill('input[name="email"]', 'admin@demo.kita');
        await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/dashboard');
        console.log('✅ Logged in');
        
        // Step 2: Navigate to import page
        console.log('📍 Step 2: Navigate to import page');
        await page.goto('http://localhost:8080/children/import');
        await page.waitForLoadState('networkidle');
        console.log('✅ On import page');
        
        // Step 3: Check organization select
        console.log('📍 Step 3: Check organization select');
        const orgSelect = page.locator('select[name="organization_id"]');
        const orgSelectExists = await orgSelect.count();
        
        if (orgSelectExists > 0) {
            console.log('  Organization select found');
            
            // Get selected value
            const selectedValue = await orgSelect.inputValue();
            console.log(`  Selected organization ID: ${selectedValue}`);
            
            // Check that a value is selected (not empty)
            expect(selectedValue).toBeTruthy();
            expect(selectedValue).not.toBe('');
            console.log('✅ Organization is preselected');
            
            // Check that there's no empty option
            const options = await orgSelect.locator('option').all();
            const optionTexts = [];
            for (const option of options) {
                const text = await option.textContent();
                const value = await option.getAttribute('value');
                optionTexts.push({ text: text.trim(), value });
            }
            
            console.log(`  Found ${optionTexts.length} options:`);
            optionTexts.forEach(opt => {
                console.log(`    - "${opt.text}" (value: ${opt.value || 'empty'})`);
            });
            
            // Check for empty option
            const hasEmptyOption = optionTexts.some(opt => 
                opt.value === '' || 
                opt.value === null || 
                opt.text.includes('Select') || 
                opt.text.includes('Wählen') ||
                opt.text.includes('--')
            );
            
            expect(hasEmptyOption).toBe(false);
            console.log('✅ No empty "-- Select --" option found');
            
        } else {
            // Organization is hidden (single org user)
            console.log('  Organization select not visible (single org user)');
            const hiddenOrg = page.locator('input[name="organization_id"][type="hidden"]');
            const hiddenOrgExists = await hiddenOrg.count();
            
            expect(hiddenOrgExists).toBeGreaterThan(0);
            
            const hiddenValue = await hiddenOrg.inputValue();
            console.log(`  Hidden organization ID: ${hiddenValue}`);
            
            expect(hiddenValue).toBeTruthy();
            expect(hiddenValue).not.toBe('');
            console.log('✅ Organization is set in hidden field');
        }
        
        // Take screenshot
        await page.screenshot({ 
            path: 'test-results/import-org-preselection.png',
            fullPage: true 
        });
        console.log('✅ Screenshot saved');
        
        console.log('');
        console.log('📊 SUMMARY:');
        console.log('  - Import page loaded: ✅');
        console.log('  - Organization preselected: ✅');
        console.log('  - No empty option: ✅');
        console.log('');
        console.log('✅ TEST PASSED!');
    });
});
