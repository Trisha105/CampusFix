FROM composer:2 AS php_dependencies
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

FROM php:8.4-apache-bookworm

# The final image requires verified TLS, PDO MySQL, mbstring and cURL.
RUN apt-get update && apt-get install -y --no-install-recommends libcurl4-openssl-dev libonig-dev ca-certificates \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql curl mbstring \
    && php -r 'exit(extension_loaded("pdo_mysql") && extension_loaded("mbstring") && extension_loaded("curl") && extension_loaded("fileinfo") ? 0 : 1);' \
    && a2enmod rewrite headers

RUN printf '%s\n' '<Directory /var/www/html>' \
    'Options -Indexes +FollowSymLinks' \
    'AllowOverride All' \
    'Require all granted' \
    '</Directory>' > /etc/apache2/conf-available/campusfix.conf \
    && a2enconf campusfix

WORKDIR /var/www/html

# Explicit allowlist keeps local SQL/demo accounts and setup endpoints out.
COPY *.php .htaccess ./
COPY admin/ ./admin/
COPY assets/ ./assets/
COPY config/ ./config/
COPY includes/ ./includes/
COPY database/bootstrap.php database/migrations.php database/migrate.php database/media_cleanup.php ./database/
COPY database/migrations/ ./database/migrations/
COPY docker/start.sh /usr/local/bin/campusfix-start
COPY --from=php_dependencies /app/vendor ./vendor/

RUN printf '%s\n' 'upload_max_filesize=5M' 'post_max_size=18M' > /usr/local/etc/php/conf.d/campusfix-uploads.ini \
    && test -f database/migrations/003_activity.sql \
    && test -f vendor/autoload.php \
    && chmod 755 /usr/local/bin/campusfix-start \
    && find /var/www/html -path /var/www/html/vendor -prune -o -name '*.php' -print0 | xargs -0 -n 1 php -l

ENV PORT=10000 APP_BASE_URL=/
EXPOSE 10000
CMD ["/usr/local/bin/campusfix-start"]
