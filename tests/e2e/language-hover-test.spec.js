// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * TEST DESCRIPTION:
 * Tests language dropdown hover behavior and mouse movement.
 * 
 * ORGANIZATION IMPACT: ❌ NONE
 * 
 * WHAT IT TESTS:
 * 1. Dropdown stays open while moving mouse to option
 * 2. No gap between flag and dropdown that causes closing
 * 3. Can click language options with realistic mouse movement
 */
test.describe('Language Dropdown Hover Tests', () => {
  
  async function login(page) {
    await page.goto('/users/login');
    await page.fill('input[name="email"]', 'admin@example.com');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL(/dashboard/);
  }

  test('should keep dropdown open while moving mouse to option', async ({ page }) => {
    // 1. Login
    await login(page);
    
    // 2. Get language flag position
    const languageFlag = page.locator('.language-flag');
    await expect(languageFlag).toBeVisible();
    
    const flagBox = await languageFlag.boundingBox();
    console.log('Flag position:', flagBox);
    
    // 3. Move mouse to language flag slowly
    await page.mouse.move(flagBox.x + flagBox.width / 2, flagBox.y + flagBox.height / 2, { steps: 10 });
    
    // 4. Wait a bit for dropdown to appear
    await page.waitForTimeout(500);
    
    // 5. Dropdown should be visible
    const dropdown = page.locator('.language-dropdown');
    await expect(dropdown).toBeVisible();
    
    // Take screenshot with dropdown visible
    await page.screenshot({ 
      path: 'screenshots/language-dropdown-visible.png' 
    });
    
    // 6. Get German option position
    const germanOption = page.locator('.language-option:has-text("Deutsch")');
    const germanBox = await germanOption.boundingBox();
    console.log('German option position:', germanBox);
    
    // 7. Move mouse slowly from flag to German option
    // This is where the problem occurs - dropdown closes before we reach it
    await page.mouse.move(
      germanBox.x + germanBox.width / 2, 
      germanBox.y + germanBox.height / 2, 
      { steps: 20 }
    );
    
    // 8. Dropdown should still be visible
    await expect(dropdown).toBeVisible({ timeout: 2000 });
    
    // 9. Take screenshot at German option
    await page.screenshot({ 
      path: 'screenshots/language-at-german-option.png' 
    });
    
    console.log('✅ Dropdown stayed open while moving to option');
  });

  test('should handle gap between flag and dropdown', async ({ page }) => {
    await login(page);
    
    // Hover on flag
    await page.hover('.language-flag');
    
    // Dropdown appears
    const dropdown = page.locator('.language-dropdown');
    await expect(dropdown).toBeVisible();
    
    // Get positions
    const flagBox = await page.locator('.language-flag').boundingBox();
    const dropdownBox = await dropdown.boundingBox();
    
    console.log('Flag bottom:', flagBox.y + flagBox.height);
    console.log('Dropdown top:', dropdownBox.y);
    console.log('Gap:', dropdownBox.y - (flagBox.y + flagBox.height), 'px');
    
    // If gap > 5px, it's a problem
    const gap = dropdownBox.y - (flagBox.y + flagBox.height);
    if (gap > 5) {
      console.log('⚠️ Gap too large! Dropdown will close when moving mouse.');
    }
    
    // Try to move mouse into dropdown
    await page.mouse.move(
      dropdownBox.x + 10,
      dropdownBox.y + 10,
      { steps: 10 }
    );
    
    // Should still be visible
    await expect(dropdown).toBeVisible();
    
    console.log('✅ Successfully moved into dropdown');
  });

  test('should click German option with slow mouse movement', async ({ page }) => {
    await login(page);
    
    // Switch to English first
    await page.hover('.language-switcher');
    await page.click('.language-option:has-text("English")');
    await page.waitForLoadState('networkidle');
    
    // Verify English
    await expect(page.locator('.language-flag')).toContainText('🇬🇧');
    
    // Now try to switch back to German with realistic mouse movement
    await page.hover('.language-switcher');
    
    // Wait for dropdown
    await page.waitForSelector('.language-dropdown', { state: 'visible' });
    
    await page.screenshot({ 
      path: 'screenshots/before-clicking-german.png' 
    });
    
    // Move mouse realistically to German option
    const germanOption = page.locator('.language-option:has-text("Deutsch")');
    await germanOption.hover({ timeout: 5000 });
    
    await page.screenshot({ 
      path: 'screenshots/hovering-german-option.png' 
    });
    
    // Click it
    await germanOption.click();
    await page.waitForLoadState('networkidle');
    
    // Verify German
    await expect(page.locator('.language-flag')).toContainText('🇩🇪');
    
    console.log('✅ Successfully clicked German option with mouse movement');
  });
});
