FROM composer:2.8 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist

FROM php:8.4-cli-alpine
RUN apk add --no-cache curl curl-dev \
    && docker-php-ext-install curl
WORKDIR /app
COPY --from=vendor /app/vendor ./vendor
COPY public ./public
RUN chown -R nobody:nobody /app
USER nobody
EXPOSE 8081
CMD ["php", "-S", "0.0.0.0:8081", "-t", "public"]
