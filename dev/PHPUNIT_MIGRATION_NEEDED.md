# 🔥 PHPUnit Tests - Migration Required

## Problem
**ALLE PHPUnit Tests sind kaputt** wegen der organization_users Migration!

Die Tests nutzen noch:
```php
$user = $usersTable->newEntity([
    'organization_id' => 1,  // ❌ Existiert nicht mehr!
    'role' => 'admin',       // ❌ Existiert nicht mehr!
    'email' => 'test@example.com',
    'password' => 'password123',
]);
```

## Was geändert wurde
### ALT (vor Migration):
- `users.organization_id` → Direkte Spalte
- `users.role` → 'admin', 'editor', 'viewer'

### NEU (nach Migration):
- `organization_users` JOIN TABLE
  - `organization_id`
  - `user_id`
  - `role` → 'org_admin', 'editor', 'viewer'
  - `is_primary`
- `users.is_system_admin` → Boolean für System-Admin

## Betroffene Test-Dateien

### 1. AuthenticationFlowTest.php
**Problem:**
- Zeile 34: `'role' => 'viewer'` → Sollte `'requested_role' => 'viewer'`
- Zeile 60: `'organization_id' => 1` → Muss entfernt werden
- Zeile 63: `'role' => 'admin'` → Muss entfernt werden
- Zeile 92-98: Alte Felder
- Zeile 103-110: Alte Felder
- Alle User-Erstellungen nutzen alte Struktur

**Fix:** 
- `organization_name` in Registration
- `requested_role` statt `role`
- Nach User-Save: organization_users Entry erstellen

### 2. PermissionsTest.php
**Problem:**
- Prüft wahrscheinlich `$user->role`
- Nutzt organization_id direkt

**Fix:**
- Prüfe `organization_users.role`
- Nutze `hasOrgRole()` Helper

### 3. RegistrationNavigationTest.php
**Problem:**
- Test für alte Registration mit `role` Feld

**Fix:**
- Teste `requested_role` Feld
- Teste dass organization_users Entry erstellt wird
- Teste Notification an org-admins

### 4. SchedulesControllerPermissionsTest.php
**Problem:**
- Access Control basiert auf alten roles

**Fix:**
- Teste mit organization_users roles
- Teste mit is_system_admin

### 5. OrganizationUsersTableTest.php
**Status:** Neu, muss erst erstellt werden!

**Braucht:**
- Test: User kann mehreren Orgs beitreten
- Test: Role hierarchy (org_admin > editor > viewer)
- Test: Primary organization
- Test: Invited_by tracking

### 6. Alle anderen Tests
**Problem:**
- Nutzen wahrscheinlich alte User-Struktur in Fixtures

## Fix-Strategy

### Schritt 1: Fixtures erweitern
Alle Test-Klassen brauchen:
```php
protected array $fixtures = [
    'app.Users',
    'app.Organizations',
    'app.OrganizationUsers',  // ← NEU!
    // ... rest
];
```

### Schritt 2: User-Erstellung anpassen
**ALT:**
```php
$user = $usersTable->newEntity([
    'organization_id' => 1,
    'role' => 'admin',
    'email' => 'test@example.com',
    'password' => 'password123',
]);
$usersTable->save($user);
```

**NEU:**
```php
// 1. Create user WITHOUT organization_id and role
$user = $usersTable->newEntity([
    'email' => 'test@example.com',
    'password' => 'password123',
    'status' => 'active',
    'email_verified' => true,
]);
$usersTable->save($user);

// 2. Create organization_users entry
$orgUsersTable = $this->getTableLocator()->get('OrganizationUsers');
$orgUser = $orgUsersTable->newEntity([
    'organization_id' => 1,
    'user_id' => $user->id,
    'role' => 'org_admin',
    'is_primary' => true,
    'joined_at' => new \DateTime(),
]);
$orgUsersTable->save($orgUser);
```

### Schritt 3: System-Admin erstellen
```php
$admin = $usersTable->newEntity([
    'email' => 'admin@test.com',
    'password' => 'password123',
    'is_system_admin' => true,  // ← System-wide admin
    'status' => 'active',
    'email_verified' => true,
]);
$usersTable->save($admin);
```

### Schritt 4: Permission-Checks anpassen
**ALT:**
```php
if ($user->role !== 'admin') { ... }
```

**NEU:**
```php
// System admin check
if (!$user->isSystemAdmin()) { ... }

// Org role check
if (!$user->hasOrgRole($organizationId, 'org_admin')) { ... }
```

## Schritt 5: Registration-Tests anpassen
**ALT:**
```php
$this->post('/users/register', [
    'organization_name' => 'Test Kita',
    'email' => 'test@example.com',
    'password' => 'password123',
    'role' => 'viewer',  // ← ALT
]);
```

**NEU:**
```php
$this->post('/users/register', [
    'organization_name' => 'Test Kita',
    'email' => 'test@example.com',
    'password' => 'password123',
    'password_confirm' => 'password123',  // ← NEU
    'requested_role' => 'viewer',  // ← NEU
]);

// Check organization_users entry was created
$orgUsersTable = $this->getTableLocator()->get('OrganizationUsers');
$orgUser = $orgUsersTable->find()
    ->where(['user_id' => $user->id])
    ->first();
    
$this->assertNotNull($orgUser);
$this->assertEquals('viewer', $orgUser->role);
```

## Priorität

### ⚠️ KRITISCH - Müssen sofort gefixt werden:
1. **AuthenticationFlowTest.php** - Login/Logout/Verification
2. **PermissionsTest.php** - Access Control
3. **RegistrationNavigationTest.php** - User Registration

### 📋 WICHTIG - Bald fixen:
4. **SchedulesControllerPermissionsTest.php** - Authorization
5. **ChildrenControllerTest.php** - Org-specific data
6. **SiblingGroupsControllerTest.php** - Org-specific data

### ✅ OPTIONAL - Später:
7. **PagesControllerTest.php** - Statische Seiten
8. **ApplicationTest.php** - Basic app test

## Zusammenfassung
**Status:** ❌ ALLE Tests sind kaputt wegen organization_users Migration
**Aufwand:** ~2-4 Stunden alle Tests zu fixen
**Priorität:** HOCH - Tests sind wichtig für Regression-Schutz

**Next Steps:**
1. Erstelle OrganizationUsersFixture
2. Fixe AuthenticationFlowTest
3. Fixe PermissionsTest
4. Fixe RegistrationNavigationTest
5. Dann rest

Ohne funktionierende Tests können wir nicht sicher sein, dass die Migration keine Bugs eingeführt hat!
