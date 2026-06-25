#!/bin/sh
set -e

cd /var/www

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache

if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    php artisan key:generate --force
fi

# Cache config, routes, and events for throughput (no per-request bootstrap).
# Regenerated on every container start, so .env edits apply after a restart.
php artisan config:cache
php artisan route:cache
php artisan event:cache

exec "$@"