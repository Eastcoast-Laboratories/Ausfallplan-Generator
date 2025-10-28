#!/bin/bash

# Quick test runner - runs tests without verbose output
# Usage: ./quick_test.sh [phpunit|playwright|coverage|all]
#   phpunit   - Run PHPUnit tests only
#   playwright - Run Playwright tests only
#   coverage  - Generate code coverage report and open in browser
#   all       - Run all tests (default)

# Get the directory where this script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
# Go to project root (one level up from dev/)
cd "$SCRIPT_DIR/.."

TEST_TYPE=${1:-all}

run_phpunit() {
    echo "🧪 PHPUnit..."
    docker compose -f docker/docker-compose.yml exec -T app vendor/bin/phpunit --no-coverage 2>&1 | tail -1
}

run_playwright() {
    echo "🎭 Playwright..."
    timeout 180 npx playwright test --project=chromium 2>&1 | grep -E "passed|failed" | tail -1
}

run_coverage() {
    echo "📊 Generating Code Coverage..."
    docker compose -f docker/docker-compose.yml exec -T app vendor/bin/phpunit --coverage-html coverage 2>&1 | tail -3
    echo ""
    echo "📁 Linking coverage to webroot..."
    docker compose -f docker/docker-compose.yml exec -T app ln -sf /var/www/html/coverage /var/www/html/webroot/coverage
    echo ""
    echo "🌐 Opening coverage in browser..."
    firefox http://localhost:8080/coverage/index.html > /dev/null 2>&1 &
    echo "✅ Coverage available at: http://localhost:8080/coverage/index.html"
}

case $TEST_TYPE in
    phpunit)
        run_phpunit
        ;;
    playwright)
        run_playwright
        ;;
    coverage)
        run_coverage
        ;;
    all)
        run_phpunit
        echo ""
        run_playwright
        ;;
    *)
        echo "Usage: $0 [phpunit|playwright|coverage|all]"
        exit 1
        ;;
esac
