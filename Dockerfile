FROM php:8.2-cli

# Instalar dependencias
RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copiar proyecto
COPY . .

# Instalar dependencias
RUN composer install --no-dev --optimize-autoloader

# Optimizar Symfony
RUN php bin/console cache:clear --env=prod

# Exponer puerto
EXPOSE 8000

# Servidor para Render
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
