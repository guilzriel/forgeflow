FROM composer:2.10 AS dependencies

WORKDIR /application

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-autoloader \
    --no-interaction \
    --no-progress \
    --prefer-dist

COPY app ./app

RUN composer dump-autoload \
    --no-dev \
    --classmap-authoritative \
    --no-interaction

FROM php:8.3-fpm-bookworm AS runtime

ARG APP_VERSION=development
ARG VCS_REF=development

LABEL org.opencontainers.image.title="ForgeFlow PHP-FPM"
LABEL org.opencontainers.image.description="PHP-FPM workload for ForgeFlow"
LABEL org.opencontainers.image.version="${APP_VERSION}"
LABEL org.opencontainers.image.revision="${VCS_REF}"
LABEL org.opencontainers.image.source="https://github.com/guilzriel/forgeflow"

WORKDIR /var/www/forgeflow

COPY --from=dependencies /application/vendor ./vendor
COPY app ./app
COPY public ./public
COPY docker/php-fpm/forgeflow.ini /usr/local/etc/php/conf.d/zz-forgeflow.ini
COPY docker/php-fpm/forgeflow-pool.conf /usr/local/etc/php-fpm.d/zz-forgeflow.conf

RUN chown -R www-data:www-data /var/www/forgeflow

ENV FORGEFLOW_VERSION=${APP_VERSION}

EXPOSE 9000

HEALTHCHECK --interval=10s --timeout=3s --retries=5 \
  CMD php-fpm -t || exit 1

CMD ["php-fpm", "-F"]
