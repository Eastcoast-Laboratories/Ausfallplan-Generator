const { test, expect } = require('@playwright/test');

test('Verify all new features', async ({ page }) => {
    console.log('🧪 VERIFYING ALL NEW FEATURES\n');
    
    // === LOGIN ===
    await page.goto('http://localhost:8080/users/login');
    await page.fill('input[name="email"]', 'admin@demo.kita');
    await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    console.log('✅ 1. Logged in as system admin\n');
    
    // === FEATURE 1: ORGANIZATION SELECTOR ===
    console.log('📋 Testing Feature 1: Organization Selector in Schedule Add\n');
    
    await page.goto('http://localhost:8080/schedules/add');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);
    
    // Check if organization selector exists
    const orgSelectorCount = await page.locator('select[name="organization_id"]').count();
    console.log(`   Organization selector found: ${orgSelectorCount > 0 ? 'YES ✅' : 'NO ❌'}`);
    
    if (orgSelectorCount > 0) {
        const options = await page.locator('select[name="organization_id"] option').count();
        console.log(`   Available organizations: ${options}`);
        
        // Get first option text
        const firstOption = await page.locator('select[name="organization_id"] option').first().textContent();
        console.log(`   First option: "${firstOption}"`);
    }
    
    expect(orgSelectorCount).toBeGreaterThan(0);
    console.log('   ✅ Organization selector works!\n');
    
    // === FEATURE 2: GENDER & BIRTHDATE IN CHILDREN LIST ===
    console.log('📋 Testing Feature 2: Gender & Birthdate Display\n');
    
    await page.goto('http://localhost:8080/children');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);
    
    // Check for gender column header
    const hasGenderHeader = await page.locator('th:has-text("Geschlecht")').count() > 0;
    console.log(`   Gender column header: ${hasGenderHeader ? 'YES ✅' : 'NO ❌'}`);
    
    // Check for birthdate column header
    const hasBirthdateHeader = await page.locator('th:has-text("Geburtsdatum")').count() > 0;
    console.log(`   Birthdate column header: ${hasBirthdateHeader ? 'YES ✅' : 'NO ❌'}`);
    
    // Check if there are any children
    const childRows = await page.locator('tbody tr').count();
    console.log(`   Children in list: ${childRows}`);
    
    if (childRows > 0) {
        // Check for gender symbols
        const hasGenderSymbols = await page.locator('td:has-text("♂️"), td:has-text("♀️"), td:has-text("❓")').count() > 0;
        console.log(`   Gender symbols visible: ${hasGenderSymbols ? 'YES ✅' : 'NO ❌'}`);
        
        // Check for dates in format dd.mm.yyyy
        const hasDates = await page.locator('td', { hasText: /\d{2}\.\d{2}\.\d{4}/ }).count() > 0;
        console.log(`   Birthdates visible: ${hasDates ? 'YES ✅' : 'NO ❌'}`);
        
        // Check for age display (number in parentheses)
        const hasAge = await page.locator('td', { hasText: /\(\d+\)/ }).count() > 0;
        console.log(`   Age calculation visible: ${hasAge ? 'YES ✅' : 'NO ❌'}`);
        
        expect(hasGenderSymbols || hasDates).toBeTruthy();
    } else {
        console.log('   ⚠️  No children in database yet - skipping symbol checks');
    }
    
    expect(hasGenderHeader).toBeTruthy();
    expect(hasBirthdateHeader).toBeTruthy();
    console.log('   ✅ Children display works!\n');
    
    // === TAKE FINAL SCREENSHOTS ===
    await page.goto('http://localhost:8080/schedules/add');
    await page.waitForLoadState('networkidle');
    await page.screenshot({ 
        path: '/var/www/Ausfallplan-Generator/verification_org_selector.png',
        fullPage: false 
    });
    console.log('📸 Screenshot 1 saved: verification_org_selector.png');
    
    await page.goto('http://localhost:8080/children');
    await page.waitForLoadState('networkidle');
    await page.screenshot({ 
        path: '/var/www/Ausfallplan-Generator/verification_children_list.png',
        fullPage: true 
    });
    console.log('📸 Screenshot 2 saved: verification_children_list.png');
    
    console.log('\n🎉 ALL FEATURES VERIFIED SUCCESSFULLY! 🎉');
});
