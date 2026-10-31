#!/bin/sh
set -e

php artisan migrate --force

if [ "${RUN_SEED:-false}" = "true" ]; then
    php artisan db:seed --force
fi

if [ "${SEED_DEMO:-false}" = "true" ]; then
    php artisan db:seed --class=DemoDataSeeder --force
fi

php artisan queue:work --sleep=3 --tries=1 &
php artisan schedule:work &

exec apache2-foreground
