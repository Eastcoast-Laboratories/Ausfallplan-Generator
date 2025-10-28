#!/bin/bash

echo "🧪 Checking all Playwright tests..."
echo ""

# Known good tests
good_tests=(
    "simple_health_check.spec.js"
    "waitlist-add-all.spec.js"
    "report-always-at-end-simple.spec.js"
    "schedule-days-count-validation.spec.js"
    "children-import-preselected-org.spec.js"
    "children-organization-column.spec.js"
)

# Tests to check
tests_to_check=(
    "active-schedule-session.spec.js"
    "admin-dashboard-route.spec.js"
    "admin-login.spec.js"
    "admin-organizations.spec.js"
    "children.spec.js"
    "dashboard-redirect.spec.js"
    "features.spec.js"
    "final_verification.spec.js"
    "german-translations.spec.js"
    "language-hover-test.spec.js"
    "language-switcher.spec.js"
    "language-switching.spec.js"
    "login-demo.spec.js"
    "navigation.spec.js"
    "organization-delete.spec.js"
    "profile.spec.js"
    "registration-login.spec.js"
    "report-generation.spec.js"
    "report-stats.spec.js"
    "schedules.spec.js"
    "sibling_badges_verification.spec.js"
    "translations.spec.js"
    "verify-organization-users.spec.js"
    "verify_features.spec.js"
    "verify_two_siblings_visible.spec.js"
)

echo "✅ Known good tests (${#good_tests[@]}):"
for test in "${good_tests[@]}"; do
    echo "  - $test"
done
echo ""

echo "❓ Tests to verify (${#tests_to_check[@]})"
echo ""

# Test each one
for test in "${tests_to_check[@]}"; do
    if [ -f "tests/e2e/$test" ]; then
        echo "Testing: $test"
        timeout 60 npx playwright test "tests/e2e/$test" --project=chromium > /tmp/test_output.txt 2>&1
        result=$?
        
        if [ $result -eq 0 ]; then
            echo "  ✅ PASSED"
        elif [ $result -eq 124 ]; then
            echo "  ⏱️  TIMEOUT - zu löschen"
        else
            echo "  ❌ FAILED - zu löschen"
        fi
    else
        echo "  ⚠️  NOT FOUND (bereits gelöscht)"
    fi
done

echo ""
echo "Fertig!"
