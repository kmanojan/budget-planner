#!/bin/bash
set -e

# Always ensure database directory and database.sqlite file exist before running any artisan commands
mkdir -p /var/www/html/database
touch /var/www/html/database/database.sqlite
chown -R www-data:www-data /var/www/html/database /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/database /var/www/html/storage /var/www/html/bootstrap/cache

# Configure Apache port dynamically based on Render's PORT env var (default to 80)
PORT="${PORT:-80}"
echo "Configuring Apache to listen on port ${PORT}..."
sed -i "s/80/${PORT}/g" /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf

# Clear stale caches
php artisan config:clear || true
php artisan route:clear || true
php artisan cache:clear || true

# Run database migrations and seeders automatically on startup
php artisan migrate --force || true
php artisan db:seed --force || true

# Start Apache in foreground
exec apache2-foreground


Host dpg-d9ukugtbedkc73afj3n0-a
DB_USERNAME budget_planner_210j_user
DB_PASSWORD eqLxj6dmP34bID8wLUlTDRpGxmP3QGRr
Internal Database URL postgresql://budget_planner_210j_user:eqLxj6dmP34bID8wLUlTDRpGxmP3QGRr@dpg-d9ukugtbedkc73afj3n0-a/budget_planner_210j

