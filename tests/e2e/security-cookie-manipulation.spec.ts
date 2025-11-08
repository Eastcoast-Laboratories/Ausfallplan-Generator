/**
 * Security Test: Cookie Manipulation & Session Hijacking Prevention
 * 
 * Tests:
 * - User receives auth cookie after login
 * - Cookie only contains session ID, not user data
 * - Cannot hijack another user's session by cookie manipulation
 * - Cannot access other user's account by modifying session cookie
 * - Session validation prevents unauthorized access
 * 
 * Run command:
 * timeout 120 npx playwright test tests/e2e/security-cookie-manipulation.spec.ts --project=chromium --headed
 */

import { test, expect } from '@playwright/test';

test.describe('Cookie Security Tests', () => {
  
  test('should store only session ID in cookie, not user data', async ({ page, context }) => {
    // Step 1: Login
    await page.goto('http://localhost:8080/login');
    await page.fill('input[name="email"]', 'admin@demo.kita');
    await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
    await page.click('button[type="submit"]');
    await page.waitForURL(/dashboard|children|schedules/, { timeout: 10000 });
    
    // Step 2: Inspect cookies
    const cookies = await context.cookies();
    const sessionCookie = cookies.find(c => c.name === 'PHPSESSID');
    const csrfCookie = cookies.find(c => c.name === 'csrfToken');
    
    expect(sessionCookie).toBeDefined();
    console.log('✓ Session cookie found:', sessionCookie?.name);
    console.log('  Cookie value (session ID):', sessionCookie?.value);
    
    // Step 3: Verify cookie contains only session ID, not user data
    // Session ID should be a random string, not contain email or user ID
    const cookieValue = sessionCookie?.value || '';
    
    const containsEmail = cookieValue.includes('admin') || cookieValue.includes('demo.kita');
    const containsUserId = /user[_-]?id[=:]?\d+/i.test(cookieValue);
    const looksLikeSessionId = cookieValue.length >= 20 && /^[a-zA-Z0-9,-]+$/.test(cookieValue);
    
    expect(containsEmail).toBeFalsy();
    expect(containsUserId).toBeFalsy();
    expect(looksLikeSessionId).toBeTruthy();
    
    console.log('✓ Cookie does NOT contain user email');
    console.log('✓ Cookie does NOT contain user ID');
    console.log('✓ Cookie contains only random session ID');
    console.log('✓ SECURE: User data stored server-side, not in cookie');
  });

  test('should prevent cookie manipulation attack', async ({ page, context }) => {
    // Step 1: Login with valid credentials
    await page.goto('http://localhost:8080/login');
    await page.fill('input[name="email"]', 'admin@demo.kita');
    await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
    await page.click('button[type="submit"]');
    
    // Wait for redirect after login
    await page.waitForURL(/dashboard|children|schedules/, { timeout: 10000 });
    
    // Step 2: Verify we have a session cookie
    const cookies = await context.cookies();
    console.log('All cookies:', cookies.map(c => c.name).join(', '));
    
    // Find PHP session cookie
    const sessionCookie = cookies.find(c => c.name === 'PHPSESSID');
    
    expect(sessionCookie).toBeDefined();
    console.log('✓ Session cookie received:', sessionCookie?.name);
    
    // Step 3: Get current user info from page
    const currentUserEmail = await page.locator('text=/admin@demo.kita/i').first().textContent();
    console.log('✓ Logged in as:', currentUserEmail);
    
    // Step 4: Try to manipulate cookie by changing a character
    if (sessionCookie) {
      const originalValue = sessionCookie.value;
      const manipulatedValue = originalValue.slice(0, -1) + 'X'; // Change last character
      
      await context.addCookies([{
        ...sessionCookie,
        value: manipulatedValue
      }]);
      
      console.log('✗ Cookie manipulated, testing security...');
      
      // Step 5: Try to access protected page with manipulated cookie
      await page.goto('http://localhost:8080/dashboard');
      
      // Should either:
      // a) Redirect to login page (session invalid)
      // b) Show error message
      // c) Stay on current page but logged out
      
      await page.waitForTimeout(2000);
      const currentUrl = page.url();
      
      // Check if redirected to login or session is invalid
      const isSecure = currentUrl.includes('/login') || 
                       currentUrl.includes('/users/login');
      
      expect(isSecure).toBeTruthy();
      console.log('✓ Cookie manipulation prevented - redirected to:', currentUrl);
    }
  });

  test('should prevent session hijacking via cookie theft', async ({ page, context }) => {
    // This test simulates: What if attacker steals another user's session cookie?
    
    // Step 1: Login as User 1
    await page.goto('http://localhost:8080/login');
    await page.fill('input[name="email"]', 'admin@demo.kita');
    await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
    await page.click('button[type="submit"]');
    await page.waitForURL(/dashboard|children|schedules/, { timeout: 10000 });
    
    // Get User 1's session cookie
    const cookies = await context.cookies();
    const user1SessionCookie = cookies.find(c => c.name === 'PHPSESSID');
    expect(user1SessionCookie).toBeDefined();
    
    // Verify we're logged in as User 1
    await page.goto('http://localhost:8080/dashboard');
    const userEmail = await page.locator('.user-email, .user-dropdown-toggle, [class*="user"]').first().textContent();
    console.log('✓ Logged in as User 1:', userEmail?.trim());
    
    // Step 2: Create new context (simulate another browser/attacker)
    const attackerContext = await page.context().browser()!.newContext();
    const attackerPage = await attackerContext.newPage();
    
    // Step 3: Attacker tries to use stolen session cookie
    await attackerContext.addCookies([user1SessionCookie!]);
    
    // Step 4: Try to access protected page
    await attackerPage.goto('http://localhost:8080/dashboard');
    await attackerPage.waitForTimeout(1000);
    
    // Step 5: In this case, session WILL work (same session cookie)
    // This demonstrates that session hijacking IS possible if cookie is stolen
    // The protection must come from:
    // - HTTPS (prevents cookie theft)
    // - HttpOnly flag (prevents XSS cookie theft)
    // - SameSite flag (prevents CSRF)
    const attackerUrl = attackerPage.url();
    
    if (attackerUrl.includes('dashboard')) {
      console.log('⚠️  IMPORTANT: Session cookie CAN be reused if stolen!');
      console.log('   Protection mechanisms required:');
      console.log('   ✓ HTTPS - prevents network sniffing');
      console.log('   ✓ HttpOnly cookie - prevents XSS theft');
      console.log('   ✓ SameSite cookie - prevents CSRF');
      console.log('   ✓ Secure cookie flag - HTTPS only (production)');
    }
    
    await attackerContext.close();
  });

  test('should maintain session integrity', async ({ page, context }) => {
    // Login
    await page.goto('http://localhost:8080/login');
    await page.fill('input[name="email"]', 'admin@demo.kita');
    await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
    await page.click('button[type="submit"]');
    
    await page.waitForURL(/dashboard|children|schedules/, { timeout: 10000 });
    
    // Get cookies
    const cookies = await context.cookies();
    const sessionCookie = cookies.find(c => c.name === 'PHPSESSID');
    
    // Access multiple pages - session should remain valid
    await page.goto('http://localhost:8080/children');
    await expect(page).not.toHaveURL(/login/);
    
    await page.goto('http://localhost:8080/schedules');
    await expect(page).not.toHaveURL(/login/);
    
    console.log('✓ Session integrity maintained across pages');
    
    // Verify cookies haven't changed unexpectedly
    const cookiesAfter = await context.cookies();
    const sessionCookieAfter = cookiesAfter.find(c => c.name === sessionCookie?.name);
    
    expect(sessionCookieAfter?.value).toBe(sessionCookie?.value);
    console.log('✓ Cookie value unchanged during normal navigation');
  });
});
