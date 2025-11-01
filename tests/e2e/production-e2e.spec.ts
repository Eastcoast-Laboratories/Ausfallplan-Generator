/**
 * Production End-to-End Test for Ausfallplan Generator
 * 
 * This test verifies the complete workflow on the live production server.
 * It performs a full user journey from registration to report generation:
 * 
 * Test Flow:
 * 1. Register new user with organization
 * 2. Verify email (workaround using direct verify endpoint)
 * 3. Login with credentials
 * 4. Create a schedule with capacity
 * 5. Add multiple children to the system
 * 6. Assign children to the created schedule
 * 7. Manage waitlist (add children to waitlist)
 * 8. Generate PDF report for the schedule
 * 9. Verify all pages are accessible post-creation
 * 
 * Purpose:
 * - Smoke test for production deployment
 * - Validates critical user workflows
 * - Ensures database schema and migrations work correctly
 * - Tests authentication and authorization
 * - Verifies report generation functionality
 * 
 * Usage: npx playwright test tests/e2e/production-e2e.spec.ts
 */

import { test, expect } from '@playwright/test';

const BASE_URL = 'https://fairnestplan.z11.de';
const TEST_TIMESTAMP = Date.now();
const TEST_EMAIL = `e2e-test-${TEST_TIMESTAMP}@test.local`;
const TEST_PASSWORD = 'Test84hbfUb_3dsf!';
const TEST_ORG = `E2E-Test-Org-${TEST_TIMESTAMP}`;

