#!/bin/bash
set -e

# Cache configuration, routes, and views for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations automatically on startup
php artisan migrate --force || true

# Start Apache in foreground
exec apache2-foreground
