# ============================================================
# Dockerfile do Backend (Laravel 13 / PHP 8.4)
#
# Multi-stage build com imagem Alpine para manter o tamanho
# final enxuto. Inclui as extensões obrigatórias do Laravel,
# nginx como web server e php-fpm como processador PHP.
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
FROM php:8.4-fpm-alpine

# Extensões necessárias para Laravel + MySQL
RUN apk add --no-cache \
        nginx \
        supervisor \
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

# Criar usuário não-root www-data se não existir
RUN set -eux; \
    if ! id -u www-data >/dev/null 2>&1; then \
        adduser -u 82 -D -S -G www-data www-data; \
    fi

WORKDIR /var/www/html

# Copiar código-fonte e dependências do stage anterior
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor

# Criar .env vazio (variáveis de produção vêm do Render Environment ou env_file)
RUN touch .env

# Copiar configuração nginx, supervisor e entrypoint
COPY --chown=root:root nginx.conf /etc/nginx/nginx.conf
COPY --chown=root:root supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY --chown=root:root docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Garantir permissões do storage e bootstrap/cache do Laravel como root
RUN mkdir -p storage/logs \
             storage/framework/cache \
             storage/framework/sessions \
             storage/framework/views \
             bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Criar diretórios necessários para nginx/php-fpm/supervisor e ajustar permissões
RUN mkdir -p /var/lib/nginx/logs /run/nginx /var/log/supervisor /tmp/nginx \
    && chown -R www-data:www-data /var/lib/nginx /run/nginx /var/log/supervisor /tmp/nginx \
    && ln -sf /tmp/nginx/access.log /var/log/nginx/access.log \
    && ln -sf /tmp/nginx/error.log /var/log/nginx/error.log

# O container inicia como root para que o supervisor possa spawnar processos.
# Os serviços php-fpm e nginx rodam como usuário www-data.
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