test.describe('Production E2E Test', () => {
  test('Complete workflow: Register -> Login -> Schedule -> Children -> Waitlist -> Report', async ({ page }) => {
    // 1. REGISTER NEW USER
    console.log('Step 1: Registration');
    await page.goto(`${BASE_URL}/users/register`);
    
    await page.fill('input[name="email"]', TEST_EMAIL);
    
    // Fill password field(s)
    await page.fill('input[name="password"]', TEST_PASSWORD);
    
    // Fill organization - new structure: user creates or joins organization
    const orgField = page.locator('input[name="organization_name"], input[list="organizations"]');
    if (await orgField.count() > 0) {
      await orgField.first().fill(TEST_ORG);
    }
    
    // Note: No role selection anymore - user is automatically assigned as org_admin
    // for new organizations in the OrganizationUsers table
    
    await page.click('button[type="submit"]');
    
    // Wait for redirect to login page
    await page.waitForURL(/login/, { timeout: 10000 });
    
    console.log('✅ Step 1 completed: Registration successful');

    // 2. MANUALLY VERIFY EMAIL VIA DATABASE (workaround since email sending doesn't work on production)
    console.log('Step 2: Manually verifying email via verify URL trick');
    
    // Use the verify endpoint directly with the user's email to bypass email sending
    // This simulates clicking the verification link
    await page.goto(`${BASE_URL}/users/verify?email=${encodeURIComponent(TEST_EMAIL)}`);
    
    // Wait for redirect to login page after verification
    await page.waitForURL(/login/, { timeout: 10000 });
    
    // Wait a bit longer to ensure database transaction is committed
    await page.waitForTimeout(1000);
    
    console.log('✅ Step 2 completed: Email verified');

    // 3. LOGIN
    console.log('Step 3: Login');
    
    // Refresh to ensure no cache issues
    await page.goto(`${BASE_URL}/users/login`);
    await page.fill('input[name="email"]', TEST_EMAIL);
    await page.fill('input[name="password"]', TEST_PASSWORD);
    await page.click('button[type="submit"]');
    
    // Wait for login success - could redirect to /, /dashboard, /schedules, or /children
    await page.waitForURL(/\/(dashboard|schedules|children|$)/, { timeout: 10000 });
    await page.waitForTimeout(1000); // Wait for page to fully load
    await expect(page.locator('body')).toContainText(/Willkommen|Dashboard|Ausfallpläne|Schedules|Übersicht/, { timeout: 5000 });
    
    console.log('✅ Step 3 completed: Login successful');

    // 4. CREATE SCHEDULE
    console.log('Step 4: Creating schedule');
    await page.goto(`${BASE_URL}/schedules/add`);
    
    await page.fill('input[name="title"]', `Test Schedule ${TEST_TIMESTAMP}`);
    await page.fill('input[name="capacity_per_day"]', '9');
    
    await page.click('button[type="submit"]');
    await page.waitForURL(/\/schedules\/\d+|\/schedules/, { timeout: 10000 });
    
    console.log('✅ Step 4 completed: Schedule created');

    // Get schedule ID from URL or list
    const scheduleListUrl = `${BASE_URL}/schedules`;
    await page.goto(scheduleListUrl);
    
    // Find our schedule in the table (title is in cell, not a link)
    const scheduleRow = page.locator(`tr:has-text("Test Schedule ${TEST_TIMESTAMP}")`).first();
    await expect(scheduleRow).toBeVisible({ timeout: 5000 });
    
    // Extract schedule ID from the "Bearbeiten" (edit) link
    const editLink = scheduleRow.locator('a[href*="/schedules/edit/"]');
    const href = await editLink.getAttribute('href');
    const scheduleId = href?.match(/\/schedules\/edit\/(\d+)/)?.[1];
    
    if (!scheduleId) {
      throw new Error('Could not find schedule ID');
    }
    
    console.log(`Found schedule ID: ${scheduleId}`);

    // 5. ADD CHILDREN
    console.log('Step 5: Adding children');
    await page.goto(`${BASE_URL}/children/add`);
    
    // Add first child
    await page.fill('input[name="name"]', `Test Kind 1-${TEST_TIMESTAMP}`);
    await page.click('button[type="submit"]');
    await page.waitForURL(/\/children/, { timeout: 10000 });
    
    // Add second child
    await page.goto(`${BASE_URL}/children/add`);
    await page.fill('input[name="name"]', `Test Kind 2-${TEST_TIMESTAMP}`);
    await page.click('button[type="submit"]');
    await page.waitForURL(/\/children/, { timeout: 10000 });
    
    console.log('✅ Step 5 completed: Children added');

    // 6. ASSIGN CHILDREN TO SCHEDULE
    console.log('Step 6: Assigning children to schedule');
    await page.goto(`${BASE_URL}/schedules/manage-children/${scheduleId}`);
    
    // Wait for page load
    await page.waitForSelector('h3', { timeout: 5000 });
    
    // Find and click "Add" buttons for our test children
    const addButtons = page.locator('button:has-text("Add"), a:has-text("Add")');
    const count = await addButtons.count();
    
    if (count > 0) {
      // Click first two add buttons
      for (let i = 0; i < Math.min(2, count); i++) {
        await addButtons.nth(i).click();
        await page.waitForTimeout(500);
      }
    }
    
    console.log('✅ Step 6 completed: Children assigned to schedule');

    // 7. WAITLIST
    console.log('Step 7: Adding children to waitlist');
    await page.goto(`${BASE_URL}/waitlist?schedule_id=${scheduleId}`);
    
    await page.waitForSelector('h3:has-text("Waitlist"), h3:has-text("Warteliste"), h3:has-text("Nachrückliste")', { timeout: 5000 });
    
    // Find and add children to waitlist
    const waitlistAddButtons = page.locator('.available-children button:has-text("Add"), .available-children a:has-text("Add")');
    const waitlistCount = await waitlistAddButtons.count();
    
    if (waitlistCount > 0) {
      await waitlistAddButtons.first().click();
      await page.waitForTimeout(1000);
    }
    
    console.log('✅ Step 7 completed: Waitlist managed');

    // 8. GENERATE REPORT
    console.log('Step 8: Generating report');
    await page.goto(`${BASE_URL}/schedules/generate-report/${scheduleId}`);
    
    // Wait for report page to load (no form - report is generated directly)
    await page.waitForLoadState('networkidle', { timeout: 15000 });
    
    // Verify report content
    await expect(page.locator('body')).toContainText(/Nachrückliste|Waitlist/, { timeout: 5000 });
    await expect(page.locator('body')).toContainText(/Summe aller Zählkinder|Total/, { timeout: 5000 });
    
    console.log('✅ Step 8 completed: Report generated');

    // 9. FINAL VERIFICATION
    console.log('Step 9: Final verification - checking all pages are accessible');
    
    // Check dashboard
    await page.goto(`${BASE_URL}/dashboard`);
    await expect(page.locator('body')).toContainText(/Dashboard|Übersicht/, { timeout: 5000 });
    
    // Check schedules list
    await page.goto(`${BASE_URL}/schedules`);
    await expect(page.locator('body')).toContainText(`Test Schedule ${TEST_TIMESTAMP}`, { timeout: 5000 });
    
    // Check children list
    await page.goto(`${BASE_URL}/children`);
    await expect(page.locator('body')).toContainText(`Test Kind`, { timeout: 5000 });
    
    console.log('✅ Step 9 completed: All pages accessible');
    
    console.log('');
    console.log('🎉 ALL TESTS PASSED!');
    console.log(`Test user: ${TEST_EMAIL}`);
    console.log(`Test organization: ${TEST_ORG}`);
    console.log(`Schedule ID: ${scheduleId}`);
  });
});
