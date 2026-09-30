FROM php:8.4-apache-bookworm

# The official image provides PDO, mbstring, and the system CA bundle.
RUN docker-php-ext-install -j"$(nproc)" pdo_mysql \
    && php -r 'exit(extension_loaded("pdo_mysql") && extension_loaded("mbstring") ? 0 : 1);' \
    && a2enmod rewrite headers

# Enable the application's .htaccess rules.
RUN printf '%s\n' '<Directory /var/www/html>' \
    'Options -Indexes +FollowSymLinks' \
    'AllowOverride All' \
    'Require all granted' \
    '</Directory>' > /etc/apache2/conf-available/campusfix.conf \
    && a2enconf campusfix

WORKDIR /var/www/html

# Copy only application files. Demo accounts and setup endpoints stay out.
COPY *.php .htaccess ./
COPY admin/ ./admin/
COPY assets/ ./assets/
COPY config/ ./config/
COPY includes/ ./includes/
COPY database/bootstrap.php database/schema.sql ./database/
COPY docker/start.sh /usr/local/bin/campusfix-start

# Check PHP syntax during image builds before any database is contacted.
RUN chmod 755 /usr/local/bin/campusfix-start \
    && find /var/www/html -name '*.php' -print0 | xargs -0 -n 1 php -l

ENV PORT=10000 APP_BASE_URL=/
EXPOSE 10000
CMD ["/usr/local/bin/campusfix-start"]
