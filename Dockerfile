FROM php:8.2-cli

# Instalar extensiones PHP necesarias
RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev libicu-dev libonig-dev \
    && docker-php-ext-install pdo pdo_mysql zip intl mbstring

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Directorio de trabajo
WORKDIR /var/www

# Copiar todo el proyecto
COPY . .

# Evitar problemas de memoria
ENV COMPOSER_MEMORY_LIMIT=-1

# Instalar dependencias de Symfony
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Limpiar cache Symfony
RUN php bin/console cache:clear --env=prod

# Exponer puerto
EXPOSE 8000

# Servidor PHP
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
