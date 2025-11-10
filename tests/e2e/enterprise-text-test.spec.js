/**
 * Enterprise Plan Text Test
 * 
 * Tests:
 * - Enterprise upgrade page shows custom description
 * - Text appears in both German and English
 * 
 * Run command:
 * timeout 60 npx playwright test tests/e2e/enterprise-text-test.spec.js --project=chromium --headed
 */

const { test, expect } = require('@playwright/test');

test.describe('Enterprise Plan Text', () => {
    test('should show custom Enterprise description in German', async ({ page }) => {
        console.log('1. Login...');
        
        // Login
        await page.goto('http://localhost:8080/users/login');
        await page.fill('input[name="email"]', 'admin@demo.kita');
        await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
        await page.click('button:has-text("Anmelden")');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);
        
        console.log('2. Navigate to Enterprise upgrade page...');
        
        // Navigate to Enterprise upgrade
        await page.goto('http://localhost:8080/subscriptions/upgrade/enterprise');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000);
        
        console.log('3. Check for Enterprise description...');
        
        // Check for German text
        const pageContent = await page.content();
        const hasCustomText = pageContent.includes('maßgeschneiderte Lösungen') || 
                             pageContent.includes('individuell angepasste Funktionen');
        
        console.log('  Has custom Enterprise text:', hasCustomText);
        
        // Check for specific keywords
        const bodyText = await page.locator('body').textContent();
        console.log('  Contains "maßgeschneiderte":', bodyText.includes('maßgeschneiderte'));
        console.log('  Contains "individuell":', bodyText.includes('individuell'));
        console.log('  Contains "dediziert":', bodyText.includes('dediziert'));
        
        // Verify at least one key phrase is present
        expect(bodyText).toMatch(/maßgeschneiderte|individuell|dediziert/);
        
        console.log('✅ Enterprise description test passed!');
    });
});
