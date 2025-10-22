#!/bin/bash

echo "🐳 Starting Ausfallplan-Generator Docker Container..."
echo ""

# Change to project root directory
cd "$(dirname "$0")/.." || exit 1

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Error: Docker is not running. Please start Docker first."
    exit 1
fi

# Detect docker compose command (V2 preferred, V1 fallback)
if docker compose version > /dev/null 2>&1; then
    DOCKER_COMPOSE="docker compose"
    echo "✓ Using Docker Compose V2 (docker compose)"
elif command -v docker compose> /dev/null 2>&1; then
    DOCKER_COMPOSE="docker-compose"
    echo "⚠ Using Docker Compose V1 (docker-compose) - Consider upgrading to V2"
else
    echo "❌ Error: Docker Compose is not installed."
    exit 1
fi
echo ""

# Create necessary directories
echo "📁 Creating necessary directories..."
mkdir -p tmp/cache/models tmp/cache/persistent tmp/cache/views tmp/sessions tmp/tests logs
chmod -R 775 tmp logs

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

# Build and start containers
echo ""
echo "🏗️  Building Docker image..."
$DOCKER_COMPOSE -f docker/docker-compose.yml build

echo ""
echo "🚀 Starting containers..."
$DOCKER_COMPOSE -f docker/docker-compose.yml up -d

# Wait for container to be ready
echo ""
echo "⏳ Waiting for container to be ready..."
sleep 3

# Run migrations
echo ""
echo "🗄️  Running database migrations..."
$DOCKER_COMPOSE -f docker/docker-compose.yml exec -T app bin/cake migrations migrate

echo ""
echo "✅ Ausfallplan-Generator is ready!"
echo ""
echo "📊 Access the application at: http://localhost:8080"
echo ""
echo "🛠️  Useful commands:"
echo "   - View logs:       $DOCKER_COMPOSE -f docker/docker-compose.yml logs -f app"
echo "   - Stop container:  $DOCKER_COMPOSE -f docker/docker-compose.yml down"
echo "   - Run tests:       $DOCKER_COMPOSE -f docker/docker-compose.yml exec app vendor/bin/phpunit"
echo "   - Shell access:    $DOCKER_COMPOSE -f docker/docker-compose.yml exec app bash"
echo ""
