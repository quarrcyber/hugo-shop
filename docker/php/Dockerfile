FROM node:24-alpine AS assets
WORKDIR /workspace
COPY tokens.css ./tokens.css
WORKDIR /workspace/services/shop
COPY services/shop/package.json services/shop/package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY services/shop ./
RUN npm run build

FROM composer:2.8 AS vendor
WORKDIR /app
COPY services/shop/composer.json services/shop/composer.lock ./
RUN composer install --no-interaction --no-progress --prefer-dist --no-scripts
COPY services/shop ./
RUN composer dump-autoload --classmap-authoritative

FROM php:8.4-fpm-alpine
RUN apk add --no-cache icu-libs libzip sqlite-libs curl \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev sqlite-dev linux-headers \
    && docker-php-ext-install bcmath intl opcache pdo_mysql pdo_sqlite zip \
    && pecl install redis-6.3.0 \
    && docker-php-ext-enable redis \
    && apk del .build-deps
WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html
COPY --from=assets /workspace/services/shop/public/build/manifest.json /var/www/html/public/build/manifest.json
COPY docker/php/php.ini /usr/local/etc/php/conf.d/hugo-shop.ini
COPY docker/php/entrypoint.sh /usr/local/bin/hugo-entrypoint
RUN chmod +x /usr/local/bin/hugo-entrypoint \
    && chown -R www-data:www-data storage bootstrap/cache
USER www-data
ENTRYPOINT ["hugo-entrypoint"]
CMD ["php-fpm", "-F"]
