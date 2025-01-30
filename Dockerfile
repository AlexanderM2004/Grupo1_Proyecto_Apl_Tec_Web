FROM php:8.1-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev

# Limpiar caché
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /var/www/api

# Crear directorios necesarios
RUN mkdir -p /var/www/api/storage/logs \
    && mkdir -p /var/www/api/storage/framework/cache \
    && mkdir -p /var/www/api/storage/framework/sessions \
    && mkdir -p /var/www/api/storage/framework/views

# Variables de entorno para Composer
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_HOME /var/www/.composer

# Copiar archivos composer
COPY ./api/composer.* ./

# Instalar dependencias
RUN composer install \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist \
    --no-autoloader

# Copiar código de la aplicación
COPY ./api .

# Establecer permisos
RUN chown -R www-data:www-data /var/www/api \
    && chmod -R 755 /var/www/api \
    && chmod -R 775 /var/www/api/storage

# Generar autoloader optimizado
RUN composer dump-autoload --optimize --classmap-authoritative

# Configurar PHP
RUN echo "error_reporting = E_ALL" > /usr/local/etc/php/conf.d/error-reporting.ini \
    && echo "display_errors = Off" >> /usr/local/etc/php/conf.d/error-reporting.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/error-reporting.ini \
    && echo "error_log = /var/www/api/storage/logs/php-error.log" >> /usr/local/etc/php/conf.d/error-reporting.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/error-reporting.ini \
    && echo "upload_max_filesize = 64M" >> /usr/local/etc/php/conf.d/error-reporting.ini \
    && echo "post_max_size = 64M" >> /usr/local/etc/php/conf.d/error-reporting.ini \
    && echo "max_execution_time = 180" >> /usr/local/etc/php/conf.d/error-reporting.ini

EXPOSE 9000
CMD ["php-fpm"]