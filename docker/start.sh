#!/usr/bin/env bash
set -e

cd /var/www/html

# Render uses PORT env - update nginx config
sed -i "s/listen 80;/listen ${PORT:-80};/" /etc/nginx/conf.d/default.conf

# Ensure storage dirs exist (nginx user for wyveo)
mkdir -p storage/framework/{cache,sessions,views,temp} storage/logs
chown -R nginx:nginx storage bootstrap/cache 2>/dev/null || true

export TMPDIR=/var/www/html/storage/framework/temp

php artisan config:cache
php artisan migrate --force
php artisan route:cache
php artisan view:cache 2>/dev/null || true

# Start nginx (background) then php-fpm (foreground)
nginx
# Find php-fpm binary
FPM=$(find /usr/sbin /usr/bin -name "php-fpm*" -type f 2>/dev/null | head -1)
[ -n "$FPM" ] && exec "$FPM" --nodaemonize
exec php-fpm --nodaemonize
