// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('Schedule Creation Tests', () => {
  
  // Helper function to login
  async function login(page) {
    await page.goto('/users/login');
    await page.fill('input[name="email"]', 'admin@example.com');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL(/dashboard/);
  }

  test('should create a new schedule successfully', async ({ page }) => {
    // 1. Login
    await login(page);
    
    // 2. Navigate to schedules
    await page.goto('/schedules');
    await page.waitForSelector('h3:has-text("Schedules")');
    
    // Take screenshot of schedules list
    await page.screenshot({ 
      path: 'screenshots/schedules-list.png' 
    });
    
    // 3. Click "New Schedule" button
    await page.click('a:has-text("New Schedule")');
    await page.waitForURL(/schedules\/add/);
    
    // 4. Fill in schedule form
    await page.fill('input[name="title"]', 'November 2025 Schedule');
    await page.fill('input[name="starts_on"]', '2025-11-01');
    await page.fill('input[name="ends_on"]', '2025-11-30');
    await page.selectOption('select[name="state"]', 'draft');
    
    // Take screenshot of filled form
    await page.screenshot({ 
      path: 'screenshots/schedule-form-filled.png' 
    });
    
    // 5. Submit form
    await page.click('button[type="submit"]');
    
    // 6. Should redirect to schedules list
    await page.waitForURL(/schedules$/);
    
    // 7. Verify schedule appears in list
    await expect(page.locator('td', { hasText: 'November 2025 Schedule' }).first()).toBeVisible();
    
    // Take screenshot of success
    await page.screenshot({ 
      path: 'screenshots/schedule-created-success.png',
      fullPage: true 
    });
    
    console.log('✅ Schedule created successfully!');
  });

  test('should show validation errors for empty form', async ({ page }) => {
    // 1. Login
    await login(page);
    
    // 2. Go to add schedule
    await page.goto('/schedules/add');
    
    // 3. Submit empty form
    await page.click('button[type="submit"]');
    
    // 4. Should stay on form page
    await page.waitForURL(/schedules\/add/);
    
    // 5. Check for error message
    await expect(page.locator('text=/could not be saved/i')).toBeVisible();
    
    console.log('✅ Validation errors shown correctly!');
  });

  test('should navigate to schedules from sidebar', async ({ page }) => {
    // 1. Login
    await login(page);
    
    // 2. Click Schedules in sidebar
    await page.click('.sidebar-nav-item:has-text("Schedules")');
    
    // 3. Should navigate to schedules
    await page.waitForURL(/schedules/);
    await expect(page.locator('h3:has-text("Schedules")')).toBeVisible();
    
    console.log('✅ Navigation from sidebar works!');
  });

  test('should view schedule details', async ({ page }) => {
    // 1. Login and create a schedule first
    await login(page);
    
    await page.goto('/schedules/add');
    await page.fill('input[name="title"]', 'Test View Schedule');
    await page.fill('input[name="starts_on"]', '2025-12-01');
    await page.fill('input[name="ends_on"]', '2025-12-31');
    await page.click('button[type="submit"]');
    await page.waitForURL(/schedules$/);
    
    // 2. Click "View" link
    await page.click('a:has-text("View"):first');
    
    // 3. Should show schedule details
    await expect(page.locator('h3:has-text("Test View Schedule")')).toBeVisible();
    await expect(page.locator('text=2025-12-01')).toBeVisible();
    await expect(page.locator('text=2025-12-31')).toBeVisible();
    
    // Take screenshot
    await page.screenshot({ 
      path: 'screenshots/schedule-view.png' 
    });
    
    console.log('✅ Schedule view page works!');
  });

  test('should edit existing schedule', async ({ page }) => {
    // 1. Login and create a schedule
    await login(page);
    
    await page.goto('/schedules/add');
    await page.fill('input[name="title"]', 'Original Title');
    await page.fill('input[name="starts_on"]', '2026-01-01');
    await page.fill('input[name="ends_on"]', '2026-01-31');
    await page.click('button[type="submit"]');
    await page.waitForURL(/schedules$/);
    
    // 2. Click "Edit" link
    await page.click('a:has-text("Edit"):first');
    await page.waitForURL(/schedules\/edit/);
    
    // 3. Update title
    await page.fill('input[name="title"]', 'Updated Title');
    await page.click('button[type="submit"]');
    
    // 4. Should redirect back to list
    await page.waitForURL(/schedules$/);
    
    // 5. Verify updated title appears
    await expect(page.locator('text=Updated Title')).toBeVisible();
    
    console.log('✅ Schedule edit works!');
  });

  test('should delete schedule', async ({ page }) => {
    // 1. Login and create a schedule
    await login(page);
    
    await page.goto('/schedules/add');
    await page.fill('input[name="title"]', 'Schedule To Delete');
    await page.fill('input[name="starts_on"]', '2026-02-01');
    await page.fill('input[name="ends_on"]', '2026-02-28');
    await page.click('button[type="submit"]');
    await page.waitForURL(/schedules$/);
    
    // 2. Verify schedule exists
    await expect(page.locator('text=Schedule To Delete')).toBeVisible();
    
    // 3. Click delete and confirm
    page.on('dialog', dialog => dialog.accept());
    await page.click('a:has-text("Delete"):first');
    
    // 4. Should redirect back to list
    await page.waitForURL(/schedules$/);
    
    // 5. Verify schedule is gone
    await expect(page.locator('text=Schedule To Delete')).not.toBeVisible();
    
    console.log('✅ Schedule delete works!');
  });
});
