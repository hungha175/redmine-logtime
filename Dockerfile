FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    nginx \
    libzip-dev \
    zip \
    unzip \
    curl \
    git \
    sqlite-libs \
    && docker-php-ext-install pdo pdo_sqlite zip bcmath \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && apk del libzip-dev

WORKDIR /var/www/html

COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --optimize-autoloader

COPY . .
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80
CMD ["/start.sh"]
