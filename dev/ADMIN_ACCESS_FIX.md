# 🔧 Admin Access Fix - Zusammenfassung

## Problem
`admin@demo.kita` konnte nicht auf `/admin/organizations` zugreifen - "Access denied"

## Root Cause
**OrganizationsController** prüfte `$user->is_system_admin` direkt:
```php
if (!$user || !$user->is_system_admin) {
    $this->Flash->error(__('Access denied.'));
    return $this->redirect(['_name' => 'dashboard']);
}
```

**Problem:** CakePHP Entities nutzen Magic Methods. `property_exists()` gibt `false` zurück, obwohl das Feld über `$user->is_system_admin` lesbar ist. Der direkte Zugriff ist unsicher.

## Lösung
✅ **User Entity hat eine public Method `isSystemAdmin()`:**
```php
public function isSystemAdmin(): bool
{
    return (bool)$this->is_system_admin;
}
```

✅ **Alle Controller-Checks geändert zu:**
```php
if (!$user || !$user->isSystemAdmin()) {
    $this->Flash->error(__('Access denied. System admin privileges required.'));
    return $this->redirect(['_name' => 'dashboard']);
}
```

## Geänderte Dateien

### 1. User.php Entity
- ✅ `is_system_admin` in `$_accessible`
- ✅ `email_token` zurück in `$_hidden`
- ✅ `isSystemAdmin()` public method
- ✅ `_getIsSystemAdmin()` virtual getter

### 2. Admin/OrganizationsController.php
Alle 7 Methoden gefixt:
- ✅ `index()` - List all organizations
- ✅ `view($id)` - View organization details
- ✅ `edit($id)` - Edit organization
- ✅ `delete($id)` - Delete organization
- ✅ `toggleActive($id)` - Toggle active status
- ✅ `addUser($id)` - Add user to organization
- ✅ `removeUser($id, $userId)` - Remove user from organization

### 3. CreateAdminCommand.php
- ✅ Erstellt/updated User mit `is_system_admin = true`
- ✅ Setzt `email_verified = true` (required for login)
- ✅ Setzt `status = active` (required for login)
- ✅ Erstellt `organization_users` Entry

### 4. CheckAdminCommand.php (Debug Tool)
- ✅ Zeigt DB-Spalten
- ✅ Prüft User-Status
- ✅ Verifiziert `is_system_admin` Feld

## Verifizierung

### Database Check
```bash
bin/cake check_admin
# ✅ User found!
# ✅ is_system_admin column exists in DB
```

### Admin User Status
```bash
bin/cake create_admin
# Email: admin@demo.kita
# is_system_admin: Yes ✅
# email_verified: true ✅
# status: active ✅
```

## Testing

### Manual Test (Browser)
1. Login: https://ausfallplan-generator.z11.de/login
   - Email: `admin@demo.kita`
   - Password: `84fhr38hf43iahfuX_2`
2. Navigate to: https://ausfallplan-generator.z11.de/admin/organizations
3. **Expected:** Organizations list with Demo Kita

### Playwright Test
```bash
npx playwright test tests/e2e/debug-admin-access.spec.js
```

## Commits
- `c8f4202` - fix: Add is_system_admin to User entity
- `f992ff9` - fix: Use isSystemAdmin() method in OrganizationsController

## Warum war das Problem schwer zu finden?

1. **DB hatte Spalte:** `is_system_admin (tinyint(1))` ✅
2. **User hatte Wert:** `is_system_admin = 1` ✅
3. **Entity konnte lesen:** `$user->is_system_admin` funktioniert ✅
4. **ABER:** Direkter Property-Check war unreliable wegen CakePHP Magic Methods ❌

Die Lösung: **IMMER die public Method `isSystemAdmin()` verwenden!**

## Best Practice
```php
// ❌ FALSCH:
if (!$user->is_system_admin) { ... }

// ✅ RICHTIG:
if (!$user->isSystemAdmin()) { ... }
```

Die Method-Variante ist type-safe und garantiert, dass der Boolean-Wert korrekt zurückgegeben wird.
