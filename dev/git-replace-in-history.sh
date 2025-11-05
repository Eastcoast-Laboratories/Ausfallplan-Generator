#!/bin/bash

# Git Replace in History Script
# Replaces sensitive words in git commit messages throughout history
#
# Usage:
#   ./dev/git-replace-in-history.sh "password123" "[REDACTED]"
#   ./dev/git-replace-in-history.sh "secret-key" "***REMOVED***"
#
# Warning: This rewrites git history and requires force push!

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check arguments
if [ $# -lt 1 ]; then
    echo -e "${RED}Error: Missing arguments${NC}"
    echo ""
    echo "Usage:"
    echo "  $0 <word-to-replace> [replacement]"
    echo ""
    echo "Examples:"
    echo "  $0 'password123' '[REDACTED]'"
    echo "  $0 'secret-key' '***REMOVED***'"
    echo "  $0 'api-key-abc123'"
    echo ""
    exit 1
fi

SEARCH_WORD="$1"
REPLACEMENT="${2:-[REDACTED]}"

# Confirmation
echo -e "${YELLOW}⚠️  WARNING: This will rewrite git history!${NC}"
echo ""
echo "Search for:  ${RED}${SEARCH_WORD}${NC}"
echo "Replace with: ${GREEN}${REPLACEMENT}${NC}"
echo ""
echo "This will:"
echo "  1. Search all commit messages for '${SEARCH_WORD}'"
echo "  2. Replace with '${REPLACEMENT}'"
echo "  3. Rewrite git history"
echo "  4. Require force push to remote"
echo ""

# Check if word exists in history
echo "Checking if word exists in history..."
FOUND_COUNT=$(git log --all --grep="${SEARCH_WORD}" --oneline | wc -l)

if [ "$FOUND_COUNT" -eq 0 ]; then
    echo -e "${YELLOW}No commits found containing '${SEARCH_WORD}'${NC}"
    echo "Nothing to do. Aborting."
    exit 0
fi

echo -e "${GREEN}Found ${FOUND_COUNT} commit(s) containing '${SEARCH_WORD}'${NC}"
echo ""
echo "Commits that will be affected:"
git log --all --grep="${SEARCH_WORD}" --oneline | head -10
if [ "$FOUND_COUNT" -gt 10 ]; then
    echo "... and $((FOUND_COUNT - 10)) more"
fi
echo ""

# Ask for confirmation
read -p "Do you want to proceed? (yes/no): " CONFIRM
if [ "$CONFIRM" != "yes" ]; then
    echo "Aborted."
    exit 0
fi

echo ""
echo "🔄 Rewriting git history..."
echo ""

# Backup current branch
CURRENT_BRANCH=$(git branch --show-current)
BACKUP_BRANCH="backup-before-filter-$(date +%Y%m%d-%H%M%S)"
echo "Creating backup branch: ${BACKUP_BRANCH}"
git branch "${BACKUP_BRANCH}"

# Run filter-branch on ALL branches and tags
# This ensures complete replacement across entire history
echo "Rewriting ALL branches and tags (this may take a while)..."
git filter-branch -f --msg-filter "sed 's/${SEARCH_WORD}/${REPLACEMENT}/g'" -- --all 2>&1 | tail -20

echo ""
echo -e "${GREEN}✅ History rewritten successfully!${NC}"
echo ""

# Clean up old refs (important!)
echo "Cleaning up old refs..."

# Remove refs/original/* from packed-refs (created by filter-branch)
if [ -f .git/packed-refs ]; then
    echo "Removing refs/original/* from packed-refs..."
    sed -i '/refs\/original\//d' .git/packed-refs
fi

# Remove refs/original directory
if [ -d .git/refs/original ]; then
    echo "Removing .git/refs/original directory..."
    rm -rf .git/refs/original
fi

# Remove filter-branch stashes
echo "Removing filter-branch stashes..."
git stash list | grep "filter-branch: rewrite" | cut -d: -f1 | while read stash; do
    git stash drop "$stash" 2>/dev/null || true
done

# Run garbage collection
echo "Running git gc --prune=now..."
git gc --prune=now 2>&1 | tail -5
echo ""

# Verify
echo "Verifying replacement..."
if git log --all --grep="${SEARCH_WORD}" --oneline | head -1 > /dev/null 2>&1; then
    REMAINING=$(git log --all --grep="${SEARCH_WORD}" --oneline | wc -l)
    echo -e "${RED}❌ ERROR: Still found ${REMAINING} commit(s) with '${SEARCH_WORD}'${NC}"
    echo ""
    echo "Commits still containing '${SEARCH_WORD}':"
    git log --all --grep="${SEARCH_WORD}" --oneline | head -10
    echo ""
    echo -e "${YELLOW}This might be a sed escaping issue.${NC}"
    echo "Try escaping special characters or using a different replacement."
else
    echo -e "${GREEN}✅ No occurrences of '${SEARCH_WORD}' found in entire history${NC}"
fi

# Check for replacement
if git log --all --grep="${REPLACEMENT}" --oneline | head -1 > /dev/null 2>&1; then
    REPLACED_COUNT=$(git log --all --grep="${REPLACEMENT}" --oneline | wc -l)
    echo -e "${GREEN}✅ Found ${REPLACED_COUNT} commit(s) with replacement '${REPLACEMENT}'${NC}"
fi

echo ""

# If replacement was successful, offer to delete backup branches
if ! git log --all --grep="${SEARCH_WORD}" --oneline | head -1 > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Replacement successful!${NC}"
    echo ""
    echo "The backup branch '${BACKUP_BRANCH}' still contains the old commits."
    echo "This is why 'git log --all' might still show them."
    echo ""
    read -p "Delete backup branch to complete cleanup? (yes/no): " DELETE_BACKUP
    if [ "$DELETE_BACKUP" = "yes" ]; then
        git branch -D "${BACKUP_BRANCH}"
        echo -e "${GREEN}✅ Backup branch deleted${NC}"
        
        # Run gc again to remove dangling commits
        echo "Running final cleanup..."
        git gc --prune=now 2>&1 | tail -3
        echo ""
        
        # Final verification
        if git log --all --grep="${SEARCH_WORD}" --oneline | head -1 > /dev/null 2>&1; then
            STILL_REMAINING=$(git log --all --grep="${SEARCH_WORD}" --oneline | wc -l)
            echo -e "${YELLOW}Note: Still found ${STILL_REMAINING} commit(s) in other backup branches${NC}"
            echo "To remove all backup branches:"
            echo "  git branch | grep backup-before-filter | xargs git branch -D"
        else
            echo -e "${GREEN}✅ Complete! No traces of '${SEARCH_WORD}' found in git history${NC}"
        fi
    else
        echo "Backup branch kept: ${BACKUP_BRANCH}"
    fi
fi

echo ""
echo -e "${YELLOW}⚠️  Next steps:${NC}"
echo ""
echo "1. Review the changes:"
echo "   git log --all --grep='${REPLACEMENT}' --oneline"
echo ""
echo "2. Force push to remote (IMPORTANT: Use --force, not --force-with-lease!):"
echo "   git push origin ${CURRENT_BRANCH} --force"
echo ""
echo "   ${YELLOW}Note: --force-with-lease will fail with 'stale info' because${NC}"
echo "   ${YELLOW}filter-branch rewrites refs. Use --force instead.${NC}"
echo ""
echo "3. If something went wrong, restore from backup:"
echo "   git reset --hard ${BACKUP_BRANCH}"
echo ""
echo "4. To remove ALL backup branches (if you're sure):"
echo "   git branch | grep backup-before-filter | xargs git branch -D"
echo ""
echo -e "${GREEN}Done!${NC}"
