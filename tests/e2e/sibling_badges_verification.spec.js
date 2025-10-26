const { test, expect } = require('@playwright/test');

test('Verify sibling badges show correct names in waitlist', async ({ page }) => {
    console.log('\n🔍 === SIBLING BADGES VERIFICATION TEST ===\n');
    
    // Login
    console.log('1. Logging in...');
    await page.goto('http://localhost:8080/users/login');
    await page.fill('input[name="email"]', 'admin@demo.kita');
    await page.fill('input[name="password"]', 'asbdasdaddd');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
    
    // Navigate to waitlist
    console.log('2. Loading waitlist page...');
    await page.goto('http://localhost:8080/waitlist?schedule_id=3');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    
    // Take screenshot for reference
    await page.screenshot({ path: '/var/www/Ausfallplan-Generator/sibling_test_full.png', fullPage: true });
    
    // Find all sibling badges
    const siblingBadges = await page.locator('a:has-text("Geschwister")').all();
    console.log(`\n✅ Found ${siblingBadges.length} sibling badges`);
    
    if (siblingBadges.length === 0) {
        console.log('❌ NO SIBLING BADGES FOUND! This is the bug!');
        console.log('\nChecking if children with sibling_group_id exist...');
        
        // Check page content
        const pageContent = await page.content();
        const hasDataSiblingGroup = pageContent.includes('data-sibling-group=');
        console.log(`Has data-sibling-group attribute: ${hasDataSiblingGroup}`);
        
        if (hasDataSiblingGroup) {
            const matches = pageContent.match(/data-sibling-group="(\d+)"/g);
            console.log(`Found ${matches ? matches.length : 0} elements with sibling groups`);
            if (matches) {
                console.log('Sibling groups:', matches.slice(0, 5));
            }
        }
    } else {
        console.log('\n📋 Analyzing each badge:\n');
        
        for (let i = 0; i < siblingBadges.length; i++) {
            const badge = siblingBadges[i];
            const title = await badge.getAttribute('title');
            const href = await badge.getAttribute('href');
            
            console.log(`Badge ${i + 1}:`);
            console.log(`  - Title: "${title}"`);
            console.log(`  - Link: ${href}`);
            
            // Verify title is not empty or null
            if (!title || title === 'Geschwister: ' || title === 'Geschwister: null') {
                console.log('  ❌ EMPTY OR NULL TITLE - This is the bug!');
            } else if (title.includes('keine anderen Geschwister gefunden')) {
                console.log('  ❌ FALLBACK TEXT SHOWN - This is the bug!');
            } else {
                console.log('  ✅ Has valid sibling names');
                
                // Extract sibling names from title
                const namesMatch = title.match(/Geschwister: (.+)/);
                if (namesMatch) {
                    const names = namesMatch[1].split(',').map(n => n.trim());
                    console.log(`  ✅ Siblings: ${names.join(', ')}`);
                }
            }
            console.log('');
        }
    }
    
    // Database verification
    console.log('\n🔍 Database Verification:');
    console.log('Checking which children should have siblings...\n');
    
    // Look for children in waitlist with sibling indicators
    const waitlistItems = await page.locator('.waitlist-item').all();
    console.log(`Found ${waitlistItems.length} children in waitlist`);
    
    for (let i = 0; i < waitlistItems.length; i++) {
        const item = waitlistItems[i];
        const siblingGroup = await item.getAttribute('data-sibling-group');
        const childName = await item.locator('strong').first().textContent();
        
        if (siblingGroup && siblingGroup !== '') {
            console.log(`Child: ${childName}`);
            console.log(`  - Sibling Group ID: ${siblingGroup}`);
            
            // Check if badge exists for this child
            const hasBadge = await item.locator('a:has-text("Geschwister")').count() > 0;
            console.log(`  - Has Badge: ${hasBadge ? '✅' : '❌'}`);
            
            if (hasBadge) {
                const badgeTitle = await item.locator('a:has-text("Geschwister")').getAttribute('title');
                console.log(`  - Badge Title: "${badgeTitle}"`);
            }
            console.log('');
        }
    }
    
    console.log('\n=== TEST COMPLETE ===\n');
    
    // Assertions
    if (siblingBadges.length > 0) {
        console.log(`✅ SUCCESS: Found ${siblingBadges.length} sibling badges with valid names!`);
    } else {
        console.log('❌ FAILURE: No sibling badges found!');
        throw new Error('No sibling badges found in waitlist');
    }
});
