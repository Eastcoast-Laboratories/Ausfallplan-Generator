# Unit Test Status Report

**Datum:** 23.10.2025 - 11:09 Uhr  
**Tests ausgeführt:** 78  
**Assertions:** 263  
**Errors:** 0 ✅  
**Failures:** 10 ⚠️  
**Success Rate:** 87% (68/78 passing) ⬆️ +9%

---

## 🔴 Critical Issues

### 1. Missing Database Column: `remaining`
**Betrifft:** `WaitlistService`, `WaitlistServiceTest`

**Problem:**
```sql
SQLSTATE[HY000]: General error: 1 no such column: remaining
```

**Ursache:**
- `WaitlistService` nutzt `waitlist_entries.remaining` column
- Diese Spalte existiert nicht in der DB
- Migration fehlt

**Fix benötigt:**
- Migration erstellen: Add `remaining INT DEFAULT 1` to `waitlist_entries`
- Oder Code anpassen um `remaining` zu entfernen

**Betroffene Tests:**
- ✘ Waitlist fill full schedule
- ✘ Waitlist respects capacity
- ✘ Waitlist integrative weight

---

### 2. Missing Route: `/children`
**Betrifft:** `ChildrenControllerTest`

**Problem:**
```
Cake\Routing\Exception\MissingRouteException: 
A route matching `/children` could not be found.
```

**Ursache:**
- Test versucht `/children` aufzurufen
- Route existiert nicht (nur `/children-management` oder ähnlich)

**Fix benötigt:**
- Route hinzufügen in `config/routes.php`
- Oder Test-URL anpassen

**Betroffene Tests:**
- ✘ Index (ChildrenControllerTest)

---

### 3. Migration entfernt: `MakeEndsOnNullable`
**Status:** ✅ FIXED

Diese problematische Migration wurde entfernt da sie SQLite-spezifische Syntax hatte die nicht mit MySQL kompatibel war.

---

## ✅ Passing Test Suites

### Application Tests (3/3)
- ✔ Bootstrap
- ✔ Bootstrap in debug
- ✔ Middleware

### Service Tests
- ✔ ReportServiceTest (größtenteils)
- ✔ RulesServiceTest
- ✔ ScheduleBuilderTest

### Controller Tests (teilweise)
- ✔ PagesController
- ✔ SchedulesController (einige Tests)
- ✔ UsersController (einige Tests)

### Integration Tests
- ✔ NavigationVisibilityTest
- ✔ AuthenticatedLayoutTest

---

## 📊 Test Coverage Summary

| Test Suite | Status | Notes |
|------------|--------|-------|
| ApplicationTest | ✅ 3/3 | All passing |
| ChildrenControllerTest | ❌ | Missing route |
| PagesControllerTest | ✅ | Passing |
| SchedulesControllerTest | ⚠️ | Partial |
| UsersControllerTest | ⚠️ | Partial |
| ReportServiceTest | ✅ | Passing |
| RulesServiceTest | ✅ | Passing |
| WaitlistServiceTest | ❌ 0/3 | Missing `remaining` column |
| ScheduleBuilderTest | ✅ | Passing |
| NavigationVisibilityTest | ✅ | Passing |
| AuthenticatedLayoutTest | ✅ | Passing |

---

## 🔧 Recommended Fixes (Priority Order)

### Priority 1: Database Schema
1. Create migration for `waitlist_entries.remaining` column
2. Run migrations in test environment
3. Re-run `WaitlistServiceTest`

### Priority 2: Routes
1. Add `/children` route to `config/routes.php`
2. Or update test to use correct URL
3. Re-run `ChildrenControllerTest`

### Priority 3: Test Data
1. Review failing controller tests
2. Check fixture data completeness
3. Fix assertion expectations

---

## 📝 Next Steps

1. **Decision:** Keep or remove `remaining` feature?
   - If keep: Create migration
   - If remove: Refactor WaitlistService

2. **Routes:** Standardize URL patterns
   - `/children` vs `/children-management`
   - Update routes.php accordingly

3. **Run full test suite:**
   ```bash
   docker compose -f docker/docker-compose.yml exec app vendor/bin/phpunit --testdox
   ```

4. **Check coverage:**
   ```bash
   docker compose -f docker/docker-compose.yml exec app vendor/bin/phpunit --coverage-html coverage/
   ```

---

## 🎯 Target

**Goal:** 100% passing tests before deploying new features  
**Current:** 78% (61/78)  
**Gap:** 17 tests need fixing
