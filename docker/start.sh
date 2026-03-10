#!/usr/bin/env bash
set -e

cd /var/www/html

php artisan config:cache
php artisan route:cache
php artisan view:cache 2>/dev/null || true

service nginx start
exec php-fpm -F
