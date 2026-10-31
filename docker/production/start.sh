#!/bin/sh
set -e

: "${PORT:=10000}"

for variable in APP_KEY DB_HOST DB_DATABASE DB_USERNAME DB_PASSWORD; do
    eval "value=\${$variable:-}"
    if [ -z "$value" ]; then
        echo "Missing required environment variable: $variable" >&2
        exit 1
    fi
done

envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

if [ "${FILESYSTEM_DISK:-local}" = "public" ]; then
    php artisan storage:link --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
fi

if [ "${RUN_SEED:-false}" = "true" ] && php -r '$pdo = new PDO("mysql:host=".getenv("DB_HOST").";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); exit(((int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()) === 0 ? 0 : 1);'; then
    php artisan db:seed --force
    if [ "${DOCKER_SEED_DEMO:-false}" = "true" ]; then
        php artisan db:seed --class=DemoDataSeeder --force
    fi
fi

exec supervisord -c /etc/supervisord.conf
