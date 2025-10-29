import { test, expect } from '@playwright/test';

test.describe('Schedules - Manage Children (Organization Order)', () => {
    test.beforeEach(async ({ page }) => {
        // Login as admin
        await page.goto('http://localhost:8080/users/login');
        await page.fill('input[name="email"]', 'admin@demo.kita');
        await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
        await page.click('button[type="submit"]');
        
        // Wait for dashboard
        await expect(page.locator('h1:has-text("Übersicht")')).toBeVisible({ timeout: 10000 });
    });

    test('should load manage-children page', async ({ page }) => {
        // Create a schedule first
        await page.goto('http://localhost:8080/schedules/add');
        await page.fill('input[name="title"]', 'Test Schedule for Manage');
        await page.fill('input[name="starts_on"]', '2025-11-01');
        await page.fill('input[name="ends_on"]', '2025-11-10');
        await page.click('button:has-text("Ausfallplan speichern")');
        
        // Wait for redirect
        await page.waitForURL('**/schedules/view/**', { timeout: 10000 });
        
        // Extract schedule ID from URL
        const url = page.url();
        const scheduleId = url.split('/').pop();
        
        // Navigate to manage-children
        await page.goto(`http://localhost:8080/schedules/manage-children/${scheduleId}`);
        
        // Check page loaded
        await expect(page.locator('h3:has-text("Organisations-Reihenfolge verwalten")')).toBeVisible({ timeout: 10000 });
        await expect(page.locator('text=Diese Reihenfolge wird in Berichten verwendet')).toBeVisible();
    });

    test('should show organization children in sortable list', async ({ page }) => {
        // Create a schedule
        await page.goto('http://localhost:8080/schedules/add');
        await page.fill('input[name="title"]', 'Schedule with Children');
        await page.fill('input[name="starts_on"]', '2025-11-01');
        await page.fill('input[name="ends_on"]', '2025-11-10');
        await page.click('button:has-text("Ausfallplan speichern")');
        
        await page.waitForURL('**/schedules/view/**', { timeout: 10000 });
        const url = page.url();
        const scheduleId = url.split('/').pop();
        
        // Add a child
        await page.goto('http://localhost:8080/children/add');
        await page.fill('input[name="name"]', 'Test Kind für Sortierung');
        await page.click('button:has-text("Kind speichern")');
        
        // Go to manage-children
        await page.goto(`http://localhost:8080/schedules/manage-children/${scheduleId}`);
        
        // Check child is visible
        await expect(page.locator('.child-item:has-text("Test Kind für Sortierung")')).toBeVisible({ timeout: 10000 });
        
        // Check drag handle is present
        await expect(page.locator('.child-item')).toBeVisible();
    });

    test('should show integrative badge', async ({ page }) => {
        // Create schedule
        await page.goto('http://localhost:8080/schedules/add');
        await page.fill('input[name="title"]', 'Schedule Integrative Test');
        await page.fill('input[name="starts_on"]', '2025-11-01');
        await page.fill('input[name="ends_on"]', '2025-11-10');
        await page.click('button:has-text("Ausfallplan speichern")');
        
        await page.waitForURL('**/schedules/view/**', { timeout: 10000 });
        const url = page.url();
        const scheduleId = url.split('/').pop();
        
        // Add integrative child
        await page.goto('http://localhost:8080/children/add');
        await page.fill('input[name="name"]', 'Integrativ Kind');
        await page.check('input[name="is_integrative"]');
        await page.click('button:has-text("Kind speichern")');
        
        // Go to manage-children
        await page.goto(`http://localhost:8080/schedules/manage-children/${scheduleId}`);
        
        // Check integrative badge
        await expect(page.locator('text=Integrativ')).toBeVisible({ timeout: 10000 });
    });
});
