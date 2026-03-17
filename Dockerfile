FROM php:8.2-cli

# Extensiones PHP necesarias + PostgreSQL
RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev libicu-dev libonig-dev libpq-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql zip intl mbstring

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copiar proyecto
COPY . .

# Evitar problemas de memoria
ENV COMPOSER_MEMORY_LIMIT=-1

# Composer sin scripts
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Exponer puerto
EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
