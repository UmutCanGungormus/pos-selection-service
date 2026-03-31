#!/bin/sh
set -e

if [ ! -f /var/www/vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

php artisan config:cache
php artisan route:cache
php artisan event:cache

exec "$@"