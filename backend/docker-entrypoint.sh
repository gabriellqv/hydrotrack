#!/bin/sh
set -eu

# Script de inicialização do container Laravel + nginx + php-fpm.
# Idempotente: pode ser executado múltiplas vezes sem efeitos colaterais.

# Garantir permissões corretas em diretórios graváveis do Laravel.
if [ -d /var/www/html/storage ] && [ -d /var/www/html/bootstrap/cache ]; then
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
fi

# Executa migrations apenas quando explicitamente solicitado.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "[entrypoint] Executando migrations..."
    php artisan migrate --force
fi

# Inicia php-fpm em background.
echo "[entrypoint] Iniciando php-fpm..."
php-fpm --daemonize --pid /tmp/php-fpm.pid

# Inicia nginx em foreground, mantendo o container vivo.
echo "[entrypoint] Iniciando nginx..."
exec nginx -g 'daemon off;'
