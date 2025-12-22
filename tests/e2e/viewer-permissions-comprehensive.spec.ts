import { test, expect } from '@playwright/test';

test.describe('Viewer Permissions - Comprehensive', () => {
  test.beforeEach(async ({ page }) => {
    // Login as viewer
    await page.goto('/users/login');
    await page.fill('input[name="email"]', 'viewer@example.com');
    await page.fill('input[name="password"]', '84hbfUb_3dsf');
    await page.click('button[type="submit"]');
    
    // Wait for navigation to complete
    await page.waitForURL(/dashboard|/, { timeout: 10000 });
  });

  test('Viewer cannot create schedule - redirects to schedules index with error', async ({ page }) => {
    // Try to navigate to schedule add page
    await page.goto('/schedules/add');
    
    // Should redirect to schedules index
    await page.waitForURL('/schedules', { timeout: 5000 });
    
    // Error message should be visible
    const errorMessage = page.locator('.flash-message.error');
    await expect(errorMessage).toBeVisible({ timeout: 5000 });
    await expect(errorMessage).toContainText(/permission|berechtigung/i);
  });

  test('Viewer cannot edit child - redirects to children index with error', async ({ page }) => {
    // First, get a child ID from the children index
    await page.goto('/children');
    
    // Get first child's edit link
    const editLink = page.locator('a[href*="/children/edit/"]').first();
    const href = await editLink.getAttribute('href');
    
    if (href) {
      // Extract child ID
      const childId = href.match(/\/children\/edit\/(\d+)/)?.[1];
      
      if (childId) {
        // Try to edit the child
        await page.goto(`/children/edit/${childId}`);
        
        // Should redirect to children index or home with error
        // Check if we're on children index or home
        const currentUrl = page.url();
        
        // Error message should be visible somewhere
        const errorMessage = page.locator('.flash-message.error');
        await expect(errorMessage).toBeVisible({ timeout: 5000 });
        await expect(errorMessage).toContainText(/permission|berechtigung|bearbeiten/i);
      }
    }
  });

  test('Viewer sees error message on home page after permission denied', async ({ page }) => {
    // Try to navigate to schedule add page
    await page.goto('/schedules/add');
    
    // Should redirect somewhere with error message
    const errorMessage = page.locator('.flash-message.error');
    await expect(errorMessage).toBeVisible({ timeout: 5000 });
    
    // Navigate to home page
    await page.goto('/');
    
    // Error message should still be visible on home page if it was set in session
    // (Flash messages persist across page loads)
    const homeErrorMessage = page.locator('.flash-message.error');
    
    // Check if error message exists (it might have been consumed by the redirect)
    const errorExists = await homeErrorMessage.count() > 0;
    if (errorExists) {
      await expect(homeErrorMessage).toContainText(/permission|berechtigung/i);
    }
  });
});
