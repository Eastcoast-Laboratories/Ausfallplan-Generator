const { test, expect } = require('@playwright/test');

test('Test new registration form', async ({ page }) => {
    console.log('🧪 Testing New Registration Form\n');
    
    // Go to registration page
    await page.goto('http://localhost:8080/users/register');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);
    
    // Take screenshot of initial state
    await page.screenshot({ 
        path: '/var/www/Ausfallplan-Generator/registration_form_initial.png',
        fullPage: true 
    });
    console.log('📸 Screenshot 1: Initial form\n');
    
    // Check if selectbox exists
    const orgSelector = await page.locator('select#organization-choice').count();
    console.log(`Organization selectbox found: ${orgSelector > 0 ? 'YES ✅' : 'NO ❌'}`);
    
    if (orgSelector > 0) {
        // Check options
        const options = await page.locator('select#organization-choice option').allTextContents();
        console.log(`Available options (${options.length}):`);
        options.forEach((opt, i) => console.log(`  ${i+1}. ${opt}`));
        
        // Check if "Neue Organisation anlegen" is first
        const firstOption = options[0];
        const hasNewOrg = firstOption.includes('Neue Organisation') || firstOption.includes('anlegen');
        console.log(`\nFirst option is "New Organization": ${hasNewOrg ? 'YES ✅' : 'NO ❌'}`);
        
        // Check role selector visibility (should be visible initially)
        const roleSelector = await page.locator('select#role-selector').isVisible();
        console.log(`Role selector visible initially: ${roleSelector ? 'YES ✅' : 'NO ❌'}`);
        
        // Select "new organization"
        await page.selectOption('select#organization-choice', 'new');
        await page.waitForTimeout(500);
        
        // Take screenshot after selecting new org
        await page.screenshot({ 
            path: '/var/www/Ausfallplan-Generator/registration_form_new_org.png',
            fullPage: true 
        });
        console.log('\n📸 Screenshot 2: After selecting "New Organization"\n');
        
        // Check if organization name input is visible
        const orgNameInput = await page.locator('input#organization-name-input').isVisible();
        console.log(`Organization name input visible: ${orgNameInput ? 'YES ✅' : 'NO ❌'}`);
        
        // Check if role selector is hidden
        const roleSelectorHidden = !(await page.locator('select#role-selector').isVisible());
        console.log(`Role selector hidden: ${roleSelectorHidden ? 'YES ✅' : 'NO ❌'}`);
        
        // Now select an existing organization
        if (options.length > 2) {
            // Find first non-divider, non-new option
            let existingOrgValue = null;
            for (let i = 0; i < options.length; i++) {
                const optValue = await page.locator(`select#organization-choice option:nth-child(${i+1})`).getAttribute('value');
                if (optValue && optValue !== 'new' && optValue !== 'divider') {
                    existingOrgValue = optValue;
                    break;
                }
            }
            
            if (existingOrgValue) {
                await page.selectOption('select#organization-choice', existingOrgValue);
                await page.waitForTimeout(500);
                
                // Take screenshot
                await page.screenshot({ 
                    path: '/var/www/Ausfallplan-Generator/registration_form_existing_org.png',
                    fullPage: true 
                });
                console.log('\n📸 Screenshot 3: After selecting existing organization\n');
                
                // Check if organization name input is hidden
                const orgNameHidden = !(await page.locator('input#organization-name-input').isVisible());
                console.log(`Organization name input hidden: ${orgNameHidden ? 'YES ✅' : 'NO ❌'}`);
                
                // Check if role selector is visible
                const roleSelectorVisible = await page.locator('select#role-selector').isVisible();
                console.log(`Role selector visible: ${roleSelectorVisible ? 'YES ✅' : 'NO ❌'}`);
                
                // Check role options
                const roleOptions = await page.locator('select#role-selector option').allTextContents();
                console.log(`\nRole options (${roleOptions.length}):`);
                roleOptions.forEach((opt, i) => console.log(`  ${i+1}. ${opt}`));
                
                // Check if translations are German
                const hasGermanRoles = roleOptions.some(opt => 
                    opt.includes('Betrachter') || opt.includes('Redakteur') || opt.includes('Organisations-Admin')
                );
                console.log(`\nGerman role translations: ${hasGermanRoles ? 'YES ✅' : 'NO ❌'}`);
            }
        }
        
        console.log('\n✅ Registration form test complete!');
        expect(orgSelector).toBeGreaterThan(0);
    } else {
        console.log('❌ Organization selectbox not found!');
        throw new Error('Organization selectbox missing');
    }
});
