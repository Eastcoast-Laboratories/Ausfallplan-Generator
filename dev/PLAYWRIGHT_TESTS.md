# 📋 Playwright E2E Tests - Übersicht

Vollständige Dokumentation aller End-to-End Tests mit Playwright.

## Test-Dateien (19 Tests)

### 1. **active-schedule-session.spec.js**
**Funktion:** Testet dass der aktive Schedule in der Session gespeichert wird und beim Navigieren zur Waitlist verfügbar ist.

**Git Commit:** `2120c90` (2025-10-23)  
**Commit Message:** Use active schedule from session in waitlist

---

### 2. **admin-login.spec.js**
**Funktion:** Testet Admin-Login und Berechtigungsprüfungen.

**Git Commit:** `d774951` (2025-10-23)  
**Commit Message:** feat: Admin Organizations Management

---

### 3. **admin-organizations.spec.js**
**Funktion:** Testet Admin-Funktionen für Organisation-Management (erstellen, bearbeiten, löschen).

**Git Commit:** `d774951` (2025-10-23)  
**Commit Message:** feat: Admin Organizations Management

---

### 4. **children.spec.js**
**Funktion:** Testet alle CRUD-Operationen für Kinder (Create, Read, Update, Delete).

**Git Commit:** `75468a5` (2025-10-22)  
**Commit Message:** Add Playwright E2E tests for Children + fix templates

---

### 5. **dashboard-redirect.spec.js**
**Funktion:** Testet den Dashboard-Redirect-Flow nach dem Login.

**Git Commit:** `5d52b05` (2025-10-23)  
**Commit Message:** Fix dashboard redirect - use redirect query parameter

---

### 6. **features.spec.js**
**Funktion:** Testet User-Features und organisationsbasierte Berechtigungen (org_admin, editor, viewer Rollen).

**Git Commit:** `fa26f9b` (2025-10-23)  
**Commit Message:** feat: Complete user management features

**Organisation Impact:** ✅ HIGH - Tests new organization_users permission system

---

### 7. **german-translations.spec.js**
**Funktion:** Umfassende Verifizierung der deutschen Übersetzungen in allen UI-Elementen.

**Git Commit:** `5a19f1b` (2025-10-22)  
**Commit Message:** Add comprehensive German translations - ALL UI elements now translated

---

### 8. **language-hover-test.spec.js**
**Funktion:** Testet das Hover-Verhalten des Sprach-Dropdowns und Mausbewegungen.

**Git Commit:** `749ec31` (2025-10-22)  
**Commit Message:** Fix language dropdown hover gap issue

---

### 9. **language-switcher.spec.js**
**Funktion:** Testet die Sprachwechsel-Funktionalität zwischen Deutsch und Englisch.

**Git Commit:** `5787796` (2025-10-22)  
**Commit Message:** Add Playwright E2E tests for language switcher (6/6 ✅)

---

### 10. **language-switching.spec.js**
**Funktion:** Umfassender Test zur Verifizierung des Sprachwechsels in der gesamten Anwendung.

**Git Commit:** `6936d96` (2025-10-23)  
**Commit Message:** Fix I18n language selection

---

### 11. **login-demo.spec.js**
**Funktion:** Demo-Login-Test mit Slow-Motion und sichtbarem Browser (für Demonstrationszwecke).

**Git Commit:** `8c6970d` (2025-10-23)  
**Commit Message:** Fix CreateAdminCommand - remove email_verified_at

---

### 12. **navigation.spec.js**
**Funktion:** Testet die Sichtbarkeit und Responsiveness der Navigation.

**Git Commit:** `e454542` (2025-10-22)  
**Commit Message:** Add Playwright E2E tests + PHPUnit registration tests

---

### 13. **profile.spec.js**
**Funktion:** Testet User-Profil-Management-Features (Ansehen, Bearbeiten).

**Git Commit:** `fa26f9b` (2025-10-23)  
**Commit Message:** feat: Complete user management features

---

### 14. **registration-login.spec.js** 🔥 ERWEITERT!
**Funktion:** Testet den kompletten User-Registrierungs- und Login-Flow mit ALLEN Szenarien.

**8 umfassende Tests:**
1. ✅ Neue Organisation erstellen → User wird org_admin
2. ✅ Bestehender Organisation als VIEWER beitreten
3. ✅ Bestehender Organisation als EDITOR beitreten  
4. ✅ ORG_ADMIN-Rolle anfordern (erfordert Genehmigung)
5. ✅ Ohne Organisation registrieren ("keine organisation")
6. ✅ Unterschiedliche Success-Messages je nach Szenario
7. ✅ Login mit ungültigen Credentials
8. ✅ Pflichtfeld-Validierung

