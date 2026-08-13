#!/bin/bash
set -e

# Configure Apache port dynamically based on Render's PORT env var (default to 80)
PORT="${PORT:-80}"
echo "Configuring Apache to listen on port ${PORT}..."
sed -i "s/80/${PORT}/g" /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf

# Create SQLite database file automatically if using SQLite
if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_HOST" ]; then
    mkdir -p /var/www/html/database
    touch /var/www/html/database/database.sqlite
    chown -R www-data:www-data /var/www/html/database
    chmod -R 775 /var/www/html/database
fi

# Ensure correct permissions for storage and cache directories
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Clear stale caches
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# Run database migrations automatically on startup
php artisan migrate --force || true

# Cache routes and views
php artisan route:cache || true
php artisan view:cache || true

# Start Apache in foreground
exec apache2-foreground
