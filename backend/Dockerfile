# ============================================================
# Dockerfile do Backend (Laravel 13 / PHP 8.4)
#
# Multi-stage build com imagem Alpine para manter o tamanho
# final abaixo de 100MB. Inclui as extensões obrigatórias
# do Laravel e um entrypoint que executa migrations + seed
# automaticamente na primeira inicialização.
# ============================================================

# --- Stage 1: Instalar dependências PHP via Composer ---
FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# --- Stage 2: Imagem final de produção ---
FROM php:8.4-cli-alpine

# Extensões necessárias para Laravel + MySQL
RUN apk add --no-cache \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
        ca-certificates \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        zip \
        intl \
        pcntl \
    && rm -rf /var/cache/apk/*

WORKDIR /var/www/html

# Copiar código-fonte e dependências do stage anterior
COPY . .
COPY --from=vendor /app/vendor ./vendor

# Permissões do storage e cache do Laravel
RUN mkdir -p storage/logs \
             storage/framework/cache \
             storage/framework/sessions \
             storage/framework/views \
             bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Criar .env vazio (variáveis de produção vêm do Render Environment)
RUN touch .env

# Expor a porta do servidor embutido do PHP
EXPOSE 8000

# Entrypoint: exporta env vars do Render para .env, roda migrations e inicia o servidor
CMD sh -c '\
    env | grep -E "^(APP_|DB_|SESSION_|SANCTUM_|FRONTEND_|MYSQL_|PORT=|INGEST_)" > /var/www/html/.env && \
    php artisan migrate --force && \
    php artisan serve --host=0.0.0.0 --port=8000'
