const { test, expect } = require('@playwright/test');

test('Debug sibling names in waitlist', async ({ page }) => {
    // Login first
    await page.goto('http://localhost:8080/users/login');
    await page.fill('input[name="email"]', 'admin@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    // Go to waitlist
    await page.goto('http://localhost:8080/waitlist?schedule_id=3');
    await page.waitForLoadState('networkidle');
    
    // Get page HTML
    const html = await page.content();
    console.log('\n=== SEARCHING FOR SIBLING DATA ===\n');
    
    // Check if there are any sibling badges
    const siblingBadges = await page.locator('text=Geschwister').count();
    console.log(`Found ${siblingBadges} sibling badges on page`);
    
    // Get all elements with sibling group id
    const elementsWithSiblingGroup = await page.locator('[data-sibling-group]').all();
    console.log(`\nFound ${elementsWithSiblingGroup.length} elements with sibling group:`);
    
    for (let i = 0; i < elementsWithSiblingGroup.length; i++) {
        const el = elementsWithSiblingGroup[i];
        const siblingGroupId = await el.getAttribute('data-sibling-group');
        const childName = await el.locator('strong').first().textContent();
        console.log(`  - Child: "${childName}", sibling_group_id: ${siblingGroupId}`);
    }
    
    // Check all title attributes containing "Geschwister"
    console.log('\n=== CHECKING TITLE ATTRIBUTES ===\n');
    const links = await page.locator('a[title*="Geschwister"]').all();
    console.log(`Found ${links.length} links with Geschwister in title:`);
    
    for (let i = 0; i < links.length; i++) {
        const link = links[i];
        const title = await link.getAttribute('title');
        const href = await link.getAttribute('href');
        console.log(`  - Title: "${title}"`);
        console.log(`    Href: ${href}`);
    }
    
    // Check error log for DEBUG messages
    console.log('\n=== CHECKING ERROR LOG ===\n');
    const { exec } = require('child_process');
    const util = require('util');
    const execPromise = util.promisify(exec);
    
    try {
        const { stdout } = await execPromise('tail -n 100 /var/www/Ausfallplan-Generator/logs/error.log | grep "DEBUG:"');
        console.log('Debug logs found:');
        console.log(stdout);
    } catch (e) {
        console.log('No DEBUG logs found in error.log');
    }
    
    // Fail the test if we see "keine anderen Geschwister gefunden"
    const pageText = await page.textContent('body');
    if (pageText.includes('keine anderen Geschwister gefunden') || 
        html.includes('keine anderen Geschwister gefunden')) {
        console.log('\n❌ ERROR: Found "keine anderen Geschwister gefunden" in page!');
    }
    
    // Check database directly
    console.log('\n=== CHECKING DATABASE ===\n');
    try {
        const { stdout: dbResult } = await execPromise(
            'docker exec ausfallplan-db mysql -u root -proot ausfallplan -e ' +
            '"SELECT c.id, c.name, c.sibling_group_id, ' +
            '(SELECT GROUP_CONCAT(c2.name SEPARATOR \', \') FROM children c2 ' +
            'WHERE c2.sibling_group_id = c.sibling_group_id AND c2.id != c.id) as siblings ' +
            'FROM children c WHERE c.sibling_group_id IS NOT NULL LIMIT 10;"'
        );
        console.log('Children with siblings in database:');
        console.log(dbResult);
    } catch (e) {
        console.log('Could not query database:', e.message);
    }
});
