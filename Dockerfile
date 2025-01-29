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

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/api

# Set environment variable for Composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy composer files first to leverage Docker cache
COPY ./api/composer.json ./api/composer.lock ./

# Install dependencies
RUN composer install --no-scripts --no-autoloader

# Copy existing application directory
COPY ./api .

# Generate autoloader
RUN composer dump-autoload --optimize

# Change ownership of our applications
RUN chown -R www-data:www-data .

EXPOSE 9000
CMD ["php-fpm"]