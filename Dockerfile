# --- Build the React frontend ---
FROM node:22-alpine AS frontend
WORKDIR /app/frontend
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci
COPY frontend/ ./
RUN npm run build

# --- Install PHP dependencies ---
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY . .
RUN composer dump-autoload --optimize --no-dev

# --- Runtime: PHP's built-in server is plenty for a demo ---
FROM php:8.4-cli-alpine
WORKDIR /app
COPY --from=vendor /app /app
COPY --from=frontend /app/frontend/dist/index.html /app/frontend/dist/logo.svg /app/public/
COPY --from=frontend /app/frontend/dist/assets /app/public/assets
RUN rm -rf frontend && chown -R www-data:www-data storage bootstrap/cache database
COPY docker/start.sh /usr/local/bin/start
RUN chmod +x /usr/local/bin/start
USER www-data
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr DB_CONNECTION=sqlite \
    SESSION_DRIVER=array CACHE_STORE=file QUEUE_CONNECTION=sync
EXPOSE 8080
CMD ["start"]
