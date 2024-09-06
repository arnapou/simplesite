FROM registry.gitlab.com/arnapou/docker/php:8.3-dev as build

COPY --chown=www-data:www-data . /app
RUN composer install --no-interaction --no-progress --optimize-autoloader --no-dev \
 && php -d 'phar.readonly=Off' ./build/build.php bin/simplesite.phar \
 && rm composer.json composer.lock \
 && rm -Rf build

FROM registry.gitlab.com/arnapou/docker/php:8.3-frankenphp as final

COPY --from=build /app /app
RUN sed -i -E 's#(^\s+root +[^ ]+ +).*public#\1/app/site/public#' /etc/caddy/Caddyfile
