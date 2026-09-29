FROM php:8.3-fpm-alpine AS php-base

RUN apk add --no-cache \
        nginx \
        wget \
        icu-dev \
        libzip-dev \
        postgresql-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install -j$(nproc) intl pdo_pgsql opcache pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS \
    && addgroup -g 1001 rms \
    && adduser -u 1001 -G rms -s /bin/sh -D rms

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Set to "true" (dev compose override) to include PHPUnit and other require-dev packages.
ARG INSTALL_DEV=false

# Install PHP dependencies first for better layer caching.
COPY backend/composer.json backend/composer.lock ./
RUN composer install \
        $([ "$INSTALL_DEV" = "true" ] || echo --no-dev) \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction

COPY backend/ ./
COPY docker/api/nginx-default.conf /etc/nginx/http.d/default.conf
COPY docker/api/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN rm -f .env \
    && composer install \
        $([ "$INSTALL_DEV" = "true" ] || echo --no-dev) \
        --optimize-autoloader \
        --prefer-dist \
        --no-interaction \
    && chmod +x /usr/local/bin/docker-entrypoint.sh \
    && mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
        /var/run/nginx \
    && chown -R rms:rms /var/www/html /var/lib/nginx /var/log/nginx /var/run/nginx \
    && chmod -R ug+rwx storage bootstrap/cache

USER rms

ENV APP_ENV=production
ENV LOG_CHANNEL=stderr

EXPOSE 8080

HEALTHCHECK --interval=15s --timeout=5s --retries=3 \
    CMD wget -qO- http://127.0.0.1:8080/health || exit 1

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
