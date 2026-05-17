# Comandos SSH para subir o projeto na Hostinger VPS

Este roteiro assume:

1. Ubuntu 22.04 ou 24.04 na VPS
2. projeto em /var/www/app
3. dominio lojagerencia.com.br e www.lojagerencia.com.br apontando para a VPS
4. repositorio Git ja disponivel

## 1. Atualizar o servidor e instalar dependencias

```bash
apt update && apt upgrade -y
apt install -y software-properties-common curl unzip git ca-certificates gnupg lsb-release nginx certbot python3-certbot-nginx mysql-server
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-bcmath php8.2-intl php8.2-gd php8.2-soap php8.2-readline
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
systemctl enable nginx
systemctl enable php8.2-fpm
systemctl enable mysql
systemctl start nginx
systemctl start php8.2-fpm
systemctl start mysql
```

## 2. Criar banco e usuario do sistema

Troque SENHA_FORTE_DO_BANCO por uma senha real.

```bash
mysql -u root <<'SQL'
CREATE DATABASE IF NOT EXISTS administrar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'administrar_user'@'localhost' IDENTIFIED BY 'SENHA_FORTE_DO_BANCO';
GRANT ALL PRIVILEGES ON administrar.* TO 'administrar_user'@'localhost';
FLUSH PRIVILEGES;
SQL
```

## 3. Publicar o projeto em /var/www/app

Se a pasta ja contem o projeto, apenas pule para a etapa 4.

```bash
mkdir -p /var/www
cd /var/www
git clone https://SEU_REPOSITORIO.git app
cd /var/www/app
```

Se o projeto ja existe e voce quer atualizar:

```bash
cd /var/www/app
git pull origin main
```

## 4. Configurar o .env de producao

```bash
cd /var/www/app
cp .env.production.example .env
nano .env
```

No .env, deixe pelo menos estes valores:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://www.lojagerencia.com.br
APP_FORCE_HTTPS=true
APP_FORCE_ROOT_URL=true
TRUSTED_PROXIES=*
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=administrar
DB_USERNAME=administrar_user
DB_PASSWORD=SENHA_FORTE_DO_BANCO
SESSION_DOMAIN=.lojagerencia.com.br
SESSION_SECURE_COOKIE=true
```

Se for o primeiro deploy:

```bash
cd /var/www/app
php artisan key:generate
```

Se o sistema ja estava em uso, preserve a APP_KEY atual.

## 5. Ajustar permissoes do Laravel

```bash
cd /var/www/app
chown -R www-data:www-data /var/www/app
chmod -R 775 storage bootstrap/cache
```

## 6. Instalar e buildar a aplicacao

```bash
cd /var/www/app
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Ou usando o script do projeto:

```bash
cd /var/www/app
chmod +x deploy/hostinger/deploy.sh
./deploy/hostinger/deploy.sh
```

## 7. Publicar o Nginx

Use o arquivo [../deploy/nginx/lojagerencia.com.br.conf](../deploy/nginx/lojagerencia.com.br.conf).

```bash
cp /var/www/app/deploy/nginx/lojagerencia.com.br.conf /etc/nginx/sites-available/lojagerencia.com.br
ln -sf /etc/nginx/sites-available/lojagerencia.com.br /etc/nginx/sites-enabled/lojagerencia.com.br
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx
```

## 8. Emitir o SSL com Certbot

```bash
certbot --nginx -d lojagerencia.com.br -d www.lojagerencia.com.br
nginx -t
systemctl reload nginx
```

## 9. Criar cron do agendador

```bash
crontab -l 2>/dev/null > /tmp/cron_laravel || true
grep -q 'schedule:run' /tmp/cron_laravel || echo '* * * * * cd /var/www/app && php artisan schedule:run >> /dev/null 2>&1' >> /tmp/cron_laravel
crontab /tmp/cron_laravel
rm -f /tmp/cron_laravel
```

## 10. Criar servico da fila

```bash
cat >/etc/systemd/system/lojagerencia-queue.service <<'EOF'
[Unit]
Description=Laravel Queue Worker - lojagerencia
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/app/artisan queue:work --sleep=3 --tries=3 --timeout=90
WorkingDirectory=/var/www/app
StandardOutput=append:/var/log/lojagerencia-queue.log
StandardError=append:/var/log/lojagerencia-queue-error.log

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable lojagerencia-queue
systemctl start lojagerencia-queue
systemctl status lojagerencia-queue --no-pager
```

## 11. Validacao final

```bash
cd /var/www/app
php artisan route:list
php artisan about
systemctl status nginx --no-pager
systemctl status php8.2-fpm --no-pager
curl -I https://www.lojagerencia.com.br
```

## 12. Atualizacao nas proximas publicacoes

```bash
cd /var/www/app
git pull origin main
./deploy/hostinger/deploy.sh
systemctl reload nginx
```