#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Track results
PHPUNIT_PASSED=0
PLAYWRIGHT_PASSED=0
TOTAL_ERRORS=0

echo ""
echo "=========================================="
echo "🧪 RUNNING ALL TESTS"
echo "=========================================="
echo ""

# ============================================
# 1. PHPUnit Tests
# ============================================
echo -e "${BLUE}📋 Running PHPUnit Tests...${NC}"
echo ""

docker compose -f docker/docker-compose.yml exec -T app vendor/bin/phpunit --colors=never > /tmp/phpunit_output.txt 2>&1
PHPUNIT_EXIT=$?

# Parse PHPUnit output
PHPUNIT_TESTS=$(grep -E "^Tests:" /tmp/phpunit_output.txt | tail -1)
PHPUNIT_SUMMARY=$(echo "$PHPUNIT_TESTS" | sed 's/^Tests: //')

if [ $PHPUNIT_EXIT -eq 0 ]; then
    echo -e "${GREEN}✅ PHPUnit Tests PASSED${NC}"
    PHPUNIT_PASSED=1
else
    echo -e "${RED}❌ PHPUnit Tests FAILED${NC}"
    TOTAL_ERRORS=$((TOTAL_ERRORS + 1))
fi

echo "   $PHPUNIT_SUMMARY"
echo ""

# Show errors if any
if [ $PHPUNIT_EXIT -ne 0 ]; then
    echo -e "${YELLOW}   Last 15 lines of output:${NC}"
    tail -15 /tmp/phpunit_output.txt | sed 's/^/   /'
    echo ""
fi

# ============================================
# 2. Playwright Tests
# ============================================
echo -e "${BLUE}🎭 Running Playwright Tests...${NC}"
echo ""

# Get list of test files
TEST_FILES=$(cd tests/e2e && ls -1 *.spec.js 2>/dev/null)
TEST_COUNT=$(echo "$TEST_FILES" | wc -l)

if [ -z "$TEST_FILES" ]; then
    echo -e "${YELLOW}⚠️  No Playwright tests found${NC}"
else
    echo "   Found $TEST_COUNT test files"
    echo ""
    
    # Run Playwright tests with timeout
    timeout 300 npx playwright test --project=chromium > /tmp/playwright_output.txt 2>&1
    PLAYWRIGHT_EXIT=$?
    
    if [ $PLAYWRIGHT_EXIT -eq 124 ]; then
        echo -e "${RED}❌ Playwright Tests TIMEOUT (> 300s)${NC}"
        TOTAL_ERRORS=$((TOTAL_ERRORS + 1))
    elif [ $PLAYWRIGHT_EXIT -eq 0 ]; then
        echo -e "${GREEN}✅ Playwright Tests PASSED${NC}"
        PLAYWRIGHT_PASSED=1
    else
        echo -e "${RED}❌ Playwright Tests FAILED${NC}"
        TOTAL_ERRORS=$((TOTAL_ERRORS + 1))
    fi
    
    # Parse Playwright summary
    PLAYWRIGHT_SUMMARY=$(grep -E "passed|failed" /tmp/playwright_output.txt | tail -1)
    if [ ! -z "$PLAYWRIGHT_SUMMARY" ]; then
        echo "   $PLAYWRIGHT_SUMMARY"
    fi
    echo ""
    
    # Show errors if any
    if [ $PLAYWRIGHT_EXIT -ne 0 ] && [ $PLAYWRIGHT_EXIT -ne 124 ]; then
        echo -e "${YELLOW}   Last 20 lines of output:${NC}"
        tail -20 /tmp/playwright_output.txt | sed 's/^/   /'
        echo ""
    fi
fi

# ============================================
# Summary
# ============================================
echo ""
echo "=========================================="
echo "📊 TEST SUMMARY"
echo "=========================================="
echo ""

# PHPUnit Summary
if [ $PHPUNIT_PASSED -eq 1 ]; then
    echo -e "${GREEN}✅ PHPUnit:${NC} $PHPUNIT_SUMMARY"
else
    echo -e "${RED}❌ PHPUnit:${NC} FAILED"
fi

# Playwright Summary
if [ ! -z "$TEST_FILES" ]; then
    if [ $PLAYWRIGHT_PASSED -eq 1 ]; then
        echo -e "${GREEN}✅ Playwright:${NC} All tests passed"
    else
        echo -e "${RED}❌ Playwright:${NC} Some tests failed"
    fi
else
    echo -e "${YELLOW}⚠️  Playwright:${NC} No tests found"
fi

echo ""

# Overall status
if [ $TOTAL_ERRORS -eq 0 ]; then
    echo -e "${GREEN}🎉 ALL TESTS PASSED!${NC}"
    echo ""
    exit 0
else
    echo -e "${RED}💥 $TOTAL_ERRORS TEST SUITE(S) FAILED${NC}"
    echo ""
    echo "Check the output above for details."
    echo ""
    exit 1
fi
