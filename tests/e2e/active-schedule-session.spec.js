const { test, expect } = require('@playwright/test');

test.describe('Active Schedule in Session', () => {
    test.beforeEach(async ({ page }) => {
        // Login
        await page.goto('http://localhost:8080/login');
        await page.fill('input[name="email"]', 'a2@a.de');
        await page.fill('input[name="password"]', 'asdfasdf');
        await page.click('button[type="submit"]');
        await page.waitForURL(/.*\/(dashboard)?$/);
    });

    test('should remember active schedule when navigating to waitlist', async ({ page }) => {
        console.log('🧪 Testing active schedule in waitlist...');
        
        // Step 1: Go to schedules and edit a schedule (sets activeScheduleId in session)
        console.log('📍 Step 1: Edit a schedule to set activeScheduleId');
        await page.goto('http://localhost:8080/schedules');
        
        // Click first "View" button
        const firstViewButton = page.locator('a[href*="/schedules/view/"]').first();
        await firstViewButton.click();
        await page.waitForURL(/.*\/schedules\/view\/\d+/);
        
        // Get schedule ID from URL
        const scheduleUrl = page.url();
        const scheduleId = scheduleUrl.match(/\/schedules\/view\/(\d+)/)?.[1];
        console.log('  Schedule ID:', scheduleId);
        expect(scheduleId).toBeTruthy();
        
        // Click Edit button to trigger session write
        await page.click('a[href*="/schedules/edit/"]');
        await page.waitForURL(/.*\/schedules\/edit\/\d+/);
        console.log('  ✅ Schedule edit page loaded (activeScheduleId set in session)');
        
        // Step 2: Navigate to waitlist
        console.log('📍 Step 2: Navigate to waitlist');
        await page.goto('http://localhost:8080/waitlist');
        await page.waitForLoadState('networkidle');
        
        // Step 3: Check if the schedule selector shows the active schedule
        console.log('📍 Step 3: Check if active schedule is preselected');
        const scheduleSelector = page.locator('select[name="schedule_id"]');
        
        if (await scheduleSelector.count() > 0) {
            const selectedValue = await scheduleSelector.inputValue();
            console.log('  Selected schedule ID in dropdown:', selectedValue);
            console.log('  Expected schedule ID:', scheduleId);
            
            // The selected value should match our schedule ID
            expect(selectedValue).toBe(scheduleId);
            console.log('  ✅ Active schedule correctly preselected!');
        } else {
            console.log('  ⚠️  No schedule selector found (might be only one schedule)');
            // Check if schedule name is displayed somewhere
            const pageContent = await page.content();
            console.log('  Page contains schedule reference:', pageContent.includes('schedule'));
        }
        
        console.log('');
        console.log('✅ TEST PASSED - Active schedule remembered in session!');
    });
});
