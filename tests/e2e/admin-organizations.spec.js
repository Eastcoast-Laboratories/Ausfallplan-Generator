const { test, expect } = require('@playwright/test');

test.describe('Admin Organizations Management', () => {
    test.beforeEach(async ({ page }) => {
        // Login as admin
        await page.goto('http://localhost:8080/login');
        await page.fill('input[name="email"]', 'admin@example.com');
        await page.fill('input[name="password"]', 'password123');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/dashboard');
    });

    test('admin can access organizations page', async ({ page }) => {
        console.log('📍 Step 1: Click on Organizations link');
        await page.click('a[href="/admin/organizations"]');
        
        console.log('📍 Step 2: Verify URL');
        await expect(page).toHaveURL(/admin\/organizations/);
        
        console.log('📍 Step 3: Verify page title');
        const pageContent = await page.content();
        const hasOrganizationsTitle = pageContent.includes('Organizations') || pageContent.includes('Organisationen');
        expect(hasOrganizationsTitle).toBe(true);
        
        console.log('✅ Admin can access organizations page');
    });

    test('admin can view organization details', async ({ page }) => {
        console.log('📍 Step 1: Navigate to organizations');
        await page.goto('http://localhost:8080/admin/organizations');
        
        console.log('📍 Step 2: Click on first View link');
        const viewLink = page.locator('a:has-text("View"), a:has-text("Ansehen")').first();
        await viewLink.click();
        
        console.log('📍 Step 3: Verify organization details page');
        await page.waitForTimeout(500);
        await expect(page).toHaveURL(/admin\/organizations\/view\/\d+/);
        
        console.log('✅ Admin can view organization details');
    });

    test('admin can edit organization', async ({ page }) => {
        console.log('📍 Step 1: Navigate to organizations');
        await page.goto('http://localhost:8080/admin/organizations');
        
        console.log('📍 Step 2: Click first Edit link');
        const editLink = page.locator('a:has-text("Edit"), a:has-text("Bearbeiten")').first();
        await editLink.click();
        
        console.log('📍 Step 3: Verify edit page');
        await expect(page).toHaveURL(/admin\/organizations\/edit\/\d+/);
        
        console.log('📍 Step 4: Fill form');
        const emailInput = page.locator('input[name="contact_email"]');
        await emailInput.fill('test@org.com');
        
        console.log('📍 Step 5: Save');
        await page.click('button[type="submit"]');
        
        console.log('📍 Step 6: Verify redirect to view page');
        await page.waitForTimeout(1000);
        await expect(page).toHaveURL(/admin\/organizations\/view\/\d+/);
        
        console.log('✅ Admin can edit organization');
    });

    test('organizations table shows stats', async ({ page }) => {
        console.log('📍 Step 1: Navigate to organizations');
        await page.goto('http://localhost:8080/admin/organizations');
        
        console.log('📍 Step 2: Check for stats columns');
        const pageContent = await page.content();
        
        // Check for Users and Children columns
        const hasUsersColumn = pageContent.includes('Users') || pageContent.includes('Benutzer');
        const hasChildrenColumn = pageContent.includes('Children') || pageContent.includes('Kinder');
        
        console.log(`Has Users column: ${hasUsersColumn}`);
        console.log(`Has Children column: ${hasChildrenColumn}`);
        
        expect(hasUsersColumn || hasChildrenColumn).toBe(true);
        
        console.log('✅ Organizations table shows stats');
    });

    test('normal user cannot access admin organizations', async ({ page }) => {
        console.log('📍 Step 1: Logout admin');
        await page.goto('http://localhost:8080/logout');
        
        console.log('📍 Step 2: Login as normal user');
        await page.goto('http://localhost:8080/login');
        await page.fill('input[name="email"]', 'editor@example.com');
        await page.fill('input[name="password"]', 'password123');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/dashboard');
        
        console.log('📍 Step 3: Try to access organizations');
        await page.goto('http://localhost:8080/admin/organizations');
        
        console.log('📍 Step 4: Should be redirected or see error');
        await page.waitForTimeout(1000);
        
        // Either redirected to dashboard or sees access denied
        const url = page.url();
        const isBlocked = url.includes('dashboard') || url.includes('login');
        
        expect(isBlocked).toBe(true);
        
        console.log('✅ Normal user cannot access admin organizations');
    });
});
