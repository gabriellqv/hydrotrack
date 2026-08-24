#!/bin/sh
set -eu

# Script de inicialização do container Laravel + nginx + php-fpm.
# Idempotente: pode ser executado múltiplas vezes sem efeitos colaterais.

# Executa migrations apenas quando explicitamente solicitado.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "[entrypoint] Executando migrations..."
    php artisan migrate --force
fi

# Inicia o supervisor, que gerencia php-fpm e nginx.
echo "[entrypoint] Iniciando servicos..."
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
