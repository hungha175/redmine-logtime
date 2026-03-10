#!/usr/bin/env sh
set -e

cd /var/www/html

php artisan config:cache
php artisan route:cache
php artisan view:cache 2>/dev/null || true

nginx
exec php-fpm -F
