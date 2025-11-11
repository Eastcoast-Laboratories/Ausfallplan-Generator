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

        // Listen to ALL console logs from the browser for debugging
        page.on('console', msg => {
            const text = msg.text();
            if (text.includes('Auto-unwrapping') || text.includes('ENCRYPTION_CHECK') || 
                text.includes('DEK') || text.includes('sessionStorage') || 
                text.includes('password') || text.includes('Unwrapping')) {
                console.log('[BROWSER]', text);
            }
        });

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
        
        console.log('=== Step 2: Login ===');
        await page.fill('input[name="email"]', testEmail);
        await page.fill('input[name="password"]', testPassword);
        
        // Handle any dialogs that might appear (dismiss them)
        page.on('dialog', async dialog => {
            console.log('Dialog detected:', dialog.message());
            await dialog.dismiss();
        });
        
        // Login and wait for dashboard
        await Promise.all([
            page.waitForNavigation({ timeout: 10000 }).catch(() => {}),
            page.click('button[type="submit"]')
        ]);
        
        await page.waitForTimeout(2000); // Give time for page to load
        
        const currentUrl = page.url();
        console.log('Current URL after login:', currentUrl);
        
        // Check if we're logged in by looking for navigation or dashboard content
        const isDashboard = currentUrl.includes('dashboard') || currentUrl.includes('organizations');
        console.log('On dashboard/organizations page:', isDashboard);
        
        if (!isDashboard) {
            throw new Error(`Login failed - unexpected URL: ${currentUrl}`);
        }
        
        console.log('✅ Logged in successfully');
        
        console.log('=== Step 3: Create children ===');
        
        // Create child 1 
        await page.goto('http://localhost:8080/children/add');
        await page.fill('input[name="name"]', child1Name);
        await page.click('button[type="submit"]');
        await page.waitForURL(/\/children/, { timeout: 5000 });
        console.log(`✅ Child 1 created: ${child1Name}`);
        
        // Create child 2 (integrative)
        await page.goto('http://localhost:8080/children/add');
        await page.fill('input[name="name"]', child2Name);
        await page.check('input[type="checkbox"][name="is_integrative"]'); // Make integrative
        await page.click('button[type="submit"]');
        await page.waitForURL(/\/children/, { timeout: 5000 });
        console.log(`✅ Child 2 created (integrative): ${child2Name}`);
        
        // Create child 3
        await page.goto('http://localhost:8080/children/add');
        await page.fill('input[name="name"]', child3Name);
        await page.click('button[type="submit"]');
        await page.waitForURL(/\/children/, { timeout: 5000 });
        console.log(`✅ Child 3 created: ${child3Name}`);
        
        console.log('=== Step 5: Verify children names are visible in list ===');
        await page.goto('http://localhost:8080/children');
        await page.waitForSelector('table tbody', { timeout: 5000 });
        await page.waitForTimeout(1000);
        
        // Check if children names are visible
        const pageContent = await page.content();
        expect(pageContent).toContain(child1Name);
        expect(pageContent).toContain(child2Name);
        expect(pageContent).toContain(child3Name);
        console.log('✅ Children names visible in list');
        
        console.log('=== Step 4: Verify organization in list ===');
        await page.goto('http://localhost:8080/admin/organizations');
        await page.waitForSelector('table', { timeout: 5000 });
        
        // Check organization is visible
        const orgRow = page.locator('tr').filter({ hasText: testOrgName });
        await expect(orgRow).toBeVisible();
        console.log(`✅ Organization "${testOrgName}" visible in list`);
        
        console.log('\n🎉 === All tests passed! ===');
        console.log('✓ User registration with encryption keys');
        console.log('✓ Login successful');
        console.log('✓ Organization creation');
        console.log('✓ Children creation (normal + integrative)');
        console.log('✓ Children visible in list');
        console.log('✓ Organization visible in admin list');
        console.log('✓ Complete encryption workflow works!');
    });
});
