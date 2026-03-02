#!/bin/bash
set -e

# Wait for database to be ready
echo "Waiting for database..."
until php -r "new PDO('pgsql:host=${DB_HOST:-postgres};port=${DB_PORT:-5432};dbname=${DB_DATABASE:-laravel}', '${DB_USERNAME:-laravel}', '${DB_PASSWORD:-secret}');" 2>/dev/null; do
    sleep 2
    echo "Still waiting for database..."
done
echo "Database is ready!"

# Install composer dependencies if vendor directory is missing
if [ ! -d "vendor" ]; then
    echo "Installing composer dependencies..."
    composer install --no-interaction --optimize-autoloader
fi

# Generate app key if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    php artisan key:generate --force
fi

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Cache configuration
php artisan config:cache
php artisan route:cache

# Set permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "Starting services..."
exec "$@"
