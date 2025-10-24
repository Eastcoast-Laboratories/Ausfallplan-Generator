// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * TEST DESCRIPTION:
 * Tests all CRUD operations for children (Create, Read, Update, Delete).
 * 
 * ORGANIZATION IMPACT: LOW
 * - Children are still linked to organizations (children.organization_id remains)
 * - Tests work as-is because organization_id is set from user's organization membership
 * 
 * WHAT IT TESTS:
 * 1. Create new child (normal and integrative)
 * 2. View child details
 * 3. Edit existing child
 * 4. Delete child
 * 5. Form validation
 * 6. Navigation from sidebar
 * 7. Active/inactive child states
 */
test.describe('Children CRUD Tests', () => {
  
  // Helper function to login
  async function login(page) {
    await page.goto('/users/login');
    await page.fill('input[name="email"]', 'admin@example.com');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL(/dashboard/);
  }

  test('should create a new child successfully', async ({ page }) => {
    // 1. Login
    await login(page);
    
    // 2. Navigate to children
    await page.goto('/children');
    await page.waitForSelector('h3:has-text("Children")');
    
    // Take screenshot
    await page.screenshot({ 
      path: 'screenshots/children-list.png' 
    });
    
    // 3. Click "New Child" button
    await page.click('a:has-text("New Child")');
    await page.waitForURL(/children\/add/);
    
    // 4. Fill in child form
    await page.fill('input[name="name"]', 'Emma Schmidt');
    // is_active is checked by default, is_integrative is unchecked
    
    // Take screenshot of form
    await page.screenshot({ 
      path: 'screenshots/child-form-filled.png' 
    });
    
    // 5. Submit form
    await page.click('button[type="submit"]');
    
    // 6. Should redirect to children list
    await page.waitForURL(/children$/);
    
    // 7. Verify child appears in list
    await expect(page.locator('td', { hasText: 'Emma Schmidt' }).first()).toBeVisible();
    
    // Take screenshot
    await page.screenshot({ 
      path: 'screenshots/child-created-success.png',
      fullPage: true 
    });
    
    console.log('✅ Child created successfully!');
  });

  test('should create an integrative child', async ({ page }) => {
    // 1. Login
    await login(page);
    
    // 2. Go to add child
    await page.goto('/children/add');
    
    // 3. Fill form with integrative flag
    await page.fill('input[name="name"]', 'Max Müller');
    // is_active is already checked by default
    await page.check('input[name="is_integrative"]');
    
    // 4. Submit
    await page.click('button[type="submit"]');
    await page.waitForURL(/children$/);
    
    // 5. Verify integrative child appears
    await expect(page.locator('text=Max Müller')).toBeVisible();
    await expect(page.locator('text=Yes')).toBeVisible(); // Integrative column
    
    console.log('✅ Integrative child created!');
  });

  test('should navigate to children from sidebar', async ({ page }) => {
    // 1. Login
    await login(page);
    
    // 2. Click Children in sidebar
    await page.click('.sidebar-nav-item:has-text("Children")');
    
    // 3. Should navigate to children
    await page.waitForURL(/children/);
    await expect(page.locator('h3:has-text("Children")')).toBeVisible();
    
    console.log('✅ Navigation from sidebar works!');
  });

  test('should view child details', async ({ page }) => {
    // 1. Login and create a child first
    await login(page);
    
    await page.goto('/children/add');
    await page.fill('input[name="name"]', 'Test View Child');
    // is_active checked by default
    await page.click('button[type="submit"]');
    await page.waitForURL(/children$/);
    
    // 2. Click "View" link
    await page.click('a:has-text("View"):first');
    
    // 3. Should show child details
    await expect(page.locator('h3:has-text("Test View Child")')).toBeVisible();
    await expect(page.locator('text=Active')).toBeVisible();
    
    // Take screenshot
    await page.screenshot({ 
      path: 'screenshots/child-view.png' 
    });
    
    console.log('✅ Child view page works!');
  });

  test('should edit existing child', async ({ page }) => {
    // 1. Login and create a child
    await login(page);
    
    await page.goto('/children/add');
    await page.fill('input[name="name"]', 'Original Child Name');
    // is_active checked by default
    await page.click('button[type="submit"]');
    await page.waitForURL(/children$/);
    
    // 2. Click "Edit" link
    await page.click('a:has-text("Edit"):first');
    await page.waitForURL(/children\/edit/);
    
    // 3. Update name
    await page.fill('input[name="name"]', 'Updated Child Name');
    await page.click('button[type="submit"]');
    
    // 4. Should redirect back to list
    await page.waitForURL(/children$/);
    
    // 5. Verify updated name appears
    await expect(page.locator('text=Updated Child Name')).toBeVisible();
    
    console.log('✅ Child edit works!');
  });

  test('should delete child', async ({ page }) => {
    // 1. Login and create a child
    await login(page);
    
    await page.goto('/children/add');
    await page.fill('input[name="name"]', 'Child To Delete');
    // is_active checked by default
    await page.click('button[type="submit"]');
    await page.waitForURL(/children$/);
    
    // 2. Verify child exists
    await expect(page.locator('text=Child To Delete')).toBeVisible();
    
    // 3. Click delete and confirm
    page.on('dialog', dialog => dialog.accept());
    await page.click('a:has-text("Delete"):first');
    
    // 4. Should redirect back to list
    await page.waitForURL(/children$/);
    
    // 5. Verify child is gone
    await expect(page.locator('text=Child To Delete')).not.toBeVisible();
    
    console.log('✅ Child delete works!');
  });

  test('should show validation errors for empty form', async ({ page }) => {
    // 1. Login
    await login(page);
    
    // 2. Go to add child
    await page.goto('/children/add');
    
    // 3. Submit empty form (no name)
    await page.click('button[type="submit"]');
    
    // 4. Should stay on form page
    await page.waitForURL(/children\/add/);
    
    // 5. Check for error message
    await expect(page.locator('text=/could not be saved/i')).toBeVisible();
    
    console.log('✅ Validation errors shown correctly!');
  });

  test('should create inactive child', async ({ page }) => {
    // 1. Login
    await login(page);
    
    // 2. Go to add child
    await page.goto('/children/add');
    
    // 3. Fill form but uncheck active
    await page.fill('input[name="name"]', 'Inactive Child Test');
    await page.uncheck('input[name="is_active"]');
    
    // 4. Submit
    await page.click('button[type="submit"]');
    await page.waitForURL(/children$/);
    
    // 5. Verify inactive child appears with "Inactive" status
    await expect(page.locator('text=Inactive Child Test')).toBeVisible();
    await expect(page.locator('td:has-text("Inactive")')).toBeVisible();
    
    console.log('✅ Inactive child created!');
  });
});
