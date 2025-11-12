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
 * timeout 180 npx playwright test tests/e2e/org-encryption-disable-decrypt.spec.ts --project=chromium --headed
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
        // Step 1: Login as admin
        console.log('Step 1: Logging in...');
        await page.goto('http://localhost:8080/users/login');
        await page.fill('input[name="email"]', 'a10@a.de');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/dashboard', { timeout: 10000 });
        
        // Step 2: Create a new organization
        console.log('Step 2: Creating organization...');
        await page.goto('http://localhost:8080/admin/organizations/add');
        const orgName = `Test Org Encryption ${Date.now()}`;
        await page.fill('input[name="name"]', orgName);
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/organizations/view/**');
        
        // Get organization ID from URL
        const url = page.url();
        const orgIdMatch = url.match(/\/view\/(\d+)/);
        orgId = parseInt(orgIdMatch![1]);
        console.log(`Organization created with ID: ${orgId}`);
        
        // Step 3: Enable encryption for the organization
        console.log('Step 3: Enabling encryption...');
        await page.goto(`http://localhost:8080/admin/organizations/edit/${orgId}`);
        await page.check('input[name="encryption_enabled"]');
        await page.click('button[type="submit"]:has-text("Speichern")');
        await page.waitForURL(`**/admin/organizations/view/${orgId}`);
        
        // Verify encryption is enabled
        const [rows1] = await dbConnection.execute(
            'SELECT encryption_enabled FROM organizations WHERE id = ?',
            [orgId]
        ) as any;
        expect(rows1[0].encryption_enabled).toBe(1);
        console.log('✅ Encryption enabled in database');
        
        // Step 4: Create encrypted children
        console.log('Step 4: Creating encrypted children...');
        const childrenNames = ['Alice Verschlüsselt', 'Bob Encrypted', 'Charlie Secret'];
        
        for (const childName of childrenNames) {
            await page.goto('http://localhost:8080/children/add');
            
            // Select the test organization
            await page.selectOption('select[name="organization_id"]', orgId.toString());
            
            // Fill in child name
            await page.fill('input[name="name"]', childName);
            
            // Select gender
            await page.selectOption('select[name="gender"]', 'm');
            
            // Submit form
            await page.click('button[type="submit"]');
            await page.waitForURL('**/children');
            
            console.log(`Child created: ${childName}`);
        }
        
        // Step 5: Verify children are encrypted in database
        console.log('Step 5: Verifying encryption in database...');
        const [rows2] = await dbConnection.execute(
            'SELECT id, name, name_encrypted FROM children WHERE organization_id = ? ORDER BY id',
            [orgId]
        ) as any;
        
        expect(rows2.length).toBe(3);
        
        for (const row of rows2) {
            childIds.push(row.id);
            console.log(`Child ${row.id}: name='${row.name}', name_encrypted='${row.name_encrypted ? row.name_encrypted.substring(0, 50) + '...' : 'NULL'}'`);
            
            // Verify name_encrypted is set and looks like encrypted data
            expect(row.name_encrypted).not.toBeNull();
            expect(row.name_encrypted.length).toBeGreaterThan(100);
        }
        console.log('✅ All children are encrypted in database');
        
        // Step 6: Disable encryption and verify auto-decryption
        console.log('Step 6: Disabling encryption and verifying auto-decryption...');
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
        
        // Check for success message
        const flashMessage = await page.locator('.message, .alert, .flash-message').first().textContent();
        console.log(`Flash message: ${flashMessage}`);
        expect(flashMessage).toMatch(/entschlüsselt|decrypted/i);
        
        // Step 7: Verify children are decrypted in database
        console.log('Step 7: Verifying decryption in database...');
        const [rows3] = await dbConnection.execute(
            'SELECT id, name, name_encrypted FROM children WHERE organization_id = ? ORDER BY id',
            [orgId]
        ) as any;
        
        expect(rows3.length).toBe(3);
        
        for (let i = 0; i < rows3.length; i++) {
            const row = rows3[i];
            console.log(`Child ${row.id}: name='${row.name}', name_encrypted='${row.name_encrypted}'`);
            
            // Verify name is decrypted
            expect(row.name).toBe(childrenNames[i]);
            
            // Verify name_encrypted is NULL
            expect(row.name_encrypted).toBeNull();
        }
        console.log('✅ All children are decrypted in database');
        
        // Verify encryption is disabled
        const [rows4] = await dbConnection.execute(
            'SELECT encryption_enabled FROM organizations WHERE id = ?',
            [orgId]
        ) as any;
        expect(rows4[0].encryption_enabled).toBe(0);
        console.log('✅ Encryption disabled in database');
        
        console.log('🎉 Test completed successfully!');
    });
});
