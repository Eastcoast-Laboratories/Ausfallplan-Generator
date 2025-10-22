// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('Language Switcher Tests', () => {
  
  // Helper function to login
  async function login(page) {
    await page.goto('/users/login');
    await page.fill('input[name="email"]', 'admin@example.com');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL(/dashboard/);
  }

  test('should display German flag by default', async ({ page }) => {
    // 1. Login
    await login(page);
    
    // 2. Verify German flag is shown
    const languageFlag = page.locator('.language-flag');
    await expect(languageFlag).toBeVisible();
    await expect(languageFlag).toContainText('🇩🇪');
    
    console.log('✅ German flag displayed by default');
  });

  test('should switch to English when clicking EN flag', async ({ page }) => {
    // 1. Login
    await login(page);
    
    // 2. Hover over language switcher to show dropdown
    await page.hover('.language-switcher');
    
    // 3. Wait for dropdown to appear
    await page.waitForSelector('.language-dropdown', { state: 'visible' });
    
    // Take screenshot before switch
    await page.screenshot({ 
      path: 'screenshots/language-switcher-dropdown.png' 
    });
    
    // 4. Click English option
    await page.click('.language-option:has-text("English")');
    
    // 5. Wait for page reload/redirect
    await page.waitForLoadState('networkidle');
    
    // 6. Verify English flag is now shown
    const languageFlag = page.locator('.language-flag');
    await expect(languageFlag).toContainText('🇬🇧');
    
    // Take screenshot after switch
    await page.screenshot({ 
      path: 'screenshots/language-switched-to-english.png' 
    });
    
    console.log('✅ Successfully switched to English');
  });

  test('should switch back to German', async ({ page }) => {
    // 1. Login and switch to English first
    await login(page);
    await page.hover('.language-switcher');
    await page.waitForSelector('.language-dropdown', { state: 'visible' });
    await page.click('.language-option:has-text("English")');
    await page.waitForLoadState('networkidle');
    
    // 2. Verify we're on English
    await expect(page.locator('.language-flag')).toContainText('🇬🇧');
    
    // 3. Switch back to German
    await page.hover('.language-switcher');
    await page.waitForSelector('.language-dropdown', { state: 'visible' });
    await page.click('.language-option:has-text("Deutsch")');
    await page.waitForLoadState('networkidle');
    
    // 4. Verify German flag is shown again
    const languageFlag = page.locator('.language-flag');
    await expect(languageFlag).toContainText('🇩🇪');
    
    console.log('✅ Successfully switched back to German');
  });

  test('should persist language choice across pages', async ({ page }) => {
    // 1. Login and switch to English
    await login(page);
    await page.hover('.language-switcher');
    await page.waitForSelector('.language-dropdown', { state: 'visible' });
    await page.click('.language-option:has-text("English")');
    await page.waitForLoadState('networkidle');
    
    // 2. Verify English is active
    await expect(page.locator('.language-flag')).toContainText('🇬🇧');
    
    // 3. Navigate to different page
    await page.click('.sidebar-nav-item:has-text("Children")');
    await page.waitForURL(/children/);
    
    // 4. Verify language is still English
    await expect(page.locator('.language-flag')).toContainText('🇬🇧');
    
    // 5. Navigate to another page
    await page.click('.sidebar-nav-item:has-text("Schedules")');
    await page.waitForURL(/schedules/);
    
    // 6. Language should still be English
    await expect(page.locator('.language-flag')).toContainText('🇬🇧');
    
    console.log('✅ Language persists across pages');
  });

  test('should show active language in dropdown', async ({ page }) => {
    // 1. Login (default German)
    await login(page);
    
    // 2. Hover to show dropdown
    await page.hover('.language-switcher');
    await page.waitForSelector('.language-dropdown', { state: 'visible' });
    
    // 3. German option should have 'active' class
    const germanOption = page.locator('.language-option:has-text("Deutsch")');
    await expect(germanOption).toHaveClass(/active/);
    
    // 4. English option should NOT have 'active' class
    const englishOption = page.locator('.language-option:has-text("English")');
    await expect(englishOption).not.toHaveClass(/active/);
    
    // 5. Switch to English
    await englishOption.click();
    await page.waitForLoadState('networkidle');
    
    // 6. Hover again
    await page.hover('.language-switcher');
    await page.waitForSelector('.language-dropdown', { state: 'visible' });
    
    // 7. Now English should be active
    const englishOptionAfter = page.locator('.language-option:has-text("English")');
    await expect(englishOptionAfter).toHaveClass(/active/);
    
    console.log('✅ Active language highlighted in dropdown');
  });

  test('should work on mobile view', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });
    
    // 1. Login
    await login(page);
    
    // 2. Language switcher should be visible even on mobile
    const languageFlag = page.locator('.language-flag');
    await expect(languageFlag).toBeVisible();
    
    // 3. Switch language
    await page.hover('.language-switcher');
    await page.waitForSelector('.language-dropdown', { state: 'visible' });
    await page.click('.language-option:has-text("English")');
    await page.waitForLoadState('networkidle');
    
    // 4. Verify switch worked
    await expect(languageFlag).toContainText('🇬🇧');
    
    // Take mobile screenshot
    await page.screenshot({ 
      path: 'screenshots/language-switcher-mobile.png',
      fullPage: true 
    });
    
    console.log('✅ Language switcher works on mobile');
  });
});
