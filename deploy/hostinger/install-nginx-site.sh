#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEFAULT_APP_DIR="$(cd "${SCRIPT_DIR}/../.." && pwd)"

APP_DIR="${APP_DIR:-$DEFAULT_APP_DIR}"
PRIMARY_DOMAIN="${PRIMARY_DOMAIN:-lojagerencia.com.br}"
WWW_DOMAIN="${WWW_DOMAIN:-www.${PRIMARY_DOMAIN}}"
PHP_FPM_SOCK="${PHP_FPM_SOCK:-/run/php/php8.2-fpm.sock}"
NGINX_SITE_NAME="${NGINX_SITE_NAME:-${PRIMARY_DOMAIN}.conf}"
NGINX_AVAILABLE_DIR="${NGINX_AVAILABLE_DIR:-/etc/nginx/sites-available}"
NGINX_ENABLED_DIR="${NGINX_ENABLED_DIR:-/etc/nginx/sites-enabled}"
NGINX_EXTRA_CONFLICT_DIRS="${NGINX_EXTRA_CONFLICT_DIRS:-/etc/nginx/conf.d}"
REMOVE_DEFAULT_SITE="${REMOVE_DEFAULT_SITE:-1}"
DISABLE_CONFLICTING_SITES="${DISABLE_CONFLICTING_SITES:-1}"

CERT_BASE="${CERT_BASE:-/etc/letsencrypt/live/${PRIMARY_DOMAIN}}"
SSL_CERT_FILE="${SSL_CERT_FILE:-${CERT_BASE}/fullchain.pem}"
SSL_KEY_FILE="${SSL_KEY_FILE:-${CERT_BASE}/privkey.pem}"
SSL_OPTIONS_FILE="${SSL_OPTIONS_FILE:-/etc/letsencrypt/options-ssl-nginx.conf}"
SSL_DHPARAM_FILE="${SSL_DHPARAM_FILE:-/etc/letsencrypt/ssl-dhparams.pem}"

PUBLIC_DIR="${APP_DIR%/}/public"
INDEX_FILE="${PUBLIC_DIR}/index.php"
SITE_CONF_PATH="${NGINX_AVAILABLE_DIR}/${NGINX_SITE_NAME}"
SITE_ENABLED_PATH="${NGINX_ENABLED_DIR}/${NGINX_SITE_NAME}"
SITE_SCHEME="http"
PRIMARY_DOMAIN_REGEX="${PRIMARY_DOMAIN//./\\.}"
WWW_DOMAIN_REGEX="${WWW_DOMAIN//./\\.}"

require_root() {
    if [[ ${EUID:-$(id -u)} -ne 0 ]]; then
        echo "Execute este script como root ou com sudo para instalar a configuracao do Nginx."
        exit 1
    fi
}

assert_paths() {
    if [[ ! -f "$INDEX_FILE" ]]; then
        echo "Nao encontrei ${INDEX_FILE}. Ajuste APP_DIR para o diretorio real do projeto Laravel."
        exit 1
    fi

    if [[ ! -S "$PHP_FPM_SOCK" ]]; then
        echo "Socket PHP-FPM nao encontrado em ${PHP_FPM_SOCK}. Ajuste PHP_FPM_SOCK antes de continuar."
        exit 1
    fi
}

disable_conflicting_sites_in_dir() {
    local directory="$1"

    if [[ ! -d "$directory" ]]; then
        return
    fi

    while IFS= read -r -d '' enabled_entry; do
        if [[ "$enabled_entry" == "$SITE_ENABLED_PATH" ]]; then
            continue
        fi

        if [[ ! -f "$enabled_entry" ]]; then
            continue
        fi

        if grep -Eq "server_name[[:space:]]+[^;]*(${PRIMARY_DOMAIN_REGEX}|${WWW_DOMAIN_REGEX})" "$enabled_entry"; then
            if [[ -L "$enabled_entry" ]]; then
                rm -f "$enabled_entry"
                echo "Site conflitante desabilitado: ${enabled_entry}"
            else
                mv "$enabled_entry" "${enabled_entry}.disabled.$(date +%Y%m%d%H%M%S)"
                echo "Arquivo conflitante renomeado: ${enabled_entry}"
            fi
        fi
    done < <(find "$directory" -mindepth 1 -maxdepth 1 -print0)
}

write_http_bootstrap_config() {
    cat >"$SITE_CONF_PATH" <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${PRIMARY_DOMAIN};

    return 301 http://${WWW_DOMAIN}\$request_uri;
}

