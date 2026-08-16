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

LABEL org.opencontainers.image.title="ForgeFlow" \
      org.opencontainers.image.description="ForgeFlow Apache + PHP-FPM runtime" \
      org.opencontainers.image.version="${APP_VERSION}" \
      org.opencontainers.image.revision="${VCS_REF}"

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        apache2 \
        curl \
        iproute2 \
        procps \
    && a2dismod mpm_prefork \
    && a2enmod mpm_event proxy proxy_fcgi rewrite headers \
    && rm -f /etc/apache2/sites-enabled/000-default.conf \
    && printf 'ServerName localhost\n' > /etc/apache2/conf-available/forgeflow-servername.conf \
    && a2enconf forgeflow-servername \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/forgeflow
COPY --from=dependencies /application/vendor ./vendor
COPY app ./app
COPY public ./public
COPY docker/apache/forgeflow.conf /etc/apache2/sites-available/forgeflow.conf
COPY docker/php-fpm/forgeflow.ini /usr/local/etc/php/conf.d/zz-forgeflow.ini
COPY docker/php-fpm/forgeflow-pool.conf /usr/local/etc/php-fpm.d/zz-forgeflow.conf
COPY docker/entrypoint.sh /usr/local/bin/forgeflow-entrypoint

RUN printf 'Listen 8080\n' > /etc/apache2/ports.conf \
    && ln -s /etc/apache2/sites-available/forgeflow.conf /etc/apache2/sites-enabled/forgeflow.conf \
    && chmod 0755 /usr/local/bin/forgeflow-entrypoint \
    && chown -R www-data:www-data /var/www/forgeflow

ENV FORGEFLOW_VERSION=${APP_VERSION}
EXPOSE 8080

HEALTHCHECK --interval=10s --timeout=3s --start-period=10s --retries=6 \
    CMD curl --fail --silent --show-error http://127.0.0.1:8080/health >/dev/null || exit 1

ENTRYPOINT ["/usr/local/bin/forgeflow-entrypoint"]
