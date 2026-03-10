FROM wyveo/nginx-php-fpm:php82

WORKDIR /var/www/html

ENV WEBROOT=/var/www/html/public

# Override nginx config for Laravel
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

# Fix tempnam(): use writable temp dir
COPY docker/php-temp.ini /etc/php/8.2/fpm/conf.d/99-temp.ini

COPY . .
COPY .env.example .env
RUN php -r "file_put_contents('.env', str_replace('APP_KEY=', 'APP_KEY=base64:' . base64_encode(random_bytes(32)), file_get_contents('.env')));"

RUN composer install --no-dev --no-interaction --optimize-autoloader

RUN mkdir -p storage/framework/temp \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/start.sh /app-bootstrap.sh
RUN chmod +x /app-bootstrap.sh

CMD ["/app-bootstrap.sh"]
