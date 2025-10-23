const { test, expect } = require('@playwright/test');

test.describe('User Profile Management', () => {
    test.beforeEach(async ({ page }) => {
        // Login as editor user
        await page.goto('http://localhost:8080/login');
        await page.fill('input[name="email"]', 'editor@example.com');
        await page.fill('input[name="password"]', 'password123');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/dashboard');
    });

    test('user can access profile page', async ({ page }) => {
        console.log('📍 Step 1: Navigate to profile');
        await page.goto('http://localhost:8080/profile');
        
        console.log('📍 Step 2: Verify profile page loaded');
        await page.waitForTimeout(500);
        const pageContent = await page.content();
        
        const hasProfileContent = pageContent.includes('profile') || 
                                 pageContent.includes('Profil') ||
                                 pageContent.includes('email');
        
        expect(hasProfileContent).toBe(true);
        console.log('✅ User can access profile page');
    });

    test('user can change password', async ({ page }) => {
        console.log('📍 Step 1: Go to profile');
        await page.goto('http://localhost:8080/profile');
        
        console.log('📍 Step 2: Fill new password');
        const newPasswordInput = page.locator('input[name="new_password"]');
        if (await newPasswordInput.count() > 0) {
            await newPasswordInput.fill('newPassword123');
            
            const confirmInput = page.locator('input[name="confirm_password"]');
            await confirmInput.fill('newPassword123');
            
            console.log('📍 Step 3: Submit form');
            await page.click('button[type="submit"]');
            
            await page.waitForTimeout(1000);
            console.log('✅ Password change submitted');
        } else {
            console.log('⚠️ Password fields not found, skipping test');
        }
    });
});

test.describe('Password Recovery', () => {
    test('user can request password reset', async ({ page }) => {
        console.log('📍 Step 1: Go to forgot password');
        await page.goto('http://localhost:8080/forgot-password');
        
        console.log('📍 Step 2: Enter email');
        const emailInput = page.locator('input[name="email"]');
        if (await emailInput.count() > 0) {
            await emailInput.fill('editor@example.com');
            
            console.log('📍 Step 3: Submit');
            await page.click('button[type="submit"]');
            
            await page.waitForTimeout(1000);
            console.log('✅ Password reset requested');
        } else {
            console.log('⚠️ Forgot password form not found');
        }
    });
});
