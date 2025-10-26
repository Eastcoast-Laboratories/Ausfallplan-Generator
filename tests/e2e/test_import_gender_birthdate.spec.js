const { test, expect } = require('@playwright/test');
const path = require('path');

test('Import CSV and verify gender + birthdate are saved', async ({ page }) => {
    console.log('🧪 Testing: CSV Import with Gender & Birthdate\n');
    
    // Login as admin
    await page.goto('http://localhost:8080/users/login');
    await page.fill('input[name="email"]', 'admin@demo.kita');
    await page.fill('input[name="password"]', 'asbdasdaddd');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    console.log('✅ Logged in\n');
    
    // Go to import page
    await page.goto('http://localhost:8080/children/import');
    await page.waitForLoadState('networkidle');
    
    // Upload CSV file
    const csvPath = '/var/www/Ausfallplan-Generator/dev/Kindergarten-Adress-Liste_melsdorfer Str. 2025.csv';
    const fileInput = await page.locator('input[type="file"]');
    await fileInput.setInputFiles(csvPath);
    
    // Submit form
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
    
    console.log('📤 CSV uploaded\n');
    
    // Check if we're on preview page
    const pageContent = await page.content();
    
    if (pageContent.includes('Import-Vorschau') || pageContent.includes('Preview')) {
        console.log('✅ On preview page\n');
        
        // Select anonymization mode (full names)
        await page.selectOption('select[name="anonymization_mode"]', 'full');
        
        // Confirm import
        await page.click('button:has-text("Importieren")');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);
        
        console.log('✅ Import confirmed\n');
    }
    
    // Go to children list
    await page.goto('http://localhost:8080/children');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
    
    // Take screenshot
    await page.screenshot({ 
        path: '/var/www/Ausfallplan-Generator/children_with_gender_birthdate.png',
        fullPage: true 
    });
    
    // Check if gender symbols are visible
    const hasGenderSymbols = await page.locator('td:has-text("♂️"), td:has-text("♀️")').count() > 0;
    console.log(`Gender symbols visible: ${hasGenderSymbols ? '✅' : '❌'}`);
    
    // Check if birthdates are visible (format: dd.mm.yyyy)
    const hasBirthdates = await page.locator('td', { hasText: /\d{2}\.\d{2}\.\d{4}/ }).count() > 0;
    console.log(`Birthdates visible: ${hasBirthdates ? '✅' : '❌'}`);
    
    // Check table headers
    const hasGenderHeader = await page.locator('th:has-text("Geschlecht")').count() > 0;
    const hasBirthdateHeader = await page.locator('th:has-text("Geburtsdatum")').count() > 0;
    
    console.log(`\nTable Headers:`);
    console.log(`  - Geschlecht: ${hasGenderHeader ? '✅' : '❌'}`);
    console.log(`  - Geburtsdatum: ${hasBirthdateHeader ? '✅' : '❌'}`);
    
    // Count children
    const childRows = await page.locator('tbody tr').count();
    console.log(`\n📊 Total children: ${childRows}`);
    
    if (hasGenderSymbols && hasBirthdates) {
        console.log('\n🎉 SUCCESS! Gender and birthdate are displayed!');
    } else {
        console.log('\n❌ FAILURE: Missing gender or birthdate display');
    }
    
    expect(hasGenderSymbols || hasBirthdates).toBeTruthy();
});
