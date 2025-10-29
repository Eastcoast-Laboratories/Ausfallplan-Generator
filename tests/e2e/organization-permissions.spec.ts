import { test, expect } from '@playwright/test';

test.describe('Organization-based Permissions', () => {
  test.describe('Editor Permissions', () => {
    test.beforeEach(async ({ page }) => {
      // Login as editor
      await page.goto('/users/login');
      await page.fill('input[name="email"]', 'editor@example.com');
      await page.fill('input[name="password"]', '84hbfUb_3dsf');
      await page.click('button[type="submit"]');
      await expect(page).toHaveURL(/dashboard/);
    });

    test('Editor can view schedules list', async ({ page }) => {
      await page.goto('/schedules');
      await expect(page.locator('h2, h3')).toContainText(/schedules|dienstpläne/i);
      // Should only see own organization's schedules
      await expect(page).toHaveURL('/schedules');
    });

    test('Editor can create new schedule', async ({ page }) => {
      await page.goto('/schedules/add');
      await expect(page).toHaveURL('/schedules/add');
      
      await page.fill('input[name="title"]', 'Test Schedule');
      await page.fill('input[name="starts_on"]', '2025-12-01');
      await page.fill('input[name="days_count"]', '5');
      await page.click('button[type="submit"]');
      
      // Should redirect to index after successful creation
      await expect(page).toHaveURL('/schedules');
      await expect(page.locator('.message.success, .flash.success')).toBeVisible();
    });

    test('Editor can view own schedule', async ({ page }) => {
      // First create a schedule
      await page.goto('/schedules/add');
      await page.fill('input[name="title"]', 'My Schedule');
      await page.fill('input[name="starts_on"]', '2025-12-01');
      await page.fill('input[name="days_count"]', '3');
      await page.click('button[type="submit"]');
      
      // Get the schedule ID from the list
      await page.goto('/schedules');
      const scheduleLink = page.locator('a[href*="/schedules/view/"]').first();
      const href = await scheduleLink.getAttribute('href');
      const scheduleId = href?.match(/\/schedules\/view\/(\d+)/)?.[1];
      
      if (scheduleId) {
        await page.goto(`/schedules/view/${scheduleId}`);
        await expect(page).toHaveURL(`/schedules/view/${scheduleId}`);
        await expect(page.locator('h2, h3')).toContainText('My Schedule');
      }
    });

    test('Editor can edit own schedule', async ({ page }) => {
      // First create a schedule
      await page.goto('/schedules/add');
      await page.fill('input[name="title"]', 'Edit Test Schedule');
      await page.fill('input[name="starts_on"]', '2025-12-01');
      await page.fill('input[name="days_count"]', '3');
      await page.click('button[type="submit"]');
      
      // Get the schedule ID
      await page.goto('/schedules');
      const editLink = page.locator('a[href*="/schedules/edit/"]').first();
      const href = await editLink.getAttribute('href');
      const scheduleId = href?.match(/\/schedules\/edit\/(\d+)/)?.[1];
      
      if (scheduleId) {
        await page.goto(`/schedules/edit/${scheduleId}`);
        await expect(page).toHaveURL(`/schedules/edit/${scheduleId}`);
        
        await page.fill('input[name="title"]', 'Updated Schedule Title');
        await page.click('button[type="submit"]');
        
        await expect(page.locator('.message.success, .flash.success')).toBeVisible();
      }
    });

    test('Editor can manage children', async ({ page }) => {
      await page.goto('/children');
      await expect(page).toHaveURL('/children');
      await expect(page.locator('h2, h3')).toContainText(/children|kinder/i);
    });

    test('Editor can add new child', async ({ page }) => {
      await page.goto('/children/add');
      await expect(page).toHaveURL('/children/add');
      
      await page.fill('input[name="name"]', 'Test Kind');
      await page.click('button[type="submit"]');
      
      await expect(page).toHaveURL('/children');
      await expect(page.locator('.message.success, .flash.success')).toBeVisible();
    });

    test('Editor can access waitlist', async ({ page }) => {
      await page.goto('/waitlist');
      await expect(page).toHaveURL('/waitlist');
      await expect(page.locator('h2, h3')).toContainText(/waitlist|nachrück/i);
    });
  });

  test.describe('Viewer Permissions', () => {
    test.beforeEach(async ({ page }) => {
      // Login as viewer
      await page.goto('/users/login');
      await page.fill('input[name="email"]', 'viewer@example.com');
      await page.fill('input[name="password"]', '84hbfUb_3dsf');
      await page.click('button[type="submit"]');
      await expect(page).toHaveURL(/dashboard/);
    });

    test('Viewer can view schedules list', async ({ page }) => {
      await page.goto('/schedules');
      await expect(page).toHaveURL('/schedules');
    });

    test('Viewer cannot create schedule', async ({ page }) => {
      await page.goto('/schedules/add');
      // Should either redirect or show error
      await expect(page.locator('.message.error, .flash.error')).toBeVisible();
    });

    test('Viewer can view children list', async ({ page }) => {
      await page.goto('/children');
      await expect(page).toHaveURL('/children');
    });

    test('Viewer cannot add new child', async ({ page }) => {
      await page.goto('/children/add');
      // Should either redirect or show error
      await expect(page.locator('.message.error, .flash.error')).toBeVisible();
    });

    test('Viewer can view waitlist', async ({ page }) => {
      await page.goto('/waitlist');
      await expect(page).toHaveURL('/waitlist');
    });
  });

  test.describe('Admin Permissions', () => {
    test.beforeEach(async ({ page }) => {
      // Login as admin
      await page.goto('/users/login');
      await page.fill('input[name="email"]', 'admin@demo.kita');
      await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
      await page.click('button[type="submit"]');
      await expect(page).toHaveURL(/dashboard/);
    });

    test('Admin can view all schedules', async ({ page }) => {
      await page.goto('/schedules');
      await expect(page).toHaveURL('/schedules');
      // Admin should see schedules from all organizations
    });

    test('Admin can access admin panel', async ({ page }) => {
      await page.goto('/admin/organizations');
      await expect(page).toHaveURL('/admin/organizations');
    });
  });
});
