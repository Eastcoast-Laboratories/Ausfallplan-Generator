const { test, expect } = require('@playwright/test');

/**
 * TEST: Two siblings visible in waitlist
 * 
 * Verifies that linked siblings are both visible in the waitlist
 * and have correct sibling group badges.
 */
test.describe('Sibling Group Visibility', () => {
    test('should show two linked siblings in waitlist', async ({ page }) => {
    console.log('\n🎯 === FINAL VERIFICATION: Two Linked Siblings ===\n');
    
    // Login
    await page.goto('http://localhost:8080/users/login');
    await page.fill('input[name="email"]', 'admin@demo.kita');
    await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    // Navigate to waitlist
    console.log('📍 Loading waitlist page...');
    await page.goto('http://localhost:8080/waitlist?schedule_id=3');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    
    // Find the first two siblings (N. Storch and A. Seehund)
    console.log('\n🔍 Searching for N. Storch and A. Seehund...\n');
    
    // Find N. Storch
    const storch = await page.locator('.waitlist-item:has-text("N. Storch")').first();
    const storchExists = await storch.count() > 0;
    console.log(`N. Storch found: ${storchExists ? '✅' : '❌'}`);
    
    if (storchExists) {
        const storchBadge = await storch.locator('a:has-text("Geschwister")').first();
        const storchHasBadge = await storchBadge.count() > 0;
        console.log(`N. Storch has badge: ${storchHasBadge ? '✅' : '❌'}`);
        
        if (storchHasBadge) {
            const storchTitle = await storchBadge.getAttribute('title');
            console.log(`N. Storch badge title: "${storchTitle}"`);
            
            // Highlight it
            await storchBadge.evaluate(el => {
                el.style.border = '3px solid red';
                el.style.boxShadow = '0 0 10px red';
            });
        }
    }
    
    // Find A. Seehund
    const seehund = await page.locator('.waitlist-item:has-text("A. Seehund")').first();
    const seehundExists = await seehund.count() > 0;
    console.log(`\nA. Seehund found: ${seehundExists ? '✅' : '❌'}`);
    
    if (seehundExists) {
        const seehundBadge = await seehund.locator('a:has-text("Geschwister")').first();
        const seehundHasBadge = await seehundBadge.count() > 0;
        console.log(`A. Seehund has badge: ${seehundHasBadge ? '✅' : '❌'}`);
        
        if (seehundHasBadge) {
            const seehundTitle = await seehundBadge.getAttribute('title');
            console.log(`A. Seehund badge title: "${seehundTitle}"`);
            
            // Highlight it
            await seehundBadge.evaluate(el => {
                el.style.border = '3px solid blue';
                el.style.boxShadow = '0 0 10px blue';
            });
        }
    }
    
    // Take screenshot with highlights
    await page.screenshot({ 
        path: '/var/www/Ausfallplan-Generator/PROOF_siblings_linked.png', 
        fullPage: true 
    });
    console.log('\n📸 Screenshot saved: PROOF_siblings_linked.png');
    
    // Count all badges
    const allBadges = await page.locator('a:has-text("Geschwister")').all();
    console.log(`\n📊 Total sibling badges found: ${allBadges.length}`);
    
    // Verify we have at least 2 badges
    expect(allBadges.length).toBeGreaterThanOrEqual(2);
    
    // Verify the two specific siblings
    if (storchExists && seehundExists) {
        const storchBadgeCount = await storch.locator('a:has-text("Geschwister")').count();
        const seehundBadgeCount = await seehund.locator('a:has-text("Geschwister")').count();
        
        console.log('\n✅ VERIFICATION RESULT:');
        console.log(`   N. Storch has sibling badge: ${storchBadgeCount > 0 ? 'YES ✅' : 'NO ❌'}`);
        console.log(`   A. Seehund has sibling badge: ${seehundBadgeCount > 0 ? 'YES ✅' : 'NO ❌'}`);
        
        if (storchBadgeCount > 0 && seehundBadgeCount > 0) {
            console.log('\n🎉 SUCCESS! Two siblings are correctly linked and visible!');
        } else {
            throw new Error('NOT ALL SIBLINGS HAVE BADGES!');
        }
    } else {
        throw new Error('Could not find N. Storch or A. Seehund in waitlist!');
    }
    
    console.log('\n=== TEST COMPLETE ===\n');
    });
});
