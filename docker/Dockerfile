FROM ubuntu:24.04

# Install Apache and PHP 8.4 with all extensions
RUN apt-get update && apt-get install -y \
    software-properties-common \
    && add-apt-repository ppa:ondrej/php -y \
    && apt-get update && apt-get install -y \
    apache2 \
    php8.4 \
    libapache2-mod-php8.4 \
    php8.4-sqlite3 \
    php8.4-mbstring \
    php8.4-gd \
    php8.4-intl \
    php8.4-bcmath \
    php8.4-xml \
    php8.4-curl \
    php8.4-zip \
    php8.4-pdo-mysql \
    php8.4-mysqli \
    php8.4-xdebug \
    git \
    curl \
    zip \
    unzip \
    sqlite3 \
    cron \
    && rm -rf /var/lib/apt/lists/*

# Configure Xdebug for code coverage
RUN echo "xdebug.mode=coverage" >> /etc/php/8.4/cli/conf.d/20-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /etc/php/8.4/cli/conf.d/20-xdebug.ini

# Enable Apache mod_rewrite
RUN a2enmod rewrite php8.4

# Set working directory
WORKDIR /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files (context is parent directory)
COPY . /var/www/html

# Install PHP dependencies (with dev for local development)
RUN composer install --optimize-autoloader --no-interaction

# Create necessary directories with proper permissions
RUN mkdir -p tmp/cache/models tmp/cache/persistent tmp/cache/views tmp/sessions tmp/tests logs coverage \
    && chmod -R 775 tmp logs coverage \
    && chown -R www-data:www-data tmp logs coverage \
    && ln -sf /var/www/html/coverage /var/www/html/webroot/coverage

# Copy Apache vhost configuration
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# Enable the site
RUN a2ensite 000-default

# Configure Apache environment variables
ENV APACHE_RUN_USER=www-data \
    APACHE_RUN_GROUP=www-data \
    APACHE_LOG_DIR=/var/log/apache2 \
    APACHE_PID_FILE=/var/run/apache2.pid \
    APACHE_LOCK_DIR=/var/lock/apache2

# Create app_local.php if it doesn't exist
RUN if [ ! -f config/app_local.php ]; then \
    cp config/app_local.example.php config/app_local.php && \
    sed -i "s/'url' => env('DATABASE_URL', null)/'url' => 'sqlite:\/\/\/var\/www\/html\/tmp\/app.sqlite'/" config/app_local.php; \
    fi

# Setup cron job for cache clearing
COPY docker/cron/cache-clear /etc/cron.d/cache-clear
RUN chmod 0644 /etc/cron.d/cache-clear && \
    crontab /etc/cron.d/cache-clear && \
    touch /var/log/cron.log

# Expose port 80
EXPOSE 80

# Create startup script to fix permissions, run cron and apache
RUN echo '#!/bin/bash\n\
echo "Fixing cache permissions..."\n\
chown -R www-data:www-data /var/www/html/tmp/cache 2>/dev/null || true\n\
chmod -R 775 /var/www/html/tmp/cache 2>/dev/null || true\n\
find /var/www/html/tmp/cache -type d -exec chmod 775 {} \\; 2>/dev/null || true\n\
find /var/www/html/tmp/cache -type f -exec chmod 664 {} \\; 2>/dev/null || true\n\
echo "Starting cron..."\n\
service cron start\n\
echo "Starting Apache..."\n\
apachectl -D FOREGROUND' > /start.sh && \
    chmod +x /start.sh

# Start cron and Apache
CMD ["/start.sh"]
