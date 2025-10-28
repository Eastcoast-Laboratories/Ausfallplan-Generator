# Testing Guide

## Quick Start

### Run Alle Tests ausführen:
```bash
bash dev/run_all_tests.sh
```

This will:
1. Run all PHPUnit tests (103 tests)
2. Run all Playwright tests (7 tests)
3. Show detailed summary with colors
4. Exit with code 0 if all pass, 1 if any fail

### Quick Test (Fast)
```bash
bash dev/quick_test.sh              # Run all tests (quick summary only)
bash dev/quick_test.sh phpunit      # PHPUnit only
bash dev/quick_test.sh playwright   # Playwright only
```

## Individual Test Commands

### PHPUnit Tests

**Run all PHPUnit tests:**
```bash
docker compose -f docker/docker-compose.yml exec -T app vendor/bin/phpunit
```

**Run specific test file:**
```bash
docker compose -f docker/docker-compose.yml exec -T app vendor/bin/phpunit tests/TestCase/Service/ReportServiceTest.php
```

**Run specific test method:**
```bash
docker compose -f docker/docker-compose.yml exec -T app vendor/bin/phpunit --filter testGenerateReportData
```

**Run with coverage:**
```bash
docker compose -f docker/docker-compose.yml exec -T app vendor/bin/phpunit --coverage-text
```

### Playwright Tests

**Run all Playwright tests:**
```bash
timeout 300 npx playwright test --project=chromium
```

**Run specific test file:**
```bash
timeout 90 npx playwright test tests/e2e/simple_health_check.spec.js --project=chromium
```

**Run with headed browser (visible):**
```bash
timeout 120 npx playwright test tests/e2e/waitlist-add-all.spec.js --project=chromium --headed
```

**Run with debug mode:**
```bash
timeout 120 npx playwright test tests/e2e/your-test.spec.js --project=chromium --debug
```

**View test report:**
```bash
npx playwright show-report
```

## Test Structure

### PHPUnit Tests (103 tests)
- **Location:** `tests/TestCase/`
- **Types:**
  - Service Tests (4 files)
  - Controller Tests (12+ files)
  - Integration Tests (1 file)
  - Model Tests (1 file)
  - View Tests (1 file)
  - Admin Tests (1 file)
  - API Tests (1 file)

### Playwright Tests (7 tests)
- **Location:** `tests/e2e/`
- **Tests:**
  1. `simple_health_check.spec.js` - Basic app health check
  2. `waitlist-add-all.spec.js` - Add all children button
  3. `children-import-preselected-org.spec.js` - Import preselection
  4. `children-organization-column.spec.js` - Organization column
  5. `report-always-at-end-simple.spec.js` - "Immer am Ende" section
  6. `schedule-days-count-validation.spec.js` - Validation test
  7. `sibling_badges_verification.spec.js` - Sibling badges

## Writing New Tests

### PHPUnit Test Template
```php
<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use Cake\TestSuite\TestCase;

/**
 * YourService Test Case
 * 
 * Brief description of what this test covers.
 * 
 * Verifies:
 * - Key functionality 1
 * - Key functionality 2
 * - Key functionality 3
 */
class YourServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.Organizations',
        'app.Users',
    ];

    public function testYourFunction(): void
    {
        // Your test code
    }
}
```

### Playwright Test Template
```javascript
const { test, expect } = require('@playwright/test');

/**
 * TEST: Your feature description
 * 
 * Tests that [what you're testing]
 */
test.describe('Your Feature', () => {
    test('should do something', async ({ page }) => {
        console.log('🧪 Testing your feature...');
        
        // Step 1: Setup
        console.log('📍 Step 1: Setup');
        // ... your code
        
        // Step 2: Action
        console.log('📍 Step 2: Perform action');
        // ... your code
        
        // Step 3: Verify
        console.log('📍 Step 3: Verify result');
        expect(something).toBe(expected);
        console.log('✅ Verified');
        
        console.log('✅ TEST PASSED!');
    });
});
```

## Best Practices

### PHPUnit
- Use fixtures for test data
- Clear test method names: `testMethodName_ExpectedBehavior`
- Add descriptive headers
- Use proper assertions
- Clean up in tearDown if needed

### Playwright
- Always use timeout: `timeout 120 npx playwright test ...`
- Always specify project: `--project=chromium`
- Create own test data (don't rely on existing data)
- Add console.log for debugging
- Use descriptive step names
- Take screenshots for verification when useful
- Add comprehensive header documentation

## Troubleshooting

### PHPUnit fails with "Record not found"
- Check your fixtures
- Ensure IDs in fixtures match test expectations
- Verify database state

### Playwright test hangs
- Always use timeout command: `timeout 120`
- Check if server is running: `curl http://localhost:8080`
- Look for JavaScript errors in console
- Check selectors are correct

### Playwright test "element not found"
- Wait for page load: `await page.waitForLoadState('networkidle')`
- Check if element exists: `await page.locator('.selector').count()`
- Take screenshot: `await page.screenshot({ path: 'debug.png' })`
- Print page content: `const content = await page.content(); console.log(content)`

## CI/CD Integration

The `run_all_tests.sh` script is designed for CI/CD:
- Returns exit code 0 on success
- Returns exit code 1 on failure
- Colored output (can be disabled if needed)

Example GitHub Actions:
```yaml
- name: Run all tests
  run: bash dev/run_all_tests.sh
```

## More Information

- Full test status: See `TEST_STATUS.md`
- Playwright best practices: See `README_Playwright.md`
- Test coverage: Run PHPUnit with `--coverage-html` flag
