// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('Registration and Login Flow', () => {
  
  test('should register a new user and then login successfully', async ({ page }) => {
    const timestamp = Date.now();
    const testEmail = `testuser${timestamp}@example.com`;
    const testPassword = 'SecurePassword123!';

    // Step 1: Navigate to registration page
    await page.goto('/users/register');
    
    // Step 2: Fill in registration form
    await page.waitForSelector('form');
    
    // Select organization (first option after empty)
    await page.selectOption('select[name="organization_id"]', { index: 1 });
    
    // Fill in email and password
    await page.fill('input[name="email"]', testEmail);
    await page.fill('input[name="password"]', testPassword);
    
    // Select role (viewer is default, but let's explicitly select it)
    await page.selectOption('select[name="role"]', 'viewer');
    
    // Take screenshot before submission
    await page.screenshot({ 
      path: `screenshots/registration-form-filled-${timestamp}.png`,
      fullPage: true 
    });
    
    console.log(`✅ Registration form filled with email: ${testEmail}`);
    
    // Step 3: Submit registration form
    await page.click('button[type="submit"]');
    
    // Wait for redirect (either to login or dashboard)
    await page.waitForURL(/\/(users\/login|dashboard)/, { timeout: 10000 });
    
    const currentUrl = page.url();
    console.log(`✅ Redirected to: ${currentUrl}`);
    
    // Take screenshot after registration
    await page.screenshot({ 
      path: `screenshots/after-registration-${timestamp}.png`,
      fullPage: true 
    });
    
    // Step 4: If we're not on login page, go there
    if (!currentUrl.includes('/users/login')) {
      console.log('⚠️  Not on login page, navigating there...');
      await page.goto('/users/login');
    }
    
    // Step 5: Login with the newly created user
    console.log(`🔐 Attempting to login with: ${testEmail}`);
    
    await page.waitForSelector('form');
    await page.fill('input[name="email"]', testEmail);
    await page.fill('input[name="password"]', testPassword);
    
    // Take screenshot before login
    await page.screenshot({ 
      path: `screenshots/login-form-filled-${timestamp}.png`,
      fullPage: true 
    });
    
    await page.click('button[type="submit"]');
    
    // Wait for dashboard or check for error
    try {
      await page.waitForURL(/dashboard/, { timeout: 5000 });
      console.log('✅ Login successful! Redirected to dashboard');
      
      // Verify we're actually logged in
      await expect(page.locator('.sidebar')).toBeVisible({ timeout: 5000 });
      await expect(page.locator('.user-avatar')).toBeVisible();
      
      // Take success screenshot
      await page.screenshot({ 
        path: `screenshots/login-success-${timestamp}.png`,
        fullPage: true 
      });
      
      console.log('✅ Dashboard loaded successfully with navigation visible');
      
    } catch (error) {
      // Check for error message
      const errorMessage = await page.textContent('body');
      
      // Take error screenshot
      await page.screenshot({ 
        path: `screenshots/login-error-${timestamp}.png`,
        fullPage: true 
      });
      
      console.error('❌ Login failed!');
      console.error('Page content:', errorMessage);
      
      // Check if "Invalid email or password" is shown
      if (errorMessage.includes('Invalid email or password') || 
          errorMessage.includes('Ungültige E-Mail oder Passwort')) {
        throw new Error(`Login failed with "Invalid email or password" for user: ${testEmail}`);
      }
      
      throw error;
    }
  });

  test('should show error for invalid credentials', async ({ page }) => {
    await page.goto('/users/login');
    
    await page.fill('input[name="email"]', 'nonexistent@example.com');
    await page.fill('input[name="password"]', 'wrongpassword');
    await page.click('button[type="submit"]');
    
    // Wait a bit for error message
    await page.waitForTimeout(1000);
    
    // Should show error message
    const content = await page.textContent('body');
    expect(
      content.includes('Invalid email or password') || 
      content.includes('Ungültige E-Mail oder Passwort')
    ).toBeTruthy();
    
    // Should NOT redirect to dashboard
    expect(page.url()).toContain('/users/login');
    
    // Should NOT show navigation
    await expect(page.locator('.sidebar')).not.toBeVisible();
  });

  test('should validate required fields on registration', async ({ page }) => {
    await page.goto('/users/register');
    
    // Try to submit empty form
    await page.click('button[type="submit"]');
    
    // Should stay on registration page
    expect(page.url()).toContain('/users/register');
    
    // HTML5 validation should prevent submission
    const emailInput = page.locator('input[name="email"]');
    const passwordInput = page.locator('input[name="password"]');
    
    await expect(emailInput).toHaveAttribute('required', '');
    await expect(passwordInput).toHaveAttribute('required', '');
  });
});
