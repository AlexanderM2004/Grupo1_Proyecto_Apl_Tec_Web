# Usar una imagen de PHP con extensiones necesarias
FROM php:8.1-fpm

# Instalar dependencias necesarias
RUN apt-get update && apt-get install -y \
    unzip \
    curl \
    git \
    libzip-dev \
    && docker-php-ext-install zip pdo pdo_mysql

# Instalar Composer dentro del contenedor
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

# Establecer directorio de trabajo
WORKDIR /var/www/api

# Copiar archivos del proyecto al contenedor
COPY . .

# Asegurar permisos correctos
RUN chown -R www-data:www-data /var/www/api

# Ejecutar Composer install
RUN composer install --no-dev --optimize-autoloader

# Exponer el puerto para la app
EXPOSE 9000

# Comando por defecto
CMD ["php-fpm"]
