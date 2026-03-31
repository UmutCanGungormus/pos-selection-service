#!/bin/sh
set -e

if [ -z "$APP_KEY" ]; then
    APP_KEY="base64:$(head -c 32 /dev/urandom | base64)"
    export APP_KEY
    # Persist to .env so the key survives container restarts
    if [ -f /var/www/.env ]; then
        sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|" /var/www/.env
    fi
fi

# Wait for MySQL to accept connections before running any artisan command
if [ -n "$DB_HOST" ]; then
    echo "Waiting for MySQL at $DB_HOST:${DB_PORT:-3306}..."
    retries=0
    until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT:-3306}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
        retries=$((retries + 1))
        if [ $retries -ge 30 ]; then
            echo "MySQL not reachable after 30 attempts, proceeding anyway..."
            break
        fi
        sleep 2
    done
    echo "MySQL is ready."
fi

# Ensure cache directory is writable (host volume mount may have wrong permissions)
mkdir -p /var/www/bootstrap/cache
chmod -R 775 /var/www/bootstrap/cache
chown -R www-data:www-data /var/www/bootstrap/cache

php artisan config:cache
php artisan route:cache
php artisan event:cache

exec "$@"
