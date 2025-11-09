const { test, expect } = require('@playwright/test');

test.describe('Profile Fields Save Test', () => {
    test('should save personal information and bank details', async ({ page }) => {
        // Login
        await page.goto('http://localhost:8080/users/login');
        await page.fill('input[name="email"]', 'admin@demo.kita');
        await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
        await page.click('button:has-text("Anmelden")');
        
        // Wait for redirect (could be /dashboard or /schedules)
        await page.waitForTimeout(3000);
        
        // Navigate to profile page
        await page.goto('http://localhost:8080/users/profile');
        await page.waitForLoadState('networkidle');
        
        // Generate unique test data
        const timestamp = Date.now();
        const testData = {
            firstName: `TestFirstName${timestamp}`,
            lastName: `TestLastName${timestamp}`,
            info: `Test info text ${timestamp}`,
            accountHolder: `Test Account Holder ${timestamp}`,
            iban: 'DE89370400440532013000',
            bic: 'COBADEFFXXX'
        };
        
        // Fill personal information
        await page.fill('input[name="first_name"]', testData.firstName);
        await page.fill('input[name="last_name"]', testData.lastName);
        await page.fill('textarea[name="info"]', testData.info);
        
        // Fill bank details
        await page.fill('input[name="bank_account_holder"]', testData.accountHolder);
        await page.fill('input[name="bank_iban"]', testData.iban);
        await page.fill('input[name="bank_bic"]', testData.bic);
        
        // Submit form
        await page.click('button.btn-primary');
        
        // Wait for success message or redirect
        await page.waitForTimeout(2000);
        
        // Reload profile page to verify data was saved
        await page.goto('http://localhost:8080/users/profile');
        await page.waitForLoadState('networkidle');
        
        // Verify all fields contain the saved data
        const firstNameValue = await page.inputValue('input[name="first_name"]');
        const lastNameValue = await page.inputValue('input[name="last_name"]');
        const infoValue = await page.inputValue('textarea[name="info"]');
        const accountHolderValue = await page.inputValue('input[name="bank_account_holder"]');
        const ibanValue = await page.inputValue('input[name="bank_iban"]');
        const bicValue = await page.inputValue('input[name="bank_bic"]');
        
        console.log('Expected:', testData);
        console.log('Actual:', {
            firstName: firstNameValue,
            lastName: lastNameValue,
            info: infoValue,
            accountHolder: accountHolderValue,
            iban: ibanValue,
            bic: bicValue
        });
        
        // Assertions
        expect(firstNameValue).toBe(testData.firstName);
        expect(lastNameValue).toBe(testData.lastName);
        expect(infoValue).toBe(testData.info);
        expect(accountHolderValue).toBe(testData.accountHolder);
        expect(ibanValue).toBe(testData.iban);
        expect(bicValue).toBe(testData.bic);
    });
    
    test('should display subscription plan on profile page', async ({ page }) => {
        // Login
        await page.goto('http://localhost:8080/users/login');
        await page.fill('input[name="email"]', 'admin@demo.kita');
        await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
        await page.click('button:has-text("Anmelden")');
        
        // Wait for redirect
        await page.waitForTimeout(3000);
        
        // Navigate to profile
        await page.goto('http://localhost:8080/users/profile');
        await page.waitForLoadState('networkidle');
        
        // Check if subscription plan is displayed
        const subscriptionLabel = await page.locator('text=Subscription Plan').count();
        expect(subscriptionLabel).toBeGreaterThan(0);
        
        // Check if manage subscription link exists
        const manageLink = await page.locator('a:has-text("Manage Subscription")').count();
        expect(manageLink).toBeGreaterThan(0);
    });
});
