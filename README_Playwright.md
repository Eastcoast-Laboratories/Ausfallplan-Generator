# Playwright Tests - Best Practices

## 🎯 Das Wichtigste zuerst

**Goldene Regel:** Wenn du einen Playwright Test anfängst, musst du ihn auch zum Laufen bringen!

## 🔑 Entscheidende Faktoren für erfolgreiche Tests

### 1. **Eigene Test-Daten erstellen**

❌ **FALSCH:**
```javascript
// Hardcoded ID verwenden
await page.goto('http://localhost:8080/schedules/generate-report/1');
```

✅ **RICHTIG:**
```javascript
// Eigene Test-Daten erstellen
await page.goto('http://localhost:8080/schedules/add');
await page.fill('input[name="title"]', `Test Schedule ${Date.now()}`);
await page.fill('input[name="starts_on"]', '2025-01-01');
await page.click('button[type="submit"]');
await page.waitForLoadState('networkidle');

// ID aus URL extrahieren
const url = page.url();
const scheduleId = url.match(/\/schedules\/view\/(\d+)/)?.[1];
console.log(`✅ Created schedule ID: ${scheduleId}`);
```

**Warum?**
- Existierende Daten können gelöscht worden sein
- Tests müssen unabhängig voneinander laufen
- Jeder Test räumt seine eigenen Daten auf

### 2. **Von funktionierenden Tests lernen**

Bevor du einen neuen Test schreibst:

```bash
# Test funktionierende Tests
timeout 90 npx playwright test tests/e2e/simple_health_check.spec.js --project=chromium
timeout 120 npx playwright test tests/e2e/waitlist-add-all.spec.js --project=chromium
```

Schau dir an:
- Wie navigieren sie?
- Wie warten sie auf Elemente?
- Wie extrahieren sie IDs?
- Welche Selektoren verwenden sie?

### 3. **Richtige Wait-Strategien**

✅ **Empfohlen:**
```javascript
await page.waitForLoadState('networkidle');  // Warte bis Netzwerk ruhig ist
await page.waitForURL('**/dashboard');       // Warte auf URL-Pattern
await expect(element).toBeVisible({ timeout: 5000 }); // Mit Timeout
```

❌ **Vermeiden:**
```javascript
await page.waitForTimeout(2000);  // Nur wenn absolut nötig
```

### 4. **Debug-Output einbauen**

Immer Logging hinzufügen:

```javascript
console.log('📍 Step 1: Login');
// ... code ...
console.log('✅ Logged in');

console.log('📍 Step 2: Create schedule');
const scheduleId = url.match(/\/schedules\/view\/(\d+)/)?.[1];
console.log(`✅ Schedule created with ID: ${scheduleId}`);
```

Bei Problemen zusätzlich:

```javascript
// Debug: Was ist wirklich auf der Seite?
const pageTitle = await page.title();
const pageURL = page.url();
console.log(`  Page title: "${pageTitle}"`);
console.log(`  Page URL: ${pageURL}`);

// Element-Count prüfen
const elementCount = await page.locator('.some-class').count();
console.log(`  Elements found: ${elementCount}`);

// Seiteninhalt prüfen
const pageContent = await page.content();
console.log(`  Has 'some-text': ${pageContent.includes('some-text')}`);
```

### 5. **Selektoren richtig wählen**

Priorität:
1. **Test-IDs** (am stabilsten): `data-testid="submit-button"`
2. **Semantische Selektoren**: `button[type="submit"]`
3. **CSS-Klassen**: `.always-end-box`
4. **Text-Content**: `text="Immer am Ende"` (Vorsicht bei i18n!)

**Tipp:** Prüfe die HTML-Struktur im Template:
```bash
grep -n "always-end-box" templates/Schedules/generate_report.php
```

### 6. **Headed Mode zum Debuggen**

```bash
# Test mit sichtbarem Browser ausführen
timeout 120 npx playwright test tests/e2e/your-test.spec.js --project=chromium --headed
```

So kannst du:
- Sehen was der Test tut
- Screenshots machen
- Probleme visuell identifizieren

### 7. **Timeouts richtig setzen**

```bash
# IMMER mit timeout-Befehl starten
timeout 120 npx playwright test tests/e2e/your-test.spec.js --project=chromium
```

**Faustregel:**
- Einzelner Test: `timeout 90`
- Test mit Daten-Erstellung: `timeout 120`
- Komplexer Test: `timeout 180`

## 📋 Test-Template

Verwende dieses Template für neue Tests:

