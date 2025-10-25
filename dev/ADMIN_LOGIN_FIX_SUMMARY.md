# Admin Login Fix - organization_id NULL Problem

**Problem:** System-Admins konnten sich nicht einloggen  
**Error:** `Expression 'organization_id' has invalid null value`

---

## 🔍 ROOT CAUSE

System-Admins haben `organization_id = null` in der `users` Tabelle.
Mehrere Controller griffen direkt auf `$user->organization_id` zu, was bei NULL zum Fehler führte.

---

## ✅ FIXED CONTROLLERS (5)

### 1. **DashboardController.php**
```php
// Redirect system admins to admin area
if ($user && $user->is_system_admin) {
    return $this->redirect(['controller' => 'Admin/Organizations', 'action' => 'index']);
}
```

### 2. **ChildrenController.php**
```php
// Use getPrimaryOrganization() with null check
$primaryOrg = $this->getPrimaryOrganization();
if (!$primaryOrg) {
    $this->Flash->error(__('Sie sind keiner Organisation zugeordnet.'));
    return $this->redirect(['controller' => 'Dashboard', 'action' => 'index']);
}

// Use $primaryOrg->id instead of $user->organization_id
$children = $this->Children->find()
    ->where(['Children.organization_id' => $primaryOrg->id])
```

### 3. **SiblingGroupsController.php**
```php
// Same pattern as ChildrenController
$primaryOrg = $this->getPrimaryOrganization();
// ... null check
$siblingGroups = $this->SiblingGroups->find()
    ->where(['SiblingGroups.organization_id' => $primaryOrg->id])
```

### 4. **WaitlistController.php**
```php
// Same pattern
$primaryOrg = $this->getPrimaryOrganization();
// ... null check
$schedules = $schedulesTable->find()
    ->where(['Schedules.organization_id' => $primaryOrg->id])
```

### 5. **Admin/OrganizationsController.php**
```php
// Fixed queries that used Users.organization_id or Children.organization_id
// Now uses Schedules.organization_id directly or organization_users table
$schedulesCount = $this->fetchTable('Schedules')->find()
    ->where(['Schedules.organization_id' => $id])
    ->count();
```

---

## 🎯 SOLUTION PATTERN

**Before (❌):**
```php
$children = $this->Children->find()
    ->where(['organization_id' => $user->organization_id]) // NULL für Admins!
    ->all();
```

**After (✅):**
```php
$primaryOrg = $this->getPrimaryOrganization();
if (!$primaryOrg) {
    // Handle users without organization
}

$children = $this->Children->find()
    ->where(['Children.organization_id' => $primaryOrg->id]) // Safe!
    ->all();
```

---

## 💡 WHY THIS WORKS

1. **System Admins:**
   - `organization_id = null` in users table
   - `getPrimaryOrganization()` returns null
   - Redirected to admin area or get helpful error

2. **Regular Users:**
   - Have entry in `organization_users` table
   - `getPrimaryOrganization()` returns their org
   - Queries work correctly with org ID

3. **Users Without Org:**
   - Get clear error message
   - Told to contact admin

---

## 📝 COMMITS

- `ac9d3c6` - OrganizationsController fix
- `d033d91` - All controller fixes (Dashboard, Children, SiblingGroups, Waitlist)

---

## ✅ RESULT

- ✅ Admin login no longer throws exception
- ✅ Admins redirected to admin area
- ✅ Regular users see their organization data
- ✅ Users without org get helpful error

**Status:** FIXED - Admin können sich einloggen! 🎉
