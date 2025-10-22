# Screenshot-Testing für die Navigation

## Problem
CakePHP **Unit-Tests können KEINE Screenshots erstellen** - sie können nur HTML-Responses prüfen.

## Lösung: Browser-Testing mit Playwright/Puppeteer

Für echte Screenshots braucht man einen **Headless Browser**:

### Option 1: Playwright (empfohlen)

```bash
# Installation
npm init -y
npm install --save-dev @playwright/test

# Playwright Browser installieren
npx playwright install chromium
```

**Test-Datei:** `tests/e2e/navigation.spec.js`

```javascript
const { test, expect } = require('@playwright/test');

test.describe('Navigation Screenshots', () => {
  test('should show navigation when logged in', async ({ page }) => {
    // 1. Gehe zur Login-Seite
    await page.goto('http://localhost:8080/users/login');
    
    // 2. Login
    await page.fill('input[name="email"]', 'admin@example.com');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    
    // 3. Warte bis Dashboard geladen ist
    await page.waitForSelector('.sidebar');
    
    // 4. Screenshot erstellen
    await page.screenshot({ 
      path: 'screenshots/navigation-desktop.png',
      fullPage: true 
    });
    
    // 5. Prüfe Navigation-Elemente
    await expect(page.locator('.sidebar')).toBeVisible();
    await expect(page.locator('.user-avatar')).toBeVisible();
    await expect(page.locator('text=Dashboard')).toBeVisible();
    await expect(page.locator('text=Logout')).toBeVisible();
  });
  
  test('should show hamburger menu on mobile', async ({ page }) => {
    // Mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });
    
    // Login und Screenshot
    await page.goto('http://localhost:8080/users/login');
    await page.fill('input[name="email"]', 'admin@example.com');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForSelector('.hamburger');
    
    // Screenshot vor Hamburger-Klick
    await page.screenshot({ 
      path: 'screenshots/navigation-mobile-closed.png' 
    });
    
    // Hamburger öffnen
    await page.click('.hamburger');
    await page.waitForSelector('.sidebar.mobile-open');
    
    // Screenshot mit offener Navigation
    await page.screenshot({ 
      path: 'screenshots/navigation-mobile-open.png' 
    });
  });
});
```

**Test ausführen:**
```bash
npx playwright test
```

---

### Option 2: Puppeteer (Node.js)

```bash
npm install --save-dev puppeteer
```

**Screenshot-Script:** `tests/screenshots.js`

```javascript
const puppeteer = require('puppeteer');

(async () => {
  const browser = await puppeteer.launch();
  const page = await browser.newPage();
  
  // Login
  await page.goto('http://localhost:8080/users/login');
  await page.type('input[name="email"]', 'admin@example.com');
  await page.type('input[name="password"]', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForNavigation();
  
  // Desktop Screenshot
  await page.setViewport({ width: 1920, height: 1080 });
  await page.screenshot({ path: 'screenshots/desktop-navigation.png' });
  
  // Mobile Screenshot
  await page.setViewport({ width: 375, height: 667 });
  await page.screenshot({ path: 'screenshots/mobile-navigation.png' });
  
  await browser.close();
})();
```

**Ausführen:**
```bash
node tests/screenshots.js
```

---

### Option 3: Einfache Alternative (ohne Installation)

**Browser DevTools nutzen:**

1. Öffne http://localhost:8080/users/login
2. Logge dich ein (admin@example.com / password123)
3. Drücke `F12` → DevTools
4. Drücke `Ctrl+Shift+P` → "Capture full size screenshot"
5. Screenshot wird automatisch gespeichert!

---

## Vergleich

| Tool | Vorteile | Nachteile |
|------|----------|-----------|
| **CakePHP Tests** | ✅ Schnell<br>✅ Einfach<br>✅ Bereits vorhanden | ❌ Keine Screenshots<br>❌ Nur HTML |
| **Playwright** | ✅ Screenshots<br>✅ Cross-Browser<br>✅ Gute Docs | ❌ Extra Installation<br>❌ Langsamer |
| **Puppeteer** | ✅ Screenshots<br>✅ Flexibel | ❌ Nur Chrome<br>❌ Mehr Setup |
| **Browser DevTools** | ✅ Kein Setup<br>✅ Sofort nutzbar | ❌ Manuell<br>❌ Nicht automatisiert |

---

## Aktueller Stand

✅ **44 Unit-Tests** prüfen die Navigation-Funktionalität
- Navigation sichtbar wenn eingeloggt
- Hamburger-Menü funktioniert
- Logout-Button vorhanden
- Language Switcher vorhanden

❌ **Keine Screenshot-Tests** - würden Playwright/Puppeteer brauchen

**Empfehlung:** Nutze erstmal die Browser DevTools für Screenshots, später kannst du Playwright hinzufügen wenn du automatisierte Visual Regression Tests brauchst.
