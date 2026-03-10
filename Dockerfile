FROM wyveo/nginx-php-fpm:php82

WORKDIR /var/www/html

ENV WEBROOT=/var/www/html/public

# Override nginx config for Laravel
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --optimize-autoloader

COPY . .
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

CMD ["/start.sh"]
