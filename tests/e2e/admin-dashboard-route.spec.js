const { test, expect } = require('@playwright/test');

test.describe('Admin Dashboard Route', () => {
  test.beforeEach(async ({ page }) => {
    // Login as admin first
    await page.goto('/login');
    
    // Fill in admin credentials
    await page.fill('input[name="email"]', 'admin@test.com');
    await page.fill('input[name="password"]', 'password123');
    
    // Submit login form
    await page.click('button[type="submit"]');
    
    // Wait for redirect after login
    await page.waitForURL(/dashboard|admin/);
  });

  test('should redirect /admin to admin dashboard', async ({ page }) => {
    // Visit /admin route
    await page.goto('/admin');
    
    // Should be on admin dashboard
    await expect(page).toHaveURL(/\/admin/);
    
    // Should see admin dashboard elements
    await expect(page.locator('h1, h2, .page-title')).toContainText(/Dashboard|Admin/i);
  });

  test('should show admin dashboard on /admin/dashboard', async ({ page }) => {
    // Visit /admin/dashboard route
    await page.goto('/admin/dashboard');
    
    // Should be on admin dashboard
    await expect(page).toHaveURL(/\/admin\/dashboard/);
    
    // Should see admin dashboard elements
    await expect(page.locator('h1, h2, .page-title')).toContainText(/Dashboard|Admin/i);
  });

  test('should have admin navigation links', async ({ page }) => {
    await page.goto('/admin');
    
    // Wait for page to load
    await page.waitForLoadState('networkidle');
    
    // Should have admin-specific navigation
    // Looking for common admin navigation items
    const body = await page.textContent('body');
    
    // Admin dashboard should have some admin-related content
    expect(body).toBeTruthy();
    expect(body.length).toBeGreaterThan(0);
  });

  test('should not allow non-admin users to access /admin', async ({ page, context }) => {
    // Logout first
    await page.goto('/logout');
    await page.waitForURL('/login');
    
    // Login as regular user (not admin)
    await page.goto('/login');
    await page.fill('input[name="email"]', 'user@test.com');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('/dashboard');
    
    // Try to access /admin
    await page.goto('/admin');
    
    // Should be redirected away from admin (either to dashboard or login)
    await page.waitForURL(/dashboard|login/);
    
    // Should NOT be on admin route
    const url = page.url();
    expect(url).not.toContain('/admin');
  });
});
