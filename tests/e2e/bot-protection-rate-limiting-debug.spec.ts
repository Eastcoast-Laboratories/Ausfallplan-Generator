/**
 * Bot Protection Rate Limiting Debug Test
 *
 * Tests:
 * - Check if session persists between requests
 * - Check if rate limiting counter works
 *
 * Run command:
 * timeout 60 npx playwright test tests/e2e/bot-protection-rate-limiting-debug.spec.ts --project=chromium --headed
 */

import { test, expect } from '@playwright/test';

test.describe('Bot Protection Rate Limiting Debug', () => {
  const testPassword = 'SecurePass123!';

  test('should maintain session between requests', async ({ page }) => {
    // Navigate to registration page
    await page.goto('/users/register?lang=en');
    await expect(page.locator('text=Register New Account')).toBeVisible();

    // Attempt 1: Fill honeypot to trigger bot detection
    console.log(`[${new Date().toISOString()}] Attempt 1: Filling form with honeypot...`);

    await page.fill('input[name="organization_name"]', 'Debug Test 1');
    await page.fill('input[name="email"]', `debug1-${Date.now()}@test.com`);
    await page.fill('input[name="password"]', testPassword);
    await page.fill('input[name="password_confirm"]', testPassword);

    // Fill honeypot field
    await page.evaluate(() => {
      const honeypot = document.querySelector('input[name="hp_data"]') as HTMLInputElement;
      if (honeypot) honeypot.value = 'filled-by-bot';
    });

    await page.click('button[type="submit"]');

    // Check what error message appears
    const errorText = await page.locator('.message.error').textContent();
    console.log(`[${new Date().toISOString()}] Error message: ${errorText}`);

    await page.waitForTimeout(1000);

    // Attempt 2: Same session, should increment counter
    console.log(`[${new Date().toISOString()}] Attempt 2: Filling form again...`);

    await page.fill('input[name="organization_name"]', 'Debug Test 2');
    await page.fill('input[name="email"]', `debug2-${Date.now()}@test.com`);
    await page.fill('input[name="password"]', testPassword);
    await page.fill('input[name="password_confirm"]', testPassword);

    await page.evaluate(() => {
      const honeypot = document.querySelector('input[name="hp_data"]') as HTMLInputElement;
      if (honeypot) honeypot.value = 'filled-by-bot';
    });

    await page.click('button[type="submit"]');

    const errorText2 = await page.locator('.message.error').textContent();
    console.log(`[${new Date().toISOString()}] Error message 2: ${errorText2}`);

    // Log all cookies to check session
    const cookies = await page.context().cookies();
    console.log(`[${new Date().toISOString()}] Cookies:`, cookies.map(c => c.name));

    // Check if we can access session data via JS
    const sessionData = await page.evaluate(() => {
      return {
        userAgent: navigator.userAgent,
        cookies: document.cookie,
      };
    });
    console.log(`[${new Date().toISOString()}] Session data:`, sessionData);
  });
});
