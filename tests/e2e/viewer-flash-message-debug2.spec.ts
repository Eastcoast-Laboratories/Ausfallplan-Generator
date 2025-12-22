import { test, expect } from '@playwright/test';

test('Viewer Flash Message Debug - Keep Session', async ({ page }) => {
  // Login as viewer
  await page.goto('/users/login');
  await page.fill('input[name="email"]', 'a4@a.de');
  await page.fill('input[name="password"]', '84hbfUb_3dsf');
  await page.click('button[type="submit"]');
  
  // Wait for navigation to dashboard
  await page.waitForURL(/dashboard/, { timeout: 10000 });
  console.log('Logged in, URL:', page.url());
  
  // Now try to navigate to schedule add page (should stay logged in)
  console.log('Navigating to /schedules/add');
  await page.goto('/schedules/add');
  
  // Log current URL
  console.log('Current URL after /schedules/add:', page.url());
  
  // Check if we're on login page
  if (page.url().includes('/users/login')) {
    console.log('ERROR: Redirected to login page!');
    console.log('This means the viewer is not authenticated when accessing /schedules/add');
  }
  
  // Check if we're on schedules page
  if (page.url().includes('/schedules')) {
    console.log('SUCCESS: On schedules page');
    
    // Check for flash message
    const flashMessage = page.locator('[class*="flash"]');
    const flashCount = await flashMessage.count();
    console.log('Flash message elements:', flashCount);
    
    if (flashCount > 0) {
      const text = await flashMessage.first().textContent();
      console.log('Flash message text:', text);
    }
  }
  
  // Take screenshot
  await page.screenshot({ path: 'viewer-flash-debug2.png' });
});
