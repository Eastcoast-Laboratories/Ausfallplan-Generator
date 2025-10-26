const { test, expect } = require('@playwright/test');

test('Language switcher works on registration page', async ({ page }) => {
    console.log('🧪 Testing Language Switcher on Registration\n');
    
    // Start at registration page
    await page.goto('http://localhost:8080/users/register');
    await page.waitForLoadState('networkidle');
    
    // Check initial language (should be German)
    let pageText = await page.textContent('body');
    let isGerman = pageText.includes('Neues Konto registrieren') || pageText.includes('Organisation');
    console.log(`Initial language is German: ${isGerman ? 'YES ✅' : 'NO ❌'}`);
    
    // Take screenshot
    await page.screenshot({ 
        path: '/var/www/Ausfallplan-Generator/register_german.png' 
    });
    console.log('📸 Screenshot 1: German version\n');
    
    // Click English flag
    await page.goto('http://localhost:8080/users/register?lang=en');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);
    
    // Check if language changed to English
    pageText = await page.textContent('body');
    let isEnglish = pageText.includes('Register New Account') || pageText.includes('Create your account');
    console.log(`After clicking EN flag, language is English: ${isEnglish ? 'YES ✅' : 'NO ❌'}`);
    
    // Take screenshot
    await page.screenshot({ 
        path: '/var/www/Ausfallplan-Generator/register_english.png' 
    });
    console.log('📸 Screenshot 2: English version\n');
    
    // Switch back to German
    await page.goto('http://localhost:8080/users/register?lang=de');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);
    
    pageText = await page.textContent('body');
    let backToGerman = pageText.includes('Neues Konto registrieren') || pageText.includes('Organisation');
    console.log(`After clicking DE flag, back to German: ${backToGerman ? 'YES ✅' : 'NO ❌'}`);
    
    // Take screenshot
    await page.screenshot({ 
        path: '/var/www/Ausfallplan-Generator/register_german_again.png' 
    });
    console.log('📸 Screenshot 3: German again\n');
    
    if (isGerman && isEnglish && backToGerman) {
        console.log('✅ Language switcher working perfectly!');
        expect(true).toBeTruthy();
    } else {
        console.log('❌ Language switcher not working properly');
        throw new Error('Language switcher failed');
    }
});
