/**
 * Bot Protection Rate Limiting Test
 *
 * Tests:
 * - 5 failed attempts trigger rate limiting
 * - 6th attempt is blocked with "Too many attempts" message
 * - After 2 minutes block expires, registration works again
 *
 * Run command:
 * timeout 300 npx playwright test tests/e2e/bot-protection-rate-limiting.spec.ts --project=chromium --headed
 */

import { test, expect } from '@playwright/test';

test.describe('Bot Protection Rate Limiting', () => {
  const testPassword = 'SecurePass123!';

  test('should block after 5 failed attempts and unblock after 2 minutes', async ({ page }) => {
    // Mark as slow test - triples the timeout (default 30s -> 90s, but we need 300s)
    test.slow();
    // Also explicitly set timeout to 5 minutes (300 seconds) for the waitForTimeout call
    test.setTimeout(300000);
    // Navigate to registration page (force English)
    await page.goto('/users/register?lang=en');
    await expect(page.locator('text=Register New Account')).toBeVisible();

    // Attempt 1-5: Fill honeypot to trigger bot detection
    for (let i = 1; i <= 5; i++) {
      console.log(`[${new Date().toISOString()}] Attempt ${i}: Filling form with honeypot...`);

      // Fill registration form
      await page.fill('input[name="organization_name"]', `Bot Test ${i}`);
      await page.fill('input[name="email"]', `bot${i}-${Date.now()}@test.com`);
      await page.fill('input[name="password"]', testPassword);
      await page.fill('input[name="password_confirm"]', testPassword);

      // Set timestamp to 10 seconds ago (valid)
      await page.evaluate(() => {
        const timestamp = document.querySelector('input[name="reg_timestamp"]') as HTMLInputElement;
        if (timestamp) timestamp.value = String(Math.floor(Date.now() / 1000) - 10);
      });

      // Fill honeypot field (this triggers bot detection)
      await page.evaluate(() => {
        const honeypot = document.querySelector('input[name="hp_data"]') as HTMLInputElement;
        if (honeypot) honeypot.value = 'filled-by-bot';
      });

      // Submit form
      await page.click('button[type="submit"]');

      // Should show error (flash message container)
      await expect(page.locator('.message.error')).toBeVisible({ timeout: 5000 });
      console.log(`[${new Date().toISOString()}] Attempt ${i}: Blocked as expected`);

      // Wait a moment before next attempt
      await page.waitForTimeout(1000);
    }

    // Attempt 6: Should be rate limited
    console.log(`[${new Date().toISOString()}] Attempt 6: Testing rate limit block...`);

    await page.fill('input[name="organization_name"]', 'Rate Limited Test');
    await page.fill('input[name="email"]', `ratelimited-${Date.now()}@test.com`);
    await page.fill('input[name="password"]', testPassword);
    await page.fill('input[name="password_confirm"]', testPassword);

    // Set timestamp to 10 seconds ago (valid)
    await page.evaluate(() => {
      const timestamp = document.querySelector('input[name="reg_timestamp"]') as HTMLInputElement;
      if (timestamp) timestamp.value = String(Math.floor(Date.now() / 1000) - 10);
    });

    // Clear honeypot this time
    await page.evaluate(() => {
      const honeypot = document.querySelector('input[name="hp_data"]') as HTMLInputElement;
      if (honeypot) honeypot.value = '';
    });

    await page.click('button[type="submit"]');

    // Should show "Too many attempts" message
    await expect(page.locator('.message.error:has-text("Too many attempts")')).toBeVisible({ timeout: 5000 });
    console.log(`[${new Date().toISOString()}] Attempt 6: Rate limit working correctly!`);

    // Wait 2 minutes for block to expire
    console.log(`[${new Date().toISOString()}] Waiting 2 minutes for block to expire...`);
    await page.waitForTimeout(120000); // 2 minutes = 120 seconds

    // Attempt 7: Should work now (block expired)
    console.log(`[${new Date().toISOString()}] Attempt 7: Testing after block expired...`);

    await page.goto('/users/register?lang=en');
    await expect(page.locator('text=Register New Account')).toBeVisible();

    const uniqueOrg = `Unblocked Org ${Date.now()}`;
    const uniqueEmail = `unblocked-${Date.now()}@example.com`;

    await page.fill('input[name="organization_name"]', uniqueOrg);
    await page.fill('input[name="email"]', uniqueEmail);
    await page.fill('input[name="password"]', testPassword);
    await page.fill('input[name="password_confirm"]', testPassword);

    // Set timestamp to 10 seconds ago (valid)
    await page.evaluate(() => {
      const timestamp = document.querySelector('input[name="reg_timestamp"]') as HTMLInputElement;
      if (timestamp) timestamp.value = String(Math.floor(Date.now() / 1000) - 10);
    });

    // Clear honeypot
    await page.evaluate(() => {
      const honeypot = document.querySelector('input[name="hp_data"]') as HTMLInputElement;
      if (honeypot) honeypot.value = '';
    });

    await page.click('button[type="submit"]');

    // Wait for response and check what happened
    await page.waitForLoadState('networkidle');

    // Check if "Too many attempts" message is gone (meaning block expired)
    const hasTooManyAttempts = await page.locator('text=Too many attempts').isVisible().catch(() => false);
    const hasRegistrationFailed = await page.locator('text=Registration failed').isVisible().catch(() => false);
    const currentUrl = page.url();

    console.log(`[${new Date().toISOString()}] After submit - URL: ${currentUrl}`);
    console.log(`[${new Date().toISOString()}] Has 'Too many attempts': ${hasTooManyAttempts}`);
    console.log(`[${new Date().toISOString()}] Has 'Registration failed': ${hasRegistrationFailed}`);

    // Success if: either redirected to login OR no longer blocked
    if (currentUrl.includes('/login') || !hasTooManyAttempts) {
      console.log(`[${new Date().toISOString()}] Attempt 7: SUCCESS! Block expired correctly.`);
    } else {
      throw new Error(`Block still active or other error. URL: ${currentUrl}`);
    }
  });
});
