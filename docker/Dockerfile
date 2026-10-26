FROM php:8.2-cli

WORKDIR /app

# Instalar dependencias del sistema y Composer
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar archivos del proyecto
COPY . .

# Instalar dependencias de Composer (si las hubiera)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

CMD ["php", "cli.php"]