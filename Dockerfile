FROM php:8.2-apache

# Install system dependencies & PHP extensions required by Laravel (MySQL, PostgreSQL, SQLite)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    libpq-dev \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql pdo_sqlite mbstring exif pcntl bcmath gd

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set Apache document root to Laravel's public folder
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/conf-available/*.conf

# Set environment variables from InfinityFree MySQL credentials
ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_KEY=base64:r5aHDWbzTFeONFIUHS1p2y1jcPA0uFPoGAuUVevbbco= \
    LOG_CHANNEL=stderr \
    SESSION_DRIVER=cookie \
    CACHE_STORE=array \
    DB_CONNECTION=mysql \
    DB_HOST=sql209.infinityfree.com \
    DB_PORT=3306 \
    DB_DATABASE=if0_42643536_budget_planner \
    DB_USERNAME=if0_42643536 \
    DB_PASSWORD=Tharsi1106

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html

# Create database directory and touch database.sqlite file so SQLite commands never fail
RUN mkdir -p /var/www/html/database && \
    touch /var/www/html/database/database.sqlite && \
    chown -R www-data:www-data /var/www/html/database && \
    chmod -R 775 /var/www/html/database

# Install Composer dependencies
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# Set permissions for storage and bootstrap cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
