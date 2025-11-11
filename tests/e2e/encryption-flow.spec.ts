/**
 * Complete Encryption Flow E2E Test
 * 
 * Tests:
 * - User registration with encryption keys
 * - Email verification via token link
 * - Login with encryption key unwrapping
 * - Navigate to children list
 * - Create encrypted child
 * - Console log error checking at each step
 * 
 * Run command:
 * timeout 180 npx playwright test tests/e2e/encryption-flow.spec.ts --project=chromium --headed
 */

import { test, expect } from '@playwright/test';

test.describe('Complete Encryption Flow', () => {
    test('Full flow: Register → Verify → Login → Create Child', async ({ page }) => {
        test.setTimeout(120000); // 2 minutes timeout for complete flow
        
        const testEmail = `test${Date.now()}@enc.test`;
        const testPassword = 'TestPass123!';
        const orgName = `TestOrg${Date.now()}`;
        const childName = `TestChild${Date.now()}`;
        
        // Collect console logs for error checking
        const consoleLogs: string[] = [];
        const consoleErrors: string[] = [];
        
        page.on('console', msg => {
            const text = msg.text();
            consoleLogs.push(text);
            if (msg.type() === 'error') {
                consoleErrors.push(text);
            }
        });
        
        // STEP 1: Register new user
        console.log('\n=== STEP 1: Register new user ===');
        await page.goto('http://localhost:8080/register');
        
        // Wait for page to be fully loaded
        await page.waitForLoadState('domcontentloaded');
        
        await page.fill('input[name="email"]', testEmail);
        await page.fill('input[name="password"]', testPassword);
        await page.fill('input[name="password_confirm"]', testPassword);
        
        // Select "Create new organization" from dropdown
        const orgChoiceSelect = page.locator('select#organization-choice');
        await orgChoiceSelect.waitFor({ state: 'visible', timeout: 5000 });
        await orgChoiceSelect.selectOption('new');
        
        // Wait for organization name field to appear and fill it
        const orgNameInput = page.locator('input#organization-name-input');
        await orgNameInput.waitFor({ state: 'visible', timeout: 5000 });
        await orgNameInput.fill(orgName);
        
        // Check logs before submit
        console.log('📝 Console logs before registration:', consoleLogs.length);
        const errorsBeforeRegister = consoleErrors.filter(e => !e.includes('Manifest'));
        if (errorsBeforeRegister.length > 0) {
            console.warn('⚠️ Errors before registration:', errorsBeforeRegister);
        }
        
        await page.click('button[type="submit"]');
        
        // Wait for redirect to login
        await page.waitForURL('**/login', { timeout: 10000 });
        await expect(page.locator('text=Please check your email to verify')).toBeVisible();
        
        console.log('✅ Registration successful - email verification required');
        console.log('📧 Email:', testEmail);
        console.log('🏢 Organization:', orgName);
        
        // STEP 2: Verify email using direct link (simulating email click)
        console.log('\n=== STEP 2: Verify email ===');
        
        // In a real scenario, user would click link in email
        // For testing, we can query the DB to get the token or use the debug viewer
        // Let's try the debug viewer approach
        await page.goto('http://localhost:8080/debug/emails');
        await page.waitForLoadState('networkidle');
        
        // Wait for emails table
        const emailsTable = page.locator('table tbody tr');
        const emailCount = await emailsTable.count();
        
        console.log(`📧 Found ${emailCount} emails in debug viewer`);
        
        if (emailCount === 0) {
            throw new Error('No emails found in debug email viewer');
        }
        
        // Click on first email
        await emailsTable.first().click();
        await page.waitForTimeout(1000);
        
        // Look for verify link
        const verifyLink = page.locator('a[href*="/users/verify/"]').first();
        const verifyHref = await verifyLink.getAttribute('href');
        
        console.log('✅ Found verification link:', verifyHref);
        
        // Navigate directly to verification URL
        if (verifyHref) {
            await page.goto(verifyHref.startsWith('http') ? verifyHref : 'http://localhost:8080' + verifyHref);
        } else {
            throw new Error('Could not extract verification link');
        }
        
        // Should see success message
        await expect(page.locator('text=Email verified successfully')).toBeVisible({ timeout: 5000 });
        
        console.log('✅ Email verified successfully');
        
        // STEP 3: Login as new user
        console.log('\n=== STEP 3: Login as new user ===');
        await page.goto('http://localhost:8080/login');
        await page.fill('input[name="email"]', testEmail);
        await page.fill('input[name="password"]', testPassword);
        
        // Clear previous logs
        consoleLogs.length = 0;
        consoleErrors.length = 0;
        
        await page.click('button[type="submit"]');
        
        // Wait for redirect to dashboard
        await page.waitForURL('**/dashboard', { timeout: 10000 });
        
        console.log('✅ Login successful - redirected to dashboard');
        
        // Wait for encryption to load
        await page.waitForTimeout(3000);
        
        // Check console logs for errors during login/encryption loading
        const loginErrors = consoleErrors.filter(e => 
            !e.includes('Manifest') && 
            !e.includes('favicon') &&
            !e.includes('sourcemap')
        );
        
        if (loginErrors.length > 0) {
            console.error('❌ Errors during login/encryption loading:', loginErrors);
            throw new Error(`Login errors detected: ${loginErrors.join(', ')}`);
        }
        
        // Check for successful encryption loading
        const hasEncryptionLoaded = consoleLogs.some(log => log.includes('OrgEncryption module loaded'));
        const hasUnwrapError = consoleLogs.some(log => log.includes('Key unwrapping error'));
        
        console.log('🔐 Encryption module loaded:', hasEncryptionLoaded);
        console.log('🔐 Unwrap errors:', hasUnwrapError);
        
        if (hasUnwrapError) {
            console.error('❌ Key unwrapping failed!');
            throw new Error('Encryption key unwrapping error detected');
        }
        
        console.log('✅ No encryption errors during login');
        
        // STEP 4: Navigate to children list
        console.log('\n=== STEP 4: Navigate to children list ===');
        consoleLogs.length = 0;
        consoleErrors.length = 0;
        
        await page.goto('http://localhost:8080/children');
        await page.waitForTimeout(1000);
        
        const childrenPageErrors = consoleErrors.filter(e => 
            !e.includes('Manifest') && 
            !e.includes('favicon') &&
            !e.includes('sourcemap')
        );
        
        if (childrenPageErrors.length > 0) {
            console.error('❌ Errors on children page:', childrenPageErrors);
            throw new Error(`Children page errors: ${childrenPageErrors.join(', ')}`);
        }
        
        console.log('✅ Children page loaded without errors');
        
        // STEP 5: Create new child
        console.log('\n=== STEP 5: Create encrypted child ===');
        await page.goto('http://localhost:8080/children/add');
        
        await page.fill('input[name="name"]', childName);
        await page.fill('input[name="birth_date"]', '2020-06-15');
        await page.selectOption('select[name="care_type"]', 'under3');
        
        consoleLogs.length = 0;
        consoleErrors.length = 0;
        
        await page.click('button[type="submit"]');
        
        // Wait for redirect
        await page.waitForURL('**/children', { timeout: 10000 });
        
        // Check for encryption errors during child creation
        const createChildErrors = consoleErrors.filter(e => 
            !e.includes('Manifest') && 
            !e.includes('favicon') &&
            !e.includes('sourcemap')
        );
        
        if (createChildErrors.length > 0) {
            console.error('❌ Errors during child creation:', createChildErrors);
            throw new Error(`Child creation errors: ${createChildErrors.join(', ')}`);
        }
        
        console.log('✅ Child created without errors');
        console.log('👶 Child name:', childName);
        
        // Verify child appears in list
        await page.waitForTimeout(1000);
        const childRows = await page.locator('tbody tr').count();
        expect(childRows).toBeGreaterThanOrEqual(1);
        
        console.log('✅ Child visible in children list');
        
        // FINAL SUMMARY
        console.log('\n=== TEST SUMMARY ===');
        console.log('✅ All steps completed successfully');
        console.log('✅ No encryption errors detected');
        console.log('✅ Email:', testEmail);
        console.log('✅ Organization:', orgName);
        console.log('✅ Child:', childName);
    });
});