```javascript
const { test, expect } = require('@playwright/test');

/**
 * TEST: [Beschreibung]
 * 
 * Tests that [was getestet wird]
 */
test.describe('[Feature Name]', () => {
    test('should [erwartetes Verhalten]', async ({ page }) => {
        console.log('🧪 Testing [Feature]...');
        
        // Step 1: Login
        console.log('📍 Step 1: Login');
        await page.goto('http://localhost:8080/users/login');
        await page.fill('input[name="email"]', 'admin@demo.kita');
        await page.fill('input[name="password"]', '84fhr38hf43iahfuX_2');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/dashboard');
        console.log('✅ Logged in');
        
        // Step 2: Create test data
        console.log('📍 Step 2: Create test data');
        // ... create schedule, child, etc.
        const id = url.match(/\/resource\/(\d+)/)?.[1];
        console.log(`✅ Created resource with ID: ${id}`);
        
        // Step 3: Navigate to feature
        console.log('📍 Step 3: Navigate to feature');
        await page.goto(`http://localhost:8080/feature/${id}`);
        await page.waitForLoadState('networkidle');
        console.log('✅ Page loaded');
        
        // Step 4: Verify expected behavior
        console.log('📍 Step 4: Verify behavior');
        const element = page.locator('.expected-element');
        await expect(element).toBeVisible({ timeout: 5000 });
        console.log('✅ Element found');
        
        // Take screenshot for verification
        console.log('📍 Step 5: Take screenshot');
        await page.screenshot({ 
            path: 'test-results/feature-verification.png',
            fullPage: true 
        });
        console.log('✅ Screenshot saved');
        
        console.log('');
        console.log('📊 SUMMARY:');
        console.log('  - Feature works: ✅');
        console.log('');
        console.log('✅ TEST PASSED!');
    });
});
```

## 🐛 Debugging-Workflow

Wenn ein Test fehlschlägt:

### 1. Test headed ausführen
```bash
timeout 120 npx playwright test tests/e2e/failing-test.spec.js --project=chromium --headed
```

### 2. Debug-Output analysieren
```javascript
// Was steht in den Logs?
console.log(`  Page title: "${pageTitle}"`);
console.log(`  Page URL: ${pageURL}`);
console.log(`  Elements found: ${elementCount}`);
```

### 3. Screenshot checken
```javascript
await page.screenshot({ 
    path: 'test-results/debug.png',
    fullPage: true 
});
```

Dann: `open test-results/debug.png`

### 4. HTML-Content prüfen
```javascript
const pageContent = await page.content();
console.log(pageContent); // Ganzes HTML
// oder
console.log(pageContent.includes('expected-text')); // Bestimmter Text vorhanden?
```

### 5. Selektor testen
```javascript
const element = page.locator('.my-selector');
const count = await element.count();
console.log(`Elements with selector: ${count}`);

if (count > 0) {
    const text = await element.first().textContent();
    console.log(`First element text: "${text}"`);
}
```

## ✅ Checklist für neue Tests

- [ ] Test erstellt basierend auf funktionierendem Template
- [ ] Eigene Test-Daten erstellen (keine hardcoded IDs)
- [ ] Debug-Output eingebaut (console.log für jeden Step)
- [ ] Richtige Wait-Strategien verwendet
- [ ] Test headed ausgeführt und visuell geprüft
- [ ] Test headless ausgeführt (wie in CI)
- [ ] Screenshot-Verifikation eingebaut
- [ ] Test ist GRÜN (alle Assertions bestehen)
- [ ] Test committed

## 🚫 Häufige Fehler

### ❌ Hardcoded IDs verwenden
```javascript
await page.goto('http://localhost:8080/schedules/view/1');
// → Schedule ID 1 existiert vielleicht nicht!
```

### ❌ Keine Waits verwenden
```javascript
await page.goto('http://localhost:8080/page');
const element = page.locator('.element');
// → Seite noch nicht geladen!
```

### ❌ Test aufgeben wenn er fehlschlägt
```javascript
// ❌ "Der Test hängt, aber PHPUnit ist OK"
// ✅ Test debuggen bis er funktioniert!
```

### ❌ Kein Debug-Output
```javascript
// Wie soll ich wissen was schiefgeht?
```

### ❌ Falsche Selektoren
```javascript
// Template hat: <div class="always-end-box">
await page.locator('h3:has-text("Immer am Ende")'); // ❌ Existiert nicht!
await page.locator('.always-end-box'); // ✅ Richtig!
```

## 📚 Weitere Ressourcen

- [Playwright Docs](https://playwright.dev/)
- [Playwright Best Practices](https://playwright.dev/docs/best-practices)
- [Playwright Selectors](https://playwright.dev/docs/selectors)

## 🎯 Erfolgsbeispiel

Der `report-always-at-end-simple.spec.js` Test zeigt alle Best Practices:

1. ✅ Erstellt eigenen Schedule
2. ✅ Extrahiert ID aus URL
3. ✅ Verwendet networkidle wait
4. ✅ Hat Debug-Output
5. ✅ Prüft HTML-Content
6. ✅ Macht Screenshot
7. ✅ Läuft zuverlässig durch

**Nutze ihn als Template für neue Tests!**
