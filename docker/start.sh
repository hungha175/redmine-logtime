#!/usr/bin/env bash
set -e

cd /var/www/html

php artisan config:cache
php artisan route:cache
php artisan view:cache 2>/dev/null || true

# Wyveo image uses supervisord; use default if exists, else nginx+php-fpm
if [ -f /start.sh ]; then
    exec /start.sh
elif [ -f /run.sh ]; then
    exec /run.sh
else
    nginx && exec php-fpm -F
fi
