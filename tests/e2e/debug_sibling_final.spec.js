const { test, expect } = require('@playwright/test');

test('Extract debug info from waitlist page', async ({ page }) => {
    // Login
    await page.goto('http://localhost:8080/users/login');
    await page.fill('input[name="email"]', 'admin@demo.kita');
    await page.fill('input[name="password"]', 'asbdasdaddd');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
    
    // Go to waitlist
    console.log('\n🔍 Loading waitlist page...');
    await page.goto('http://localhost:8080/waitlist?schedule_id=3');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
    
    // Extract debug info
    console.log('\n📊 === DEBUG INFO FROM PAGE ===\n');
    
    // Check for siblingNames array
    const siblingNamesDiv = await page.locator('div:has-text("DEBUG: siblingNames Array")').first();
    if (await siblingNamesDiv.count() > 0) {
        const siblingNamesText = await siblingNamesDiv.textContent();
        console.log('🟨 SIBLING NAMES ARRAY:');
        console.log(siblingNamesText);
        console.log('');
    }
    
    // Check for controller debug info
    const debugInfoDiv = await page.locator('div:has-text("DEBUG: Controller Debug Info")').first();
    const errorDiv = await page.locator('div:has-text("ERROR: $debugInfo")').first();
    const orangeDiv = await page.locator('div:has-text("$debugInfo is SET but EMPTY")').first();
    
    if (await debugInfoDiv.count() > 0) {
        const debugInfoText = await debugInfoDiv.textContent();
        console.log('🟡 CONTROLLER DEBUG INFO:');
        console.log(debugInfoText);
        console.log('');
    } else if (await orangeDiv.count() > 0) {
        console.log('🟠 $debugInfo is SET but EMPTY!');
    } else if (await errorDiv.count() > 0) {
        console.log('❌ $debugInfo NOT SET AT ALL!');
    } else {
        console.log('❌ NO CONTROLLER DEBUG INFO DIVS FOUND!');
        // Show all divs to debug
        const allDivs = await page.locator('div[style*="background"]').all();
        console.log(`\nFound ${allDivs.length} divs with background style`);
        for (let i = 0; i < Math.min(5, allDivs.length); i++) {
            const text = await allDivs[i].textContent();
            console.log(`Div ${i+1}: ${text.substring(0, 100)}...`);
        }
    }
    
    // Check for waitlist entries with sibling groups
    const entriesDiv = await page.locator('div:has-text("DEBUG: Waitlist Entries with Sibling Groups")').first();
    if (await entriesDiv.count() > 0) {
        const entriesText = await entriesDiv.textContent();
        console.log('🔵 WAITLIST ENTRIES:');
        console.log(entriesText);
        console.log('');
    }
    
    // Check for actual sibling badges
    const siblingBadges = await page.locator('a:has-text("Geschwister")').all();
    console.log(`\n👨‍👩‍👧 Found ${siblingBadges.length} sibling badges`);
    
    for (let i = 0; i < siblingBadges.length; i++) {
        const badge = siblingBadges[i];
        const title = await badge.getAttribute('title');
        console.log(`  Badge ${i + 1}: "${title}"`);
    }
    
    console.log('\n=== END DEBUG INFO ===\n');
    
    // Take screenshot for visual inspection
    await page.screenshot({ path: '/var/www/Ausfallplan-Generator/waitlist-debug.png', fullPage: true });
    console.log('📸 Screenshot saved to waitlist-debug.png');
});
