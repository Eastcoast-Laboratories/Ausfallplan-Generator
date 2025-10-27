const { test, expect } = require('@playwright/test');

test('Debug login flow', async ({ page }) => {
    console.log('🔍 Testing login flow...\n');
    
    // Go to login page
    await page.goto('http://localhost:8080/users/login');
    console.log('✅ 1. Login page loaded');
    
    // Fill credentials
    await page.fill('input[name="email"]', 'admin@demo.kita');
    await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
    console.log('✅ 2. Credentials filled');
    
    // Click submit and wait
    await page.click('button[type="submit"]');
    console.log('✅ 3. Submit clicked');
    
    // Wait a bit
    await page.waitForTimeout(2000);
    
    // Check current URL
    const currentUrl = page.url();
    console.log(`📍 4. Current URL: ${currentUrl}`);
    
    // Check page title
    const title = await page.title();
    console.log(`📄 5. Page title: "${title}"`);
    
    // Check if there's an error message
    const errorMessage = await page.locator('.message.error').count();
    if (errorMessage > 0) {
        const errorText = await page.locator('.message.error').textContent();
        console.log(`❌ Error message found: "${errorText}"`);
    } else {
        console.log('✅ No error message');
    }
    
    // Check if navigation is visible
    const navCount = await page.locator('nav.sidebar').count();
    console.log(`📋 6. Navigation visible: ${navCount > 0 ? 'YES' : 'NO'}`);
    
    // Take screenshot
    await page.screenshot({ path: 'debug_login_result.png', fullPage: true });
    console.log('📸 Screenshot saved: debug_login_result.png');
});