**Organisation Impact:** ✅ CRITICAL - Testet komplettes organization_users System mit Rollen und Benachrichtigungen

**Git Commit:** `6d9fd06` (2025-10-25)  
**Commit Message:** test: Comprehensive registration tests for all scenarios  
**Original Commit:** `94fa417` (2025-10-22)

---

### 15. **report-generation.spec.js**
**Funktion:** Testet die Report-Generierung aus Schedules (PDF-Export).

**Git Commit:** `876868c` (2025-10-22)  
**Commit Message:** Implement complete Ausfallplan report generation with tests

---

### 16. **report-stats.spec.js**
**Funktion:** Testet dass Report-Statistik-Spalten (Z, D, ⬇️) korrekt angezeigt werden.

**Git Commit:** `7527b7a` (2025-10-23)  
**Commit Message:** ✅ Fix report statistics - D and ⬇️ columns now working!

---

### 17. **schedules.spec.js**
**Funktion:** Testet Schedule CRUD-Operationen (Dienstplan erstellen, anzeigen, bearbeiten, löschen).

**Git Commit:** `608e2cd` (2025-10-22)  
**Commit Message:** Add Playwright E2E tests for Schedule CRUD (2/6 passing)

---

### 18. **translations.spec.js**
**Funktion:** Testet die Anzeige deutscher Übersetzungen in der Anwendung.

**Git Commit:** `d209030` (2025-10-22)  
**Commit Message:** Implement German translations with LocaleMiddleware

---

### 19. **waitlist-add-all.spec.js**
**Funktion:** Testet die 'Add All Children' Button-Funktionalität in der Waitlist.

**Git Commit:** `2920978` (2025-10-23)  
**Commit Message:** Add Playwright test for 'Add All Children' button

---

## Test-Kategorien

### 🔐 **Authentication & Authorization** (5 Tests)
- `admin-login.spec.js` - Admin-Zugang
- `features.spec.js` - Rollen-basierte Berechtigungen
- `login-demo.spec.js` - Login-Demo
- `profile.spec.js` - User-Profil
- `registration-login.spec.js` - Registrierung & Login

### 👥 **User & Organization Management** (3 Tests)
- `admin-organizations.spec.js` - Organisation-Verwaltung
- `children.spec.js` - Kinder-Verwaltung
- `features.spec.js` - Organisation-User-Permissions

### 📅 **Schedule & Waitlist** (4 Tests)
- `schedules.spec.js` - Dienstplan-Verwaltung
- `waitlist-add-all.spec.js` - Waitlist-Funktionen
- `active-schedule-session.spec.js` - Session-Management
- `report-generation.spec.js` - Report-Erstellung

### 📊 **Reports** (2 Tests)
- `report-generation.spec.js` - PDF-Generierung
- `report-stats.spec.js` - Statistik-Spalten

### 🌐 **Internationalization** (5 Tests)
- `german-translations.spec.js` - Deutsche Übersetzungen
- `language-hover-test.spec.js` - Dropdown-Interaktion
- `language-switcher.spec.js` - Sprachwechsel
- `language-switching.spec.js` - Umfassender Sprach-Test
- `translations.spec.js` - Übersetzungs-Display

### 🧭 **Navigation & UI** (2 Tests)
- `navigation.spec.js` - Navigation-Sichtbarkeit
- `dashboard-redirect.spec.js` - Dashboard-Redirect

---

## Wichtige Meilensteine

### **Organization-Users System (2025-10-23/24/25)**
Das neue Many-to-Many Organization-User System wurde umfassend getestet:
- `features.spec.js` - Testet org_admin, editor, viewer Rollen
- `admin-organizations.spec.js` - Testet Organization Management
- Ermöglicht Users Mitglied in mehreren Organisationen zu sein

### **Translations (2025-10-22)**
Vollständige Implementierung und Testing der Mehrsprachigkeit:
- Alle UI-Elemente übersetzt
- Sprachwechsel funktioniert seamless
- Hover-Behavior korrekt

### **Reports (2025-10-22/23)**
Complete Report-System mit Tests:
- PDF-Generierung
- Statistik-Berechnungen (Zählkinder, etc.)
- Korrekte Spalten-Anzeige

---

## Test-Ausführung

```bash
# Alle Tests ausführen
npx playwright test

# Einzelner Test
npx playwright test tests/e2e/features.spec.js

# Mit UI-Modus
npx playwright test --ui

# Mit Browser sichtbar
npx playwright test --headed

# Nur bestimmte Kategorie (regex)
npx playwright test --grep "admin"
```

---

**Erstellt:** 2025-10-25  
**Letzte Aktualisierung:** 2025-10-25  
**Gesamtanzahl Tests:** 19
