#!/bin/sh

# Cache configurations
echo "Caching config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
echo "Running migrations..."
php artisan migrate --force
