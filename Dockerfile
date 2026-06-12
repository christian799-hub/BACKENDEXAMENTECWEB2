FROM php:8.2-apache

# Instalar las dependencias necesarias para PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev && rm -rf /var/lib/apt/lists/*

# Ahora instalar la extensión pdo_pgsql
RUN docker-php-ext-install pdo_pgsql

COPY . /var/www/html/

EXPOSE 80
