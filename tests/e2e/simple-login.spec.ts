import { test, expect } from '@playwright/test';

test.describe('Login Test', () => {
  
  test('admin can login successfully', async ({ page }) => {
    // Go to login page
    await page.goto('http://localhost:8080/login');
    
    // Fill in credentials
    await page.fill('input[name="email"]', 'admin@demo.kita');
    await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
    
    // Submit
    await page.click('button[type="submit"]');
    
    // Wait for redirect
    await page.waitForURL(/dashboard|children|schedules/, { timeout: 5000 });
    
    // Should be logged in - check for logout link or similar
    const content = await page.content();
    const isLoggedIn = content.includes('logout') || 
                      content.includes('Logout') || 
                      content.includes('Abmelden') ||
                      content.includes('Dashboard');
    
    expect(isLoggedIn).toBeTruthy();
    
    console.log('✅ Admin login successful!');
  });
});
