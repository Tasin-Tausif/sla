FROM composer:2 AS vendor

WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader

FROM node:22-alpine AS assets

WORKDIR /var/www/html
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY public ./public
COPY storage/framework/views ./storage/framework/views
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY --from=vendor /var/www/html/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

FROM php:8.3-fpm-alpine AS app

COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql mbstring intl zip bcmath pcntl opcache exif
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /var/www/html/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /var/www/html/public/build ./public/build
COPY --chown=www-data:www-data .env.docker .env.docker
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-sla.ini

RUN composer dump-autoload --optimize \
    && php artisan package:discover \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
        storage/logs storage/app/private storage/app/public storage/app/samples \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

USER www-data
EXPOSE 9000
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]

FROM nginx:1.27-alpine AS web

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public

FROM app AS production

USER root
ENTRYPOINT []
RUN apk add --no-cache nginx supervisor gettext ca-certificates \
    && mkdir -p /run/nginx /var/log/supervisor
COPY docker/production/nginx.conf.template /etc/nginx/nginx.conf.template
COPY docker/production/supervisord.conf /etc/supervisord.conf
COPY docker/production/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
        storage/logs storage/app/private storage/app/public storage/app/samples \
    && chown -R www-data:www-data storage bootstrap/cache
EXPOSE 10000
CMD ["/usr/local/bin/start.sh"]
