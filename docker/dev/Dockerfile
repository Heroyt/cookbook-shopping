FROM rg.fr-par.scw.cloud/esoul-starters/php-fpm-laravel:8.5 AS base

LABEL authors="Tomáš Vojík"
LABEL maintainer="Tomáš Vojík <vojik@esoul.cz>"

# Install dcron for scheduler and su-exec for privilege dropping
RUN apk add --no-cache dcron supervisor su-exec

# Add composer and required PHP extensions
RUN install-php-extensions @composer


WORKDIR /var/www

FROM base AS app

ARG WWWGROUP

LABEL authors="Tomáš Vojík"
LABEL maintainer="Tomáš Vojík <vojik@esoul.cz>"

# Cleanup unnecessary files from base image
RUN rm -rf  \
    /var/cache/apk/*  \
    /tmp/*  \
    /usr/local/lib/php/docs/*  \
    /usr/local/lib/php/test/*  \
    /usr/local/bin/install-php-extensions

# Install Node.js (LTS) via apk
RUN apk add --no-cache nodejs npm

# Install pnpm via npm (pinned to match "packageManager" in package.json)
RUN npm install -g pnpm@11.17.0

# COPY php configuration
COPY docker/dev/php.ini /usr/local/etc/php/conf.d/app.ini
# Copy php-fpm configuration
COPY docker/dev/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
# Copy supervisor configuration for queue worker
COPY docker/dev/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/dev/supervisor-queue-worker.conf /etc/supervisor/conf.d/queue-worker.conf
COPY docker/dev/supervisor-vite.conf /etc/supervisor/conf.d/vite.conf
COPY docker/dev/supervisor-php.conf /etc/supervisor/conf.d/php.conf

# Setup cron for Laravel scheduler
RUN echo "* * * * * cd /var/www && php artisan schedule:run >> /var/www/storage/logs/scheduler.log 2>&1" > /etc/crontabs/www-data \
    && chown www-data:www-data /etc/crontabs/www-data \
    && chmod 0600 /etc/crontabs/www-data

WORKDIR /var/www

COPY docker/dev/start /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

RUN addgroup -S $WWWGROUP && \
  adduser -s /bin/sh -u 1337 -G $WWWGROUP -S sail

# Don't switch to www-data here - let entrypoint handle it after starting crond
# USER www-data

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
