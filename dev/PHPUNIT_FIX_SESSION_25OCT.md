# PHPUnit Fix Session - 25.10.2025, 09:18 Uhr

## Initial Status
```
Tests: 104, Assertions: 188, Errors: 31, Failures: 28
Success Rate: 43% (45 passed, 59 failed)
```

## Hauptprobleme identifiziert:

### 1. **Missing PasswordResets Fixture** (7+ Tests)
- AuthenticationFlowTest: Alle Tests brechen ab
- `UnexpectedValueException: Could not find fixture app.PasswordResets`

### 2. **organization_users Migration** (30+ Tests)
- ChildrenController Tests
- SiblingGroupsController Tests  
- PermissionsTest
- RegistrationNavigationTest
- SchedulesController Tests
- OrganizationUsers API Tests

### 3. **Navigation/Layout Tests** (5+ Tests)
- `/users/logout` nicht in Response
- AuthenticatedLayoutTest
- NavigationVisibilityTest

### 4. **Deprecated API** (1 Warning)
- `SelectQuery::group()` → `groupBy()` in ReportService.php

### 5. **Capacity/Waitlist Logic** (10+ Tests)
- Apply to schedule
- Respects capacity
- Integrative weight

---

## Fix Strategy:

### Phase 1: Fixtures & Foundation
1. ✅ Test-DB erstellt
2. ⏳ PasswordResets Fixture erstellen
3. ⏳ Tests auf organization_users Migration anpassen

### Phase 2: Code Fixes
4. ⏳ Deprecated API fixen (ReportService)
5. ⏳ Navigation/Logout Route prüfen

### Phase 3: Logic Tests
6. ⏳ Waitlist/Capacity Logic debuggen

---

## Zu fixende Test-Dateien (Priorität):

**Critical (Blocker):**
1. `AuthenticationFlowTest.php` - Fixture fehlt
2. `ChildrenControllerTest.php` - org_users
3. `SiblingGroupsControllerTest.php` - org_users

**High:**
4. `PermissionsTest.php` - org_users ✅ (bereits gefixt)
5. `RegistrationNavigationTest.php` - org_users ✅ (bereits gefixt)
6. `SchedulesControllerPermissionsTest.php` - org_users ✅ (bereits gefixt)
7. `AuthenticatedLayoutTest.php` - Navigation
8. `NavigationVisibilityTest.php` - Navigation

**Medium:**
9. `OrganizationUsersTableTest.php` - org_users API
10. `SchedulesControllerTest.php` - org_users
11. `SchedulesControllerCapacityTest.php` - Capacity logic
12. `UsersControllerTest.php` - Profile/Password

**Low:**
13. `WaitlistServiceTest.php` - Capacity
14. `ScheduleBuilderTest.php` - Logic
15. `ReportServiceTest.php` - Distribution

---

## Nächster Schritt:
1. PasswordResetsFixture erstellen
2. AuthenticationFlowTest fixen
3. ChildrenController + SiblingGroups fixen
