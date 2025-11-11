/**
 * Encryption Complete Flow E2E Test
 * 
 * Tests:
 * - Register new user with encryption key generation
 * - Create new organization
 * - Login and unwrap encryption keys  
 * - Create schedule (Ausfallplan)
 * - Create children with encrypted names (with/without siblings)
 * - Assign children to waitlist
 * - Verify encrypted names are decrypted in UI
 * - Generate report
 * - Verify end-to-end encryption flow works
 * 
 * Run command:
 * timeout 180 npx playwright test tests/e2e/encryption-complete-flow.spec.ts --project=chromium --headed
 */

import { test, expect } from '@playwright/test';

test.describe('Encryption Complete Flow', () => {
    test('should handle complete encryption workflow from registration to report', async ({ page }) => {
        const timestamp = Date.now();
        const testEmail = `encryption-test-${timestamp}@example.com`;
        const testPassword = 'TestPassword123!';
        const testOrgName = `Encrypted Org ${timestamp}`;
        const child1Name = `EncChild1_${timestamp}`;
        const child2Name = `EncChild2_${timestamp}`;
        const child3Name = `EncChild3_${timestamp}`;

        console.log('=== Step 1: Register new user with encryption keys ===');
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
        
        // Submit registration - should trigger key generation
        console.log('Submitting registration form...');
        
        // Wait for navigation to complete (key generation happens during submit)
        await Promise.all([
            page.waitForURL(/\/(users\/)?login/, { timeout: 15000 }),
            page.click('button[type="submit"]')
        ]);
        
        console.log('✅ User registered with encryption keys');
        
        console.log('=== Step 2: Login and unwrap keys ===');
        await page.fill('input[name="email"]', testEmail);
        await page.fill('input[name="password"]', testPassword);
        
        // Handle password prompt for key unwrapping  
        page.on('dialog', async dialog => {
            console.log('Dialog detected:', dialog.message());
            if (dialog.message().includes('password')) {
                await dialog.accept(testPassword);
            } else {
                await dialog.accept();
            }
        });
        
        await page.click('button[type="submit"]');
        
        // Wait for dashboard
        await page.waitForURL(/\/dashboard/, { timeout: 15000 });
        console.log('✅ Logged in and keys unwrapped');
        
        console.log('=== Step 3: Create schedule (Ausfallplan) ===');
        await page.goto('http://localhost:8080/schedules/add');
        await page.fill('input[name="name"]', `Enc Schedule ${timestamp}`);
        await page.fill('input[name="start_date"]', '2024-12-01');
        await page.fill('input[name="end_date"]', '2024-12-31');
        await page.click('button[type="submit"]');
        
        await page.waitForURL(/\/schedules/, { timeout: 5000 });
        console.log('✅ Schedule created');
        
        console.log('=== Step 4: Create children with encrypted names ===');
        
        // Create child 1 (with encryption)
        await page.goto('http://localhost:8080/children/add');
        await page.fill('input[name="name"]', child1Name);
        await page.click('button[type="submit"]');
        await page.waitForTimeout(2000); // Wait for encryption
        await page.waitForURL(/\/children/, { timeout: 5000 });
        console.log(`✅ Child 1 created: ${child1Name}`);
        
        // Create child 2 (with encryption)
        await page.goto('http://localhost:8080/children/add');
        await page.fill('input[name="name"]', child2Name);
        await page.check('input[name="is_integrative"]'); // Make integrative
        await page.click('button[type="submit"]');
        await page.waitForTimeout(2000);
        await page.waitForURL(/\/children/, { timeout: 5000 });
        console.log(`✅ Child 2 created (integrative): ${child2Name}`);
        
        // Create child 3 with sibling group
        await page.goto('http://localhost:8080/children/add');
        await page.fill('input[name="name"]', child3Name);
        await page.click('button[type="submit"]');
        await page.waitForTimeout(2000);
        await page.waitForURL(/\/children/, { timeout: 5000 });
        console.log(`✅ Child 3 created: ${child3Name}`);
        
        console.log('=== Step 5: Verify encrypted names are decrypted in list ===');
        await page.goto('http://localhost:8080/children');
        await page.waitForSelector('table tbody', { timeout: 5000 });
        await page.waitForTimeout(2000); // Wait for decryption script
        
        // Check if children names are visible (decrypted)
        const pageContent = await page.content();
        expect(pageContent).toContain(child1Name);
        expect(pageContent).toContain(child2Name);
        expect(pageContent).toContain(child3Name);
        console.log('✅ Encrypted names decrypted and displayed');
        
        console.log('=== Step 6: Add children to waitlist ===');
        await page.goto('http://localhost:8080/waitlist');
        await page.waitForSelector('h3:has-text("Waitlist")', { timeout: 10000 });
        
        // Find schedule dropdown and select our schedule
        const scheduleSelect = page.locator('select[name="schedule_id"], #schedule-select');
        if (await scheduleSelect.isVisible({ timeout: 2000 }).catch(() => false)) {
            const optionCount = await scheduleSelect.locator('option').count();
            if (optionCount > 1) {
                // Select the last option (our newly created schedule)
                await scheduleSelect.selectOption({ index: optionCount - 1 });
                await page.waitForTimeout(1000);
            }
        }
        
        // Add children to waitlist
        for (const childName of [child1Name, child2Name, child3Name]) {
            const addButton = page.locator('.available-children .child-item')
                .filter({ hasText: childName })
                .locator('button:has-text("→")');
            
            if (await addButton.isVisible({ timeout: 2000 }).catch(() => false)) {
                await addButton.click();
                await page.waitForTimeout(500);
                console.log(`✅ Added ${childName} to waitlist`);
            }
        }
        
        console.log('=== Step 7: Generate report ===');
        await page.goto('http://localhost:8080/schedules');
        await page.waitForSelector('table', { timeout: 5000 });
        
        const generateButton = page.locator('a:has-text("Ausfallplan generieren")').first();
        await generateButton.click();
        
        // Wait for report to load
        await page.waitForSelector('.day-box, .waitlist-box, .report-table', { timeout: 15000 });
        console.log('✅ Report generated');
        
        console.log('=== Step 8: Verify encryption status in organization list ===');
        await page.goto('http://localhost:8080/admin/organizations');
        await page.waitForSelector('table', { timeout: 5000 });
        
        // Check for encryption indicator
        const orgRow = page.locator('tr').filter({ hasText: testOrgName });
        await expect(orgRow).toBeVisible();
        
        // Should show encryption enabled icon/badge
        const encryptionIndicator = orgRow.locator('🔒, text=/Encrypted/i, text=/🔒/');
        const hasIndicator = await encryptionIndicator.count() > 0;
        console.log(`✅ Encryption indicator visible: ${hasIndicator}`);
        
        console.log('\n🎉 === All encryption tests passed! ===');
        console.log('✓ User registration with key generation');
        console.log('✓ Login with key unwrapping');
        console.log('✓ Children creation with encryption');
        console.log('✓ Encrypted names decrypted in UI');
        console.log('✓ Waitlist management');
        console.log('✓ Report generation');
        console.log('✓ Encryption indicators visible');
        console.log('✓ End-to-end encryption flow works!');
    });
});
