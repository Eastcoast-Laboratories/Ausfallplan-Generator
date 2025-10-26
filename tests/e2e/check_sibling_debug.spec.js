const { test, expect } = require('@playwright/test');

test('Check sibling names debug output', async ({ page }) => {
    // Login
    await page.goto('http://localhost:8080/users/login');
    await page.fill('input[name="email"]', 'admin@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    // Go to waitlist
    await page.goto('http://localhost:8080/waitlist?schedule_id=3');
    await page.waitForLoadState('networkidle');
    
    // Get the debug div content
    const debugDiv = await page.locator('div:has-text("DEBUG: siblingNames Array")').first();
    
    if (await debugDiv.count() > 0) {
        const debugText = await debugDiv.textContent();
        console.log('\n=== DEBUG OUTPUT FROM PAGE ===');
        console.log(debugText);
        console.log('=== END DEBUG OUTPUT ===\n');
    } else {
        console.log('\n❌ ERROR: No debug div found! siblingNames not set!');
    }
    
    // Also check for sibling badges
    const siblingLinks = await page.locator('a:has-text("Geschwister")').all();
    console.log(`\nFound ${siblingLinks.length} sibling badges`);
    
    for (let i = 0; i < siblingLinks.length; i++) {
        const link = siblingLinks[i];
        const title = await link.getAttribute('title');
        console.log(`Badge ${i + 1}: title="${title}"`);
    }
});
