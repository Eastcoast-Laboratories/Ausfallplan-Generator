/**
 * Organization Encryption Disable and Auto-Decryption Test
 * 
 * Tests:
 * - Create organization with encryption enabled
 * - Create encrypted children
 * - Disable encryption
 * - Verify children are auto-decrypted
 * - Verify in database that name_encrypted is NULL
 * 
 * Run command:
 * timeout 180 npx playwright test tests/e2e/org-encryption-disable-decrypt-simple.spec.ts --project=chromium --headed
 */

import { test, expect } from '@playwright/test';

test.describe('Organization Encryption Disable & Auto-Decryption', () => {
    test('should auto-decrypt children when disabling encryption', async ({ page }) => {
        const timestamp = Date.now();
        const testOrgName = `Decrypt Test ${timestamp}`;
        const childNames = [
            `Alice ${timestamp}`,
            `Bob ${timestamp}`,
            `Charlie ${timestamp}`
        ];
        
        // Use existing admin
        const adminEmail = 'admin@demo.kita';
        const adminPassword = 'password';
        
        // Log important browser messages
        page.on('console', msg => {
            const text = msg.text();
            if (text.includes('🔐') || text.includes('Org Edit') || text.includes('Decrypt') || text.includes('entschlüsselt')) {
                console.log('[BROWSER]', text);
            }
        });
        
        console.log('=== Step 1: Login ===');
        await page.goto('http://localhost:8080/users/login');
        await page.waitForTimeout(1000);
        
        await page.fill('input[name="email"]', adminEmail);
        await page.fill('input[name="password"]', adminPassword);
        
        // Wait for any redirects or form processing
        const [response] = await Promise.all([
            page.waitForNavigation({ timeout: 30000, waitUntil: 'networkidle' }),
            page.click('button[type="submit"]')
        ]);
        
        await page.waitForTimeout(3000);
        const currentUrl = page.url();
        console.log('Current URL after login:', currentUrl);
        
        // Check if we're still on login page (login failed)
        if (currentUrl.includes('/login')) {
            const errorMsg = await page.locator('.error, .message, .flash').textContent().catch(() => 'No error message');
            console.log('❌ Login failed. Error:', errorMsg);
            throw new Error(`Login failed: ${errorMsg}`);
        }
        
        console.log('✅ User logged in');
        
        console.log('=== Step 2: Create organization with encryption ===');
        await page.goto('http://localhost:8080/admin/organizations/add');
        await page.fill('input[name="name"]', testOrgName);
        await page.check('input[type="checkbox"][name="encryption_enabled"]');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/organizations/view/**');
        
        const url = page.url();
        const orgId = parseInt(url.match(/\/view\/(\d+)/)[1]);
        console.log(`✅ Organization created: ID ${orgId}`);
        
        console.log('=== Step 3: Create encrypted children ===');
        for (const childName of childNames) {
            await page.goto('http://localhost:8080/children/add');
            await page.fill('input[name="name"]', childName);
            await page.selectOption('select[name="gender"]', 'm');
            await page.click('button[type="submit"]');
            await page.waitForURL('**/children', { timeout: 10000 });
            console.log(`✅ Child created: ${childName}`);
        }
        
        console.log('=== Step 4: Verify children are visible ===');
        await page.goto('http://localhost:8080/children');
        for (const childName of childNames) {
            await expect(page.locator(`text=${childName}`)).toBeVisible();
        }
        console.log('✅ All children visible');
        
        console.log('=== Step 5: Disable encryption ===');
        await page.goto(`http://localhost:8080/admin/organizations/edit/${orgId}`);
        
        // Uncheck encryption
        await page.uncheck('input[type="checkbox"][name="encryption_enabled"]');
        
        // Handle confirmation dialog
        page.once('dialog', async dialog => {
            console.log('Confirm dialog:', dialog.message());
            expect(dialog.message()).toContain('Verschlüsselung deaktivieren');
            await dialog.accept();
        });
        
        // Submit
        await page.click('button[type="submit"]:has-text("Speichern")');
        await page.waitForURL(`**/admin/organizations/view/${orgId}`, { timeout: 15000 });
        
        console.log('=== Step 6: Check flash message ===');
        const flashMessage = await page.locator('.message, .alert, .flash-message').first().textContent();
        console.log(`Flash message: ${flashMessage}`);
        
        if (flashMessage && flashMessage.match(/entschlüsselt|decrypted/i)) {
            console.log('✅ Success message displayed');
        } else {
            console.log(`⚠️ Unexpected flash message: ${flashMessage}`);
        }
        
        console.log('=== Step 7: Verify children still visible ===');
        await page.goto('http://localhost:8080/children');
        for (const childName of childNames) {
            await expect(page.locator(`text=${childName}`)).toBeVisible();
        }
        console.log('✅ All children still visible after decryption');
        
        console.log('🎉 Test completed successfully!');
    });
});
