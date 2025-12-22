import { test, expect } from '@playwright/test';

test.describe('Viewer Schedule Creation Permission', () => {
  test.beforeEach(async ({ page }) => {
    // Login as viewer (using credentials from organization-permissions test)
    await page.goto('/users/login');
    await page.fill('input[name="email"]', 'viewer@example.com');
    await page.fill('input[name="password"]', '84hbfUb_3dsf');
    await page.click('button[type="submit"]');
    
    // Wait for navigation to complete
    await page.waitForURL(/dashboard|/, { timeout: 10000 });
  });

  test('Viewer cannot create schedule and sees error message', async ({ page }) => {
    // Try to navigate to schedule add page
    await page.goto('/schedules/add');
    
    // Should be redirected to home page with error message
    // Check for error flash message on the page
    const errorMessage = page.locator('.flash-message.error');
    await expect(errorMessage).toBeVisible({ timeout: 5000 });
    
    // Verify the error message contains the expected text
    await expect(errorMessage).toContainText(/permission|berechtigung/i);
  });

  test('Viewer sees error message on home page after attempting to create schedule', async ({ page }) => {
    // Try to navigate to schedule add page
    await page.goto('/schedules/add');
    
    // Should redirect to home page
    await page.waitForURL('/', { timeout: 5000 });
    
    // Error message should be visible on home page
    const errorMessage = page.locator('.flash-message.error');
    await expect(errorMessage).toBeVisible({ timeout: 5000 });
    await expect(errorMessage).toContainText(/permission|berechtigung|create schedules|erstellen/i);
  });
});
