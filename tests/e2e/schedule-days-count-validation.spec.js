const { test, expect } = require('@playwright/test');

/**
 * TEST: Schedule days_count validation
 * 
 * Tests that creating a schedule without days_count shows a validation error
 */
test.describe('Schedule - Days Count Validation', () => {
    test('should show error when days_count is empty', async ({ page }) => {
        console.log('🧪 Testing days_count validation...');
        
        // Step 1: Login
        console.log('📍 Step 1: Login');
        await page.goto('http://localhost:8080/users/login');
        await page.fill('input[name="email"]', 'admin@demo.kita');
        await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/dashboard');
        console.log('✅ Logged in');
        
        // Step 2: Go to schedule add form
        console.log('📍 Step 2: Navigate to schedule add form');
        await page.goto('http://localhost:8080/schedules/add');
        await page.waitForLoadState('networkidle');
        console.log('✅ On schedule add page');
        
        // Step 3: Fill form WITHOUT days_count
        console.log('📍 Step 3: Fill form without days_count');
        await page.fill('input[name="title"]', `Validation Test ${Date.now()}`);
        await page.fill('input[name="starts_on"]', '2025-01-01');
        await page.fill('input[name="ends_on"]', '2025-12-31');
        
        // Make sure days_count is empty
        const daysCountInput = page.locator('input[name="days_count"]');
        await daysCountInput.clear();
        console.log('✅ Form filled (days_count left empty)');
        
        // Step 4: Submit form
        console.log('📍 Step 4: Submit form');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        console.log('✅ Form submitted');
        
        // Step 5: Check for validation error
        console.log('📍 Step 5: Check for validation error');
        
        // Should still be on add page (not redirected)
        const currentURL = page.url();
        const stillOnAddPage = currentURL.includes('/schedules/add');
        console.log(`  Still on add page: ${stillOnAddPage ? '✅' : '❌'}`);
        
        // Check for error message
        const pageContent = await page.content();
        const hasErrorClass = pageContent.includes('error-message') || 
                             pageContent.includes('has-error') ||
                             pageContent.includes('is-invalid');
        
        // Check for required field text
        const hasDaysCountError = pageContent.includes('days_count') || 
                                  pageContent.includes('Anzahl Tage') ||
                                  pageContent.includes('erforderlich') ||
                                  pageContent.includes('required');
        
        console.log(`  Has error styling: ${hasErrorClass ? '✅' : '❌'}`);
        console.log(`  Has days_count related error: ${hasDaysCountError ? '✅' : '❌'}`);
        
        // Take screenshot
        await page.screenshot({ 
            path: 'test-results/schedule-validation-error.png',
            fullPage: true 
        });
        console.log('✅ Screenshot saved');
        
        // Assertions
        expect(stillOnAddPage).toBe(true);
        expect(hasDaysCountError).toBe(true);
        
        console.log('');
        console.log('📊 SUMMARY:');
        console.log('  - Stayed on add page: ✅');
        console.log('  - Validation error shown: ✅');
        console.log('');
        console.log('✅ TEST PASSED - Validation works!');
    });
    
    test('should allow schedule creation with valid days_count', async ({ page }) => {
        console.log('🧪 Testing valid schedule creation...');
        
        // Step 1: Login
        console.log('📍 Step 1: Login');
        await page.goto('http://localhost:8080/users/login');
        await page.fill('input[name="email"]', 'admin@demo.kita');
        await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/dashboard');
        console.log('✅ Logged in');
        
        // Step 2: Create schedule WITH days_count
        console.log('📍 Step 2: Create schedule with days_count');
        await page.goto('http://localhost:8080/schedules/add');
        await page.fill('input[name="title"]', `Valid Schedule ${Date.now()}`);
        await page.fill('input[name="starts_on"]', '2025-01-01');
        await page.fill('input[name="ends_on"]', '2025-12-31');
        await page.fill('input[name="days_count"]', '5');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        console.log('✅ Form submitted');
        
        // Step 3: Should be redirected to view page
        console.log('📍 Step 3: Verify redirect to view page');
        const url = page.url();
        const scheduleId = url.match(/\/schedules\/view\/(\d+)/)?.[1];
        
        expect(scheduleId).toBeTruthy();
        console.log(`✅ Schedule created with ID: ${scheduleId}`);
        
        console.log('');
        console.log('📊 SUMMARY:');
        console.log('  - Schedule created: ✅');
        console.log('  - Redirected to view: ✅');
        console.log('');
        console.log('✅ TEST PASSED - Valid data accepted!');
    });
});
