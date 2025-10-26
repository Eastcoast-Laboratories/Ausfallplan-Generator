# PHPUnit Tests - Verbleibende Arbeit

**Stand:** 26.10.2025, 13:10 Uhr  
**Commit:** 2cf56b4

## Aktueller Status

```
Tests: 104, Assertions: 211, Errors: 11, Failures: 56
= 67 Tests fehlgeschlagen (von initial 61)
```

**PROGRESS seit letzter Session:**
- ✅ ChildrenControllerTest: 9 Failures → 2 Failures (7 FIXED!)
- ✅ Session-Format Bug identifiziert und gefixt
- **Root Cause:** `$this->session(['Auth' => ['User' => $user]])` → `$this->session(['Auth' => $user])`

## Bereits Behoben ✅

### Kritische Fixes (Session 1-14)
1. **email_verified Type Mismatch** - INT statt BOOLEAN (Batch-Fix in allen Tests)
2. **SQL Integrity Constraint Violations** - email_verified NULL errors
3. **OrganizationUsers Fixtures** - Alle Test-Dateien ergänzt
4. **User Schema Migration** - Teilweise abgeschlossen:
   - ✅ AuthenticationFlowTest (teilweise)
   - ✅ OrganizationsControllerTest (komplett)
   - ✅ SchedulesAccessTest (komplett)
   - ✅ SchedulesControllerCapacityTest (komplett)
   - ✅ AuthenticatedLayoutTest (teilweise)

### Commits
```
510b94d - email_verified Batch-Fix (INT)
a226dcb - API Organizations (2/2 passing)
f5101b8 - User Verification Logic
bde1a84 - Flash Messages & SQL Syntax
153697a - OrganizationUsers fixtures
7446522 - SchedulesAccessTest user schema
e3d7d64 - SchedulesControllerCapacityTest user schema
```

## Root-Cause Analysis

### Hauptproblem: User-Schema-Migration nicht abgeschlossen

**Alt (vor Migration):**
```php
$user = $usersTable->newEntity([
    'organization_id' => 1,
    'email' => 'test@test.com',
    'password' => 'password123',
    'role' => 'admin',  // oder 'editor', 'viewer'
]);
```

**Neu (aktuelles Schema):**
```php
$user = $usersTable->newEntity([
    'email' => 'test@test.com',
    'password' => 'password123',
    'is_system_admin' => false,
    'email_verified' => 1,
    'status' => 'active',
]);

// Separat: Organization-User-Zuordnung
$orgUsers = $this->getTableLocator()->get('OrganizationUsers');
$orgUsers->save($orgUsers->newEntity([
    'user_id' => $user->id,
    'organization_id' => 1,
    'role' => 'org_admin', // oder 'editor', 'viewer'
    'is_primary' => true,
    'joined_at' => new \DateTime(),
]));
```

**Session-Format Alt:**
```php
$this->session(['Auth' => $user]); // Ganzes Entity
// oder
$this->session(['Auth' => [
    'id' => $user->id,
    'email' => $user->email,
    'role' => 'admin',
    'organization_id' => 1,
]]);
```

**Session-Format Neu:**
```php
$this->session(['Auth' => [
    'id' => $user->id,
    'email' => $user->email,
    'is_system_admin' => false,
]]);
```

## Verbleibende Test-Dateien (nach Kategorie)

### Controller Tests (Höchste Priorität)

**ChildrenControllerTest** - 8 Tests fehlgeschlagen
- `tests/TestCase/Controller/ChildrenControllerTest.php`
- Fixtures: ✅ OrganizationUsers bereits hinzugefügt
- Problem: User-Creation Pattern + Session Format
- Geschätzte Zeit: 15 Min

**SchedulesControllerTest** - ~5 Tests
- `tests/TestCase/Controller/SchedulesControllerTest.php`
- Problem: User-Creation + Session
- Geschätzte Zeit: 10 Min

