#!/bin/sh
set -eu

if [ "${1:-}" = "php-fpm" ]; then
  php artisan migrate --force
  php artisan db:seed --force
  php artisan storage:link >/dev/null 2>&1 || true
  php artisan config:cache
  php artisan route:cache
fi

exec "$@"
