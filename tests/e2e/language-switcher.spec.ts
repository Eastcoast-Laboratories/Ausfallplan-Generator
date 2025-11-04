/**
 * Language Switcher Test
 * 
 * Tests:
 * - Homepage loads in German by default
 * - Can switch to English via flag
 * - Can switch back to German via flag
 * - Language persists in session
 * - All main texts are translated correctly
 * 
 * Run command:
 * timeout 120 npx playwright test tests/e2e/language-switcher.spec.ts --project=chromium --headed
 */

import { test, expect } from '@playwright/test';

test.describe('Language Switcher', () => {
  test('should load homepage in German by default', async ({ page }) => {
    await page.goto('http://localhost:8080/');
    
    // Check German text
    await expect(page.locator('h1')).toContainText('FairNestPlan');
    await expect(page.locator('text=Einfache und faire Planung')).toBeVisible();
    await expect(page.locator('nav a[href="#features"]')).toBeVisible();
    await expect(page.locator('nav a[href="#pricing"]')).toContainText('Preise');
    
    // Check German flag is active
    const germanFlag = page.locator('a[title="Deutsch"]');
    await expect(germanFlag).toHaveClass(/active/);
  });

  test('should switch to English when clicking GB flag', async ({ page }) => {
    await page.goto('http://localhost:8080/');
    
    // Click English flag
    await page.click('a[title="English"]');
    
    // Wait for page reload
    await page.waitForLoadState('networkidle');
    
    // Check English text
    await expect(page.locator('text=Simple and fair planning')).toBeVisible();
    await expect(page.locator('text=Pricing')).toBeVisible();
    
    // Check English flag is active
    const englishFlag = page.locator('a[title="English"]');
    await expect(englishFlag).toHaveClass(/active/);
  });

  test('should switch back to German when clicking DE flag', async ({ page }) => {
    // Start in English
    await page.goto('http://localhost:8080/set-language?locale=en_US');
    await page.goto('http://localhost:8080/');
    
    // Verify we're in English
    await expect(page.locator('text=Simple and fair planning')).toBeVisible();
    
    // Click German flag
    await page.click('a[title="Deutsch"]');
    
    // Wait for page reload
    await page.waitForLoadState('networkidle');
    
    // Check German text is back
    await expect(page.locator('text=Einfache und faire Planung')).toBeVisible();
    await expect(page.locator('text=Preise')).toBeVisible();
  });

  test('should persist language in session', async ({ page }) => {
    // Switch to English
    await page.goto('http://localhost:8080/set-language?locale=en_US');
    await page.goto('http://localhost:8080/');
    
    // Verify English
    await expect(page.locator('text=Simple and fair planning')).toBeVisible();
    
    // Navigate to another page (if logged in)
    // For now, just reload homepage
    await page.reload();
    
    // Should still be in English
    await expect(page.locator('text=Simple and fair planning')).toBeVisible();
  });

  test('should translate all main sections', async ({ page }) => {
    // Test German
    await page.goto('http://localhost:8080/');
    await expect(page.locator('text=Hauptfunktionen')).toBeVisible();
    await expect(page.locator('text=Preispläne')).toBeVisible();
    await expect(page.locator('.hero .btn-primary').first()).toContainText('Kostenlos registrieren');
    
    // Switch to English
    await page.click('a[title="English"]');
    await page.waitForLoadState('networkidle');
    
    // Test English
    await expect(page.locator('text=Key Features')).toBeVisible();
    await expect(page.locator('text=Pricing Plans')).toBeVisible();
    await expect(page.locator('.hero .btn-primary').first()).toContainText('Register for free');
  });
});
