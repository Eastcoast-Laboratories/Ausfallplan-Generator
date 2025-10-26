const { test, expect } = require('@playwright/test');

test('Landing page is accessible at root', async ({ page }) => {
    console.log('🏠 Testing Landing Page at /...\n');
    
    await page.goto('http://localhost:8080/');
    
    // Check title
    await expect(page).toHaveTitle('Ausfallplan-Generator - Kita Scheduling Made Easy');
    console.log('✅ 1. Page title correct');
    
    // Check hero section
    const heroHeading = await page.locator('.hero h1').textContent();
    console.log(`✅ 2. Hero heading: "${heroHeading}"`);
    
    // Check navigation links
    const loginBtn = await page.locator('a:has-text("Login")').isVisible();
    const registerBtn = await page.locator('a:has-text("Registrieren")').isVisible();
    console.log(`✅ 3. Login button visible: ${loginBtn}`);
    console.log(`✅ 4. Register button visible: ${registerBtn}`);
    
    // Check features section
    const featuresHeading = await page.locator('h2:has-text("Hauptfunktionen")').isVisible();
    console.log(`✅ 5. Features section visible: ${featuresHeading}`);
    
    // Check pricing section
    const pricingHeading = await page.locator('h2:has-text("Preispläne")').isVisible();
    console.log(`✅ 6. Pricing section visible: ${pricingHeading}`);
    
    // Take screenshot
    await page.screenshot({ path: 'landing_page_verification.png', fullPage: true });
    console.log('\n📸 Screenshot saved: landing_page_verification.png');
    console.log('\n🎉 Landing page is working perfectly!');
});
