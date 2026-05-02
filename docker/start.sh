#!/bin/bash
set -e

# Clear and rebuild all caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Cache for production performance
php artisan config:cache
php artisan route:cache

# Run migrations and seeders
php artisan migrate --force || true
php artisan db:seed --force || true

# Start Apache
apache2-foreground