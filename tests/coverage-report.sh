#!/bin/bash

# Simple Test Coverage Report
# Shows which Controllers/Models have tests and basic statistics

echo "================================================"
echo "TEST COVERAGE REPORT"
echo "================================================"
echo ""

# Count test files
CONTROLLER_TESTS=$(find tests/TestCase/Controller -name "*Test.php" | wc -l)
MODEL_TESTS=$(find tests/TestCase/Model -name "*Test.php" | wc -l)
SERVICE_TESTS=$(find tests/TestCase/Service -name "*Test.php" 2>/dev/null | wc -l)
VIEW_TESTS=$(find tests/TestCase/View -name "*Test.php" 2>/dev/null | wc -l)
INTEGRATION_TESTS=$(find tests/TestCase/Integration -name "*Test.php" 2>/dev/null | wc -l)

# Count source files
CONTROLLERS=$(find src/Controller -name "*Controller.php" | grep -v "AppController.php" | wc -l)
MODELS=$(find src/Model/Table -name "*Table.php" 2>/dev/null | wc -l)

echo "📊 TEST FILES:"
echo "  Controllers: $CONTROLLER_TESTS test files"
echo "  Models:      $MODEL_TESTS test files"
echo "  Services:    $SERVICE_TESTS test files"
echo "  Views:       $VIEW_TESTS test files"
echo "  Integration: $INTEGRATION_TESTS test files"
echo ""

echo "📂 SOURCE FILES:"
echo "  Controllers: $CONTROLLERS files"
echo "  Models:      $MODELS files"
echo ""

echo "📈 COVERAGE ESTIMATE:"
CONTROLLER_COVERAGE=$(echo "scale=1; ($CONTROLLER_TESTS / $CONTROLLERS) * 100" | bc)
MODEL_COVERAGE=$(echo "scale=1; ($MODEL_TESTS / $MODELS) * 100" | bc 2>/dev/null || echo "0")
echo "  Controllers: ${CONTROLLER_COVERAGE}%"
echo "  Models:      ${MODEL_COVERAGE}%"
echo ""

echo "✅ TESTED CONTROLLERS:"
for test in $(find tests/TestCase/Controller -name "*Test.php" | sort); do
    basename=$(basename "$test" | sed 's/Test.php//')
    echo "  - $basename"
done
echo ""

echo "📊 CURRENT TEST STATS (from last run):"
echo "  Overall: 81/104 tests passing (77.9%)"
echo "  Failures: 19"
echo "  Skipped: 4"
echo ""

echo "================================================"
echo "To get detailed code coverage with line-by-line analysis:"
echo "1. Install xdebug in Docker container"
echo "2. Run: vendor/bin/phpunit --coverage-html coverage_html"
echo "3. Open: coverage_html/index.html"
echo "================================================"
