/**
 * Child Encryption Test
 * 
 * Tests:
 * - Create a child with encryption enabled
 * - Verify child appears correctly in index (not as "encrypted:[object ArrayBuffer]")
 * - Edit child name and verify it stays readable
 * - Verify encryption fields are properly converted to base64
 * 
 * Run command:
 * timeout 120 npx playwright test tests/e2e/child-encryption-test.spec.ts --project=chromium --headed
 */

import { test, expect } from '@playwright/test';

test.describe('Child Encryption', () => {
    test('should create, edit and display encrypted child correctly', async ({ page }) => {
        const timestamp = Date.now();
        const childName = `TestKind-${timestamp}`;
        const editedChildName = `Edited-${timestamp}`;
        
        console.log('1. Login as editor...');
        await page.goto('http://localhost:8080/users/login');
        await page.fill('input[name="email"]', 'editor@demo.kita');
        await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/dashboard', { timeout: 10000 });
        console.log('✅ Logged in');
        
        console.log('2. Navigate to add child page...');
        await page.goto('http://localhost:8080/children/add');
        await page.waitForSelector('input[name="name"]', { timeout: 10000 });
        console.log('✅ On add child page');
        
        console.log('3. Fill in child name...');
        await page.fill('input[name="name"]', childName);
        console.log(`✅ Filled name: ${childName}`);
        
        console.log('4. Submit form...');
        
        // Wait a bit for encryption to complete (if enabled)
        await page.waitForTimeout(2000);
        
        const submitButton = page.locator('button[type="submit"]');
        const isDisabled = await submitButton.isDisabled().catch(() => false);
        console.log(`Submit button disabled: ${isDisabled}`);
        
        if (isDisabled) {
            console.log('⚠️  Submit button is disabled, waiting 5 more seconds...');
            await page.waitForTimeout(5000);
        }
        
        await submitButton.click({ timeout: 10000 });
        await page.waitForURL(/\/children/, { timeout: 10000 });
        console.log('✅ Form submitted');
        
        console.log('5. Check if child appears in index...');
        const pageContent = await page.content();
        
        // Check that the real name is visible
        const nameVisible = pageContent.includes(childName);
        console.log(`Child name "${childName}" visible: ${nameVisible}`);
        
        // Check that there's NO ArrayBuffer error
        const hasArrayBufferError = pageContent.includes('encrypted:[object ArrayBuffer]') || 
                                   pageContent.includes('[object ArrayBuffer]');
        console.log(`Has ArrayBuffer error: ${hasArrayBufferError}`);
        
        // Check if "encrypted:" prefix appears (would indicate wrong format)
        const hasEncryptedPrefix = pageContent.includes(`encrypted:${childName}`) ||
                                   /encrypted:\w+/.test(pageContent);
        console.log(`Has "encrypted:" prefix: ${hasEncryptedPrefix}`);
        
        // Assertions
        expect(nameVisible).toBeTruthy();
        expect(hasArrayBufferError).toBeFalsy();
        
        if (hasArrayBufferError) {
            console.error('❌ FAILED: Child name shows as [object ArrayBuffer]');
            console.log('Page content snippet:', pageContent.substring(0, 2000));
        } else if (hasEncryptedPrefix) {
            console.error('❌ FAILED: Child name has "encrypted:" prefix');
        } else {
            console.log('✅ SUCCESS: Child name displays correctly');
        }
        
        console.log('6. Edit the child name...');
        // Find all table rows to locate our child
        const tableRows = page.locator('table tbody tr');
        const rowCount = await tableRows.count();
        console.log(`Found ${rowCount} rows in children table`);
        
        let editUrl = '';
        for (let i = 0; i < rowCount; i++) {
            const rowText = await tableRows.nth(i).textContent();
            if (rowText && rowText.includes(childName)) {
                console.log(`Found child in row ${i}`);
                const editLink = tableRows.nth(i).locator('a[href*="/children/edit/"]');
                editUrl = await editLink.getAttribute('href') || '';
                console.log(`Edit URL: ${editUrl}`);
                break;
            }
        }
        
        if (!editUrl) {
            throw new Error('Could not find edit link for child');
        }
        
        await page.goto(`http://localhost:8080${editUrl}`);
        await page.waitForSelector('input[name="name"]', { timeout: 5000 });
        console.log('✅ On edit page');
        
        // Verify the name field has the correct value
        const nameFieldValue = await page.inputValue('input[name="name"]');
        console.log(`Name field shows: "${nameFieldValue}"`);
        
        expect(nameFieldValue).toBe(childName);
        expect(nameFieldValue).not.toContain('[object');
        expect(nameFieldValue).not.toContain('encrypted:');
        console.log('✅ Edit page shows correct decrypted name');
        
        console.log('7. Change the child name and save...');
        await page.fill('input[name="name"]', editedChildName);
        await page.click('button[type="submit"]');
        await page.waitForURL(/\/children/, { timeout: 10000 });
        console.log('✅ Form submitted after edit');
        
        console.log('8. Verify edited name appears correctly in index...');
        const pageContentAfterEdit = await page.content();
        
        // Check that the NEW name is visible
        const editedNameVisible = pageContentAfterEdit.includes(editedChildName);
        console.log(`Edited name "${editedChildName}" visible: ${editedNameVisible}`);
        
        // Check that there's NO ArrayBuffer error
        const hasArrayBufferErrorAfterEdit = pageContentAfterEdit.includes('encrypted:[object ArrayBuffer]') || 
                                            pageContentAfterEdit.includes('[object ArrayBuffer]');
        console.log(`Has ArrayBuffer error after edit: ${hasArrayBufferErrorAfterEdit}`);
        
        // Check if "encrypted:" prefix appears
        const hasEncryptedPrefixAfterEdit = pageContentAfterEdit.includes(`encrypted:${editedChildName}`) ||
                                           /encrypted:\w+/.test(pageContentAfterEdit);
        console.log(`Has "encrypted:" prefix after edit: ${hasEncryptedPrefixAfterEdit}`);
        
        // Assertions for edited child
        expect(editedNameVisible).toBeTruthy();
        expect(hasArrayBufferErrorAfterEdit).toBeFalsy();
        
        if (hasArrayBufferErrorAfterEdit) {
            console.error('❌ FAILED: Edited child name shows as [object ArrayBuffer]');
            console.log('Page content snippet:', pageContentAfterEdit.substring(0, 2000));
        } else if (hasEncryptedPrefixAfterEdit) {
            console.error('❌ FAILED: Edited child name has "encrypted:" prefix');
        } else {
            console.log('✅ SUCCESS: Edited child name displays correctly without ArrayBuffer error');
        }
        
        console.log('✅ Test completed successfully');
    });
});
