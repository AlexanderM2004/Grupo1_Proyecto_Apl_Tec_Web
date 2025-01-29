FROM php:8.1-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/api

# Copy composer files
COPY ./api/composer.* ./

# Set environment variables for Composer
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_HOME /var/www/.composer

# Install dependencies with specific settings
RUN composer install \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist \
    --no-autoloader

# Copy application files
COPY ./api .

# Generate optimized autoloader
RUN composer dump-autoload --optimize --classmap-authoritative

# Set permissions
RUN chown -R www-data:www-data /var/www/api \
    && chmod -R 755 /var/www/api \
    && chmod -R 775 /var/www/api/storage

# Configure error reporting
RUN echo "error_reporting = E_ALL" > /usr/local/etc/php/conf.d/error-reporting.ini \
    && echo "display_errors = On" >> /usr/local/etc/php/conf.d/error-reporting.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/error-reporting.ini \
    && echo "error_log = /var/www/api/storage/logs/php-error.log" >> /usr/local/etc/php/conf.d/error-reporting.ini

EXPOSE 9000
CMD ["php-fpm"]