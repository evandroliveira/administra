#!/usr/bin/env bash

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/app}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NPM_BIN="${NPM_BIN:-npm}"
USE_MAINTENANCE_MODE="${USE_MAINTENANCE_MODE:-1}"

cleanup() {
    if [[ "$USE_MAINTENANCE_MODE" == "1" ]]; then
        "$PHP_BIN" artisan up || true
    fi
}

trap cleanup EXIT

cd "$APP_DIR"

if [[ ! -f .env ]]; then
    echo "Arquivo .env nao encontrado em $APP_DIR. Copie .env.production.example para .env e ajuste as credenciais antes do deploy."
    exit 1
fi

if [[ "$USE_MAINTENANCE_MODE" == "1" ]]; then
    "$PHP_BIN" artisan down --retry=60 || true
fi

"$COMPOSER_BIN" install --no-dev --prefer-dist --optimize-autoloader
"$NPM_BIN" ci
"$NPM_BIN" run build

"$PHP_BIN" artisan migrate --force
"$PHP_BIN" artisan storage:link || true

"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache

echo "Deploy concluido com sucesso em $(date '+%Y-%m-%d %H:%M:%S')."