/**
 * Organization Encryption Disable and Auto-Decryption Test
 * 
 * Test Flow:
 * 1. Register new user with new organization  
 * 2. Enable encryption for the organization
 * 3. Create children with names (should be encrypted)
 * 4. Verify children are displayed correctly in list
 * 5. Disable encryption for the organization
 * 6. Verify success message about decryption
 * 7. Verify children are still displayed correctly
 * 
 * Run command:
 * timeout 180 npx playwright test tests/e2e/org-encryption-disable-decrypt-simple.spec.ts --project=chromium --headed
 */

import { test, expect } from '@playwright/test';

test.describe('Organization Encryption Disable & Auto-Decryption', () => {
    test('should auto-decrypt children names when disabling organization encryption', async ({ page }) => {
        const timestamp = Date.now();
        const testEmail = `decrypt-test-${timestamp}@example.com`;
        const testPassword = 'TestPassword123!';
        const testOrgName = `Decrypt Test Org ${timestamp}`;
        const childrenNames = [
            `Alice Verschlüsselt ${timestamp}`,
            `Bob Encrypted ${timestamp}`,
            `Charlie Secret ${timestamp}`
        ];
        
        // Listen to console logs for debugging
        page.on('console', msg => {
            if (msg.text().includes('🔐') || msg.text().includes('🔓') || msg.text().includes('Decrypt')) {
                console.log('[BROWSER]', msg.text());
            }
        });
        
        console.log('=== Step 1: Register new user with encryption ===');
        await page.goto('http://localhost:8080/users/register');
        
        // Select "new organization"
        await page.selectOption('#organization-choice', 'new');
        await page.waitForTimeout(500);
        
        // Fill organization name
        await page.fill('#organization-name-input', testOrgName);
        
        // Fill user details
        await page.fill('input[name="email"]', testEmail);
        await page.fill('input[name="password"]', testPassword);
        await page.fill('input[name="password_confirm"]', testPassword);
        
        // Submit registration - will redirect to login
        await Promise.all([
            page.waitForURL(/\/(users\/)?login/, { timeout: 15000 }),
            page.click('button[type="submit"]')
        ]);
        console.log('✅ User registered');
        
        console.log('=== Step 1.5: Verify Email ===');
        // Go to debug emails page to verify email
        await page.goto('http://localhost:8080/debug/emails');
        
        // Wait for the page to load
        await page.waitForTimeout(2000);
        
        // Get the HTML content and search for our email address to find the right verify link
        const htmlContent = await page.content();
        
        // Find the section containing our test email
        const emailSectionRegex = new RegExp(`To:.*?${testEmail.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}[\\s\\S]*?href="(http:\\/\\/localhost:8080\\/users\\/verify\\/[a-f0-9]+)"`, 'i');
        const match = htmlContent.match(emailSectionRegex);
        
        if (!match || !match[1]) {
            console.log('Could not find verify link for our email, trying fallback...');
            // Fallback: get all links and use the last one
            const verifyLinkMatches = htmlContent.matchAll(/href="(http:\/\/localhost:8080\/users\/verify\/[a-f0-9]+)"/g);
            const allMatches = [...verifyLinkMatches];
            if (allMatches.length === 0) {
                throw new Error('No verify links found');
            }
            var verifyLink = allMatches[allMatches.length - 1][1];
        } else {
            var verifyLink = match[1];
        }
        
        console.log(`Using verify link: ${verifyLink}`);
        
        // Navigate to the verify URL directly
        await page.goto(verifyLink);
        await page.waitForTimeout(2000);
        
        // Check if verification was successful
        const pageContent = await page.content();
        if (pageContent.includes('verified') || pageContent.includes('success') || page.url().includes('/login')) {
            console.log('✅ Email verified, current URL:', page.url());
        } else {
            console.log('⚠️ Verification page content:', pageContent.substring(0, 500));
            throw new Error('Email verification may have failed');
        }
        
        console.log('=== Step 1.6: Login ===');
        await page.goto('http://localhost:8080/users/login');
        await page.fill('input[name="email"]', testEmail);
        await page.fill('input[name="password"]', testPassword);
        await page.click('button[type="submit"]');
        
        // Wait for dashboard
        try {
            await page.waitForURL(/\/(dashboard|organizations)/, { timeout: 15000 });
        } catch (e) {
            const currentUrl = page.url();
            console.log('Login timeout - Current URL:', currentUrl);
            if (currentUrl.includes('/login')) {
                const errorMsg = await page.locator('.error, .message.error, .flash.error').textContent().catch(() => 'No error found');
                console.log('Login error:', errorMsg);
                throw new Error(`Login failed: ${errorMsg}`);
            }
        }
        
        await page.waitForTimeout(2000);
        console.log('✅ User logged in, URL:', page.url());
        
        // Get organization ID from organizations page
        await page.goto('http://localhost:8080/admin/organizations');
        await page.waitForSelector('table');
        
        // Find our organization in the table and click on it
        const orgRow = page.locator(`tr:has-text("${testOrgName}")`);
        await orgRow.locator('a:has-text("Ansehen")').first().click();
        await page.waitForURL('**/admin/organizations/view/**');
        
        const url = page.url();
        const orgIdMatch = url.match(/\/view\/(\d+)/);
        const orgId = parseInt(orgIdMatch![1]);
        console.log(`✅ Organization ID: ${orgId}`);
        
        console.log('=== Step 2: Enable encryption for the organization ===');
        await page.goto(`http://localhost:8080/admin/organizations/edit/${orgId}`);
        await page.check('input[type="checkbox"][name="encryption_enabled"]');
        await page.click('button[type="submit"]:has-text("Speichern")');
        await page.waitForURL(`**/admin/organizations/view/${orgId}`);
        console.log('✅ Encryption enabled');
        
        console.log('=== Step 2.5: Setup encryption keys (DEK) for organization ===');
        // Go to profile page to setup encryption for this org
        await page.goto('http://localhost:8080/users/profile');
        await page.waitForTimeout(2000);
        
        // Check if "Setup Encryption" button exists
        const setupButton = page.locator('button:has-text("Setup Encryption"), button:has-text("Verschlüsselung einrichten")');
        const hasSetupButton = await setupButton.isVisible().catch(() => false);
        
        if (hasSetupButton) {
            console.log('Setup Encryption button found, clicking...');
            await setupButton.click();
            await page.waitForTimeout(1000);
            
            // Enter password
            const passwordInput = page.locator('input[type="password"]').first();
            await passwordInput.fill(testPassword);
            
            // Click confirm button
            await page.click('button:has-text("Setup"), button:has-text("Einrichten")');
            await page.waitForTimeout(3000);
            console.log('✅ DEK created for organization');
        } else {
            console.log('✅ Encryption already setup (DEK exists)');
        }
        
        console.log('=== Step 3: Create encrypted children ===');
        for (let i = 0; i < childrenNames.length; i++) {
            const childName = childrenNames[i];
            await page.goto('http://localhost:8080/children/add');
            
            // For the first child, we may need to enter the password to unlock keys
            if (i === 0) {
                await page.waitForTimeout(2000);
                // Check if password modal is visible
                const passwordModal = page.locator('#encryption-password-modal, .modal:has-text("Password")');
                const isModalVisible = await passwordModal.isVisible().catch(() => false);
                
                if (isModalVisible) {
                    console.log('Password modal detected, entering password...');
                    await page.fill('input[type="password"]', testPassword);
                    await page.click('button:has-text("Unlock"), button:has-text("Entsperren")');
                    await page.waitForTimeout(2000);
                }
            }
            
            // Fill in child name
            await page.fill('input[name="name"]', childName);
            
            // Select gender
            await page.selectOption('select[name="gender"]', 'm');
            
            // Submit form
            await page.click('button[type="submit"]');
            await page.waitForURL('**/children', { timeout: 10000 });
            
            console.log(`✅ Child created: ${childName}`);
        }
        
        console.log('=== Step 4: Verify children are displayed in list ===');
        await page.goto('http://localhost:8080/children');
        
        // Check that all children are visible
        for (const childName of childrenNames) {
            await expect(page.locator(`text=${childName}`)).toBeVisible();
            console.log(`✅ Child visible in list: ${childName}`);
        }
        
        console.log('=== Step 5: Disable encryption and verify auto-decryption ===');
        await page.goto(`http://localhost:8080/admin/organizations/edit/${orgId}`);
        
        // Uncheck encryption
        await page.uncheck('input[name="encryption_enabled"]');
        
        // Click Save - this will trigger the confirmation dialog
        page.once('dialog', async dialog => {
            console.log('Dialog appeared:', dialog.message());
            expect(dialog.message()).toContain('Verschlüsselung deaktivieren');
            await dialog.accept();
        });
        
        await page.click('button[type="submit"]:has-text("Speichern")');
        
        // Wait for redirect back to view page
        await page.waitForURL(`**/admin/organizations/view/${orgId}`, { timeout: 15000 });
        
        console.log('=== Step 6: Verify success message ===');
        // Check for success message
        const flashMessage = await page.locator('.message, .alert, .flash-message').first().textContent();
        console.log(`Flash message: ${flashMessage}`);
        expect(flashMessage).toMatch(/entschlüsselt|decrypted|Verschlüsselung deaktiviert/i);
        console.log('✅ Success message displayed');
        
        console.log('=== Step 7: Verify children are still displayed correctly ===');
        await page.goto('http://localhost:8080/children');
        
        // Check that all children are still visible after decryption
        for (const childName of childrenNames) {
            await expect(page.locator(`text=${childName}`)).toBeVisible();
            console.log(`✅ Child still visible after decryption: ${childName}`);
        }
        
        console.log('🎉 Test completed successfully!');
    });
});
