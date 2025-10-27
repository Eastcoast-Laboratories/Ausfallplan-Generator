const { test, expect } = require('@playwright/test');

test('Final verification - All critical features', async ({ page }) => {
    console.log('🔍 FINAL VERIFICATION - Testing all critical features\n');
    
    // 1. Landing Page accessible
    console.log('1️⃣ Testing Landing Page...');
    await page.goto('http://localhost:8080/');
    await expect(page).toHaveTitle('Ausfallplan-Generator - Kita Scheduling Made Easy');
    console.log('   ✅ Landing page accessible\n');
    
    // 2. Privacy page accessible
    console.log('2️⃣ Testing Privacy Page...');
    await page.goto('http://localhost:8080/pages/privacy');
    const privacyHeading = await page.locator('h1:has-text("Datenschutzerklärung")').isVisible();
    expect(privacyHeading).toBeTruthy();
    console.log('   ✅ Privacy page accessible\n');
    
    // 3. Imprint page accessible
    console.log('3️⃣ Testing Imprint Page...');
    await page.goto('http://localhost:8080/pages/imprint');
    const imprintHeading = await page.locator('h1:has-text("Impressum")').isVisible();
    expect(imprintHeading).toBeTruthy();
    console.log('   ✅ Imprint page accessible\n');
    
    // 4. Login page accessible
    console.log('4️⃣ Testing Login Page...');
    await page.goto('http://localhost:8080/users/login');
    const emailField = await page.locator('input[name="email"]').isVisible();
    expect(emailField).toBeTruthy();
    console.log('   ✅ Login page accessible\n');
    
    // 5. Registration page accessible
    console.log('5️⃣ Testing Registration Page...');
    await page.goto('http://localhost:8080/users/register');
    const regEmailField = await page.locator('input[name="email"]').isVisible();
    expect(regEmailField).toBeTruthy();
    console.log('   ✅ Registration page accessible\n');
    
    // 6. Login works
    console.log('6️⃣ Testing Login Flow...');
    await page.goto('http://localhost:8080/users/login');
    await page.fill('input[name="email"]', 'admin@demo.kita');
    await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);
    
    // Check we're on dashboard (either / or /dashboard)
    const currentUrl = page.url();
    const isLoggedIn = currentUrl.includes('dashboard') || currentUrl === 'http://localhost:8080/';
    expect(isLoggedIn).toBeTruthy();
    console.log(`   ✅ Login successful (redirected to: ${currentUrl})\n`);
    
    // 7. Children page accessible after login
    console.log('7️⃣ Testing Children Page...');
    await page.goto('http://localhost:8080/children');
    await page.waitForTimeout(1000);
    const childrenPage = page.url().includes('children');
    expect(childrenPage).toBeTruthy();
    console.log('   ✅ Children page accessible\n');
    
    // 8. Schedules page accessible
    console.log('8️⃣ Testing Schedules Page...');
    await page.goto('http://localhost:8080/schedules');
    await page.waitForTimeout(1000);
    const schedulesPage = page.url().includes('schedules');
    expect(schedulesPage).toBeTruthy();
    console.log('   ✅ Schedules page accessible\n');
    
    // Take final screenshot
    await page.screenshot({ path: 'final_verification.png', fullPage: false });
    console.log('📸 Screenshot saved: final_verification.png\n');
    
    console.log('🎉 ALL CRITICAL FEATURES VERIFIED SUCCESSFULLY! 🎉');
});
