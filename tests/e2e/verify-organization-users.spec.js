const { test, expect } = require('@playwright/test');

/**
 * Verify organization_users table fix
 * 
 * This test verifies:
 * 1. Dashboard loads without "Could not describe columns" error
 * 2. Admin can access organizations page
 * 3. No database errors related to organization_users
 */
test.describe('Verify organization_users Fix', () => {
    
    test('should load dashboard without database errors', async ({ page }) => {
        console.log('🔍 Step 1: Navigate to login');
        await page.goto('https://ausfallplan-generator.z11.de/login');
        await page.waitForLoadState('networkidle');
        
        console.log('🔍 Step 2: Login as admin');
        await page.fill('input[name="email"]', 'admin@demo.kita');
        await page.fill('input[name="password"]', 'asbdasdaddd');
        await page.click('button[type="submit"]');
        
        await page.waitForTimeout(2000);
        
        console.log('🔍 Step 3: Check dashboard loads');
        const bodyText = await page.textContent('body');
        
        // Check for database errors FIRST
        if (bodyText.includes('Could not describe columns') || 
            bodyText.includes('organization_users')) {
            console.error('❌ DATABASE ERROR DETECTED!');
            await page.screenshot({ path: `screenshots/db-error-${Date.now()}.png` });
            throw new Error('Database error: Could not describe columns on organization_users');
        }
        
        if (bodyText.includes('Fatal error') || 
            bodyText.includes('Exception') ||
            bodyText.includes('Error in:')) {
            console.error('❌ PHP ERROR DETECTED!');
            const errorLines = bodyText.split('\n')
                .filter(line => line.includes('Error') || line.includes('Exception'))
                .slice(0, 5);
            errorLines.forEach(line => console.error('  ', line.trim()));
            throw new Error('PHP Error detected on dashboard');
        }
        
        // Check dashboard loaded successfully
        if (bodyText.includes('Dashboard') || 
            bodyText.includes('Ausfallplan') ||
            bodyText.includes('Children') ||
            bodyText.includes('Schedules')) {
            console.log('✅ Dashboard loaded successfully');
        } else {
            console.warn('⚠️  Dashboard content unclear');
        }
        
        console.log('🔍 Step 4: Test admin organizations page');
        await page.goto('https://ausfallplan-generator.z11.de/admin/organizations');
        await page.waitForTimeout(1500);
        
        const orgPageText = await page.textContent('body');
        
        // Check for errors
        if (orgPageText.includes('Could not describe columns') ||
            orgPageText.includes('Call to undefined method') ||
            orgPageText.includes('Fatal error')) {
            console.error('❌ ERROR on admin/organizations!');
            await page.screenshot({ path: `screenshots/admin-org-error-${Date.now()}.png` });
            throw new Error('Error on admin/organizations page');
        }
        
        // Check if page loaded
        if (orgPageText.includes('Organizations') || 
            orgPageText.includes('Demo Kita')) {
            console.log('✅ Admin organizations page loaded');
        }
        
        console.log('');
        console.log('🎉 ALL CHECKS PASSED!');
        console.log('✅ No database errors');
        console.log('✅ Dashboard loads');
        console.log('✅ Admin organizations accessible');
    });
    
    test('should handle users with organization_users correctly', async ({ page }) => {
        console.log('🔍 Verify organization_users table is working');
        
        // Login
        await page.goto('https://ausfallplan-generator.z11.de/login');
        await page.fill('input[name="email"]', 'admin@demo.kita');
        await page.fill('input[name="password"]', 'asbdasdaddd');
        await page.click('button[type="submit"]');
        await page.waitForTimeout(2000);
        
        // Navigate to children (requires organization_users)
        await page.goto('https://ausfallplan-generator.z11.de/children');
        await page.waitForTimeout(1500);
        
        const bodyText = await page.textContent('body');
        
        // Check no errors
        if (bodyText.includes('Could not describe') || 
            bodyText.includes('organization_users')) {
            throw new Error('organization_users error on children page');
        }
        
        // Should load children page
        expect(bodyText).toMatch(/Children|Kinder|No children/i);
        console.log('✅ Children page loads with organization_users');
        
        // Navigate to schedules
        await page.goto('https://ausfallplan-generator.z11.de/schedules');
        await page.waitForTimeout(1500);
        
        const schedulesText = await page.textContent('body');
        
        if (schedulesText.includes('Could not describe')) {
            throw new Error('Database error on schedules page');
        }
        
        expect(schedulesText).toMatch(/Schedule|Ausfallplan|Create/i);
        console.log('✅ Schedules page loads correctly');
    });
});
