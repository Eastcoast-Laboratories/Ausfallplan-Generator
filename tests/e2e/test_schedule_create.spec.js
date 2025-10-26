const { test, expect } = require('@playwright/test');

test('System admin can create schedule', async ({ page }) => {
    console.log('🧪 Testing: System Admin Schedule Creation\n');
    
    // Login as admin
    await page.goto('http://localhost:8080/users/login');
    await page.fill('input[name="email"]', 'admin@demo.kita');
    await page.fill('input[name="password"]', 'asbdasdaddd');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    console.log('✅ Logged in as admin@demo.kita\n');
    
    // Try to create a schedule
    await page.goto('http://localhost:8080/schedules/add');
    await page.waitForLoadState('networkidle');
    
    // Check if we get error message or can see the form
    const pageContent = await page.content();
    
    if (pageContent.includes('Sie müssen einer Organisation angehören')) {
        console.log('❌ ERROR: Got organization error message!');
        console.log('   Message: "Sie müssen einer Organisation angehören, um Dienstpläne zu erstellen."');
        throw new Error('System admin cannot create schedules - getPrimaryOrganization() returns null!');
    }
    
    // Check if form is visible
    const hasForm = pageContent.includes('name="name"') || pageContent.includes('Ausfallplan erstellen');
    
    if (hasForm) {
        console.log('✅ SUCCESS: Schedule creation form is visible!');
        console.log('   System admin can create schedules!');
    } else {
        console.log('❌ FAILURE: No form visible, but no error either');
        console.log('   Page might have redirected');
    }
    
    // Take screenshot for proof
    await page.screenshot({ path: '/var/www/Ausfallplan-Generator/schedule_create_test.png' });
    console.log('📸 Screenshot saved: schedule_create_test.png');
    
    expect(hasForm).toBeTruthy();
});
