const { test, expect} = require('@playwright/test');

/**
 * TEST: Simple health check
 * 
 * Basic test to verify the application is reachable and responds.
 * Tests that the homepage loads and has a title.
 */
test.describe('Health Check', () => {
    test('should load homepage successfully', async ({ page }) => {
    console.log('🔍 Testing if app is reachable...');
    
    await page.goto('http://localhost:8080');
    
    console.log('✅ App is reachable!');
    
    // Check if we get any response
    const title = await page.title();
    console.log(`📄 Page title: "${title}"`);
    
    expect(title.length).toBeGreaterThan(0);
    });
});
