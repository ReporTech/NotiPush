FROM php:8.2-apache

# Instala dependencias y extensiones necesarias
RUN apt-get update && apt-get install -y \
    libgmp-dev \
    libsqlite3-dev \
    libzip-dev \
    zip \
    unzip \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install \
        bcmath \
        gmp \
        pdo \
        pdo_sqlite

# Habilita mod_rewrite para que funcione .htaccess
RUN a2enmod rewrite

# Permite el uso de .htaccess en Apache
RUN sed -i 's/AllowOverride None/AllowOverride All/i' /etc/apache2/apache2.conf

# Copia el código fuente al contenedor
COPY . /var/www/html/

# Asigna permisos adecuados
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Expone el puerto del contenedor
EXPOSE 80