**SiblingGroupsControllerTest** - ~3 Tests
- `tests/TestCase/Controller/SiblingGroupsControllerTest.php`
- Problem: User-Creation + Session
- Geschätzte Zeit: 10 Min

**UsersControllerTest** - ~4 Tests
- `tests/TestCase/Controller/UsersControllerTest.php`
- Problem: User-Creation + Session
- Geschätzte Zeit: 10 Min

**RegistrationNavigationTest** - 2 Tests
- `tests/TestCase/Controller/RegistrationNavigationTest.php`
- Problem: User-Creation + Session
- Geschätzte Zeit: 5 Min

### Permission/Authorization Tests

**SchedulesControllerPermissionsTest** - ~5 Tests
- `tests/TestCase/Controller/SchedulesControllerPermissionsTest.php`
- Problem: Komplexe Permissions mit neuem Schema
- Geschätzte Zeit: 15 Min

### View/Layout Tests

**AuthenticatedLayoutTest** - 4 Tests
- `tests/TestCase/View/AuthenticatedLayoutTest.php`
- Status: Teilweise gefixt, aber noch Fehler
- Problem: Session + Response Assertions
- Geschätzte Zeit: 10 Min

**NavigationVisibilityTest** - 2 Tests
- `tests/TestCase/Integration/NavigationVisibilityTest.php`
- Problem: User-Creation + Session
- Geschätzte Zeit: 5 Min

### Service Tests

**WaitlistServiceTest** - mehrere Tests
- `tests/TestCase/Service/WaitlistServiceTest.php`
- Problem: Komplexe Business-Logic + User-Dependencies
- Geschätzte Zeit: 20 Min

**ScheduleBuilderTest** - ~5 Tests
- `tests/TestCase/Service/ScheduleBuilderTest.php`
- Problem: User-Creation + Organization Dependencies
- Geschätzte Zeit: 15 Min

**ReportServiceTest** - ~3 Tests
- `tests/TestCase/Service/ReportServiceTest.php`
- Problem: User-Creation Dependencies
- Geschätzte Zeit: 10 Min

## Systematischer Fix-Ansatz

### Schritt 1: Grep-Pattern für betroffene Zeilen
```bash
# Finde alle verbleibenden organization_id User-Creations
grep -rn "'organization_id'" tests/TestCase/Controller/*.php tests/TestCase/Service/*.php
grep -rn "'role' =>" tests/TestCase/Controller/*.php tests/TestCase/Service/*.php
```

### Schritt 2: Batch-Replacement Pattern (Vorsicht!)
```bash
# NICHT automatisch ausführen! Nur als Vorlage:
# 1. Remove organization_id from user entity
# 2. Remove role from user entity  
# 3. Add is_system_admin, email_verified, status
# 4. Add OrganizationUsers creation after user save
# 5. Update session format
```

### Schritt 3: Test für Test durchgehen
Für jeden Test:
1. User-Creation Pattern updaten
2. OrganizationUsers Entry hinzufügen
3. Session-Format anpassen
4. Test ausführen
5. Bei Failures: Debug und spezifischen Fix

## Bekannte Probleme & Lösungen

### Problem 1: "Failed asserting that 302 is between 200 and 204"
**Ursache:** Authentifizierung schlägt fehl, Redirect zu /users/login  
**Lösung:** Session-Format prüfen, User muss `email_verified=1` und `status='active'` haben

### Problem 2: "Failed asserting that 'X' is in response body"
**Ursache:** User hat keine Rechte oder kein Organization-Zugriff  
**Lösung:** OrganizationUsers Entry mit korrekter `role` erstellen

### Problem 3: "Integrity constraint violation: organization_id cannot be null"
**Ursache:** Schedule/Child wird ohne organization_id erstellt  
**Lösung:** organization_id aus User's OrganizationUser-Eintrag holen

### Problem 4: Password Reset Tests
**Ursache:** whereNull() SQL Syntax  
**Status:** BEHOBEN in e3d7d64  
**Lösung:** `.whereNull('used_at')` statt `'used_at IS' => null`

