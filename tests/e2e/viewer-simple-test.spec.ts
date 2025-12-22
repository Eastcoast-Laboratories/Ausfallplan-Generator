import { test, expect } from '@playwright/test';

test('Viewer Simple Test - Check Authentication', async ({ page }) => {
  // Login as viewer
  // Credentials are stored in .env file (not in repo)
  // TEST_VIEWER_EMAIL=a4@a.de
  // TEST_VIEWER_PASSWORD=asbdasdaddd
  
  await page.goto('/users/login');
  console.log('On login page');
  
  const viewerEmail = process.env.TEST_VIEWER_EMAIL || 'a4@a.de';
  const viewerPassword = process.env.TEST_VIEWER_PASSWORD || '';
  
  await page.fill('input[name="email"]', viewerEmail);
  await page.fill('input[name="password"]', viewerPassword);
  await page.click('button[type="submit"]');
  
  // Wait for navigation
  await page.waitForTimeout(3000);
  console.log('After login, URL:', page.url());
  
  // Check if we're logged in
  if (page.url().includes('/users/login')) {
    console.log('ERROR: Still on login page after login attempt');
    return;
  }
  
  // Navigate to schedules index
  await page.goto('/schedules');
  console.log('On schedules index, URL:', page.url());
  
  // Navigate to schedules add
  await page.goto('/schedules/add');
  console.log('After /schedules/add, URL:', page.url());
  
  // Check if we're on login page
  if (page.url().includes('/users/login')) {
    console.log('ERROR: Redirected to login page when accessing /schedules/add');
  } else {
    console.log('SUCCESS: Still authenticated on /schedules/add');
  }
});
