// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('Navigation Visibility Tests', () => {
  
  test('should show navigation after login - Desktop', async ({ page }) => {
    // 1. Go to login page
    await page.goto('/users/login');
    
    // 2. Login with test user
    await page.fill('input[name="email"]', 'admin@example.com');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    
    // 3. Wait for dashboard to load
    await page.waitForURL(/dashboard/);
    await page.waitForSelector('.sidebar', { timeout: 5000 });
    
    // 4. Verify navigation elements are visible
    await expect(page.locator('.sidebar')).toBeVisible();
    await expect(page.locator('.user-avatar')).toBeVisible();
    await expect(page.locator('text=Dashboard')).toBeVisible();
    await expect(page.locator('text=Logout')).toBeVisible();
    await expect(page.locator('.hamburger')).toBeVisible();
    
    // 5. Take screenshot
    await page.screenshot({ 
      path: 'screenshots/navigation-desktop-logged-in.png',
      fullPage: true 
    });
    
    console.log('✅ Desktop navigation screenshot saved!');
  });

  test('should show hamburger menu on mobile', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });
    
    // Login
    await page.goto('/users/login');
    await page.fill('input[name="email"]', 'admin@example.com');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL(/dashboard/);
    await page.waitForSelector('.hamburger');
    
    // Screenshot with menu closed
    await page.screenshot({ 
      path: 'screenshots/navigation-mobile-closed.png' 
    });
    
    // Verify sidebar is hidden on mobile
    const sidebar = page.locator('.sidebar');
    const isHidden = await sidebar.evaluate(el => {
      const transform = window.getComputedStyle(el).transform;
      return transform.includes('matrix') || transform.includes('translate');
    });
    expect(isHidden).toBeTruthy();
    
    // Open hamburger menu
    await page.click('.hamburger');
    await page.waitForTimeout(500); // Wait for animation
    
    // Screenshot with menu open
    await page.screenshot({ 
      path: 'screenshots/navigation-mobile-open.png' 
    });
    
    // Verify menu is now visible
    await expect(page.locator('.sidebar.mobile-open')).toBeVisible();
    
    console.log('✅ Mobile navigation screenshots saved!');
  });

  test('should show user dropdown menu', async ({ page }) => {
    // Login
    await page.goto('/users/login');
    await page.fill('input[name="email"]', 'admin@example.com');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL(/dashboard/);
    
    // Click on user avatar
    await page.click('.user-avatar');
    await page.waitForTimeout(300);
    
    // Verify dropdown items
    await expect(page.locator('text=Settings')).toBeVisible();
    await expect(page.locator('text=My Account')).toBeVisible();
    await expect(page.locator('text=Logout')).toBeVisible();
    
    // Screenshot with dropdown open
    await page.screenshot({ 
      path: 'screenshots/user-dropdown-open.png' 
    });
    
    console.log('✅ User dropdown screenshot saved!');
  });

  test('should NOT show navigation on public pages', async ({ page }) => {
    // Visit login page
    await page.goto('/users/login');
    
    // Verify navigation is NOT visible
    const sidebar = page.locator('.sidebar');
    await expect(sidebar).not.toBeVisible();
    
    // Screenshot of login page (no navigation)
    await page.screenshot({ 
      path: 'screenshots/login-page-no-navigation.png',
      fullPage: true 
    });
    
    // Visit landing page
    await page.goto('/');
    
    // Verify navigation is NOT visible on landing page
    await expect(sidebar).not.toBeVisible();
    
    await page.screenshot({ 
      path: 'screenshots/landing-page-no-navigation.png',
      fullPage: true 
    });
    
    console.log('✅ Public pages screenshots saved!');
  });

  test('should show language switcher', async ({ page }) => {
    // Login
    await page.goto('/users/login');
    await page.fill('input[name="email"]', 'admin@example.com');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL(/dashboard/);
    
    // Find and hover language switcher
    const langSwitcher = page.locator('.language-switcher');
    await expect(langSwitcher).toBeVisible();
    await langSwitcher.hover();
    await page.waitForTimeout(300);
    
    // Screenshot with language dropdown
    await page.screenshot({ 
      path: 'screenshots/language-switcher.png' 
    });
    
    console.log('✅ Language switcher screenshot saved!');
  });
});
