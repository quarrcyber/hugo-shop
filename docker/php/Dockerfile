FROM composer:2.8 AS vendor
WORKDIR /app
COPY services/shop/composer.json services/shop/composer.lock ./
RUN composer install --no-interaction --no-progress --prefer-dist --no-scripts
COPY services/shop ./
RUN composer dump-autoload --classmap-authoritative

FROM php:8.4-fpm-alpine
RUN apk add --no-cache icu-dev libzip-dev linux-headers curl \
    && docker-php-ext-install bcmath intl opcache pdo_mysql zip \
    && pecl install redis \
    && docker-php-ext-enable redis
WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html
COPY docker/php/php.ini /usr/local/etc/php/conf.d/hugo-shop.ini
COPY docker/php/entrypoint.sh /usr/local/bin/hugo-entrypoint
RUN chmod +x /usr/local/bin/hugo-entrypoint \
    && chown -R www-data:www-data storage bootstrap/cache
USER www-data
ENTRYPOINT ["hugo-entrypoint"]
CMD ["php-fpm", "-F"]
