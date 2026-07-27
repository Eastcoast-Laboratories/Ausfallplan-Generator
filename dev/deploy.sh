#!/bin/bash
set -e

echo "🚀 Deploying to production server..."

# 1. Commit and push to GitHub
echo "📤 Step 1: Pushing to GitHub..."
echo "Auf Branch main"
git status
echo ""

# Check if there are changes to commit
if git diff-index --quiet HEAD --; then
    echo "Keine Änderungen zu committen."
else
    # Commit and push
    echo "Commit message:"
    read -r commit_message
    git add -A
    git commit -m "$commit_message"
fi

git push origin main

# 2. Deploy to server
echo "🌐 Step 2: Deploying to eclabs-vm06-web..."
ssh eclabs-vm06-web "cd /var/kunden/webs/ruben/www/fairnestplan.z11.de && \
    git reset --hard 8c1fed22 && \
    git pull origin main && \
    echo '📦 Updating composer dependencies for PHP 8.5...' && \
    composer update --no-dev --optimize-autoloader && \
    rm -rf tmp/cache/* && \
    echo '🗄️  Running database migrations...' && \
    bin/cake migrations migrate && \
    echo '✅ Deployment completed!'"

echo ""
echo "✅ Deployment successful!"
echo "🔗 Check: https://fairnestplan.z11.de/"


