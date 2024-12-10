FROM registry.gitlab.com/arnapou/docker/php:8.4-dev AS build

COPY --chown=www-data:www-data . /app
RUN composer run build:phar

FROM registry.gitlab.com/arnapou/docker/php:8.4-frankenphp AS final

COPY --from=build /app/bin/simplesite.phar /srv/simplesite.phar
COPY --from=build /app/bin/simplesite.sh   /srv/simplesite.sh

CMD ["/srv/simplesite.sh"]
