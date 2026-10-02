FROM php:8.2-apache

# Install system dependencies and SSL certificates
RUN apt-get update && apt-get install -y \
    libssl-dev \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache rewrite rules and response headers
RUN a2enmod rewrite headers

# Set Apache document root to project root
ENV APACHE_DOCUMENT_ROOT /var/www/html

# Update Apache default site config
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow .htaccess overrides in document root
RUN printf '%s\n' '<Directory /var/www/html>' \
    'Options FollowSymLinks' \
    'AllowOverride All' \
    'Require all granted' \
    '</Directory>' > /etc/apache2/conf-available/campusfix.conf \
    && a2enconf campusfix

# Copy project files into the container
COPY . /var/www/html/

# Fail the build if versioned database migrations were omitted from the image.
RUN test -f /var/www/html/database/migrations/001_baseline.sql \
    && test -f /var/www/html/database/migrations.php

# Fix permissions
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

# Expose port 80
EXPOSE 80

# Start Apache in foreground
CMD ["sh", "-c", "php /var/www/html/database/bootstrap.php && exec apache2-foreground"]
