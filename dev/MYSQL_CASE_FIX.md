# MySQL Case-Sensitive Table Names Fix

**Problem:** `Table 'ausfallplan.OrganizationUsers' doesn't exist`  
**When:** Accessing `/admin/organizations` as admin

---

## 🔍 ROOT CAUSE

**MySQL auf Linux ist case-sensitive für Tabellennamen!**

- Tabelle in DB: `organization_users` (snake_case) ✅
- Query verwendete: `OrganizationUsers` (PascalCase) ❌
- MySQL konnte Tabelle nicht finden

---

## ✅ FIX

**File:** `src/Controller/Admin/OrganizationsController.php`

### Before (❌):
```php
$organizations = $this->Organizations->find()
    ->select([
        // ...
        'user_count' => $this->Organizations->find()->func()->count('DISTINCT OrganizationUsers.user_id'),
        // ...
    ])
    ->leftJoin('OrganizationUsers', ['OrganizationUsers.organization_id = Organizations.id'])
    // ...
```

### After (✅):
```php
$organizations = $this->Organizations->find()
    ->select([
        // ...
        'user_count' => $this->Organizations->find()->func()->count('DISTINCT organization_users.user_id'),
        // ...
    ])
    ->leftJoin('organization_users', ['organization_users.organization_id = Organizations.id'])
    // ...
```

---

## 💡 LESSON LEARNED

**CakePHP Konventionen:**
- Model/Entity Namen: PascalCase (`OrganizationUsers`)
- Tabellenamen: snake_case (`organization_users`)
- **In Queries IMMER snake_case verwenden!**

**MySQL Besonderheit:**
- Windows: Case-insensitive (beide würden funktionieren)
- Linux: Case-sensitive (nur exakter Name funktioniert)
- Docker nutzt Linux → Case-sensitive!

---

## 📝 CHECKLIST FÜR ÄHNLICHE PROBLEME

Wenn "Table doesn't exist" aber Tabelle existiert:

1. ✅ Check case: `SHOW TABLES LIKE 'org%';`
2. ✅ Prüfe Query: Nutzt sie PascalCase statt snake_case?
3. ✅ Fix: Ändere zu snake_case
4. ✅ Test: Query neu ausführen

---

## 🎯 RESULT

- ✅ Admin organizations page works
- ✅ User count displays correctly
- ✅ No more MySQL table not found errors

**Status:** FIXED - MySQL table names now correctly case-sensitive! 🎉

---

**Commit:** `89fc43f` - fix: MySQL table name case in Admin OrganizationsController
