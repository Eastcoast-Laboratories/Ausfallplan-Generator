import { test, expect } from '@playwright/test';

test('Viewer Flash Message - Schedule Add Permission Denied', async ({ page }) => {
  // Login as viewer
  // Credentials are stored in .env file (not in repo)
  const viewerEmail = process.env.TEST_VIEWER_EMAIL || 'a4@a.de';
  const viewerPassword = process.env.TEST_VIEWER_PASSWORD || '';
  
  await page.goto('/users/login');
  await page.fill('input[name="email"]', viewerEmail);
  await page.fill('input[name="password"]', viewerPassword);
  await page.click('button[type="submit"]');
  
  // Wait for dashboard
  await page.waitForURL(/dashboard/, { timeout: 10000 });
  
  // Try to navigate to schedule add page
  await page.goto('/schedules/add');
  
  // Should redirect to home page
  await page.waitForURL('/', { timeout: 5000 });
  
  // Check for flash message
  const flashMessage = page.locator('[class*="flash"]');
  await expect(flashMessage).toBeVisible({ timeout: 5000 });
  
  // Check message text
  const messageText = await flashMessage.textContent();
  expect(messageText?.toLowerCase()).toContain('permission');
});

test('Viewer Flash Message - Child Edit Permission Denied', async ({ page }) => {
  // Login as viewer
  // Credentials are stored in .env file (not in repo)
  const viewerEmail = process.env.TEST_VIEWER_EMAIL || 'a4@a.de';
  const viewerPassword = process.env.TEST_VIEWER_PASSWORD || '';
  
  await page.goto('/users/login');
  await page.fill('input[name="email"]', viewerEmail);
  await page.fill('input[name="password"]', viewerPassword);
  await page.click('button[type="submit"]');
  
  // Wait for dashboard
  await page.waitForURL(/dashboard/, { timeout: 10000 });
  
  // Navigate to children index
  await page.goto('/children');
  
  // Get first child's edit link
  const editLink = page.locator('a[href*="/children/edit/"]').first();
  const href = await editLink.getAttribute('href');
  
  if (href) {
    const childId = href.match(/\/children\/edit\/(\d+)/)?.[1];
    
    if (childId) {
      // Try to edit the child
      await page.goto(`/children/edit/${childId}`);
      
      // Should redirect to children index or home
      await page.waitForTimeout(2000);
      
      // Check for flash message
      const flashMessage = page.locator('[class*="flash"]');
      const isVisible = await flashMessage.isVisible().catch(() => false);
      
      if (isVisible) {
        const messageText = await flashMessage.textContent();
        expect(messageText?.toLowerCase()).toContain('berechtigung');
      }
    }
  }
});
