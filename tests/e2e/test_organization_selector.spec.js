const { test, expect } = require('@playwright/test');

test('Organization selector visible for system admin', async ({ page }) => {
    console.log('🧪 Testing: Organization Selector in Schedule Add\n');
    
    // Login as admin (system admin)
    await page.goto('http://localhost:8080/users/login');
    await page.fill('input[name="email"]', 'admin@demo.kita');
    await page.fill('input[name="password"]', 'asbdasdaddd');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    console.log('✅ Logged in as system admin\n');
    
    // Go to add schedule page
    await page.goto('http://localhost:8080/schedules/add');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
    
    // Check if organization selector is visible
    const orgSelector = await page.locator('select[name="organization_id"], #organization-id').count();
    
    console.log(`Organization selector count: ${orgSelector}`);
    
    if (orgSelector > 0) {
        console.log('✅ Organization selector IS visible for system admin');
        
        // Get options
        const options = await page.locator('select[name="organization_id"] option, #organization-id option').count();
        console.log(`  - Available organizations: ${options}`);
        
        // Take screenshot
        await page.screenshot({ 
            path: '/var/www/Ausfallplan-Generator/schedule_add_with_org_selector.png' 
        });
        console.log('  - Screenshot saved\n');
    } else {
        console.log('❌ Organization selector NOT visible for system admin!\n');
        
        // Take screenshot anyway to debug
        await page.screenshot({ 
            path: '/var/www/Ausfallplan-Generator/schedule_add_no_selector.png' 
        });
    }
    
    // Check the form content
    const formContent = await page.locator('form').textContent();
    const hasOrgField = formContent.includes('Organization') || formContent.includes('organisation');
    
    console.log(`Form contains 'Organization' text: ${hasOrgField ? 'YES' : 'NO'}`);
    
    expect(orgSelector).toBeGreaterThan(0);
});
