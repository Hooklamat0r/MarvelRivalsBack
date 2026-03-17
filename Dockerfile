FROM php:8.2-cli

# Extensiones PHP necesarias
RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev libicu-dev libonig-dev \
    && docker-php-ext-install pdo pdo_mysql zip intl mbstring

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copiar todo el proyecto
COPY . .

# Evitar problemas de memoria
ENV COMPOSER_MEMORY_LIMIT=-1

# Instalar dependencias
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Optimizar Symfony
RUN php bin/console cache:clear --env=prod

# Exponer puerto
EXPOSE 8000

# Servidor para Render
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
