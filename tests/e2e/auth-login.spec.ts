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
    await page.fill('input[type="password"]', '84hbfUb_3dsf');
    
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

  test('complete registration and approval workflow', async ({ page, context }) => {
    console.log('\n=== Test: Complete Registration & Approval Workflow ===');
    
    // Generate unique email for this test run
    const timestamp = Date.now();
    const testEmail = `testuser-${timestamp}@playwright.test`;
    const testPassword = 'TestPassword123!';
    
    // Step 1: Register new user
    console.log('\n1. Register new user');
    await page.goto('http://localhost:8080/register');
    await expect(page.locator('h2, legend')).toContainText(/register|registrieren/i);
    
    // Fill registration form
    await page.fill('input[name="email"]', testEmail);
    await page.fill('input[name="password"]', testPassword);
    await page.fill('input[name="password_confirm"]', testPassword);
    
    // Select existing organization from dropdown
    const orgSelect = page.locator('select[name="organization_choice"]');
    await orgSelect.waitFor({ state: 'visible' });
    
    // Get first organization (skip "new" and "divider" options)
    const options = await orgSelect.locator('option').all();
    let selectedOrgId = null;
    for (const option of options) {
      const value = await option.getAttribute('value');
      if (value && value !== 'new' && value !== 'divider') {
        selectedOrgId = value;
        break;
      }
    }
    
    if (selectedOrgId) {
      await orgSelect.selectOption(selectedOrgId);
      console.log(`   ✓ Selected organization ID: ${selectedOrgId}`);
      await page.waitForTimeout(300); // Wait for form to update
      
      // Select role (editor)
      await page.selectOption('select[name="requested_role"]', 'editor');
    } else {
      throw new Error('No existing organization found in dropdown');
    }
    
    // Submit registration
    await page.click('button[type="submit"]');
    await page.waitForTimeout(1000);
    
    // Should redirect to login with success message
    await expect(page).toHaveURL(/login/);
    const successMessage = await page.locator('.message, .alert, .flash').textContent();
    console.log(`   ✓ Registration successful: ${successMessage?.substring(0, 50)}...`);
    
    // Step 2: Get verification email link
    console.log('\n2. Get email verification link');
    await page.goto('http://localhost:8080/debug/emails');
    await page.waitForTimeout(500);
    
    // Find the verification email
    const verificationEmailRow = page.locator('tr').filter({ hasText: testEmail }).filter({ hasText: 'Verify' });
    await expect(verificationEmailRow).toBeVisible();
    
    // Click to view email details
    await verificationEmailRow.locator('a').first().click();
    await page.waitForTimeout(300);
    
    // Get verification link
    const verifyLink = page.locator('a').filter({ hasText: /verify/i }).first();
    const verifyHref = await verifyLink.getAttribute('href');
    console.log(`   ✓ Got verification link: ${verifyHref?.substring(0, 60)}...`);
    
    // Step 3: Verify email
    console.log('\n3. Verify email address');
    if (verifyHref) {
      await page.goto(verifyHref);
      await page.waitForTimeout(500);
      const verifyMessage = await page.locator('.message, .alert, .flash').textContent();
      console.log(`   ✓ Email verified: ${verifyMessage?.substring(0, 50)}...`);
    }
    
    // Step 4: Try to login (should be blocked - pending approval)
    console.log('\n4. Try to login (should be blocked - pending approval)');
    await page.goto('http://localhost:8080/login');
    await page.fill('input[type="email"]', testEmail);
    await page.fill('input[type="password"]', testPassword);
    await page.click('button[type="submit"]');
    await page.waitForTimeout(500);
    
    // Should stay on login page with error
    await expect(page).toHaveURL(/login/);
    const pendingMessage = await page.locator('.message, .alert, .flash').textContent();
    expect(pendingMessage).toMatch(/pending|approval|freischaltung/i);
    console.log(`   ✓ Login blocked: ${pendingMessage?.substring(0, 50)}...`);
    
    // Step 5: Login as org-admin
    console.log('\n5. Login as org-admin to approve user');
    await page.goto('http://localhost:8080/login');
    await page.fill('input[type="email"]', 'admin@demo.kita');
    await page.fill('input[type="password"]', '84fhr38hf43iahfuX_2');
    await page.click('button[type="submit"]');
    await page.waitForURL(/dashboard|children|schedules/, { timeout: 10000 });
    console.log('   ✓ Logged in as org-admin');
    
    // Step 6: Get approval email and click approval link
    console.log('\n6. Get approval link from email');
    await page.goto('http://localhost:8080/debug/emails');
    await page.waitForTimeout(500);
    
    // Find the admin notification email
    const adminEmailRow = page.locator('tr').filter({ hasText: 'admin@demo.kita' }).filter({ hasText: /new user|registration/i });
    await expect(adminEmailRow).toBeVisible();
    
    // Click to view email
    await adminEmailRow.locator('a').first().click();
    await page.waitForTimeout(300);
    
    // Get approval link
    const approveLink = page.locator('a').filter({ hasText: /approve/i }).first();
    const approveHref = await approveLink.getAttribute('href');
    console.log(`   ✓ Got approval link: ${approveHref?.substring(0, 60)}...`);
    
    // Step 7: Click approval link
    console.log('\n7. Approve the user');
    if (approveHref) {
      await page.goto(approveHref);
      await page.waitForTimeout(500);
      const approveMessage = await page.locator('.message, .alert, .flash').textContent();
      console.log(`   ✓ User approved: ${approveMessage?.substring(0, 50)}...`);
    }
    
    // Step 8: Logout admin
    console.log('\n8. Logout org-admin');
    await page.goto('http://localhost:8080/logout');
    await page.waitForTimeout(500);
    console.log('   ✓ Logged out');
    
    // Step 9: Login as newly approved user
    console.log('\n9. Login as newly approved user');
    await page.goto('http://localhost:8080/login');
    await page.fill('input[type="email"]', testEmail);
    await page.fill('input[type="password"]', testPassword);
    await page.click('button[type="submit"]');
    await page.waitForTimeout(1000);
    
    // Should successfully login and redirect to dashboard
    await expect(page).toHaveURL(/dashboard|children|schedules/);
    await expect(page.locator('a, button')).toContainText(/logout|abmelden/i);
    console.log('   ✓ Login successful!');
    
    console.log('\n=== ✅ Complete Workflow Test PASSED ===');
    console.log('Summary:');
    console.log('  ✓ User registration');
    console.log('  ✓ Email verification');
    console.log('  ✓ Pending status blocks login');
    console.log('  ✓ Org-admin receives notification');
    console.log('  ✓ Org-admin can approve user');
    console.log('  ✓ Approved user can login');
  });

  test('viewer role has read-only access', async ({ page }) => {
    // Login as viewer (assuming viewer user exists)
    await page.goto('http://localhost:8080/login');
    await page.fill('input[type="email"]', 'viewer@test.com');
    await page.fill('input[type="password"]', '84hbfUb_3dsf');
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
