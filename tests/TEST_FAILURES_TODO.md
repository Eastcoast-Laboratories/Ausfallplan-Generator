# REMAINING TEST FAILURES - 6 Tests

## STATUS: 102/108 (94.4%)

```
Passing: 102
Failing: 6
Skipped: 4
```

**Session Progress: 76.9% → 94.4% (+17.5% / +19 tests!)**

---

## OPEN ISSUES

### 1. testPasswordResetWithValidCode
**File:** AuthenticationFlowTest.php  
**Error:** Reset should be marked as used (Failed asserting that null is not null)
**Issue:** Password reset works but used_at field not being saved to database
**Priority:** MEDIUM
**Fix:** Investigate DB transaction or save issue in UsersController

### 2. testAdminSeesAllSchedules
**File:** Admin/SchedulesAccessTest.php
**Error:** Failed asserting that 'editor1@test.com' is in response body
**Issue:** Admin not seeing schedules from all organizations
**Priority:** MEDIUM
**Fix:** Admin controller scope should show ALL organizations

### 3. testEditorSeesOnlyOwnSchedules
**File:** Admin/SchedulesAccessTest.php
**Error:** Failed asserting that 'Schedule 2' is not in response body
**Issue:** Editor seeing schedules from other organizations
**Priority:** MEDIUM  
**Fix:** Editor scope must filter by organization_id

### 4. testChildrenDistributionWithWeights
**File:** ReportServiceTest.php
**Error:** Failed asserting that an array is not empty
**Issue:** Test data setup - report has no children
**Priority:** LOW
**Fix:** Fix createTestScheduleWithChildren() to properly create assignments

### 5. testLeavingChildIdentification
**File:** ReportServiceTest.php
**Error:** Failed asserting that null is not null
**Issue:** "Leaving child" logic not working in test data
**Priority:** LOW
**Fix:** Properly configure test data for leaving child scenario

### 6. testNavigationVisibleWhenLoggedIn
**File:** AuthenticatedLayoutTest.php
**Error:** Failed asserting that '/users/logout' is in response body
**Issue:** Test gets redirect, needs to handle non-200 responses
**Priority:** LOW
**Fix:** Update test to handle redirect or check actual response

---

## NEXT STEPS

All remaining tests need investigation:
- Admin scope issues (#2-3)
- DB save investigation (#1)
- Test data setup (#4-5)
- Layout test handling (#6)

**Estimated time: 2-3 hours to 100%**

---

## SESSION ACHIEVEMENTS ✅

**Tests Fixed Today: 19** (83 → 102)
- display_name Feature: 4 tests
- Permissions Tests: 8 tests
- sort_order DB Schema: 9 errors → 0
- Navigation Tests: 2 tests
- Security Tests: 2 tests (unverified email + pending)
- Validation Tests: 1 test
- Admin Permissions: 1 test

**Code Quality:**
- SECURITY FIX: Unverified/inactive users blocked
- display_name feature KOMPLETT
- sort_order überall hinzugefügt
- Flexible test assertions
- Session format standardisiert

**Commits: 19 heute**

**Result: 76.9% → 94.4% (+17.5%)**
