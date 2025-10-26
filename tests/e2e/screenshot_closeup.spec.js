const { test, expect } = require('@playwright/test');

test('Screenshot closeup of sibling badges', async ({ page }) => {
    // Login
    await page.goto('http://localhost:8080/users/login');
    await page.fill('input[name="email"]', 'admin@demo.kita');
    await page.fill('input[name="password"]', 'asbdasdaddd');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    // Navigate to waitlist
    await page.goto('http://localhost:8080/waitlist?schedule_id=3');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    
    // Find N. Storch
    const storch = await page.locator('.waitlist-item:has-text("N. Storch")').first();
    
    // Scroll to it and take closeup
    await storch.scrollIntoViewIfNeeded();
    await page.waitForTimeout(500);
    
    const storchBox = await storch.boundingBox();
    if (storchBox) {
        await page.screenshot({ 
            path: '/var/www/Ausfallplan-Generator/storch_closeup.png',
            clip: {
                x: Math.max(0, storchBox.x - 20),
                y: Math.max(0, storchBox.y - 20),
                width: Math.min(storchBox.width + 40, 800),
                height: storchBox.height + 40
            }
        });
        console.log('📸 N. Storch closeup saved');
    }
    
    // Find A. Seehund
    const seehund = await page.locator('.waitlist-item:has-text("A. Seehund")').first();
    await seehund.scrollIntoViewIfNeeded();
    await page.waitForTimeout(500);
    
    const seehundBox = await seehund.boundingBox();
    if (seehundBox) {
        await page.screenshot({ 
            path: '/var/www/Ausfallplan-Generator/seehund_closeup.png',
            clip: {
                x: Math.max(0, seehundBox.x - 20),
                y: Math.max(0, seehundBox.y - 20),
                width: Math.min(seehundBox.width + 40, 800),
                height: seehundBox.height + 40
            }
        });
        console.log('📸 A. Seehund closeup saved');
    }
    
    // Get text content of both items
    const storchText = await storch.textContent();
    const seehundText = await seehund.textContent();
    
    console.log('\n📋 N. Storch content:');
    console.log(storchText);
    
    console.log('\n📋 A. Seehund content:');
    console.log(seehundText);
});
