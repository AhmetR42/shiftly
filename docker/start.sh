#!/bin/sh
set -e
# SQLite lives inside the container, so the demo data resets on every deploy.
touch database/database.sqlite
# Render can't generate a Laravel-style key, so make one if none was set.
if [ -z "$APP_KEY" ]; then export APP_KEY="$(php artisan key:generate --show)"; fi
php artisan config:cache
php artisan migrate --force
php artisan db:seed --force
# Prune old demo teams in the background instead of a separate cron service.
(while true; do php artisan demo:prune >/dev/null 2>&1; sleep 3600; done) &
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}" --no-reload
