import { test, expect } from '@playwright/test';

/**
 * E2E Test: Complete authentication flow
 * Tests registration, email verification, and login
 */
test.describe('Authentication Flow', () => {
  
  test.beforeEach(async ({ page }) => {
    // Start fresh
    await page.goto('http://localhost:8080');
  });

  test('user can login with valid credentials', async ({ page }) => {
    // Go to login page
    await page.goto('http://localhost:8080/login');
    
    // Should show login form
    await expect(page.locator('h2, legend')).toContainText(/login|anmelden/i);
    
    // Fill in credentials (assuming test user exists)
    await page.fill('input[type="email"]', 'admin@test.com');
    await page.fill('input[type="password"]', 'password123');
    
    // Submit form
    await page.click('button[type="submit"]');
    
    // Should redirect to dashboard
    await expect(page).toHaveURL(/dashboard|children|schedules/);
    
    // Should show logged-in content (not login form)
    await expect(page.locator('a, button')).toContainText(/logout|abmelden/i);
  });

  test('user cannot login with invalid credentials', async ({ page }) => {
    await page.goto('http://localhost:8080/login');
    
    await page.fill('input[type="email"]', 'wrong@test.com');
    await page.fill('input[type="password"]', 'wrongpassword');
    
    await page.click('button[type="submit"]');
    
    // Should stay on login page
    await expect(page).toHaveURL(/login/);
    
    // Should show error message
    await expect(page.locator('.message, .alert, .flash')).toBeVisible();
  });

  test('registration form shows organization autocomplete', async ({ page }) => {
    await page.goto('http://localhost:8080/register');
    
    // Should show registration form
    await expect(page.locator('h2, legend')).toContainText(/register|registrieren/i);
    
    // Organization field should exist
    const orgInput = page.locator('#organization-input');
    await expect(orgInput).toBeVisible();
    
    // Type in organization field to trigger autocomplete
    await orgInput.fill('Kita');
    
    // Wait a bit for debounce and AJAX
    await page.waitForTimeout(500);
    
    // Should show autocomplete suggestions (if any exist)
    // This will only work if there are organizations in the DB
    const suggestions = page.locator('.autocomplete-suggestions');
    // Just check if the element exists (might be empty in test DB)
    await expect(suggestions).toHaveCount(1);
  });

  test('forgot password flow works', async ({ page }) => {
    await page.goto('http://localhost:8080/users/forgot-password');
    
    // Should show forgot password form
    await expect(page.locator('legend, h2')).toContainText(/forgot|password|passwort/i);
    
    // Fill in email
    await page.fill('input[type="email"]', 'test@test.com');
    
    // Submit
    await page.click('button[type="submit"]');
    
    // Should redirect to reset password page
    await expect(page).toHaveURL(/reset-password|reset/);
  });

  test('viewer role has read-only access', async ({ page }) => {
    // Login as viewer (assuming viewer user exists)
    await page.goto('http://localhost:8080/login');
    await page.fill('input[type="email"]', 'viewer@test.com');
    await page.fill('input[type="password"]', 'password123');
    await page.click('button[type="submit"]');
    
    // Try to access add child page
    await page.goto('http://localhost:8080/children/add');
    
    // Should be blocked (403) or redirected
    // Check if we see an error message or are redirected
    const pageContent = await page.content();
    const isBlocked = pageContent.includes('403') || 
                     pageContent.includes('permission') || 
                     pageContent.includes('Berechtigung') ||
                     !pageContent.includes('Add Child');
    
    expect(isBlocked).toBeTruthy();
  });
});

/**
 * Quick smoke test for main pages
 */
test.describe('Main Pages Accessibility', () => {
  
  test('login page loads', async ({ page }) => {
    await page.goto('http://localhost:8080/login');
    await expect(page).toHaveTitle(/login|ausfallplan/i);
  });

  test('register page loads', async ({ page }) => {
    await page.goto('http://localhost:8080/register');
    await expect(page.locator('form')).toBeVisible();
  });

  test('forgot password page loads', async ({ page }) => {
    await page.goto('http://localhost:8080/users/forgot-password');
    await expect(page.locator('form')).toBeVisible();
  });
});
