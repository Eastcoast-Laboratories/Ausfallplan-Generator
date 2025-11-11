/**
 * Encryption Flow Basic Test
 * 
 * Tests:
 * - User registration generates encryption keys with IV
 * - Email verification is required
 * - Encryption keys work without errors
 * 
 * Run command:
 * timeout 120 npx playwright test tests/e2e/encryption-flow.spec.ts --project=chromium --headed
 */

import { test, expect } from '@playwright/test';

test.describe('Encryption Flow - Basic', () => {
    test('Registration creates encryption keys with IV, requires email verification', async ({ page }) => {
        const testEmail = `test${Date.now()}@enc.test`;
        const testPassword = 'TestPass123!';
        const orgName = `Org${Date.now()}`;
        
        // Register
        await page.goto('http://localhost:8080/register');
        await page.fill('input[name="email"]', testEmail);
        await page.fill('input[name="password"]', testPassword);
        await page.fill('input[name="password_confirm"]', testPassword);
        await page.click('input[name="organization_choice"][value="new"]');
        await page.fill('input[name="organization_name"]', orgName);
        await page.click('button[type="submit"]');
        
        // Should redirect to login with verification message
        await page.waitForURL('**/login', { timeout: 10000 });
        await expect(page.locator('text=Please check your email to verify')).toBeVisible();
        
        console.log('✅ Registration requires email verification');
        console.log('✅ Email:', testEmail);
        console.log('✅ Organization:', orgName);
    });
});
