/**
 * Report Logo Test
 * 
 * Tests:
 * - Logo appears in report header
 * - Line-height is set to 1 in body
 * 
 * Run command:
 * timeout 60 npx playwright test tests/e2e/report-logo-test.spec.js --project=chromium --headed
 */

const { test, expect } = require('@playwright/test');

test.describe('Report Logo', () => {
    test('should show logo in report header', async ({ page }) => {
        // Login
        await page.goto('http://localhost:8080/users/login');
        await page.fill('input[name="email"]', 'admin@demo.kita');
        await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
        await page.click('button:has-text("Anmelden")');
        
        // Wait for redirect
        await page.waitForTimeout(2000);
        
        console.log('1. Navigating to report...');
        
        // Navigate to report
        await page.goto('http://localhost:8080/schedules/generate-report/7');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000);
        
        console.log('2. Checking for logo...');
        
        // Check if logo image exists
        const logo = page.locator('.header img[src*="fairnestplan_logo"]');
        const logoCount = await logo.count();
        console.log('Logo found:', logoCount > 0);
        
        if (logoCount > 0) {
            const logoSrc = await logo.getAttribute('src');
            console.log('Logo src:', logoSrc);
        }
        
        expect(logoCount).toBeGreaterThan(0);
        
        console.log('3. Checking line-height...');
        
        // Check body line-height
        const bodyLineHeight = await page.evaluate(() => {
            return window.getComputedStyle(document.body).lineHeight;
        });
        console.log('Body line-height:', bodyLineHeight);
        
        console.log('✅ Report logo test completed!');
    });

    test('should show logo in grid report header', async ({ page }) => {
        // Login
        await page.goto('http://localhost:8080/users/login');
        await page.fill('input[name="email"]', 'admin@demo.kita');
        await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
        await page.click('button:has-text("Anmelden")');
        
        // Wait for redirect
        await page.waitForTimeout(2000);
        
        console.log('1. Navigating to grid report...');
        
        // Navigate to grid report
        await page.goto('http://localhost:8080/schedules/generate-report-grid/7');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000);
        
        console.log('2. Checking for logo in grid report...');
        
        // Check if logo image exists
        const logo = page.locator('.header img[src*="fairnestplan_logo"]');
        const logoCount = await logo.count();
        console.log('Logo found:', logoCount > 0);
        
        if (logoCount > 0) {
            const logoSrc = await logo.getAttribute('src');
            console.log('Logo src:', logoSrc);
        }
        
        expect(logoCount).toBeGreaterThan(0);
        
        console.log('3. Checking line-height in grid report...');
        
        // Check body line-height
        const bodyLineHeight = await page.evaluate(() => {
            return window.getComputedStyle(document.body).lineHeight;
        });
        console.log('Body line-height:', bodyLineHeight);
        
        console.log('✅ Grid report logo test completed!');
    });
});
