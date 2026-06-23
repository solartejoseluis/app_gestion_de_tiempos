FROM php:8.4-apache

# Extensiones PHP necesarias para el proyecto
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
  && docker-php-ext-install \
    pdo \
    pdo_mysql \
    mysqli \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*

# Habilitar mod_rewrite para el Router MVC
RUN a2enmod rewrite

# Copiar configuración del virtual host
COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf

# Directorio de trabajo
WORKDIR /var/www/html

# Permisos
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