server {
    listen 80;
    listen [::]:80;
    server_name ${WWW_DOMAIN};

    root ${PUBLIC_DIR};
    index index.php index.html;

    charset utf-8;
    client_max_body_size 32M;

    access_log /var/log/nginx/${PRIMARY_DOMAIN}.access.log;
    error_log /var/log/nginx/${PRIMARY_DOMAIN}.error.log;

    add_header X-Frame-Options SAMEORIGIN;
    add_header X-Content-Type-Options nosniff;
    add_header Referrer-Policy strict-origin-when-cross-origin;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico {
        access_log off;
        log_not_found off;
    }

    location = /robots.txt {
        access_log off;
        log_not_found off;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:${PHP_FPM_SOCK};
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT \$realpath_root;
        fastcgi_read_timeout 300;
    }

    location ~* \.(?:css|js|jpg|jpeg|gif|png|svg|ico|webp|avif|woff|woff2)$ {
        expires 7d;
        access_log off;
        add_header Cache-Control "public, max-age=604800, immutable";
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF
}

write_https_config() {
    cat >"$SITE_CONF_PATH" <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${PRIMARY_DOMAIN} ${WWW_DOMAIN};

    return 301 https://${WWW_DOMAIN}\$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ${PRIMARY_DOMAIN};

    ssl_certificate ${SSL_CERT_FILE};
    ssl_certificate_key ${SSL_KEY_FILE};
    include ${SSL_OPTIONS_FILE};
    ssl_dhparam ${SSL_DHPARAM_FILE};

    return 301 https://${WWW_DOMAIN}\$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ${WWW_DOMAIN};

    root ${PUBLIC_DIR};
    index index.php index.html;

    charset utf-8;
    client_max_body_size 32M;

    access_log /var/log/nginx/${PRIMARY_DOMAIN}.access.log;
    error_log /var/log/nginx/${PRIMARY_DOMAIN}.error.log;

    ssl_certificate ${SSL_CERT_FILE};
    ssl_certificate_key ${SSL_KEY_FILE};
    include ${SSL_OPTIONS_FILE};
    ssl_dhparam ${SSL_DHPARAM_FILE};

    add_header X-Frame-Options SAMEORIGIN;
    add_header X-Content-Type-Options nosniff;
    add_header Referrer-Policy strict-origin-when-cross-origin;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico {
        access_log off;
        log_not_found off;
    }

    location = /robots.txt {
        access_log off;
        log_not_found off;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:${PHP_FPM_SOCK};
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT \$realpath_root;
        fastcgi_read_timeout 300;
    }

    location ~* \.(?:css|js|jpg|jpeg|gif|png|svg|ico|webp|avif|woff|woff2)$ {
        expires 7d;
        access_log off;
        add_header Cache-Control "public, max-age=604800, immutable";
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF
}

install_site() {
    mkdir -p "$NGINX_AVAILABLE_DIR" "$NGINX_ENABLED_DIR"

    if [[ -f "$SITE_CONF_PATH" ]]; then
        cp "$SITE_CONF_PATH" "${SITE_CONF_PATH}.bak.$(date +%Y%m%d%H%M%S)"
    fi

    if [[ -f "$SSL_CERT_FILE" && -f "$SSL_KEY_FILE" && -f "$SSL_OPTIONS_FILE" && -f "$SSL_DHPARAM_FILE" ]]; then
        SITE_SCHEME="https"
        write_https_config
        echo "Configuracao HTTPS gerada para ${WWW_DOMAIN}."
    else
        write_http_bootstrap_config
        echo "Configuracao HTTP temporaria gerada para bootstrap inicial do Certbot."
        echo "Depois de emitir o SSL, execute este script novamente para ativar HTTPS."
    fi

    ln -sfn "$SITE_CONF_PATH" "$SITE_ENABLED_PATH"

    if [[ "$REMOVE_DEFAULT_SITE" == "1" ]]; then
        rm -f "${NGINX_ENABLED_DIR}/default"
    fi

    if [[ "$DISABLE_CONFLICTING_SITES" == "1" ]]; then
        disable_conflicting_sites_in_dir "$NGINX_ENABLED_DIR"

        for extra_conflict_dir in $NGINX_EXTRA_CONFLICT_DIRS; do
            disable_conflicting_sites_in_dir "$extra_conflict_dir"
        done
    fi

    nginx -t
    systemctl reload nginx
}

print_summary() {
    echo "Site Nginx instalado com sucesso."
    echo "Arquivo ativo: ${SITE_CONF_PATH}"
    echo "Raiz publicada: ${PUBLIC_DIR}"
    echo "Validacoes recomendadas:"
    echo "  curl -I ${SITE_SCHEME}://${WWW_DOMAIN}/login"
    echo "  curl -I ${SITE_SCHEME}://${WWW_DOMAIN}/clientes"
    echo "  cd ${APP_DIR} && php artisan route:list"
}

require_root
assert_paths
install_site
print_summary