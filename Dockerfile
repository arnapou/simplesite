FROM registry.gitlab.com/arnapou/docker/php:8.4-dev as build

COPY --chown=www-data:www-data . /app
RUN composer run build:phar



FROM registry.gitlab.com/arnapou/docker/php:8.4-frankenphp as demo

COPY --from=build /app/bin /app/bin
COPY --from=build /app/demo /app/demo
RUN sed -i -E 's#(^\s+base_path_admin.*)$#//\1#' /app/demo/public/index.php
RUN sed -i -E 's#(^\s+root +[^ ]+ +).*public#\1/app/demo/public#' /etc/caddy/Caddyfile



FROM registry.gitlab.com/arnapou/docker/php:8.4-frankenphp AS final

COPY --from=build /app/bin/simplesite.phar /srv/simplesite.phar
COPY --from=build /app/bin/simplesite.sh   /srv/simplesite.sh

RUN mkdir -p /app/data \
 && mkdir -p /app/log \
 && mkdir -p /app/pages \
 && mkdir -p /app/public \
 && mkdir -p /app/src \
 && mkdir -p /app/templates \
 && chown www-data:www-data /app/*

CMD ["/srv/simplesite.sh"]
