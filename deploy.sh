#!/bin/bash
set -e

echo "🚀 Starting deployment..."

# Get the directory of the script
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Pull latest changes
echo "📥 Pulling latest changes from git..."
git pull origin main

# Install/update composer dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

# Run database migrations
echo "🗄️  Running database migrations..."
bin/cake migrations migrate

# Clear cache
echo "🧹 Clearing cache..."
rm -rf tmp/cache/*

# Set permissions
echo "🔐 Setting permissions..."
chmod -R 775 tmp logs
chown -R www-data:www-data tmp logs

echo "✅ Deployment completed successfully!"
