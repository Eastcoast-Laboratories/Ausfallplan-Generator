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
        await page.waitForSelector('a:has-text("Verify Email")');
        await page.click('a:has-text("Verify Email")');
        await page.waitForURL(/users\/verify\//, { timeout: 5000 });
        await page.waitForTimeout(1000);
        console.log('✅ Email verified');
        
        console.log('=== Step 1.6: Login ===');
        await page.goto('http://localhost:8080/users/login');
        await page.fill('input[name="email"]', testEmail);
        await page.fill('input[name="password"]', testPassword);
        await page.click('button[type="submit"]');
        
        // Wait for dashboard
        await page.waitForURL(/\/(dashboard|organizations)/, { timeout: 15000 });
        console.log('✅ User logged in');
        
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
        await page.check('input[name="encryption_enabled"]');
        await page.click('button[type="submit"]:has-text("Speichern")');
        await page.waitForURL(`**/admin/organizations/view/${orgId}`);
        console.log('✅ Encryption enabled');
        
        console.log('=== Step 3: Create encrypted children ===');
        for (const childName of childrenNames) {
            await page.goto('http://localhost:8080/children/add');
            
            // Fill in child name
            await page.fill('input[name="name"]', childName);
            
            // Select gender
            await page.selectOption('select[name="gender"]', 'm');
            
            // Submit form
            await page.click('button[type="submit"]');
            await page.waitForURL('**/children');
            
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
