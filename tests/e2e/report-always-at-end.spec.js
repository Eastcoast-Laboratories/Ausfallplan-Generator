const { test, expect } = require('@playwright/test');

test.describe('Report - Always at End Section', () => {
    test('should show children assigned to schedule but not on waitlist in "Always at End" section', async ({ page }) => {
        console.log('🧪 Testing Always at End section...');
        
        // Step 1: Login
        console.log('📍 Step 1: Login as admin');
        await page.goto('http://localhost:8080/users/login');
        await page.fill('input[name="email"]', 'admin@demo.kita');
        await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
        await page.click('button[type="submit"]');
        await page.waitForURL(/.*\/(dashboard)?$/);
        console.log('  ✅ Logged in');
        
        // Step 2: Create test children
        console.log('📍 Step 2: Create test children');
        const timestamp = Date.now();
        const childOnWaitlist = `WaitlistChild_${timestamp}`;
        const childAlwaysAtEnd = `AlwaysAtEndChild_${timestamp}`;
        
        // Create first child (will be on waitlist)
        await page.goto('http://localhost:8080/children/add');
        await page.fill('input[name="display_name"]', childOnWaitlist);
        await page.fill('input[name="first_name"]', 'Test');
        await page.fill('input[name="last_name"]', 'Child1');
        await page.click('button[type="submit"]');
        await page.waitForURL(/.*\/children$/);
        console.log(`  ✅ Created child: ${childOnWaitlist}`);
        
        // Create second child (will be always at end)
        await page.goto('http://localhost:8080/children/add');
        await page.fill('input[name="display_name"]', childAlwaysAtEnd);
        await page.fill('input[name="first_name"]', 'Test');
        await page.fill('input[name="last_name"]', 'Child2');
        await page.click('button[type="submit"]');
        await page.waitForURL(/.*\/children$/);
        console.log(`  ✅ Created child: ${childAlwaysAtEnd}`);
        
        // Step 3: Create schedule
        console.log('📍 Step 3: Create test schedule');
        await page.goto('http://localhost:8080/schedules/add');
        const scheduleTitle = `TestSchedule_${timestamp}`;
        await page.fill('input[name="title"]', scheduleTitle);
        await page.fill('input[name="capacity_per_day"]', '5');
        await page.fill('input[name="days_count"]', '3');
        await page.click('button[type="submit"]');
        await page.waitForURL(/.*\/schedules$/);
        console.log(`  ✅ Created schedule: ${scheduleTitle}`);
        
        // Step 4: Add first child to waitlist
        console.log('📍 Step 4: Add first child to waitlist');
        await page.goto('http://localhost:8080/waitlist');
        
        // Click "Add Child" button
        await page.click('a[href*="/waitlist/add"]');
        await page.waitForURL(/.*\/waitlist\/add.*/);
        
        // Select the first child
        const childSelect = await page.locator('select[name="child_id"]');
        const options = await childSelect.locator('option').all();
        let childOnWaitlistId = null;
        
        for (const option of options) {
            const text = await option.textContent();
            if (text.includes(childOnWaitlist)) {
                childOnWaitlistId = await option.getAttribute('value');
                await childSelect.selectOption(childOnWaitlistId);
                break;
            }
        }
        
        console.log(`  ✅ Selected child: ${childOnWaitlist} (ID: ${childOnWaitlistId})`);
        await page.click('button[type="submit"]');
        await page.waitForURL(/.*\/waitlist$/);
        console.log('  ✅ Child added to waitlist');
        
        // Step 5: Manually assign second child to schedule WITHOUT adding to waitlist
        console.log('📍 Step 5: Assign second child directly to schedule (not waitlist)');
        
        // Get schedule ID from current page
        const scheduleIdMatch = await page.url().match(/schedule_id=(\d+)/);
        const scheduleId = scheduleIdMatch ? scheduleIdMatch[1] : null;
        
        if (!scheduleId) {
            // Find schedule ID from schedules page
            await page.goto('http://localhost:8080/schedules');
            const scheduleLink = await page.locator(`a:has-text("${scheduleTitle}")`).first();
            const href = await scheduleLink.getAttribute('href');
            const match = href.match(/\/schedules\/view\/(\d+)/);
            const foundScheduleId = match ? match[1] : null;
            
            if (foundScheduleId) {
                console.log(`  ✅ Found schedule ID: ${foundScheduleId}`);
                
                // Get second child ID
                await page.goto('http://localhost:8080/children');
                const childRow = await page.locator(`tr:has-text("${childAlwaysAtEnd}")`).first();
                const editLink = await childRow.locator('a[href*="/children/edit/"]').getAttribute('href');
                const childIdMatch = editLink.match(/\/children\/edit\/(\d+)/);
                const childAlwaysAtEndId = childIdMatch ? childIdMatch[1] : null;
                
                if (childAlwaysAtEndId) {
                    console.log(`  ✅ Found child ID: ${childAlwaysAtEndId}`);
                    
                    // Use direct database update via API or edit page
                    // Navigate to edit page and set schedule_id
                    await page.goto(`http://localhost:8080/children/edit/${childAlwaysAtEndId}`);
                    
                    // Set schedule_id in a hidden field or via developer tools
                    await page.evaluate(async (scheduleId, childId) => {
                        // Make AJAX call to update
                        const formData = new FormData();
                        formData.append('schedule_id', scheduleId);
                        formData.append('waitlist_order', ''); // NULL
                        
                        const response = await fetch(`/children/edit/${childId}`, {
                            method: 'POST',
                            body: formData
                        });
                        
                        return response.ok;
                    }, foundScheduleId, childAlwaysAtEndId);
                    
                    console.log('  ✅ Child assigned to schedule (not on waitlist)');
                }
            }
        }
        
        // Step 6: Generate and view report
        console.log('📍 Step 6: Generate report');
        await page.goto('http://localhost:8080/schedules');
        const viewLink = await page.locator(`a:has-text("${scheduleTitle}")`).first();
        await viewLink.click();
        await page.waitForURL(/.*\/schedules\/view\/\d+/);
        
        // Click generate report button
        const generateButton = await page.locator('a[href*="/schedules/generate-report/"]').first();
        await generateButton.click();
        await page.waitForURL(/.*\/schedules\/generate-report\/\d+/);
        console.log('  ✅ Report page loaded');
        
        // Step 7: Verify "Always at End" section
        console.log('📍 Step 7: Verify "Always at End" section');
        
        // Check if "Always at End" section exists
        const alwaysAtEndSection = await page.locator('h3:has-text("Immer am Ende")');
        await expect(alwaysAtEndSection).toBeVisible();
        console.log('  ✅ "Immer am Ende" section found');
        
        // Check content - should NOT show "Keine"
        const sectionText = await page.locator('h3:has-text("Immer am Ende")').locator('..').textContent();
        
        if (sectionText.includes('Keine')) {
            console.log('  ❌ FAIL: Section shows "Keine" but should show children');
            console.log('  Section content:', sectionText);
        } else {
            console.log('  ✅ Section does NOT show "Keine"');
        }
        
        // Check if second child appears in "Always at End"
        const alwaysAtEndContent = await page.locator('h3:has-text("Immer am Ende")').locator('..').textContent();
        
        if (alwaysAtEndContent.includes(childAlwaysAtEnd)) {
            console.log(`  ✅ Child "${childAlwaysAtEnd}" found in "Always at End" section`);
        } else {
            console.log(`  ❌ FAIL: Child "${childAlwaysAtEnd}" NOT found in "Always at End" section`);
            console.log('  Section content:', alwaysAtEndContent);
        }
        
        // Verify first child is NOT in "Always at End"
        if (!alwaysAtEndContent.includes(childOnWaitlist)) {
            console.log(`  ✅ Child "${childOnWaitlist}" correctly NOT in "Always at End" (on waitlist)`);
        } else {
            console.log(`  ⚠️ Child "${childOnWaitlist}" unexpectedly in "Always at End"`);
        }
        
        // Final assertions
        expect(sectionText).not.toContain('Keine');
        expect(alwaysAtEndContent).toContain(childAlwaysAtEnd);
        expect(alwaysAtEndContent).not.toContain(childOnWaitlist);
        
        console.log('');
        console.log('🎉 TEST COMPLETED SUCCESSFULLY!');
        console.log('  - "Always at End" section exists: ✅');
        console.log('  - Does NOT show "Keine": ✅');
        console.log(`  - Shows ${childAlwaysAtEnd}: ✅`);
        console.log(`  - Does NOT show ${childOnWaitlist}: ✅`);
    });
});
