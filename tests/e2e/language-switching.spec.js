// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('Language Switching Verification', () => {
  
  test('should verify default language and language switching', async ({ page }) => {
    console.log('🚀 Starting language verification test...');
    
    // Step 1: Go to login page
    console.log('📍 Step 1: Navigate to login page');
    await page.goto('/users/login');
    await page.waitForLoadState('networkidle');
    
    // Step 2: Check what language is displayed on login page
    console.log('🔍 Step 2: Check login page language');
    const loginPageContent = await page.textContent('body');
    
    const hasGerman = loginPageContent.includes('Anmelden') || 
                      loginPageContent.includes('E-Mail') || 
                      loginPageContent.includes('Passwort');
    const hasEnglish = loginPageContent.includes('Login') && 
                       loginPageContent.includes('Email') && 
                       loginPageContent.includes('Password');
    
    console.log(`  - Has German words: ${hasGerman}`);
    console.log(`  - Has English words: ${hasEnglish}`);
    
    await page.screenshot({ 
      path: 'screenshots/language-test-login-page.png',
      fullPage: true 
    });
    
    // Step 3: Login
    console.log('🔐 Step 3: Login with admin credentials');
    await page.fill('input[name="email"]', 'admin@demo.kita');
    await page.fill('input[name="password"]', 'asbdasdaddd');
    await page.click('button[type="submit"]');
    
    // Wait for dashboard
    await page.waitForURL(/dashboard/, { timeout: 10000 });
    await page.waitForTimeout(1000);
    
    // Step 4: Check dashboard language
    console.log('🔍 Step 4: Check dashboard language');
    const dashboardContent = await page.textContent('body');
    
    const dashboardHasGerman = dashboardContent.includes('Übersicht') || 
                               dashboardContent.includes('Kinder') || 
                               dashboardContent.includes('Pläne') ||
                               dashboardContent.includes('Abmelden');
    const dashboardHasEnglish = dashboardContent.includes('Dashboard') && 
                                dashboardContent.includes('Children') && 
                                dashboardContent.includes('Schedules') &&
                                dashboardContent.includes('Logout');
    
    console.log(`  - Dashboard has German: ${dashboardHasGerman}`);
    console.log(`  - Dashboard has English: ${dashboardHasEnglish}`);
    
    await page.screenshot({ 
      path: 'screenshots/language-test-dashboard-before.png',
      fullPage: true 
    });
    
    // Step 5: Find and click language switcher
    console.log('🌍 Step 5: Looking for language switcher...');
    
    // Try to find language switcher (flag or dropdown)
    const languageSwitcher = page.locator('.language-switcher, .language-flag');
    const isVisible = await languageSwitcher.isVisible().catch(() => false);
    
    if (isVisible) {
      console.log('  ✅ Found language switcher!');
      
      // Hover over it to show dropdown
      await languageSwitcher.hover();
      await page.waitForTimeout(500);
      
      // Take screenshot of dropdown
      await page.screenshot({ 
        path: 'screenshots/language-test-dropdown.png',
        fullPage: true 
      });
      
      // Try to click German flag/option
      const germanOption = page.locator('a[href*="changeLanguage/de"], a[href*="lang=de"]');
      const germanExists = await germanOption.count() > 0;
      
      if (germanExists) {
        console.log('  🇩🇪 Clicking German language option...');
        await germanOption.first().click();
        await page.waitForTimeout(2000);
        
        // Step 6: Verify language changed
        console.log('🔍 Step 6: Verify language changed to German');
        const afterSwitchContent = await page.textContent('body');
        
        const nowHasGerman = afterSwitchContent.includes('Übersicht') || 
                            afterSwitchContent.includes('Kinder') || 
                            afterSwitchContent.includes('Abmelden');
        
        console.log(`  - After switch has German: ${nowHasGerman}`);
        
        await page.screenshot({ 
          path: 'screenshots/language-test-after-switch.png',
          fullPage: true 
        });
        
        // Also try English switch
        await languageSwitcher.hover();
        await page.waitForTimeout(500);
        
        const englishOption = page.locator('a[href*="changeLanguage/en"], a[href*="lang=en"]');
        const englishExists = await englishOption.count() > 0;
        
        if (englishExists) {
          console.log('  🇬🇧 Clicking English language option...');
          await englishOption.first().click();
          await page.waitForTimeout(2000);
          
          const afterEnglishContent = await page.textContent('body');
          const nowHasEnglish = afterEnglishContent.includes('Dashboard') && 
                               afterEnglishContent.includes('Children') && 
                               afterEnglishContent.includes('Logout');
          
          console.log(`  - After switch has English: ${nowHasEnglish}`);
          
          await page.screenshot({ 
            path: 'screenshots/language-test-english.png',
            fullPage: true 
          });
        }
      } else {
        console.log('  ⚠️  Could not find German language option in dropdown');
      }
    } else {
      console.log('  ❌ Language switcher not visible!');
    }
    
    // Final summary
    console.log('\n📊 SUMMARY:');
    console.log(`  - Login page shows German: ${hasGerman}`);
    console.log(`  - Login page shows English: ${hasEnglish}`);
    console.log(`  - Dashboard shows German: ${dashboardHasGerman}`);
    console.log(`  - Dashboard shows English: ${dashboardHasEnglish}`);
    console.log(`  - Language switcher visible: ${isVisible}`);
    
    // Test assertions
    if (!dashboardHasGerman && dashboardHasEnglish) {
      throw new Error('❌ PROBLEM: Dashboard is in English, but should default to German!');
    }
  });

  test('should verify translation files are loaded', async ({ page }) => {
    // Login first
    await page.goto('/users/login');
    await page.fill('input[name="email"]', 'admin@demo.kita');
    await page.fill('input[name="password"]', 'asbdasdaddd');
    await page.click('button[type="submit"]');
    await page.waitForURL(/dashboard/, { timeout: 10000 });
    
    // Navigate to different pages and check for German text
    const pagesToCheck = [
      { url: '/dashboard', germanWords: ['Übersicht', 'Kinder', 'Pläne'] },
      { url: '/children', germanWords: ['Kinder', 'Neues Kind'] },
      { url: '/schedules', germanWords: ['Pläne', 'Neuer Plan'] },
    ];
    
    for (const pageInfo of pagesToCheck) {
      console.log(`\n📄 Checking ${pageInfo.url}...`);
      await page.goto(pageInfo.url);
      await page.waitForLoadState('networkidle');
      
      const content = await page.textContent('body');
      
      for (const word of pageInfo.germanWords) {
        const hasWord = content.includes(word);
        console.log(`  - "${word}": ${hasWord ? '✅' : '❌'}`);
      }
      
      await page.screenshot({ 
        path: `screenshots/language-test-${pageInfo.url.replace('/', '-')}.png`,
        fullPage: true 
      });
    }
  });
});
