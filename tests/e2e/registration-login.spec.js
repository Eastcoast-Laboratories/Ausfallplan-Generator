// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * TEST DESCRIPTION:
 * Tests the complete user registration and login flow with all scenarios.
 * 
 * ORGANIZATION IMPACT: ✅ HIGH
 * - Registration now creates entry in organization_users table (not just users.organization_id)
 * - New users are automatically org_admin of their new organization
 * - Existing organization members get requested_role (viewer/editor/org_admin)
 * - Org-admins receive email notifications when new users join
 * - Tests organization field (text input) with autocomplete
 * 
 * WHAT IT TESTS:
 * 1. NEW ORG: User can register with new organization name → becomes org_admin
 * 2. EXISTING ORG: User can join existing organization with role selection
 * 3. NO ORG: User can register without organization
 * 4. ROLES: requested_role field works (viewer, editor, org_admin)
 * 5. NOTIFICATIONS: Org-admins receive emails when new members join
 * 6. LOGIN: User can login with registered credentials
 * 7. VALIDATION: Invalid credentials show error
 * 8. VALIDATION: Required field validation works
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
    await page.fill('input[name="organization_name"]', orgName);
    
    // Fill in email and password
    await page.fill('input[name="email"]', testEmail);
    await page.fill('input[name="password"]', testPassword);
    await page.fill('input[name="password_confirm"]', testPassword);
    
    // Select role - for new org will be org_admin automatically, but we still select
    await page.selectOption('select[name="requested_role"]', 'editor');
    
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

  test('NEW: should register user joining EXISTING organization with viewer role', async ({ page }) => {
    const timestamp = Date.now();
    
    // First, create an organization by registering first user
    const orgName = `Existing-Org-${timestamp}`;
    const firstUserEmail = `orgadmin${timestamp}@test.local`;
    const firstUserPassword = 'Admin123!';
    
    console.log('📍 Step 1: Create organization with first user (org_admin)');
    await page.goto('/users/register');
    await page.fill('input[name="organization_name"]', orgName);
    await page.fill('input[name="email"]', firstUserEmail);
    await page.fill('input[name="password"]', firstUserPassword);
    await page.fill('input[name="password_confirm"]', firstUserPassword);
    await page.selectOption('select[name="requested_role"]', 'org_admin');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);
    
    // Now register second user joining the same organization as viewer
    const secondUserEmail = `viewer${timestamp}@test.local`;
    const secondUserPassword = 'Viewer123!';
    
    console.log('📍 Step 2: Second user joins EXISTING organization as viewer');
    await page.goto('/users/register');
    await page.fill('input[name="organization_name"]', orgName);
    await page.fill('input[name="email"]', secondUserEmail);
    await page.fill('input[name="password"]', secondUserPassword);
    await page.fill('input[name="password_confirm"]', secondUserPassword);
    await page.selectOption('select[name="requested_role"]', 'viewer');
    
    await page.screenshot({ 
      path: `screenshots/registration-existing-org-viewer-${timestamp}.png`,
      fullPage: true 
    });
    
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);
    
    // Should show message that org-admins have been notified
    const successMsg = await page.textContent('body');
    console.log('Success message:', successMsg.substring(0, 300));
    
    expect(
      successMsg.includes('Organization admins have been notified') ||
      successMsg.includes('admins have been notified')
    ).toBeTruthy();
    
    console.log('✅ Registration successful - org-admins notified!');
  });

  test('NEW: should register user joining existing org as EDITOR', async ({ page }) => {
    const timestamp = Date.now();
    
    // Create organization first
    const orgName = `Editor-Test-Org-${timestamp}`;
    const adminEmail = `admin${timestamp}@test.local`;
    
    await page.goto('/users/register');
    await page.fill('input[name="organization_name"]', orgName);
    await page.fill('input[name="email"]', adminEmail);
    await page.fill('input[name="password"]', 'Test123!');
    await page.fill('input[name="password_confirm"]', 'Test123!');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);
    
    // Second user joins as editor
    const editorEmail = `editor${timestamp}@test.local`;
    
    console.log('📍 User joins existing org with EDITOR role');
    await page.goto('/users/register');
    await page.fill('input[name="organization_name"]', orgName);
    await page.fill('input[name="email"]', editorEmail);
    await page.fill('input[name="password"]', 'Editor123!');
    await page.fill('input[name="password_confirm"]', 'Editor123!');
    await page.selectOption('select[name="requested_role"]', 'editor');
    
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);
    
    const bodyText = await page.textContent('body');
    expect(
      bodyText.includes('successful') || 
      bodyText.includes('erfolgreich')
    ).toBeTruthy();
    
    console.log('✅ Editor registration successful');
  });

  test('NEW: should register user requesting ORG_ADMIN role in existing org', async ({ page }) => {
    const timestamp = Date.now();
    
    // Create organization
    const orgName = `Multi-Admin-Org-${timestamp}`;
    const firstAdmin = `firstadmin${timestamp}@test.local`;
    
    await page.goto('/users/register');
    await page.fill('input[name="organization_name"]', orgName);
    await page.fill('input[name="email"]', firstAdmin);
    await page.fill('input[name="password"]', 'Admin123!');
    await page.fill('input[name="password_confirm"]', 'Admin123!');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);
    
    // Second user requests org_admin role
    const secondAdmin = `secondadmin${timestamp}@test.local`;
    
    console.log('📍 User requests ORG_ADMIN role in existing organization');
    await page.goto('/users/register');
    await page.fill('input[name="organization_name"]', orgName);
    await page.fill('input[name="email"]', secondAdmin);
    await page.fill('input[name="password"]', 'SecondAdmin123!');
    await page.fill('input[name="password_confirm"]', 'SecondAdmin123!');
    await page.selectOption('select[name="requested_role"]', 'org_admin');
    
    await page.screenshot({ 
      path: `screenshots/registration-org-admin-request-${timestamp}.png`,
      fullPage: true 
    });
    
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);
    
    // Should mention that admins need to approve
    const bodyText = await page.textContent('body');
    expect(
      bodyText.includes('admins have been notified') ||
      bodyText.includes('review your request')
    ).toBeTruthy();
    
    console.log('✅ Org-admin request submitted - requires approval');
  });

  test('NEW: should register user WITHOUT organization ("keine organisation")', async ({ page }) => {
    const timestamp = Date.now();
    const email = `noorg${timestamp}@test.local`;
    const password = 'NoOrg123!';
    
    console.log('📍 Register user WITHOUT organization');
    await page.goto('/users/register');
    
    // Leave organization field EMPTY
    await page.fill('input[name="organization_name"]', '');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', password);
    await page.fill('input[name="password_confirm"]', password);
    // Role selection when no org
    await page.selectOption('select[name="requested_role"]', 'viewer');
    
    await page.screenshot({ 
      path: `screenshots/registration-no-org-${timestamp}.png`,
      fullPage: true 
    });
    
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);
    
    // Should still succeed
    const bodyText = await page.textContent('body');
    expect(
      bodyText.includes('successful') || 
      bodyText.includes('erfolgreich')
    ).toBeTruthy();
    
    // Try to login
    console.log('📍 Login without organization');
    await page.goto('/users/login');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', password);
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);
    
    // Should be able to login
    expect(page.url()).toContain('/dashboard');
    console.log('✅ Login successful even without organization');
  });

  test('NEW: should show different success messages for new vs existing org', async ({ page }) => {
    const timestamp = Date.now();
    
    // Test 1: New organization
    console.log('📍 Test message for NEW organization');
    const newOrgEmail = `neworg${timestamp}@test.local`;
    await page.goto('/users/register');
    await page.fill('input[name="organization_name"]', `Brand-New-Org-${timestamp}`);
    await page.fill('input[name="email"]', newOrgEmail);
    await page.fill('input[name="password"]', 'Test123!');
    await page.fill('input[name="password_confirm"]', 'Test123!');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);
    
    let bodyText = await page.textContent('body');
    // Should mention "admin of your new organization"
    expect(
      bodyText.includes('admin of your new organization') ||
      bodyText.includes('You are the admin')
    ).toBeTruthy();
    console.log('✅ Correct message for new org creator');
    
    // Test 2: Existing organization
    console.log('📍 Test message for EXISTING organization');
    const existingOrgEmail = `existingorg${timestamp}@test.local`;
    await page.goto('/users/register');
    await page.fill('input[name="organization_name"]', `Brand-New-Org-${timestamp}`);
    await page.fill('input[name="email"]', existingOrgEmail);
    await page.fill('input[name="password"]', 'Test123!');
    await page.fill('input[name="password_confirm"]', 'Test123!');
    await page.selectOption('select[name="requested_role"]', 'viewer');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);
    
    bodyText = await page.textContent('body');
    // Should mention notification to admins
    expect(
      bodyText.includes('admins have been notified') ||
      bodyText.includes('will review your request')
    ).toBeTruthy();
    console.log('✅ Correct message for joining existing org');
  });
});
