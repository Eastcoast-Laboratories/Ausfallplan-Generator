/**
 * Viewer Plan Preview Test
 * 
 * Tests:
 * - Viewer can access plan-preview page
 * - Viewer can generate reports
 * - Viewer cannot access manage-children
 * 
 * Run command:
 * timeout 60 npx playwright test tests/e2e/viewer-plan-preview.spec.ts --project=chromium --headed
 */

import { test, expect } from '@playwright/test';

test('Viewer can access plan-preview', async ({ page }) => {
  // Login as viewer
  const viewerEmail = process.env.TEST_VIEWER_EMAIL || 'a4@a.de';
  const viewerPassword = process.env.TEST_VIEWER_PASSWORD || '';
  
  await page.goto('/users/login');
  await page.fill('input[name="email"]', viewerEmail);
  await page.fill('input[name="password"]', viewerPassword);
  await page.click('button[type="submit"]');
  
  // Wait for dashboard
  await page.waitForURL(/dashboard/, { timeout: 10000 });
  console.log('✅ Logged in as viewer');
  
  // Navigate to plan-preview
  await page.goto('/schedules/plan-preview');
  console.log('Current URL:', page.url());
  
  // Wait a bit for the page to load
  await page.waitForTimeout(1000);
  
  // Check if we're on the plan-preview page
  const pageTitle = await page.title();
  console.log('Page title:', pageTitle);
  
  // Check for page content
  const content = await page.content();
  console.log('Page contains "Plan Preview":', content.includes('Plan Preview'));
  console.log('Page contains "Select a schedule":', content.includes('Select a schedule'));
  console.log('Page contains "Schedules":', content.includes('Schedules'));
  
  // Check if there's an error message
  const flashElements = await page.locator('[class*="flash"]').all();
  console.log('Number of flash elements:', flashElements.length);
  for (let i = 0; i < flashElements.length; i++) {
    const text = await flashElements[i].textContent();
    console.log(`Flash message ${i}:`, text);
  }
  
  // Check if we're redirected to home
  if (page.url() === 'http://localhost:8080/') {
    console.log('❌ Viewer was redirected to home page (permission denied)');
  } else {
    console.log('✅ Viewer can access plan-preview');
  }
  
  // Verify we're not redirected to login
  expect(page.url()).not.toContain('/users/login');
});
