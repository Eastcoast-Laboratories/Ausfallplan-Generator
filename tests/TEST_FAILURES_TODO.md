# REMAINING TEST FAILURES - 7 Tests

## STATUS: 101/108 (93.5%)

```
Passing: 101
Failing: 7
Skipped: 4
```

---

## OPEN ISSUES

### 1. 🔥 testLoginBlocksUnverifiedEmail (SECURITY!)
**File:** AuthenticationFlowTest.php
**Error:** Failed asserting that 'Dashboard' is not in response body
**Issue:** Unverified users can login - SECURITY PROBLEM
**Priority:** HIGH
**Fix:** Ensure authentication middleware blocks unverified users

### 2. testPasswordResetWithValidCode
**File:** AuthenticationFlowTest.php  
**Error:** Failed asserting that null is not null (used_at field)
**Issue:** Password reset works but used_at not being saved
**Priority:** MEDIUM
**Fix:** Investigate why $reset->used_at not saved in DB

### 3. testAdminSeesAllSchedules
**File:** Admin/SchedulesAccessTest.php
**Error:** Failed asserting that 'editor1@test.com' is in response body
**Issue:** Admin not seeing all organization schedules
**Priority:** MEDIUM
**Fix:** Admin scope should show ALL orgs, not just own

### 4. testEditorSeesOnlyOwnSchedules
**File:** Admin/SchedulesAccessTest.php
**Error:** Failed asserting that 'Schedule 2' is not in response body
**Issue:** Editor seeing schedules from other organizations
**Priority:** MEDIUM  
**Fix:** Editor scope must filter by organization_id

### 5. testChildrenDistributionWithWeights
**File:** ReportServiceTest.php
**Error:** Failed asserting that an array is not empty
**Issue:** Test data setup - no children in report
**Priority:** LOW
**Fix:** Fix createTestScheduleWithChildren() helper

### 6. testLeavingChildIdentification
**File:** ReportServiceTest.php
**Error:** Failed asserting that null is not null
**Issue:** "Leaving child" logic not working in test
**Priority:** LOW
**Fix:** Fix test data to properly mark leaving child

### 7. testNavigationVisibleWhenLoggedIn
**File:** AuthenticatedLayoutTest.php
**Error:** Failed asserting that '/users/logout' is in response body
**Issue:** Test gets redirect instead of 200 with content
**Priority:** LOW
**Fix:** Follow redirect or check actual response status

---

## WORK PLAN

### Now: Fix #1 (SECURITY - 30 min)
Unverified email login is a security issue.

### Next: Fix #2-4 (MEDIUM - 2 hours)
- Password reset investigation
- Admin/Editor scope issues

### Later: Fix #5-7 (LOW - 1 hour)
- ReportService test data
- Layout test assertion

---

## TARGET: 95%+ (104/108)
Fix #1-3 = 95.4%
