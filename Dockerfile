FROM php:8.2-cli

# Instalar extensiones necesarias y PostgreSQL
RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev libicu-dev libonig-dev libpq-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql zip intl mbstring \
    && apt-get clean

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Directorio de trabajo
WORKDIR /var/www

# Copiar proyecto
COPY . .

# Evitar problemas de memoria con Composer
ENV COMPOSER_MEMORY_LIMIT=-1

# Instalar dependencias de PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copiar entrypoint
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Exponer puerto
EXPOSE 8000

# Arrancar entrypoint
CMD ["/entrypoint.sh"]
