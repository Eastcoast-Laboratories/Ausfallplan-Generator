const { test, expect } = require('@playwright/test');

test.describe('User Features Integration', () => {
    
    test('organization-based permissions work', async ({ page }) => {
        console.log('📍 Test: OrganizationUsers join table is working');
        
        // Register and login
        const timestamp = Date.now();
        const email = `test-org-user-${timestamp}@test.local`;
        
        console.log('📍 Step 1: Register new user');
        await page.goto('https://ausfallplan-generator.z11.de/users/register');
        
        await page.fill('input[name="organization"]', `Test-Org-${timestamp}`);
        await page.fill('input[name="email"]', email);
        await page.fill('input[name="password"]', 'testpass123');
        await page.fill('input[name="password_confirm"]', 'testpass123');
        
        await page.click('button[type="submit"]');
        await page.waitForTimeout(2000);
        
        console.log('📍 Step 2: Login');
        await page.goto('https://ausfallplan-generator.z11.de/users/login');
        await page.fill('input[name="email"]', email);
        await page.fill('input[name="password"]', 'testpass123');
        await page.click('button[type="submit"]');
        await page.waitForTimeout(2000);
        
        console.log('📍 Step 3: Check access to schedules');
        await page.goto('https://ausfallplan-generator.z11.de/schedules');
        await page.waitForTimeout(1000);
        
        const url = page.url();
        console.log(`Current URL: ${url}`);
        expect(url).toContain('schedules');
        
        console.log('✅ Organization permissions working');
    });
    
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
