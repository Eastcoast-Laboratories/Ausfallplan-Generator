const { test, expect } = require('@playwright/test');

/**
 * DEBUG TEST: Find why admin@demo.kita gets access denied
 * 
 * This test will:
 * 1. Login as admin@demo.kita
 * 2. Check if login succeeded
 * 3. Try to access /admin/organizations
 * 4. Show exact error message
 * 5. Check session/identity state
 */
test.describe('Debug Admin Access to Organizations', () => {
    
    test('should login as system admin and access organizations', async ({ page }) => {
        const timestamp = Date.now();
        
        console.log('🔍 Step 1: Navigate to login page');
        await page.goto('https://ausfallplan-generator.z11.de/login');
        await page.waitForLoadState('networkidle');
        
        // Take screenshot of login page
        await page.screenshot({ 
            path: `screenshots/debug-login-page-${timestamp}.png`,
            fullPage: true 
        });
        
        console.log('🔍 Step 2: Fill login form with admin@demo.kita');
        await page.fill('input[name="email"]', 'admin@demo.kita');
        await page.fill('input[name="password"]', 'asbdasdaddd');
        
        await page.screenshot({ 
            path: `screenshots/debug-login-filled-${timestamp}.png`,
            fullPage: true 
        });
        
        console.log('🔍 Step 3: Submit login');
        await page.click('button[type="submit"]');
        
        // Wait for navigation
        await page.waitForTimeout(2000);
        
        const currentUrl = page.url();
        console.log(`Current URL after login: ${currentUrl}`);
        
        // Take screenshot after login
        await page.screenshot({ 
            path: `screenshots/debug-after-login-${timestamp}.png`,
            fullPage: true 
        });
        
        // Check if we're on dashboard
        const bodyText = await page.textContent('body');
        
        if (currentUrl.includes('dashboard')) {
            console.log('✅ Login successful - redirected to dashboard');
        } else if (bodyText.includes('Invalid') || bodyText.includes('ungültig')) {
            console.error('❌ Login failed - invalid credentials shown');
            console.error('Body excerpt:', bodyText.substring(0, 200));
            throw new Error('Login failed with invalid credentials');
        } else if (bodyText.includes('verify') || bodyText.includes('verifizieren')) {
            console.error('❌ Login failed - email verification required');
            throw new Error('Email verification required');
        } else {
            console.warn('⚠️  Login status unclear');
            console.log('Body excerpt:', bodyText.substring(0, 300));
        }
        
        console.log('');
        console.log('🔍 Step 4: Navigate to /admin/organizations');
        await page.goto('https://ausfallplan-generator.z11.de/admin/organizations');
        await page.waitForTimeout(1500);
        
        const orgUrl = page.url();
        console.log(`URL after organizations request: ${orgUrl}`);
        
        // Take screenshot
        await page.screenshot({ 
            path: `screenshots/debug-organizations-${timestamp}.png`,
            fullPage: true 
        });
        
        const orgPageText = await page.textContent('body');
        
        // Check various possible outcomes
        console.log('');
        console.log('🔍 Step 5: Analyze response');
        
        // Check for PHP errors FIRST (most important!)
        if (orgPageText.includes('Call to undefined method') || 
            orgPageText.includes('Fatal error') || 
            orgPageText.includes('Parse error') ||
            orgPageText.includes('Error in:')) {
            console.error('');
            console.error('❌❌❌ PHP ERROR DETECTED! ❌❌❌');
            console.error('');
            
            // Extract the error message
            const errorLines = orgPageText.split('\n')
                .filter(line => line.includes('Error') || line.includes('Call to') || line.includes('line'))
                .slice(0, 10);
            
            errorLines.forEach(line => console.error('  ', line.trim()));
            
            // Find the specific error type
            if (orgPageText.includes('Call to undefined method')) {
                const methodMatch = orgPageText.match(/Call to undefined method ([^\n]+)/);
                if (methodMatch) {
                    console.error('');
                    console.error('🔴 METHOD ERROR:', methodMatch[1]);
                }
            }
            
            throw new Error('PHP Error on admin/organizations page! See console output above.');
        }
        
        if (orgUrl.includes('/admin/organizations') && !orgUrl.includes('login')) {
            console.log('✅ URL is /admin/organizations (not redirected)');
            
            if (orgPageText.includes('Organizations') || orgPageText.includes('Organisationen')) {
                console.log('✅ SUCCESS! Organizations page loaded correctly');
            } else if (orgPageText.includes('denied') || orgPageText.includes('verweigert')) {
                console.error('❌ ACCESS DENIED message shown');
                console.error('Error excerpt:', orgPageText.substring(0, 500));
                
                // Try to find the exact error
                const errorMatch = orgPageText.match(/(?:denied|verweigert|access|zugriff)[^.]{0,200}/i);
                if (errorMatch) {
                    console.error('Error message:', errorMatch[0]);
                }
            } else {
                console.warn('⚠️  Page loaded but content unclear');
                console.log('Page excerpt:', orgPageText.substring(0, 300));
            }
        } else if (orgUrl.includes('login')) {
            console.error('❌ Redirected to login - session lost or not authorized');
        } else if (orgUrl.includes('dashboard')) {
            console.error('❌ Redirected to dashboard - no permission');
        } else {
            console.error('❌ Unexpected redirect to:', orgUrl);
        }
        
        // Check if there's an error container
        const errorContainer = await page.locator('.error-container, .error, .flash').count();
        if (errorContainer > 0) {
            console.log('');
            console.log('🔍 Step 6: Found error elements');
            const errorText = await page.locator('.error-container, .error, .flash').first().textContent();
            console.error('Error text:', errorText);
        }
        
        // Final assertion
        expect(orgUrl).toContain('/admin/organizations');
        expect(orgPageText).toMatch(/Organizations|Organisationen/i);
    });
    
    test('should check what user identity shows', async ({ page }) => {
        console.log('🔍 Checking user identity after login');
        
        // Login
        await page.goto('https://ausfallplan-generator.z11.de/login');
        await page.fill('input[name="email"]', 'admin@demo.kita');
        await page.fill('input[name="password"]', 'asbdasdaddd');
        await page.click('button[type="submit"]');
        await page.waitForTimeout(2000);
        
        // Try to access a page that shows user info
        await page.goto('https://ausfallplan-generator.z11.de/dashboard');
        await page.waitForTimeout(1000);
        
        const bodyText = await page.textContent('body');
        
        // Look for email display
        if (bodyText.includes('admin@demo.kita')) {
            console.log('✅ User email shown on page - user is logged in');
        } else {
            console.warn('⚠️  User email not visible on page');
        }
        
        // Check if user menu exists
        const userMenu = await page.locator('.user-menu, .user-avatar, [data-user]').count();
        console.log(`User menu elements found: ${userMenu}`);
    });
});
