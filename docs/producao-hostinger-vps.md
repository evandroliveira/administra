# Producao na Hostinger VPS

Este projeto ja esta preparado para rodar em producao com dominio proprio, HTTPS e rotas amigaveis do Laravel, desde que o deploy use o diretorio public como raiz do site e o proxy repasse os headers padrao.

## Dominio

No hPanel da Hostinger:

1. Aponte o dominio lojagerencia.com.br para o IP publico da VPS.
2. Aponte o subdominio www para o mesmo IP.
3. Aguarde a propagacao antes de emitir o SSL.

## Arquivo de ambiente do servidor

Use [../.env.production.example](../.env.production.example) como base para o .env da VPS.

Valores obrigatorios para o site:

1. APP_ENV=production
2. APP_DEBUG=false
3. APP_URL=https://www.lojagerencia.com.br
4. APP_FORCE_HTTPS=true
5. APP_FORCE_ROOT_URL=true
6. TRUSTED_PROXIES=*
7. SESSION_DOMAIN=.lojagerencia.com.br
8. SESSION_SECURE_COOKIE=true

Essas variaveis fazem duas coisas importantes:

1. geram URLs e redirects sempre com https://www.lojagerencia.com.br
2. permitem que o Laravel reconheca corretamente HTTPS quando estiver atras do Nginx ou proxy da VPS

## Nginx

Use o arquivo final [../deploy/nginx/lojagerencia.com.br.conf](../deploy/nginx/lojagerencia.com.br.conf) em /etc/nginx/sites-available/lojagerencia.com.br.

Pontos que nao podem estar errados:

1. o root precisa terminar em /public
2. a location / precisa ter try_files $uri $uri/ /index.php?$query_string
3. o bloco PHP precisa apontar para o socket correto do php-fpm
4. o dominio sem www deve redirecionar para www

Sem isso, as rotas nomeadas podem ate existir no Laravel, mas o servidor nao vai entregar corretamente URLs como /login, /dashboard, /clientes e /vendas.

## Passos de deploy

Assumindo o projeto em /var/www/app:

```bash
cd /var/www/app
composer install --no-dev --optimize-autoloader
npm ci
npm run build
cp .env.production.example .env
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan optimize
```

Gere a APP_KEY apenas no primeiro deploy. Se a aplicacao ja estiver em uso, preserve a chave atual.

Se o banco ja estiver populado, ajuste apenas o .env e rode somente os comandos necessarios.

Se preferir um fluxo repetivel, use [../deploy/hostinger/deploy.sh](../deploy/hostinger/deploy.sh):

```bash
cd /var/www/app
chmod +x deploy/hostinger/deploy.sh
./deploy/hostinger/deploy.sh
```

Esse script ja executa:

1. modo manutencao temporario
2. composer install de producao
3. npm ci e build dos assets
4. migrate --force
5. config:cache, route:cache e view:cache

Se voce quiser a sequencia completa de provisionamento da VPS via SSH, use [hostinger-vps-comandos.md](hostinger-vps-comandos.md).

## Permissoes

Garanta escrita para o usuario do Nginx/PHP-FPM em:

1. storage
2. bootstrap/cache

Exemplo comum no Ubuntu:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## SSL

No Ubuntu com Nginx:

```bash
sudo apt update
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d lojagerencia.com.br -d www.lojagerencia.com.br
```

Depois valide:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

## Fila e tarefas agendadas

Se o sistema usar fila em producao, configure um worker permanente. Exemplo simples com systemd:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=90
```

Para tarefas agendadas:

```bash
* * * * * cd /var/www/app && php artisan schedule:run >> /dev/null 2>&1
```

## Checklist final

1. www.lojagerencia.com.br abre com cadeado HTTPS
2. o login redireciona para /dashboard sem trocar para http
3. refresh em rotas internas como /clientes e /vendas continua funcionando
4. php artisan route:list executa sem erro no servidor
5. php artisan optimize executa sem erro no servidor