# syntax=docker/dockerfile:1
#
# The container build of an AS application.
#
# Copy this file to the root of the application repository as `Dockerfile`, beside the
# AS submodule. Copy AS/docker/dockerignore beside it as `.dockerignore`. gcgov/deploy's
# build workflow, called with `kind: api`, builds the two targets below and publishes
# them as one Release:
#
#   php    PHP-FPM running the application, as www-data
#   nginx  nginx serving www/, sending every other request to php
#
# Every file this Dockerfile takes from AS/docker/ comes from the AS submodule, so a
# submodule update changes the PHP and nginx settings without an edit here. Edit this copy
# only for the two lists below. AS/docker/README.md covers the rest.
#
# Build both by hand:
#   docker build --target php   -t <app>-php   --build-arg APP_VERSION=$(git describe --tags --always) .
#   docker build --target nginx -t <app>-nginx .

# The PHP extensions the application needs, by the names install-php-extensions takes.
# AS reads SQL Server through pdo_sqlsrv, and mPDF needs gd. Add what the application
# uses, such as imap, mongodb, soap or intl.
ARG AS_PHP_EXTENSIONS="opcache pdo_sqlsrv pdo_mysql ldap gd zip"

# The directories, relative to the application root, that the application writes while it
# runs. Every other file in the image belongs to root and is read-only to the application.
# cache/ holds AS's output cache. Add the application's own, such as www/receipts.
ARG AS_WRITABLE_DIRS="cache var/tmp"

# ---- base: PHP-FPM with the extensions and the AS settings ----
# Debian, not Alpine: Microsoft's SQL Server ODBC driver ships for Debian, and AS's
# tools::sql() relies on glibc's iconv transliteration.
FROM php:8.4-fpm-bookworm AS base
ARG AS_PHP_EXTENSIONS
# PHP takes its character locale from LANG. In the image's default C locale, iconv's
# transliteration turns every accented letter into ?, so tools::sql() would store José as
# Jos?. C.UTF-8 turns it into Jose, and keeps . as the decimal point.
ENV LANG=C.UTF-8
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions ${AS_PHP_EXTENSIONS}

# The base image ships no php.ini and a pool that expects a root master process. Both are
# replaced. See the comments in each file.
COPY AS/docker/php/conf.d/as.ini /usr/local/etc/php/conf.d/zz-as.ini
COPY AS/docker/php/php-fpm.d/zz-as.conf /usr/local/etc/php-fpm.d/zz-as.conf
RUN set -eux; \
    rm -f /usr/local/etc/php-fpm.d/www.conf /usr/local/etc/php-fpm.d/www.conf.default; \
    install -d -o www-data -g www-data -m 0700 /var/lib/php/sessions
WORKDIR /var/www/app

# ---- build: the steps update-production.ps1 runs on an IIS server ----
FROM base AS build
ARG AS_ENV=prod
ARG APP_VERSION=unknown
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends git unzip; \
    rm -rf /var/lib/apt/lists/*
COPY . /var/www/app
RUN sh AS/docker/build-release.sh "$AS_ENV" "$APP_VERSION"

# ---- php: the application image ----
FROM base AS php
ARG APP_VERSION=unknown
ARG AS_WRITABLE_DIRS
ENV APP_VERSION=${APP_VERSION}
COPY --from=build /var/www/app /var/www/app
RUN set -eu; \
    for dir in ${AS_WRITABLE_DIRS}; do \
        case "$dir" in /*|*..*) echo "AS_WRITABLE_DIRS: '$dir' must be a path inside the application" >&2; exit 1 ;; esac; \
        mkdir -p "$dir"; \
        chown -R www-data:www-data "$dir"; \
    done
# Secrets are never in the image. gcgov/deploy mounts them at runtime, and
# AS_CONFIG_LOCAL_FILE names the file. PHP errors go to stderr, which is the container's log.
USER www-data
EXPOSE 9000
CMD ["php-fpm"]

# ---- nginx: serves www/ and sends everything else to php ----
FROM nginx:1.30-alpine AS nginx
COPY AS/docker/nginx/as-fastcgi-params /etc/nginx/as-fastcgi-params
COPY AS/docker/nginx/default.conf.template /etc/nginx/as/default.conf.template
COPY --chmod=0755 AS/docker/nginx/40-as-render-config.sh /docker-entrypoint.d/40-as-render-config.sh
# 40-as-render-config.sh writes the site at container start, from the variables below.
RUN rm -f /etc/nginx/conf.d/default.conf
# Only the web root. nginx has no reason to read the application's code or config.
COPY www /var/www/app/www

# PHP_FPM_HOST is the php service. AS_BASE_PATH is the path the application answers
# under, such as /landfill, or empty for the root of its host name.
ENV PHP_FPM_HOST=php:9000 \
    AS_BASE_PATH="" \
    HEALTH_URL=http://127.0.0.1/healthz

# /healthz runs AS/bin/health.php in the php container, so this checks nginx, PHP-FPM and
# the application's configuration together. 127.0.0.1, not localhost: the site listens on
# IPv4 only, and localhost resolves to ::1 first in Alpine.
HEALTHCHECK --interval=15s --timeout=3s --start-period=20s --retries=3 \
    CMD wget --quiet --tries=1 --spider "$HEALTH_URL" || exit 1
