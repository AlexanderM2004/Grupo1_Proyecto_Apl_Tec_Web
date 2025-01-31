# Usar PHP 8.1 con FPM
FROM php:8.1-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    unzip \
    curl \
    git \
    libzip-dev \
    libpq-dev \
    postgresql \
    postgresql-client \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP
RUN docker-php-ext-install zip \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo \
    && docker-php-ext-install pdo_pgsql \
    && docker-php-ext-install pgsql

# Verificar que las extensiones están instaladas
RUN php -m | grep -i pdo_pgsql || exit 1

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

# Crear directorio de trabajo y configurar permisos
WORKDIR /var/www/api
RUN chown -R www-data:www-data /var/www

# Copiar archivos del proyecto
COPY --chown=www-data:www-data . /var/www/

# Ejecutar Composer dentro del contenedor
RUN if [ -f "composer.json" ]; then composer install --no-dev --optimize-autoloader; fi

# Exponer el puerto para PHP-FPM
EXPOSE 9000

# Comando por defecto
CMD ["php-fpm"]
