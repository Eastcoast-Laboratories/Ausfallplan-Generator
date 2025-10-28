#!/bin/bash

# Quick test runner - runs tests without verbose output
# Usage: ./quick_test.sh [phpunit|playwright|all]

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

case $TEST_TYPE in
    phpunit)
        run_phpunit
        ;;
    playwright)
        run_playwright
        ;;
    all)
        run_phpunit
        echo ""
        run_playwright
        ;;
    *)
        echo "Usage: $0 [phpunit|playwright|all]"
        exit 1
        ;;
esac
