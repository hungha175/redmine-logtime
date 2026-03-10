FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    nginx \
    libzip-dev \
    zip \
    unzip \
    curl \
    git \
    && docker-php-ext-install pdo pdo_sqlite zip pcntl bcmath opcache \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-interaction --optimize-autoloader

COPY . .
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80
CMD ["/start.sh"]
