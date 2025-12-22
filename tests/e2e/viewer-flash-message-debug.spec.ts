import { test, expect } from '@playwright/test';

test('Viewer Flash Message Debug - Schedule Add', async ({ page }) => {
  // Login as viewer
  await page.goto('/users/login');
  await page.fill('input[name="email"]', 'a4@a.de');
  await page.fill('input[name="password"]', '84hbfUb_3dsf');
  await page.click('button[type="submit"]');
  
  // Wait for navigation
  await page.waitForURL(/dashboard|/, { timeout: 10000 });
  
  // Try to navigate to schedule add page
  console.log('Navigating to /schedules/add');
  await page.goto('/schedules/add');
  
  // Log current URL
  console.log('Current URL:', page.url());
  
  // Wait a bit for redirect
  await page.waitForTimeout(2000);
  
  // Log current URL again
  console.log('URL after wait:', page.url());
  
  // Check page content
  const pageContent = await page.content();
  console.log('Page contains "flash-message":', pageContent.includes('flash-message'));
  console.log('Page contains "error":', pageContent.includes('error'));
  console.log('Page contains "permission":', pageContent.toLowerCase().includes('permission'));
  console.log('Page contains "berechtigung":', pageContent.toLowerCase().includes('berechtigung'));
  
  // Take screenshot
  await page.screenshot({ path: 'viewer-flash-debug.png' });
  
  // Check for flash message elements
  const flashElements = await page.locator('[class*="flash"]').count();
  console.log('Flash elements found:', flashElements);
  
  // Check for error messages
  const errorElements = await page.locator('[class*="error"]').count();
  console.log('Error elements found:', errorElements);
  
  // Log all text content
  const bodyText = await page.locator('body').textContent();
  console.log('Body text (first 500 chars):', bodyText?.substring(0, 500));
});
