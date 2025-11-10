/**
 * Language Switcher Test - Authenticated Area
 * 
 * Tests:
 * - Flag shows current language (DE when German, GB when English)
 * - Dropdown shows both languages
 * - Current language is highlighted and not clickable
 * - Other language is clickable
 * - Language switch works in both directions (DE->EN and EN->DE)
 * 
 * Run command:
 * timeout 120 npx playwright test tests/e2e/language-switcher-authenticated.spec.js --project=chromium --headed
 */

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
        
        // Check the flag shows DE (current language)
        const flagText1 = await page.locator('.language-flag').textContent();
        console.log('Flag shows:', flagText1);
        expect(flagText1.trim()).toBe('🇩🇪');
        
        // Click language switcher to open dropdown
        await page.click('.language-flag');
        await page.waitForTimeout(500);
        
        // Verify dropdown shows both languages
        const allOptions1 = await page.locator('.language-dropdown .language-option').allTextContents();
        console.log('Dropdown shows:', allOptions1);
        expect(allOptions1.length).toBe(2);
        
        // Verify Deutsch is active (highlighted, not clickable)
        const activeOption1 = await page.locator('.language-dropdown .language-option.active').textContent();
        console.log('Active (highlighted):', activeOption1);
        expect(activeOption1).toContain('Deutsch');
        
        // Verify English is clickable
        const clickableOption1 = await page.locator('.language-dropdown a.language-option').textContent();
        console.log('Clickable:', clickableOption1);
        expect(clickableOption1).toContain('English');
        
        // Click English option
        console.log('2. Switching to English...');
        await page.click('a:has-text("English")');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000);
        
        // Now flag should show GB (current language)
        const flagText2 = await page.locator('.language-flag').textContent();
        console.log('Flag shows:', flagText2);
        expect(flagText2.trim()).toBe('🇬🇧');
        
        // Click language switcher again
        await page.click('.language-flag');
        await page.waitForTimeout(500);
        
        // Verify dropdown shows both languages
        const allOptions2 = await page.locator('.language-dropdown .language-option').allTextContents();
        console.log('Dropdown shows:', allOptions2);
        expect(allOptions2.length).toBe(2);
        
        // Verify English is active (highlighted, not clickable)
        const activeOption2 = await page.locator('.language-dropdown .language-option.active').textContent();
        console.log('Active (highlighted):', activeOption2);
        expect(activeOption2).toContain('English');
        
        // Verify Deutsch is clickable
        const clickableOption2 = await page.locator('.language-dropdown a.language-option').textContent();
        console.log('Clickable:', clickableOption2);
        expect(clickableOption2).toContain('Deutsch');
        
        // Switch back to German
        console.log('3. Switching back to German...');
        await page.click('a:has-text("Deutsch")');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000);
        
        // Flag should show DE again
        const flagText3 = await page.locator('.language-flag').textContent();
        console.log('Flag shows:', flagText3);
        expect(flagText3.trim()).toBe('🇩🇪');
        
        // Click language switcher again
        await page.click('.language-flag');
        await page.waitForTimeout(500);
        
        // Verify Deutsch is active again
        const activeOption3 = await page.locator('.language-dropdown .language-option.active').textContent();
        console.log('Active (highlighted):', activeOption3);
        expect(activeOption3).toContain('Deutsch');
        
        // Verify English is clickable again
        const clickableOption3 = await page.locator('.language-dropdown a.language-option').textContent();
        console.log('Clickable:', clickableOption3);
        expect(clickableOption3).toContain('English');
        
        console.log('✅ Language switcher works correctly in both directions!');
    });
});
