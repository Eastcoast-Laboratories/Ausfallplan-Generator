/**
 * Online Login Test
 * 
 * Tests:
 * - Login to production server (https://ausfallplan-generator.z11.de)
 * - Navigation to /schedules page
 * - Navigation to /children page
 * - Verify user is logged in (logout link visible)
 * - Support for German and English page titles
 * 
 * Run command:
 * npx playwright test tests/e2e/online-login.spec.ts --project=chromium --headed
 */

import { test, expect } from '@playwright/test';

test.describe('Online Login Test', () => {
    const BASE_URL = 'https://ausfallplan-generator.z11.de';
    
    test('should login successfully and access schedules', async ({ page }) => {
        // Navigate to login page
        await page.goto(`${BASE_URL}/users/login`);
        
        // Wait for page to load
        await page.waitForLoadState('networkidle');
        
        // Fill login form
        await page.fill('input[name="email"]', 'admin@example.com');
        await page.fill('input[name="password"]', 'admin123');
        
        // Submit form
        await page.click('button[type="submit"]');
        
        // Wait for navigation
        await page.waitForURL(/children|schedules|dashboard/, { timeout: 10000 });
        
        // Should be logged in - check for logout link or user menu
        const isLoggedIn = await page.locator('a[href*="logout"], .user-menu').count() > 0;
        expect(isLoggedIn).toBeTruthy();
        
        // Navigate to schedules
        await page.goto(`${BASE_URL}/schedules`);
        await page.waitForLoadState('networkidle');
        
        // Should see schedules page (German: "Ausfallpläne")
        const title = await page.title();
        expect(title).toMatch(/Schedules|Ausfallpläne/);
        
        // Navigate to children
        await page.goto(`${BASE_URL}/children`);
        await page.waitForLoadState('networkidle');
        
        // Should see children page (German: "Kinder")
        const childrenTitle = await page.title();
        expect(childrenTitle).toMatch(/Children|Kinder/);
        
        console.log('✅ Online login and navigation successful!');
    });
});
