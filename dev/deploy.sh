#!/bin/bash
set -e

echo "🚀 Deploying to production server..."

# 1. Commit and push to GitHub
echo "📤 Step 1: Pushing to GitHub..."
git add -A
git status
read -p "Commit message: " commit_msg
git commit -m "$commit_msg" || echo "Nothing to commit"
git push origin main

# 2. Deploy to server
echo "🌐 Step 2: Deploying to eclabs-vm06..."
ssh eclabs-vm06 "cd /var/kunden/webs/ruben/www/ausfallplan-generator.z11.de && \
    git pull origin main && \
    rm -rf tmp/cache/* && \
    echo '✅ Deployment completed!'"

echo ""
echo "✅ Deployment successful!"
echo "🔗 Check: https://ausfallplan-generator.z11.de/"

# Check for uncommitted changes
if ! git diff --quiet || ! git diff --cached --quiet; then
    echo "❌ ERROR: Uncommitted changes detected!"
    echo ""
    echo "📋 Changed files:"
    git status --porcelain
    echo ""
    echo "🤖 Suggested commit message based on changes:"
    
    # Generate AI-powered commit message suggestion
    CHANGED_FILES=$(git status --porcelain | head -10)
    DIFF_SUMMARY=$(git diff --stat 2>/dev/null | head -5)
    
    echo "   git add .; git commit -m \"ADD_MESSAGE_HERE\""
    echo ""
    echo "💡 Please commit your changes first, then run deploy.sh again."
    exit 1
fi

# Proceed with deployment if no uncommitted changes
git push
for P in /var/kunden/webs/ruben/www/ausfallplan-generator.z11.de; do
    echo "############# deploy to '$P'"
    ssh eclabs-vm06 'cd '$P'; git reset ba388264 --hard; git checkout master -f; git pull'
    echo "############# done"
done
echo "Deployed $(date)"
echo "Please check the logs for any errors with ssh eclabs-vm06 'tail -f /var/log/nginx/error.log'|grep ausfallplan-generator"


