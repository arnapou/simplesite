FROM registry.gitlab.com/arnapou/docker/php:8.4-dev as build

COPY --chown=www-data:www-data . /app
RUN composer run build:phar

FROM registry.gitlab.com/arnapou/docker/php:8.4-frankenphp as final

COPY --from=build /app /app
RUN sed -i -E 's#(^\s+root +[^ ]+ +).*public#\1/app/demo/public#' /etc/caddy/Caddyfile
