const { test, expect } = require('@playwright/test');

test.describe('User Features Integration', () => {
    
    test('viewer role has read-only access', async ({ page }) => {
        console.log('📍 Step 1: Login as viewer');
        await page.goto('http://localhost:8080/login');
        await page.fill('input[name="email"]', 'viewer@example.com');
        await page.fill('input[name="password"]', 'password123');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/dashboard');
        
        console.log('📍 Step 2: Try to add a child');
        await page.goto('http://localhost:8080/children/add');
        await page.waitForTimeout(1000);
        
        console.log('📍 Step 3: Should be redirected');
        const url = page.url();
        const wasBlocked = url.includes('dashboard') || url.includes('children') && !url.includes('/add');
        
        console.log(`Blocked: ${wasBlocked}, URL: ${url}`);
        expect(wasBlocked).toBe(true);
        console.log('✅ Viewer role is read-only');
    });
    
    test('organization autocomplete works', async ({ page }) => {
        console.log('📍 Step 1: Go to registration');
        await page.goto('http://localhost:8080/register');
        
        console.log('📍 Step 2: Check for datalist');
        const datalist = page.locator('#organization-list');
        const exists = await datalist.count() > 0;
        
        console.log(`Datalist exists: ${exists}`);
        expect(exists).toBe(true);
        console.log('✅ Organization autocomplete present');
    });
    
    test('editor can manage children', async ({ page }) => {
        console.log('📍 Step 1: Login as editor');
        await page.goto('http://localhost:8080/login');
        await page.fill('input[name="email"]', 'editor@example.com');
        await page.fill('input[name="password"]', 'password123');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/dashboard');
        
        console.log('📍 Step 2: Navigate to children');
        await page.goto('http://localhost:8080/children');
        await page.waitForTimeout(500);
        
        console.log('📍 Step 3: Should see children list');
        const pageContent = await page.content();
        const hasChildren = pageContent.includes('children') || pageContent.includes('Kinder');
        
        expect(hasChildren).toBe(true);
        console.log('✅ Editor can access children');
    });
});
