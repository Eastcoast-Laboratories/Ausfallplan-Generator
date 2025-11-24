#!/bin/bash

# Clear all CakePHP caches
echo "Clearing CakePHP cache..."

# Clear cache directories
rm -rf tmp/cache/models/*
rm -rf tmp/cache/persistent/*
rm -rf tmp/cache/views/*

# Fix permissions
chown -R www-data:www-data tmp/cache
chmod -R 775 tmp/cache

echo "Cache cleared and permissions fixed!"
