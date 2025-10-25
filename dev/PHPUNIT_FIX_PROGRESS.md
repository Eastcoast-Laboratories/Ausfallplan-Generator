# PHPUnit Tests Fix Progress

## Status: IN PROGRESS ⚙️

### Problem
ALL 20 PHPUnit tests broken after organization_users migration:
- Users table no longer has `organization_id` and `role`
- New `organization_users` join table
- New `is_system_admin` flag

## ✅ COMPLETED

### 1. Fixtures Fixed
- ✅ **UsersFixture.php** - Removed old fields, added new fields
  - Removed: `organization_id`, `role`
  - Added: `is_system_admin`, `status`, `email_verified`, `email_token`, `approved_at`, `approved_by`
  - User 1: System admin (`is_system_admin = true`)
  - Users 2-4: Regular users

- ✅ **OrganizationUsersFixture.php** - Extended with all users
  - User 1: org_admin in org 1
  - User 2: editor in org 1
  - User 3: viewer in org 1
  - User 4: editor in org 2

### 2. Tests Fixed

#### ✅ AuthenticationFlowTest.php (8 tests)
- **Status:** COMPLETE ✅
- Added `OrganizationUsers` to fixtures
- All user creation updated:
  - Create User WITHOUT organization_id/role
  - Create OrganizationUsers entry separately
- Registration updated: `role` → `requested_role`, added `password_confirm`
- **Tests:**
  1. ✅ testRegistrationCreatesPendingUser
  2. ✅ testEmailVerificationActivatesFirstUser
  3. ✅ testEmailVerificationSetsPendingForSecondUser
  4. ✅ testLoginBlocksUnverifiedEmail
  5. ✅ testLoginBlocksPendingStatus
  6. ✅ testPasswordResetCreatesEntry
  7. ✅ testPasswordResetWithValidCode
  8. ✅ (last password test)

## 🔄 REMAINING (19 tests)

### Priority 1 - CRITICAL
These tests access user roles/permissions:

#### 📋 PermissionsTest.php
- **Issue:** Checks `$user->role` directly
- **Fix:** Use `$user->isSystemAdmin()` or `organization_users.role`
- **Status:** TODO

#### 📋 SchedulesControllerPermissionsTest.php
- **Issue:** Role-based access control
- **Fix:** Check organization_users roles
- **Status:** TODO

#### 📋 Admin/SchedulesAccessTest.php
- **Issue:** Admin role checks
- **Fix:** Use `is_system_admin`
- **Status:** TODO

### Priority 2 - IMPORTANT
Tests that create users:

#### 📋 RegistrationNavigationTest.php
- **Issue:** Uses old registration with `role`
- **Fix:** Use `requested_role`, test organization_users creation
- **Status:** TODO

#### 📋 UsersControllerTest.php
- **Issue:** User CRUD with old structure
- **Fix:** Update user creation/update
- **Status:** TODO

#### 📋 ChildrenControllerTest.php
- **Issue:** Org-specific data access
- **Fix:** Ensure user has organization_users entry
- **Status:** TODO

#### 📋 SiblingGroupsControllerTest.php
- **Issue:** Org-specific data
- **Fix:** Organization membership
- **Status:** TODO

#### 📋 SchedulesControllerTest.php
- **Issue:** Org-specific schedules
- **Fix:** Organization membership
- **Status:** TODO

#### 📋 SchedulesControllerCapacityTest.php
- **Issue:** Schedule capacity calculations
- **Fix:** Organization membership
- **Status:** TODO

### Priority 3 - MEDIUM
Model/Service tests:

#### 📋 OrganizationUsersTableTest.php
- **Issue:** Join table tests
- **Fix:** Test multi-org membership, roles, primary org
- **Status:** TODO (might be NEW file to create)

#### 📋 NavigationVisibilityTest.php
- **Issue:** Menu visibility based on role
- **Fix:** Check organization_users roles
- **Status:** TODO

#### 📋 AuthenticatedLayoutTest.php
- **Issue:** Layout for authenticated users
- **Fix:** Ensure user has org membership
- **Status:** TODO

### Priority 4 - LOW
API and Service tests (likely less affected):

#### 📋 ApplicationTest.php
- **Issue:** Basic app test
- **Fix:** Minimal changes
- **Status:** TODO

#### 📋 PagesControllerTest.php
- **Issue:** Static pages
- **Fix:** Minimal changes
- **Status:** TODO

#### 📋 Api/OrganizationsControllerTest.php
- **Issue:** API endpoints
- **Fix:** Admin checks
- **Status:** TODO

#### 📋 ReportServiceTest.php
- **Issue:** Report generation
- **Fix:** Org context
- **Status:** TODO

#### 📋 RulesServiceTest.php
- **Issue:** Rules logic
- **Fix:** Minimal
- **Status:** TODO

#### 📋 ScheduleBuilderTest.php
- **Issue:** Schedule builder
- **Fix:** Org context
- **Status:** TODO

#### 📋 WaitlistServiceTest.php
- **Issue:** Waitlist logic
- **Fix:** Org context
- **Status:** TODO

## Pattern for Fixing Tests

### Old Pattern (BROKEN):
```php
$user = $usersTable->newEntity([
    'organization_id' => 1,  // ❌ No longer exists
    'role' => 'admin',       // ❌ No longer exists
    'email' => 'test@example.com',
    'password' => 'password123',
]);
$usersTable->save($user);
```

### New Pattern (CORRECT):
```php
// 1. Create user
$user = $usersTable->newEntity([
    'email' => 'test@example.com',
    'password' => 'password123',
    'is_system_admin' => false,  // or true for system admin
    'status' => 'active',
    'email_verified' => true,
]);
$usersTable->save($user);

// 2. Create organization membership
$orgUsersTable = $this->getTableLocator()->get('OrganizationUsers');
$orgUser = $orgUsersTable->newEntity([
    'organization_id' => 1,
    'user_id' => $user->id,
    'role' => 'org_admin',  // or 'editor', 'viewer'
    'is_primary' => true,
    'joined_at' => new \DateTime(),
]);
$orgUsersTable->save($orgUser);
```

### System Admin Pattern:
```php
$admin = $usersTable->newEntity([
    'email' => 'admin@example.com',
    'password' => 'password123',
    'is_system_admin' => true,  // ← System-wide admin
    'status' => 'active',
    'email_verified' => true,
]);
$usersTable->save($admin);
// No organization_users entry needed for system admin!
```

## Summary
- **Total:** 20 tests
- **Fixed:** 1 test file (8 tests) ✅
- **Remaining:** 19 test files 📋
- **Estimated Time:** 1-2 hours for remaining tests

## Next Steps
1. Fix PermissionsTest (role checks)
2. Fix SchedulesControllerPermissionsTest (access control)
3. Fix RegistrationNavigationTest (new registration)
4. Fix remaining controller tests
5. Fix service/model tests
6. Run full test suite on server with MySQL

## Database Setup Required
Test DB needs to be created by MySQL root:
```sql
CREATE DATABASE ausfallplan_generator_test CHARACTER SET utf8mb4;
GRANT ALL PRIVILEGES ON ausfallplan_generator_test.* TO 'ausfallplan_generator'@'localhost';
```

Once DB is ready, tests can be run with:
```bash
vendor/bin/phpunit --testdox
```
