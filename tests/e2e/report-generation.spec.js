import { test, expect } from '@playwright/test';

test.describe('Report Generation', () => {
    test.beforeEach(async ({ page }) => {
        // Login first
        await page.goto('/login');
        await page.fill('input[name="email"]', 'admin@eastcoast-labs.de');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.waitForURL('/dashboard');
    });

    test('should show Generate Report button on schedules index', async ({ page }) => {
        await page.goto('/schedules');
        
        // Should have at least one Generate Report button
        const generateButtons = page.locator('text=Ausfallplan generieren');
        await expect(generateButtons.first()).toBeVisible();
    });

    test('should generate and display report with correct structure', async ({ page }) => {
        // Go to schedules
        await page.goto('/schedules');
        
        // Click first Generate Report button
        await page.locator('text=Ausfallplan generieren').first().click();
        
        // Should navigate to report page
        await page.waitForURL(/\/schedules\/generateReport\/\d+/);
        
        // Check for main elements
        await expect(page.locator('.header')).toContainText('Ausfallplan');
        await expect(page.locator('.days-grid')).toBeVisible();
        await expect(page.locator('.sidebar')).toBeVisible();
    });

    test('should display day boxes with animal names', async ({ page }) => {
        await page.goto('/schedules');
        await page.locator('text=Ausfallplan generieren').first().click();
        await page.waitForURL(/\/schedules\/generateReport\/\d+/);
        
        // Check for day boxes
        const dayBoxes = page.locator('.day-box');
        const count = await dayBoxes.count();
        expect(count).toBeGreaterThan(0);
        
        // Check first day has animal name
        const firstDay = dayBoxes.first();
        await expect(firstDay.locator('.day-title')).toContainText('-Tag');
    });

    test('should display children with weights in day boxes', async ({ page }) => {
        await page.goto('/schedules');
        await page.locator('text=Ausfallplan generieren').first().click();
        await page.waitForURL(/\/schedules\/generateReport\/\d+/);
        
        // Check for children list
        const childrenList = page.locator('.children-list').first();
        if (await childrenList.locator('.child-item').count() > 0) {
            // Should have child name and weight
            const firstChild = childrenList.locator('.child-item').first();
            await expect(firstChild.locator('.child-name')).toBeVisible();
            await expect(firstChild.locator('.child-weight')).toBeVisible();
        }
    });

    test('should display waitlist sidebar', async ({ page }) => {
        await page.goto('/schedules');
        await page.locator('text=Ausfallplan generieren').first().click();
        await page.waitForURL(/\/schedules\/generateReport\/\d+/);
        
        // Check for waitlist box
        const waitlistBox = page.locator('.waitlist-box');
        await expect(waitlistBox).toBeVisible();
        await expect(waitlistBox.locator('.box-title')).toContainText('Nachrückliste');
    });

    test('should display "Immer am Ende" section', async ({ page }) => {
        await page.goto('/schedules');
        await page.locator('text=Ausfallplan generieren').first().click();
        await page.waitForURL(/\/schedules\/generateReport\/\d+/);
        
        // Check for always-end box
        const alwaysEndBox = page.locator('.always-end-box');
        await expect(alwaysEndBox).toBeVisible();
        await expect(alwaysEndBox.locator('.box-title')).toContainText('Immer am Ende');
    });

    test('should display explanation for parents', async ({ page }) => {
        await page.goto('/schedules');
        await page.locator('text=Ausfallplan generieren').first().click();
        await page.waitForURL(/\/schedules\/generateReport\/\d+/);
        
        // Check for explanation
        const explanation = page.locator('.explanation');
        await expect(explanation).toBeVisible();
        await expect(explanation).toContainText('Hinweis für Eltern');
    });

    test('should have print button', async ({ page }) => {
        await page.goto('/schedules');
        await page.locator('text=Ausfallplan generieren').first().click();
        await page.waitForURL(/\/schedules\/generateReport\/\d+/);
        
        // Check for print button
        const printButton = page.locator('text=Drucken');
        await expect(printButton).toBeVisible();
    });

    test('should have back to schedules link', async ({ page }) => {
        await page.goto('/schedules');
        await page.locator('text=Ausfallplan generieren').first().click();
        await page.waitForURL(/\/schedules\/generateReport\/\d+/);
        
        // Check for back link
        const backLink = page.locator('text=Zurück').or(page.locator('text=Back'));
        await expect(backLink.first()).toBeVisible();
        
        // Click back link
        await backLink.first().click();
        await page.waitForURL('/schedules');
    });

    test('should display leaving child with flag icon', async ({ page }) => {
        await page.goto('/schedules');
        await page.locator('text=Ausfallplan generieren').first().click();
        await page.waitForURL(/\/schedules\/generateReport\/\d+/);
        
        // Check if any day has a leaving child
        const leavingChild = page.locator('.leaving-child');
        const count = await leavingChild.count();
        if (count > 0) {
            // Should contain flag icon
            await expect(leavingChild.first()).toContainText('🏁');
        }
    });
});
