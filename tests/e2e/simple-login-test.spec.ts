/**
 * Simple Login Test
 * 
 * Tests basic login without encryption to verify auth works
 * 
 * Run command:
 * timeout 60 npx playwright test tests/e2e/simple-login-test.spec.ts --project=chromium --headed
 */

import { test, expect } from '@playwright/test';

test.describe('Simple Login Test', () => {
    test('should login successfully with basic user', async ({ page }) => {
        const timestamp = Date.now();
        const testEmail = `simple-test-${timestamp}@example.com`;
        const testPassword = 'TestPassword123!';
        const testOrgName = `Simple Org ${timestamp}`;

        console.log('=== Step 1: Register ===');
        await page.goto('http://localhost:8080/users/register');
        
        // Select "new organization"
        await page.selectOption('#organization-choice', 'new');
        await page.waitForTimeout(300);
        
        // Fill form WITHOUT triggering encryption
        await page.fill('#organization-name-input', testOrgName);
        await page.fill('input[name="email"]', testEmail);
        await page.fill('input[name="password"]', testPassword);
        await page.fill('input[name="password_confirm"]', testPassword);
        
        // Submit WITHOUT waiting for encryption
        await page.evaluate(() => {
            // Disable encryption script
            const scripts = document.querySelectorAll('script');
            scripts.forEach(s => {
                if (s.textContent && s.textContent.includes('OrgEncryption')) {
                    s.remove();
                }
            });
        });
        
        await Promise.all([
            page.waitForURL(/\/(users\/)?login/, { timeout: 10000 }),
            page.click('button[type="submit"]')
        ]);
        
        console.log('✅ User registered');
        
        console.log('=== Step 2: Login ===');
        await page.fill('input[name="email"]', testEmail);
        await page.fill('input[name="password"]', testPassword);
        
        await page.click('button[type="submit"]');
        
        // Wait for redirect
        await page.waitForTimeout(3000);
        
        const currentUrl = page.url();
        console.log('Current URL:', currentUrl);
        
        const isDashboard = currentUrl.includes('dashboard') || currentUrl.includes('organizations');
        
        if (!isDashboard) {
            const pageContent = await page.content();
            console.log('Page HTML:', pageContent.substring(0, 500));
            throw new Error(`Login failed - unexpected URL: ${currentUrl}`);
        }
        
        console.log('✅ Login successful');
    });
});
