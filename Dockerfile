FROM wyveo/nginx-php-fpm:php82

WORKDIR /var/www/html

ENV WEBROOT=/var/www/html/public

# Override nginx config for Laravel
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

COPY . .
COPY .env.example .env
RUN php -r "file_put_contents('.env', str_replace('APP_KEY=', 'APP_KEY=base64:' . base64_encode(random_bytes(32)), file_get_contents('.env')));"

RUN composer install --no-dev --no-interaction --optimize-autoloader

# Fix tempnam(): create temp dir + set in php.ini (fpm + cli). Wyveo runs PHP-FPM as nginx user.
RUN mkdir -p storage/framework/temp \
    && for ini in /etc/php/8.2/fpm/php.ini /etc/php/8.2/cli/php.ini; do \
        echo "sys_temp_dir = /var/www/html/storage/framework/temp" >> "$ini"; \
        echo "upload_tmp_dir = /var/www/html/storage/framework/temp" >> "$ini"; \
    done \
    && chown -R nginx:nginx storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chmod 777 storage/framework/temp

COPY docker/start.sh /app-bootstrap.sh
RUN chmod +x /app-bootstrap.sh

CMD ["/app-bootstrap.sh"]
