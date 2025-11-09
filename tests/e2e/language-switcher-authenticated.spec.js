const { test, expect } = require('@playwright/test');

test.describe('Language Switcher - Authenticated', () => {
    test('should switch between German and English in authenticated area', async ({ page }) => {
        // Login
        await page.goto('http://localhost:8080/users/login');
        await page.fill('input[name="email"]', 'admin@demo.kita');
        await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
        await page.click('button:has-text("Anmelden")');
        
        // Wait for redirect
        await page.waitForTimeout(3000);
        
        // Should be in German by default
        console.log('1. Checking German is active...');
        
        // Check the flag shows GB (because we can switch TO English)
        const flagText1 = await page.locator('.language-flag').textContent();
        console.log('Flag shows:', flagText1);
        expect(flagText1.trim()).toBe('🇬🇧');
        
        // Click language switcher to open dropdown
        await page.click('.language-flag');
        await page.waitForTimeout(500);
        
        // Click English option
        console.log('2. Switching to English...');
        await page.click('a:has-text("English")');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000);
        
        // Now flag should show DE (because we can switch TO German)
        const flagText2 = await page.locator('.language-flag').textContent();
        console.log('Flag shows:', flagText2);
        expect(flagText2.trim()).toBe('🇩🇪');
        
        console.log('✅ First language switch works correctly!');
    });
});
