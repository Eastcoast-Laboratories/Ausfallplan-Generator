// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * TEST DESCRIPTION:
 * Tests the complete user registration and login flow.
 * 
 * ORGANIZATION IMPACT: ✅ HIGH
 * - Registration now creates entry in organization_users table (not just users.organization_id)
 * - New users are automatically org_admin of their new organization
 * - Tests organization field (text input for new org) instead of dropdown
 * 
 * WHAT IT TESTS:
 * 1. User can register with new organization name
 * 2. Registration creates user + organization + organization_user entry
 * 3. User can login with registered credentials
 * 4. Invalid credentials show error
 * 5. Required field validation works
 */
test.describe('Registration and Login Flow', () => {
  
  test('should register a new user and then login successfully', async ({ page }) => {
    const timestamp = Date.now();
    const testEmail = `testuser${timestamp}@example.com`;
    const testPassword = 'SecurePassword123!';

    // Step 1: Navigate to registration page
    await page.goto('/users/register');
    
    // Step 2: Fill in registration form
    await page.waitForSelector('form');
    
    // NEW: Organization is now a text input for new org name (with autocomplete)
    const orgName = `Test-Org-${timestamp}`;
    await page.fill('input[name="organization"]', orgName);
    
    // Fill in email and password
    await page.fill('input[name="email"]', testEmail);
    await page.fill('input[name="password"]', testPassword);
    await page.fill('input[name="password_confirm"]', testPassword);
    
    // NOTE: Role is no longer in registration form - automatically org_admin for new org
    
    // Take screenshot before submission
    await page.screenshot({ 
      path: `screenshots/registration-form-filled-${timestamp}.png`,
      fullPage: true 
    });
    
    console.log(`✅ Registration form filled with email: ${testEmail}`);
    
    // Step 3: Submit registration form
    await page.click('button[type="submit"]');
    
    // Wait a bit for processing
    await page.waitForTimeout(2000);
    
    // Check if there are any error messages
    const bodyContent = await page.textContent('body');
    console.log('📄 Page after submit (first 500 chars):', bodyContent.substring(0, 500));
    
    // Check for validation errors
    if (bodyContent.includes('error') || bodyContent.includes('Error') || 
        bodyContent.includes('Fehler') || bodyContent.includes('required')) {
      await page.screenshot({ 
        path: `screenshots/registration-validation-error-${timestamp}.png`,
        fullPage: true 
      });
      console.log('⚠️  Possible validation error detected!');
    }
    
    // Registration was successful - check for success message
    await expect(page.locator('body')).toContainText('Your account has been created', { timeout: 5000 });
    console.log('✅ Registration successful! Account created.');
    
    const currentUrl = page.url();
    console.log(`📍 Current URL: ${currentUrl}`);
    
    // Take screenshot after registration
    await page.screenshot({ 
      path: `screenshots/after-registration-${timestamp}.png`,
      fullPage: true 
    });
    
    // Step 4: Navigate to login page (explicit navigation to ensure clean state)
    console.log('🔄 Navigating to login page...');
    await page.goto('/users/login');
    await page.waitForLoadState('networkidle');
    
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
