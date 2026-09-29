#!/bin/sh
set -eu

if [ "${1:-}" = "php-fpm" ]; then
  php artisan optimize:clear
  php artisan migrate --force
  if [ "$(php artisan db:show --json | grep -c '\"table\": \"users\"')" -gt 0 ]; then
    USER_COUNT=$(php -r "require 'vendor/autoload.php'; \$app = require_once 'bootstrap/app.php'; \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo \Illuminate\Support\Facades\DB::table('users')->count();")
    if [ "$USER_COUNT" -eq 0 ]; then
      php artisan db:seed --force
    fi
  else
    php artisan db:seed --force
  fi
  php artisan storage:link >/dev/null 2>&1 || true

  if [ "${APP_ENV:-production}" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
  fi
fi

exec "$@"
