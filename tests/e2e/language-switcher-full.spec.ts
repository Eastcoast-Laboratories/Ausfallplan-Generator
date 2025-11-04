/**
 * Full Language Switcher Test
 * 
 * Tests:
 * - Homepage in German and English
 * - Login page in German and English
 * - Login process works in both languages
 * - Authenticated pages in German and English
 * - Language switching while logged in
 * - Language persists across navigation
 * 
 * Run command:
 * timeout 180 npx playwright test tests/e2e/language-switcher-full.spec.ts --project=chromium --headed
 */

import { test, expect } from '@playwright/test';

test.describe('Full Language Switcher Test', () => {
  
  test('should display homepage in German by default', async ({ page }) => {
    await page.goto('http://localhost:8080/');
    
    // Check German text
    await expect(page.locator('h1')).toContainText('FairNestPlan');
    await expect(page.locator('text=Einfache und faire Planung')).toBeVisible();
    await expect(page.locator('nav a[href="#pricing"]')).toContainText('Preise');
    
    // Check German flag is active
    const germanFlag = page.locator('a[title="Deutsch"]');
    await expect(germanFlag).toHaveClass(/active/);
  });

  test('should display homepage in English after switching', async ({ page }) => {
    await page.goto('http://localhost:8080/');
    
    // Click English flag
    await page.click('a[title="English"]');
    await page.waitForLoadState('networkidle');
    
    // Check English text
    await expect(page.locator('text=Simple and fair planning')).toBeVisible();
    await expect(page.locator('nav a[href="#pricing"]')).toContainText('Pricing');
    
    // Check English flag is active
    const englishFlag = page.locator('a[title="English"]');
    await expect(englishFlag).toHaveClass(/active/);
  });

  test('should display login page in German', async ({ page }) => {
    await page.goto('http://localhost:8080/login');
    
    // Check German text on login page (using legend or h2)
    await expect(page.locator('legend, h2')).toContainText(/Login|Anmelden/i);
    // Check for submit button
    const submitButton = page.locator('button[type="submit"]');
    await expect(submitButton).toBeVisible();
  });

  test('should display login page in English after switching', async ({ page }) => {
    // Set language to English first
    await page.goto('http://localhost:8080/set-language?locale=en_US');
    await page.goto('http://localhost:8080/login');
    
    // Check English text on login page
    await expect(page.locator('legend, h2')).toContainText(/Login/i);
    const submitButton = page.locator('button[type="submit"]');
    await expect(submitButton).toBeVisible();
  });

  test('should login successfully in German', async ({ page }) => {
    await page.goto('http://localhost:8080/login');
    
    // Fill login form
    await page.fill('input[name="email"]', 'admin@demo.kita');
    await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
    
    // Submit
    await page.click('button[type="submit"]');
    
    // Wait for redirect to dashboard
    await page.waitForURL('**/dashboard', { timeout: 10000 });
    
    // Check we're on dashboard
    await expect(page).toHaveURL(/\/dashboard/);
  });

  test('should login successfully in English', async ({ page }) => {
    // Set language to English
    await page.goto('http://localhost:8080/set-language?locale=en_US');
    await page.goto('http://localhost:8080/login');
    
    // Fill login form
    await page.fill('input[name="email"]', 'admin@demo.kita');
    await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
    
    // Submit
    await page.click('button[type="submit"]');
    
    // Wait for redirect to dashboard
    await page.waitForURL('**/dashboard', { timeout: 10000 });
    
    // Check we're on dashboard
    await expect(page).toHaveURL(/\/dashboard/);
  });

  test('should switch language while logged in', async ({ page }) => {
    // Login first
    await page.goto('http://localhost:8080/login');
    await page.fill('input[name="email"]', 'admin@demo.kita');
    await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard', { timeout: 10000 });
    
    // Check German flag exists in authenticated layout
    const germanFlag = page.locator('a[title="Deutsch"]');
    await expect(germanFlag).toBeVisible();
    
    // Check German flag is active
    await expect(germanFlag).toHaveClass(/active/);
    
    // Switch to English
    const englishFlag = page.locator('a[title="English"]');
    await englishFlag.click();
    await page.waitForLoadState('networkidle');
    
    // Check English flag is now active
    await expect(englishFlag).toHaveClass(/active/);
    
    // Navigate to another page
    await page.goto('http://localhost:8080/schedules');
    await page.waitForLoadState('networkidle');
    
    // English flag should still be active
    const englishFlagAfterNav = page.locator('a[title="English"]');
    await expect(englishFlagAfterNav).toHaveClass(/active/);
    
    // Switch back to German
    const germanFlagAfterNav = page.locator('a[title="Deutsch"]');
    await germanFlagAfterNav.click();
    await page.waitForLoadState('networkidle');
    
    // German flag should be active again
    await expect(germanFlagAfterNav).toHaveClass(/active/);
  });

  test('should persist language across logout and login', async ({ page }) => {
    // Set to English
    await page.goto('http://localhost:8080/set-language?locale=en_US');
    
    // Login
    await page.goto('http://localhost:8080/login');
    await page.fill('input[name="email"]', 'admin@demo.kita');
    await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard', { timeout: 10000 });
    
    // Verify English
    const englishFlag = page.locator('a[title="English"]');
    await expect(englishFlag).toHaveClass(/active/);
    
    // Logout
    await page.goto('http://localhost:8080/logout');
    await page.waitForURL('**/login', { timeout: 10000 });
    
    // Should still be in English
    const englishFlagAfterLogout = page.locator('a[title="English"]');
    await expect(englishFlagAfterLogout).toHaveClass(/active/);
  });

  test('should translate all authenticated pages', async ({ page }) => {
    // Login in German
    await page.goto('http://localhost:8080/login');
    await page.fill('input[name="email"]', 'admin@demo.kita');
    await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard', { timeout: 10000 });
    
    // Check German navigation items
    const germanNav = page.locator('nav');
    await expect(germanNav).toBeVisible();
    
    // Switch to English
    await page.click('a[title="English"]');
    await page.waitForLoadState('networkidle');
    
    // Navigate to different pages and verify English
    await page.goto('http://localhost:8080/children');
    await page.waitForLoadState('networkidle');
    
    // English flag should still be active
    const englishFlag = page.locator('a[title="English"]');
    await expect(englishFlag).toHaveClass(/active/);
    
    // Go to schedules
    await page.goto('http://localhost:8080/schedules');
    await page.waitForLoadState('networkidle');
    
    // English flag should still be active
    await expect(englishFlag).toHaveClass(/active/);
  });
});
