#!/usr/bin/env bash
set -e

cd /var/www/html

# Render uses PORT env - update nginx config
sed -i "s/listen 80;/listen ${PORT:-80};/" /etc/nginx/conf.d/default.conf

# Ensure storage dirs exist
mkdir -p storage/framework/{cache,sessions,views} storage/logs

php artisan config:cache
php artisan route:cache
php artisan view:cache 2>/dev/null || true

# Wyveo image uses supervisord
exec /usr/bin/supervisord -n
