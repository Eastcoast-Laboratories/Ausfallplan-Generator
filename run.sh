#!/bin/bash

echo "🐳 Starting FairNestPlan Docker Container..."
echo ""

# Change to project root directory (where this script is located)
cd "$(dirname "$0")" || exit 1

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Error: Docker is not running. starting Docker ..."
    sudo service docker start
fi

DOCKER_COMPOSE="docker compose"
echo "✓ Using Docker Compose V2 (docker compose)"

echo ""

# Check if nginx is running and stop it (it usually uses port 8080)
echo "🔍 Checking for nginx..."
if systemctl is-active --quiet nginx; then
    echo "⚠️  Nginx is running and may block port 8080."
    echo "   Stopping nginx..."
    sudo systemctl stop nginx
    
    if [ $? -eq 0 ]; then
        echo "   ✓ Nginx stopped successfully."
    else
        echo "   ❌ Failed to stop nginx. Please run: sudo systemctl stop nginx"
        exit 1
    fi
else
    echo "✓ Nginx is not running."
fi

# Check if MySQL is running and stop it (it usually uses port 3306)
echo "🔍 Checking for MySQL..."
if systemctl is-active --quiet mysql; then
    echo "⚠️  MySQL is running and may block port 3306."
    echo "   Stopping MySQL..."
    sudo systemctl stop mysql
    sleep 3
    if [ $? -eq 0 ]; then
        echo "   ✓ MySQL stopped successfully."
    else
        echo "   ❌ Failed to stop MySQL. Please run: sudo systemctl stop mysql"
        exit 1
    fi
else
    echo "✓ MySQL is not running."
fi

echo ""

# Double-check if port 8080 is available
if lsof -Pi :8080 -sTCP:LISTEN -t >/dev/null 2>&1; then
    echo "⚠️  Port 8080 is still in use by another process:"
    lsof -Pi :8080 -sTCP:LISTEN
    echo "   Please stop it manually or change the port in docker-compose.yml"
    exit 1
fi
echo ""

# Create necessary directories
echo "📁 Creating necessary directories..."
mkdir -p tmp/cache/models tmp/cache/persistent tmp/cache/views tmp/sessions tmp/tests logs
chmod -Rf 777 tmp logs

# Copy app_local.php if it doesn't exist
if [ ! -f config/app_local.php ]; then
    echo "📝 Creating config/app_local.php from example..."
    cp config/app_local.example.php config/app_local.php
    
    # Update database URL to use SQLite (4 slashes for absolute path)
    sed -i "s/'url' => env('DATABASE_URL', null),/'url' => env('DATABASE_URL', 'sqlite:\/\/\/\/var\/www\/html\/tmp\/app.sqlite'),/" config/app_local.php
    
    # Generate a random salt
    RANDOM_SALT=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-64)
    sed -i "s/'salt' => env('SECURITY_SALT', '__SALT__'),/'salt' => env('SECURITY_SALT', '$RANDOM_SALT'),/" config/app_local.php
fi

# Build (if needed) and start containers
echo ""
echo "🚀 Building (if needed) and starting containers..."
$DOCKER_COMPOSE -f docker/docker-compose.yml up -d --build

# Wait for container to be ready
echo ""
echo "⏳ Waiting for container to be ready..."
sleep 4

# Fix permissions for logs and tmp (required for volume mounts)
echo ""
echo "🔧 Fixing permissions for logs and tmp directories..."
$DOCKER_COMPOSE -f docker/docker-compose.yml exec -T app chown -R www-data:www-data /var/www/html/logs /var/www/html/tmp
$DOCKER_COMPOSE -f docker/docker-compose.yml exec -T app chmod -R 777 /var/www/html/logs /var/www/html/tmp

# Run migrations
echo ""
echo "🗄️  Running database migrations..."
$DOCKER_COMPOSE -f docker/docker-compose.yml exec -T app bin/cake migrations migrate

echo ""
echo "✅ FairNestPlan is ready!"
echo ""
echo "📊 Access the application at: http://localhost:8080"
echo ""
echo "🛠️  Useful commands:"
echo "   - View logs:       $DOCKER_COMPOSE -f docker/docker-compose.yml logs -f app"
echo "   - Stop container:  $DOCKER_COMPOSE -f docker/docker-compose.yml down"
echo "   - Run tests:       $DOCKER_COMPOSE -f docker/docker-compose.yml exec app vendor/bin/phpunit"
echo "   - Shell access:    $DOCKER_COMPOSE -f docker/docker-compose.yml exec app bash"
echo ""
