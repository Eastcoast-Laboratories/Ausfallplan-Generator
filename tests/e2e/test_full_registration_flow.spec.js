const { test, expect } = require('@playwright/test');

test('Complete registration flow - new organization', async ({ page }) => {
    console.log('🧪 Testing Complete Registration Flow (New Org)\n');
    
    const timestamp = Date.now();
    const testEmail = `test${timestamp}@example.com`;
    const testOrgName = `TestOrg ${timestamp}`;
    
    // Go to registration
    await page.goto('http://localhost:8080/users/register');
    await page.waitForLoadState('networkidle');
    
    // Select "New Organization"
    await page.selectOption('select#organization-choice', 'new');
    await page.waitForTimeout(300);
    
    // Fill organization name
    await page.fill('input#organization-name-input', testOrgName);
    console.log(`✅ Organization name: ${testOrgName}`);
    
    // Fill user details
    await page.fill('input[name="email"]', testEmail);
    await page.fill('input[name="password"]', 'testpassword123');
    await page.fill('input[name="password_confirm"]', 'testpassword123');
    console.log(`✅ Email: ${testEmail}`);
    
    // Check that role selector is hidden
    const roleVisible = await page.locator('select#role-selector').isVisible();
    console.log(`Role selector hidden (should be): ${!roleVisible ? 'YES ✅' : 'NO ❌'}`);
    
    // Take screenshot before submit
    await page.screenshot({ 
        path: '/var/www/Ausfallplan-Generator/registration_before_submit.png',
        fullPage: true 
    });
    
    // Submit form
    await page.click('button:has-text("Konto erstellen"), button:has-text("Create Account")');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
    
    // Should redirect to login with success message
    const url = page.url();
    const isOnLogin = url.includes('/users/login');
    console.log(`\nRedirected to login: ${isOnLogin ? 'YES ✅' : 'NO ❌'}`);
    
    // Check for success message
    const pageContent = await page.textContent('body');
    const hasSuccess = pageContent.includes('Registration successful') || 
                       pageContent.includes('Registrierung erfolgreich') ||
                       pageContent.includes('admin of your new organization');
    console.log(`Success message visible: ${hasSuccess ? 'YES ✅' : 'NO ❌'}`);
    
    // Take screenshot of result
    await page.screenshot({ 
        path: '/var/www/Ausfallplan-Generator/registration_success.png',
        fullPage: true 
    });
    
    console.log('\n✅ Registration flow completed successfully!');
    expect(isOnLogin).toBeTruthy();
    expect(hasSuccess).toBeTruthy();
});

test('Complete registration flow - existing organization', async ({ page }) => {
    console.log('🧪 Testing Complete Registration Flow (Existing Org)\n');
    
    const timestamp = Date.now();
    const testEmail = `test${timestamp}@example.com`;
    
    // Go to registration
    await page.goto('http://localhost:8080/users/register');
    await page.waitForLoadState('networkidle');
    
    // Get first existing organization
    const options = await page.locator('select#organization-choice option').allTextContents();
    let firstOrgValue = null;
    for (let i = 0; i < options.length; i++) {
        const optValue = await page.locator(`select#organization-choice option:nth-child(${i+1})`).getAttribute('value');
        if (optValue && optValue !== 'new' && optValue !== 'divider') {
            firstOrgValue = optValue;
            break;
        }
    }
    
    if (!firstOrgValue) {
        console.log('⚠️  No existing organizations found, skipping test');
        return;
    }
    
    // Select existing organization
    await page.selectOption('select#organization-choice', firstOrgValue);
    await page.waitForTimeout(300);
    console.log(`✅ Selected existing organization (ID: ${firstOrgValue})`);
    
    // Check that org name input is hidden
    const orgNameHidden = !(await page.locator('input#organization-name-input').isVisible());
    console.log(`Organization name input hidden: ${orgNameHidden ? 'YES ✅' : 'NO ❌'}`);
    
    // Check that role selector is visible
    const roleVisible = await page.locator('select#role-selector').isVisible();
    console.log(`Role selector visible: ${roleVisible ? 'YES ✅' : 'NO ❌'}`);
    
    // Fill user details
    await page.fill('input[name="email"]', testEmail);
    await page.fill('input[name="password"]', 'testpassword123');
    await page.fill('input[name="password_confirm"]', 'testpassword123');
    console.log(`✅ Email: ${testEmail}`);
    
    // Select role (editor)
    await page.selectOption('select#role-selector', 'editor');
    console.log(`✅ Selected role: editor`);
    
    // Take screenshot before submit
    await page.screenshot({ 
        path: '/var/www/Ausfallplan-Generator/registration_existing_org_before_submit.png',
        fullPage: true 
    });
    
    // Submit form
    await page.click('button:has-text("Konto erstellen"), button:has-text("Create Account")');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
    
    // Should redirect to login with success message
    const url = page.url();
    const isOnLogin = url.includes('/users/login');
    console.log(`\nRedirected to login: ${isOnLogin ? 'YES ✅' : 'NO ❌'}`);
    
    // Check for success message (different for existing org)
    const pageContent = await page.textContent('body');
    const hasSuccess = pageContent.includes('Registration successful') || 
                       pageContent.includes('Registrierung erfolgreich') ||
                       pageContent.includes('admins have been notified') ||
                       pageContent.includes('review your request');
    console.log(`Success message visible: ${hasSuccess ? 'YES ✅' : 'NO ❌'}`);
    
    // Take screenshot of result
    await page.screenshot({ 
        path: '/var/www/Ausfallplan-Generator/registration_existing_org_success.png',
        fullPage: true 
    });
    
    console.log('\n✅ Registration flow (existing org) completed successfully!');
    expect(isOnLogin).toBeTruthy();
    expect(hasSuccess).toBeTruthy();
});
