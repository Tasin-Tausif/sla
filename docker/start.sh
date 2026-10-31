#!/bin/sh
set -e

cd /var/www/html
PORT="${PORT:-80}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9][0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

if [ -z "${APP_KEY:-}" ]; then
    if [ "${APP_ENV:-local}" = "production" ]; then
        echo "APP_KEY is required in production." >&2
        exit 1
    fi
    if [ ! -f storage/app/.app_key ]; then
        php artisan key:generate --show > storage/app/.app_key
    fi
    export APP_KEY="$(cat storage/app/.app_key)"
fi

if [ "${APP_ENV:-local}" = "production" ]; then
    for variable in DB_HOST DB_DATABASE DB_USERNAME DB_PASSWORD; do
        eval "value=\${$variable:-}"
        if [ -z "$value" ]; then
            echo "Missing required environment variable: $variable" >&2
            exit 1
        fi
    done
fi

if [ "${RUN_MIGRATIONS:-true}" != "false" ]; then
    attempts=0
    until php artisan migrate --force; do
        attempts=$((attempts + 1))
        if [ "$attempts" -ge 20 ]; then
            echo "Migrations failed after 20 attempts." >&2
            exit 1
        fi
        sleep 3
    done
fi

if [ "${RUN_SEED:-false}" = "true" ] && php -r '$pdo = new PDO("mysql:host=".getenv("DB_HOST").";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); exit(((int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()) === 0 ? 0 : 1);'; then
    php artisan db:seed --force
    if [ "${SEED_DEMO:-false}" = "true" ]; then
        php artisan db:seed --class=DemoDataSeeder --force
    fi
fi

if [ "${APP_ENV:-local}" = "production" ]; then
    php artisan config:cache
    php artisan view:cache
    php artisan event:cache
fi

if [ "${RUN_QUEUE:-true}" != "false" ]; then
    while true; do
        runuser -u www-data -- php artisan queue:work --tries=3 --timeout=3600 --sleep=3 --max-time=3600
        sleep 2
    done &
fi

if [ "${RUN_SCHEDULER:-true}" != "false" ]; then
    while true; do
        runuser -u www-data -- php artisan schedule:work
        sleep 2
    done &
fi

exec apache2-foreground
