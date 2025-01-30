# Usar PHP 8.1 con FPM
FROM php:8.1-fpm

# Instalar dependencias necesarias
RUN apt-get update && apt-get install -y \
    unzip \
    curl \
    git \
    libzip-dev \
    && docker-php-ext-install zip pdo pdo_mysql

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

# Crear directorio de trabajo en el contenedor
WORKDIR /var/www/api

# Copiar archivos del proyecto al contenedor
COPY . /var/www/

# Ajustar permisos de los archivos copiados
RUN chown -R www-data:www-data /var/www/api

# Ejecutar Composer dentro del contenedor
RUN if [ -f "composer.json" ]; then composer install --no-dev --optimize-autoloader; fi

# Exponer el puerto para PHP-FPM
EXPOSE 9000

# Comando por defecto
CMD ["php-fpm"]
