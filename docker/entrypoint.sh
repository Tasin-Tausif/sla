#!/bin/sh
set -e

cd /var/www/html

if [ ! -f storage/app/.app_key ]; then
    if [ "${CONTAINER_ROLE:-app}" = "app" ]; then
        key="$(php artisan key:generate --show)"
        printf '%s' "$key" > storage/app/.app_key
    else
        attempts=0
        while [ ! -f storage/app/.app_key ] && [ "$attempts" -lt 120 ]; do
            sleep 1
            attempts=$((attempts + 1))
        done
        if [ ! -f storage/app/.app_key ]; then
            echo "Timed out waiting for the application key." >&2
            exit 1
        fi
    fi
fi

key="$(cat storage/app/.app_key)"
awk -v key="$key" 'BEGIN { replaced = 0 } /^APP_KEY=/ { print "APP_KEY=" key; replaced = 1; next } { print } END { if (!replaced) print "APP_KEY=" key }' .env.docker > .env

if [ "${CONTAINER_ROLE:-app}" = "app" ]; then
    attempts=0
    until php -r '$pdo = new PDO("mysql:host=".getenv("DB_HOST").";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); $pdo->query("SELECT 1");' >/dev/null 2>&1; do
        attempts=$((attempts + 1))
        if [ "$attempts" -ge 90 ]; then
            echo "Timed out waiting for MySQL." >&2
            exit 1
        fi
        sleep 1
    done

    php artisan migrate --force
    if php -r '$pdo = new PDO("mysql:host=".getenv("DB_HOST").";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); exit(((int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()) === 0 ? 0 : 1);'; then
        php artisan db:seed --force
        if [ "${DOCKER_SEED_DEMO:-true}" = "true" ]; then
            php artisan db:seed --class=DemoDataSeeder --force
        fi
    fi
    : > storage/app/.docker-ready
else
    attempts=0
    while [ ! -f storage/app/.docker-ready ] && [ "$attempts" -lt 120 ]; do
        sleep 1
        attempts=$((attempts + 1))
    done
    if [ ! -f storage/app/.docker-ready ]; then
        echo "Timed out waiting for application bootstrap." >&2
        exit 1
    fi
fi

exec "$@"
