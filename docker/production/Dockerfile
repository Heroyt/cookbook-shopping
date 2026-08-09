FROM rg.fr-par.scw.cloud/esoul-starters/php-fpm-laravel:8.5 AS base

LABEL authors="Tomáš Vojík"
LABEL maintainer="Tomáš Vojík <vojik@esoul.cz>"

# Install dcron for scheduler
RUN apk add --no-cache dcron

# Add composer and required PHP extensions
RUN install-php-extensions @composer


FROM base AS php_builder

# Selectively copy application files to leverage Docker cache
COPY app /var/www/app
COPY bootstrap /var/www/bootstrap
COPY config /var/www/config
COPY database /var/www/database
COPY public /var/www/public
COPY resources /var/www/resources
COPY routes /var/www/routes
COPY storage /var/www/storage
COPY artisan /var/www/artisan
COPY composer.json /var/www/composer.json
COPY composer.lock /var/www/composer.lock
COPY pnpm-workspace.yaml /var/www/pnpm-workspace.yaml
COPY pnpm-lock.yaml /var/www/pnpm-lock.yaml
COPY package.json /var/www/package.json
COPY vite.config.ts /var/www/vite.config.ts
COPY tsconfig.json /var/www/tsconfig.json
COPY .npmrc /var/www/.npmrc
COPY .env.docker /var/www/.env

RUN mkdir -p /var/www/storage  \
    /var/www/storage/framework/views  \
    /var/www/storage/framework/cache  \
    /var/www/storage/framework/testing  \
    /var/www/storage/framework/sessions  \
    /var/www/bootstrap/cache  \
    /var/www/resources/views \
    && chown -R www-data:www-data /var/www

WORKDIR /var/www

RUN composer install --no-dev --optimize-autoloader && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan storage:link && \
    php artisan wayfinder:generate

FROM node:lts-alpine AS node_builder

ENV PNPM_HOME="/pnpm"
ENV PATH="$PNPM_HOME:$PATH"
RUN corepack enable && corepack prepare pnpm@latest --activate

# Copy only package files to leverage Docker cache
WORKDIR /var/www
COPY package.json pnpm-lock.yaml vite.config.ts ./
COPY resources ./resources

#FROM node_builder AS node_production
# Use --no-frozen-lockfile to handle cross-platform builds
#RUN --mount=type=cache,id=pnpm,target=/pnpm/store pnpm install --prod --no-frozen-lockfile

FROM php_builder AS node_build
# Copy the entire /usr/local directory from node_builder to ensure all Node.js components work
# This includes node binary, npm, corepack and all their dependencies
COPY --from=node_builder /usr/local /usr/local

ENV PNPM_HOME="/pnpm"
ENV PATH="$PNPM_HOME:$PATH"

# Copy resources and build assets
COPY --from=php_builder /var/www/resources/js/actions /var/www/resources/js/actions
COPY --from=php_builder /var/www/resources/js/routes /var/www/resources/js/routes
COPY --from=php_builder /var/www/resources/js/wayfinder /var/www/resources/js/wayfinder

# Use --no-frozen-lockfile to allow pnpm to resolve platform-specific packages
RUN --mount=type=cache,id=pnpm,target=/pnpm/store pnpm install --no-frozen-lockfile && \
    pnpm run build

FROM base AS app

LABEL authors="Tomáš Vojík"
LABEL maintainer="Tomáš Vojík <vojik@esoul.cz>"

# Cleanup unnecessary files from base image
RUN rm -rf  \
    /var/cache/apk/*  \
    /tmp/*  \
    /usr/local/lib/php/docs/*  \
    /usr/local/lib/php/test/*  \
    /usr/local/bin/install-php-extensions

# Copy application files from builder
COPY --from=php_builder --chown=www-data:www-data /var/www /var/www
#COPY --from=node_production --chown=www-data:www-data /var/www/node_modules /var/www/node_modules
COPY --from=node_build --chown=www-data:www-data /var/www/public/build /var/www/public/build

# COPY php configuration
COPY docker/production/php.ini /usr/local/etc/php/conf.d/app.ini
# Copy php-fpm configuration
COPY docker/production/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Ensure proper permissions for storage and bootstrap/cache
RUN mkdir -p /var/www/storage/framework/cache  \
    /var/www/storage/framework/sessions  \
    /var/www/storage/framework/views \
    && mkdir -p /var/www/storage/logs \
    && mkdir -p /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage \
    && chown -R www-data:www-data /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache

# Setup cron for Laravel scheduler
RUN echo "* * * * * cd /var/www && php artisan schedule:run >> /var/www/storage/logs/scheduler.log 2>&1" > /etc/crontabs/www-data \
    && chown www-data:www-data /etc/crontabs/www-data \
    && chmod 0600 /etc/crontabs/www-data

WORKDIR /var/www

COPY docker/production/start /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Don't switch to www-data here - let entrypoint handle it after starting crond
# USER www-data

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]