// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Demo Login Test - Slow motion with visible browser
 * This test runs slowly so you can see what's happening
 */
test.describe('Login Demo with Visible GUI', () => {
  
  test('should show login process step by step', async ({ page }) => {
    console.log('🚀 Starting visible login demo...');
    
    // Step 1: Navigate to login page
    console.log('📍 Step 1: Navigating to login page...');
    await page.goto('/users/login');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000); // Pause to see the page
    
    // Step 2: Show the login form
    console.log('📋 Step 2: Login form is visible');
    await expect(page.locator('form')).toBeVisible();
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await page.waitForTimeout(1000);
    
    // Step 3: Fill in email (slowly, character by character)
    console.log('✍️  Step 3: Typing email address...');
    const email = 'admin@demo.kita';
    await page.fill('input[name="email"]', '');
    for (const char of email) {
      await page.type('input[name="email"]', char, { delay: 100 });
    }
    await page.waitForTimeout(500);
    
    // Step 4: Fill in password (slowly)
    console.log('🔑 Step 4: Typing password...');
    await page.fill('input[name="password"]', '');
    await page.type('input[name="password"]', 'asbdasdaddd', { delay: 100 });
    await page.waitForTimeout(500);
    
    // Step 5: Show filled form
    console.log('✅ Step 5: Form is filled');
    await page.screenshot({ 
      path: 'screenshots/demo-login-filled.png',
      fullPage: true 
    });
    await page.waitForTimeout(1000);
    
    // Step 6: Click login button
    console.log('🖱️  Step 6: Clicking login button...');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(500);
    
    // Step 7: Wait for dashboard
    console.log('⏳ Step 7: Waiting for redirect to dashboard...');
    await page.waitForURL(/dashboard/, { timeout: 5000 });
    await page.waitForTimeout(1000);
    
    // Step 8: Verify navigation is visible
    console.log('🎯 Step 8: Verifying dashboard loaded...');
    await expect(page.locator('.sidebar')).toBeVisible();
    await expect(page.locator('.user-avatar')).toBeVisible();
    await expect(page.locator('text=Dashboard')).toBeVisible();
    await page.waitForTimeout(1000);
    
    // Step 9: Take final screenshot
    console.log('📸 Step 9: Taking final screenshot...');
    await page.screenshot({ 
      path: 'screenshots/demo-login-success.png',
      fullPage: true 
    });
    await page.waitForTimeout(1000);
    
    console.log('✅ Demo complete! Login successful.');
  });

  test('should show registration process step by step', async ({ page }) => {
    const timestamp = Date.now();
    const email = `demo${timestamp}@example.com`;
    
    console.log('🚀 Starting visible registration demo...');
    
    // Step 1: Navigate to registration page
    console.log('📍 Step 1: Navigating to registration page...');
    await page.goto('/users/register');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
    
    // Step 2: Show the registration form
    console.log('📋 Step 2: Registration form is visible');
    await expect(page.locator('form')).toBeVisible();
    await page.waitForTimeout(1000);
    
    // Step 3: Select organization
    console.log('🏢 Step 3: Selecting organization...');
    await page.selectOption('select[name="organization_id"]', { index: 1 });
    await page.waitForTimeout(800);
    
    // Step 4: Type email
    console.log('✍️  Step 4: Typing email address...');
    await page.fill('input[name="email"]', '');
    for (const char of email) {
      await page.type('input[name="email"]', char, { delay: 80 });
    }
    await page.waitForTimeout(800);
    
    // Step 5: Type password
    console.log('🔑 Step 5: Typing password...');
    await page.type('input[name="password"]', 'SecurePass123!', { delay: 80 });
    await page.waitForTimeout(800);
    
    // Step 6: Select role
    console.log('👤 Step 6: Selecting role...');
    await page.selectOption('select[name="role"]', 'viewer');
    await page.waitForTimeout(800);
    
    // Step 7: Screenshot filled form
    console.log('📸 Step 7: Form filled - taking screenshot...');
    await page.screenshot({ 
      path: `screenshots/demo-registration-filled-${timestamp}.png`,
      fullPage: true 
    });
    await page.waitForTimeout(1000);
    
    // Step 8: Submit form
    console.log('🖱️  Step 8: Clicking "Create Account" button...');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);
    
    // Step 9: Verify success
    console.log('✅ Step 9: Checking for success message...');
    await expect(page.locator('body')).toContainText('Your account has been created');
    await page.screenshot({ 
      path: `screenshots/demo-registration-success-${timestamp}.png`,
      fullPage: true 
    });
    await page.waitForTimeout(1500);
    
    console.log('✅ Demo complete! Registration successful.');
  });
});
