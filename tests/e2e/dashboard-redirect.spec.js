const { test, expect } = require('@playwright/test');

/**
 * TEST DESCRIPTION:
 * Tests the dashboard redirect flow after login.
 * 
 * ORGANIZATION IMPACT: ❌ NONE
 * 
 * WHAT IT TESTS:
 * 1. Root URL redirects to login with redirect parameter
 * 2. After login, user is redirected to dashboard
 * 3. Authenticated users can access dashboard directly
 */
test.describe('Dashboard Redirect Flow', () => {
    test('should redirect / to login, then to dashboard after login', async ({ page }) => {
        console.log('🧪 Testing dashboard redirect flow...');
        
        // Step 1: Visit root URL
        console.log('📍 Step 1: Navigate to /');
        await page.goto('http://localhost:8080/');
        
        // Should be redirected to login with redirect parameter
        console.log('📍 Step 2: Check redirect to login');
        await page.waitForURL(/.*\/login.*/);
        const currentUrl = page.url();
        console.log('  Current URL:', currentUrl);
        
        // Verify redirect parameter is present
        expect(currentUrl).toContain('redirect=');
        console.log('  ✅ Redirect parameter present');
        
        // Step 3: Fill in login form
        console.log('📍 Step 3: Fill login form');
        await page.fill('input[name="email"]', 'a2@a.de');
        await page.fill('input[name="password"]', 'asdfasdf');
        
        // Step 4: Submit login
        console.log('📍 Step 4: Submit login');
        await page.click('button[type="submit"]');
        
        // Step 5: Should be redirected to dashboard
        console.log('📍 Step 5: Check redirect to dashboard');
        await page.waitForURL(/.*\/(dashboard)?$/);
        const finalUrl = page.url();
        console.log('  Final URL:', finalUrl);
        
        // Verify we're on dashboard
        const isDashboard = finalUrl.endsWith('/') || finalUrl.endsWith('/dashboard');
        expect(isDashboard).toBeTruthy();
        console.log('  ✅ On dashboard page');
        
        // Verify dashboard content is visible
        await expect(page.locator('h1, h2')).toContainText(/Dashboard|Übersicht/i);
        console.log('  ✅ Dashboard content visible');
        
        console.log('');
        console.log('✅ TEST PASSED - Dashboard redirect working correctly!');
    });
    
    test('should allow direct access to /dashboard when authenticated', async ({ page }) => {
        console.log('🧪 Testing direct dashboard access...');
        
        // Login first
        console.log('📍 Step 1: Login');
        await page.goto('http://localhost:8080/login');
        await page.fill('input[name="email"]', 'a2@a.de');
        await page.fill('input[name="password"]', 'asdfasdf');
        await page.click('button[type="submit"]');
        await page.waitForURL(/.*\/(dashboard)?$/);
        console.log('  ✅ Logged in');
        
        // Now access dashboard directly
        console.log('📍 Step 2: Access /dashboard directly');
        await page.goto('http://localhost:8080/dashboard');
        
        // Should stay on dashboard, not redirect
        const url = page.url();
        console.log('  Current URL:', url);
        expect(url).toMatch(/\/(dashboard)?$/);
        console.log('  ✅ Direct access successful');
        
        console.log('');
        console.log('✅ TEST PASSED - Direct dashboard access working!');
    });
});
