#!/usr/bin/env bash

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/app}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NPM_BIN="${NPM_BIN:-npm}"
USE_MAINTENANCE_MODE="${USE_MAINTENANCE_MODE:-1}"

ensure_runtime_permissions() {
    mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache

    if [[ ${EUID:-$(id -u)} -eq 0 ]]; then
        chown -R www-data:www-data storage bootstrap/cache
    else
        echo "Aviso: deploy executado sem sudo/root. Ownership de storage e bootstrap/cache nao sera ajustado automaticamente."
    fi

    chmod -R 775 storage bootstrap/cache 2>/dev/null || true

    local required_paths=(
        "storage"
        "storage/framework"
        "storage/framework/cache"
        "storage/framework/sessions"
        "storage/framework/views"
        "bootstrap/cache"
    )

    for path in "${required_paths[@]}"; do
        if [[ ! -w "$path" ]]; then
            echo "Permissao insuficiente em $path. Ajuste com: sudo chown -R www-data:www-data storage bootstrap/cache && sudo chmod -R 775 storage bootstrap/cache"
            exit 1
        fi
    done
}

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

if ! grep -Eq '^APP_KEY=.+$' .env; then
    echo "APP_KEY ausente no .env. Gere a chave uma unica vez com: php artisan key:generate --force"
    exit 1
fi

ensure_runtime_permissions

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