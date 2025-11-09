/**
 * Subscription Management E2E Test
 * 
 * Tests:
 * - Access subscription page
 * - View current subscription plan
 * - View available plans (test, pro, enterprise)
 * - Navigate to upgrade page
 * - Select payment method
 * - Cancel subscription
 * 
 * Run command:
 * timeout 120 npx playwright test tests/e2e/subscriptions.spec.ts --project=chromium --headed
 */

import { test, expect } from '@playwright/test';

test.describe('Subscription Management', () => {
  
  test.beforeEach(async ({ page }) => {
    // Login as test user
    await page.goto('http://localhost:8080/login');
    await page.fill('input[type="email"]', 'admin@demo.kita');
    await page.fill('input[type="password"]', 'password123');
    await page.click('button[type="submit"]');
    
    // Wait for dashboard with longer timeout
    await page.waitForURL(/dashboard|children|schedules/, { timeout: 10000 });
  });

  test('can access subscription page from sidebar', async ({ page }) => {
    // Click on subscription link in sidebar
    await page.click('a[href="/subscriptions"]');
    
    // Should be on subscriptions page
    await expect(page).toHaveURL('http://localhost:8080/subscriptions');
    
    // Should show subscription plans heading
    await expect(page.locator('h3')).toContainText(/Subscription Plans|Abonnement-Pläne/i);
  });

  test('shows current subscription status', async ({ page }) => {
    await page.goto('http://localhost:8080/subscriptions');
    
    // Should show current subscription section
    await expect(page.locator('h4')).toContainText(/Current Subscription|Aktuelles Abonnement/i);
    
    // Should show plan name (Test, Pro, or Enterprise)
    const planText = await page.locator('text=/Plan|Plan:/i').textContent();
    expect(planText).toMatch(/Test|Pro|Enterprise/i);
    
    // Should show status
    const statusText = await page.locator('text=/Status|Status:/i').textContent();
    expect(statusText).toMatch(/Active|Pending|Expired|Cancelled/i);
  });

  test('displays all three subscription plans', async ({ page }) => {
    await page.goto('http://localhost:8080/subscriptions');
    
    // Should show Test Plan
    await expect(page.locator('.price-card')).toContainText(/Test Plan/i);
    
    // Should show Pro Plan
    await expect(page.locator('.price-card')).toContainText(/Pro/i);
    
    // Should show Enterprise Plan
    await expect(page.locator('.price-card')).toContainText(/Enterprise/i);
    
    // Test plan should show "Free"
    await expect(page.locator('.price-card:has-text("Test Plan")')).toContainText(/Free|Kostenlos/i);
    
    // Pro plan should show price
    await expect(page.locator('.price-card:has-text("Pro")')).toContainText(/€5/i);
  });

  test('can navigate to upgrade page for Pro plan', async ({ page }) => {
    await page.goto('http://localhost:8080/subscriptions');
    
    // Click upgrade button for Pro plan (if not already on Pro)
    const upgradeButton = page.locator('a[href*="/subscriptions/upgrade/pro"]').first();
    
    // Check if upgrade button exists (user is not already on Pro)
    const isVisible = await upgradeButton.isVisible().catch(() => false);
    
    if (isVisible) {
      await upgradeButton.click();
      
      // Should be on upgrade page
      await expect(page).toHaveURL('http://localhost:8080/subscriptions/upgrade/pro');
      
      // Should show upgrade form
      await expect(page.locator('h3')).toContainText(/Upgrade/i);
      
      // Should show payment method options
      await expect(page.locator('legend')).toContainText(/Payment Method|Zahlungsmethode/i);
      
      // Should show PayPal option
      await expect(page.locator('label')).toContainText(/PayPal/i);
      
      // Should show Bank Transfer option
      await expect(page.locator('label')).toContainText(/Bank Transfer|Banküberweisung/i);
    }
  });

  test('payment method selection works', async ({ page }) => {
    await page.goto('http://localhost:8080/subscriptions/upgrade/pro');
    
    // Select PayPal
    const paypalRadio = page.locator('input[type="radio"][value="paypal"]');
    await paypalRadio.check();
    await expect(paypalRadio).toBeChecked();
    
    // Select Bank Transfer
    const bankRadio = page.locator('input[type="radio"][value="bank_transfer"]');
    await bankRadio.check();
    await expect(bankRadio).toBeChecked();
    await expect(paypalRadio).not.toBeChecked();
  });

  test('enterprise plan shows contact information', async ({ page }) => {
    await page.goto('http://localhost:8080/subscriptions/upgrade/enterprise');
    
    // Should show contact message
    await expect(page.locator('p')).toContainText(/contact us directly|kontaktieren Sie uns/i);
    
    // Should show email link
    await expect(page.locator('a[href^="mailto:"]')).toBeVisible();
    
    // Should have back to plans link
    await expect(page.locator('a[href="/subscriptions"]')).toContainText(/Back to Plans|Zurück/i);
  });

  test('can cancel subscription (if not on test plan)', async ({ page }) => {
    await page.goto('http://localhost:8080/subscriptions');
    
    // Check if cancel button exists (user is not on test plan)
    const cancelButton = page.locator('a:has-text("Cancel Subscription"), a:has-text("Abonnement kündigen")').first();
    const isVisible = await cancelButton.isVisible().catch(() => false);
    
    if (isVisible) {
      // Click cancel - this will show a confirmation dialog
      page.on('dialog', dialog => dialog.accept());
      await cancelButton.click();
      
      // Should show success message
      await expect(page.locator('.message, .alert, .flash')).toContainText(/cancelled|gekündigt/i);
      
      // Should be back on subscriptions page
      await expect(page).toHaveURL('http://localhost:8080/subscriptions');
    }
  });

  test('subscription page is accessible only when logged in', async ({ page }) => {
    // Logout
    await page.click('a:has-text("Logout"), a:has-text("Abmelden")');
    
    // Try to access subscriptions page
    await page.goto('http://localhost:8080/subscriptions');
    
    // Should redirect to login
    await expect(page).toHaveURL(/login/);
  });

  test('shows plan features correctly', async ({ page }) => {
    await page.goto('http://localhost:8080/subscriptions');
    
    // Test plan features
    const testCard = page.locator('.price-card:has-text("Test Plan")');
    await expect(testCard).toContainText(/1 Organization|1 Organisation/i);
    await expect(testCard).toContainText(/1 active schedule|1 aktiver/i);
    
    // Pro plan features
    const proCard = page.locator('.price-card:has-text("Pro")');
    await expect(proCard).toContainText(/Unlimited organizations|Unbegrenzte/i);
    await expect(proCard).toContainText(/Unlimited schedules|Unbegrenzte/i);
    
    // Enterprise plan features
    const enterpriseCard = page.locator('.price-card:has-text("Enterprise")');
    await expect(enterpriseCard).toContainText(/SSO|SAML/i);
    await expect(enterpriseCard).toContainText(/SLA/i);
  });
});
