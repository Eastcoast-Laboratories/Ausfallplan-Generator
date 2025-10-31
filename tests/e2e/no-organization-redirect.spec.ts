/**
 * No Organization Redirect Test
 * 
 * Tests:
 * - User without organization can login
 * - Gets redirected to organizations page
 * - Can see "Add Organization" button
 * - No infinite redirect loop
 * 
 * Run command:
 * npx playwright test tests/e2e/no-organization-redirect.spec.ts --project=chromium --headed
 */

import { test, expect } from '@playwright/test';

test('user without organization can login and access organizations page', async ({ page }) => {
  // Go to login page
  await page.goto('http://localhost:8765/users/login');
  
  // Login with a user that has no organization
  // We'll create a test user or use existing one
  await page.fill('input[name="email"]', 'test-no-org@example.com');
  await page.fill('input[name="password"]', 'test123456');
  await page.click('button[type="submit"]');
  
  // Should redirect to organizations page (not logout, not infinite loop)
  await page.waitForURL('**/admin/organizations**', { timeout: 5000 });
  
  // Should see info message
  const infoMessage = page.locator('.message.info, .flash-message.info');
  await expect(infoMessage).toBeVisible();
  await expect(infoMessage).toContainText(/Organisation/i);
  
  // Should see "Add Organization" or "New Organization" button
  const addButton = page.locator('a[href*="organizations/add"], button:has-text("Organisation")');
  await expect(addButton).toBeVisible();
  
  // Verify we're still logged in (not logged out)
  const logoutLink = page.locator('a[href*="logout"]');
  await expect(logoutLink).toBeVisible();
  
  // Verify no redirect loop by checking URL doesn't change for 2 seconds
  const currentUrl = page.url();
  await page.waitForTimeout(2000);
  expect(page.url()).toBe(currentUrl);
});

test('organizations page is accessible without organization', async ({ page }) => {
  // Direct access to organizations page
  await page.goto('http://localhost:8765/admin/organizations');
  
  // Should either show login or organizations page (not error)
  await page.waitForLoadState('networkidle');
  
  // Check we don't have an error page
  const errorHeading = page.locator('h1:has-text("Error"), h2:has-text("Error")');
  await expect(errorHeading).not.toBeVisible();
});