## Deprecation Warnings

### loadIdentifier() Warning
```
Since 3.3.0: loadIdentifier() usage is deprecated. 
Directly pass 'identifier' config to the Authenticator.
Location: src/Application.php, line 158
```

**Status:** NICHT BEHOBEN (würde Tests brechen)  
**Grund:** Änderung führt zu 500-Errors in allen Tests  
**TODO:** Nach allen Test-Fixes nochmal versuchen mit besserem Testing

## Geschätzte Gesamt-Zeit bis 0 Fehler

- Controller Tests: ~1 Stunde
- Service Tests: ~45 Min
- View Tests: ~15 Min
- Debugging & Fixes: ~30 Min
- **Gesamt: ~2.5 Stunden**

## Nächste Schritte (Empfohlen)

1. **ChildrenControllerTest** komplett fixen (höchste Prio, 8 Tests)
2. **SchedulesControllerTest** fixen
3. **Service Tests** (WaitlistServiceTest, ScheduleBuilderTest)
4. **View Tests** (AuthenticatedLayoutTest, NavigationVisibilityTest)
5. **Finale Verification:** `docker exec ausfallplan-generator vendor/bin/phpunit`
6. **Commit:** "fix: All PHPUnit tests passing - user schema migration complete"

## Hilfreiche Befehle

```bash
# Alle Tests ausführen
docker exec ausfallplan-generator vendor/bin/phpunit --testdox

# Nur eine Test-Datei
docker exec ausfallplan-generator vendor/bin/phpunit tests/TestCase/Controller/ChildrenControllerTest.php --testdox

# Nur ein spezifischer Test
docker exec ausfallplan-generator vendor/bin/phpunit tests/TestCase/Controller/ChildrenControllerTest.php --filter testIndex

# Status-Überblick
docker exec ausfallplan-generator vendor/bin/phpunit --testdox --no-output 2>&1 | tail -5

# Fehlgeschlagene Tests finden
docker exec ausfallplan-generator vendor/bin/phpunit --testdox 2>&1 | grep "✘"
```

## Code-Template für Test-Fixes

```php
// Typisches User-Setup in Tests (NEU)
$users = $this->getTableLocator()->get('Users');
$user = $users->newEntity([
    'email' => 'test@test.com',
    'password' => 'password123',
    'is_system_admin' => false,  // true für Admins
    'email_verified' => 1,
    'status' => 'active',
]);
$users->save($user);

// Organization-Zuordnung
$orgUsers = $this->getTableLocator()->get('OrganizationUsers');
$orgUsers->save($orgUsers->newEntity([
    'user_id' => $user->id,
    'organization_id' => 1,  // oder aus $org->id
    'role' => 'editor',  // 'viewer', 'editor', 'org_admin'
    'is_primary' => true,
    'joined_at' => new \DateTime(),
]));

// Session setzen
$this->session(['Auth' => [
    'id' => $user->id,
    'email' => $user->email,
    'is_system_admin' => false,
]]);

// Für System-Admins
$this->session(['Auth' => [
    'id' => $admin->id,
    'email' => $admin->email,
    'is_system_admin' => true,
]]);
```

## Wichtige Erinnerungen

- ❗ **IMMER** `email_verified => 1` und `status => 'active'` setzen
- ❗ **NIEMALS** `organization_id` oder `role` direkt in Users-Table
- ❗ **IMMER** OrganizationUsers-Eintrag nach User-Creation
- ❗ Session-Format: Nur `id`, `email`, `is_system_admin`
- ❗ Bei Schedule/Child-Creation: `organization_id` aus OrganizationUser holen

## Kontakt/Fragen

Bei Fragen oder Problemen:
- Siehe Checkpoint-Summary in vorherigen Chat-Sessions
- Commit-History: `git log --oneline -20`
- Diese Datei updaten bei neuen Erkenntnissen!

---

**Good Luck! 🚀**
