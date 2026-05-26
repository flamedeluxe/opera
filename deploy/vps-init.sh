#!/usr/bin/env bash
# Первоначальная настройка Ubuntu 24.04 VPS для uuopera.ru
# Запускать от root: bash deploy/vps-init.sh [домен]
set -euo pipefail

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'
ok()   { echo -e "${GREEN}✓ $*${NC}"; }
info() { echo -e "${YELLOW}▶ $*${NC}"; }
err()  { echo -e "${RED}✗ $*${NC}" >&2; exit 1; }

[ "$(id -u)" -eq 0 ] || err "Запускать от root"

DOMAIN="${1:-new.uuopera.ru}"
APP_DIR="/srv/opera/www"
DB_NAME="bitrix"
DB_USER="bitrix"
DB_PASS="${DB_PASS:-$(openssl rand -base64 24 | tr -d '/+=')}"
DB_ROOT_PASS="${DB_ROOT_PASS:-$(openssl rand -base64 24 | tr -d '/+=')}"

# ─── 1. Системные пакеты ────────────────────────────────────────────────────
info "Обновление системы..."
apt-get update -qq
apt-get upgrade -y -qq
apt-get install -y -qq curl git ufw software-properties-common gnupg2

# ─── 2. PHP 8.2 ─────────────────────────────────────────────────────────────
info "Установка PHP 8.2..."
add-apt-repository -y ppa:ondrej/php
apt-get update -qq
apt-get install -y -qq \
    php8.2-fpm php8.2-mysql php8.2-curl php8.2-gd php8.2-mbstring \
    php8.2-xml php8.2-zip php8.2-opcache php8.2-intl php8.2-soap \
    php8.2-bcmath php8.2-fileinfo php8.2-ldap
ok "PHP 8.2 установлен"

# ─── 3. MariaDB ─────────────────────────────────────────────────────────────
info "Установка MariaDB..."
apt-get install -y -qq mariadb-server

systemctl enable --now mariadb

mariadb -e "
    ALTER USER 'root'@'localhost' IDENTIFIED BY '${DB_ROOT_PASS}';
    CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
    GRANT ALL ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
    FLUSH PRIVILEGES;
"
ok "MariaDB настроена"

# ─── 4. Memcached ───────────────────────────────────────────────────────────
info "Установка memcached..."
apt-get install -y -qq memcached php8.2-memcached
systemctl enable --now memcached
ok "Memcached установлен"

# ─── 5. nginx ───────────────────────────────────────────────────────────────
info "Установка nginx..."
apt-get install -y -qq nginx certbot python3-certbot-nginx

mkdir -p "${APP_DIR}"

cat > /etc/nginx/sites-available/uuopera.conf << NGINX
server {
    listen 80;
    server_name ${DOMAIN};
    charset utf-8;
    root ${APP_DIR};
    index index.php index.html bitrixsetup.php;

    client_max_body_size 256M;

    location /afisha/ {
        try_files \$uri \$uri/ /afisha/_route.php\$is_args\$args;
    }

    location / {
        try_files \$uri \$uri/ /uuopera_route.php\$is_args\$args;
    }

    location ~ ^/(restore\\.php|bitrix/admin/1c_exchange\\.php)$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_read_timeout 21600;
        fastcgi_send_timeout 21600;
        client_body_buffer_size 128m;
        client_max_body_size 1024m;
    }

    location /wp-content/uploads/ {
        try_files \$uri @wp_uploads_proxy;
        expires 30d;
        add_header Cache-Control public;
        access_log off;
    }
    location @wp_uploads_proxy {
        proxy_pass https://uuopera.ru;
        proxy_set_header Host uuopera.ru;
        proxy_ssl_server_name on;
        expires 30d;
        add_header Cache-Control public;
        access_log off;
    }

    location ~* ^.+\\.(jpg|jpeg|gif|png|svg|webp|js|css|ico|woff2?|ttf|eot)$ {
        try_files \$uri =404;
        expires 30d;
        add_header Cache-Control public;
        access_log off;
    }

    location ~ \\.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_read_timeout 600;
        fastcgi_send_timeout 600;
    }
}
NGINX

ln -sf /etc/nginx/sites-available/uuopera.conf /etc/nginx/sites-enabled/uuopera.conf
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl enable --now nginx && systemctl reload nginx
ok "nginx настроен"

# ─── 6. PHP-FPM настройки ───────────────────────────────────────────────────
info "Настройка PHP-FPM..."
PHP_INI="/etc/php/8.2/fpm/php.ini"
sed -i 's/^upload_max_filesize.*/upload_max_filesize = 256M/' "${PHP_INI}"
sed -i 's/^post_max_size.*/post_max_size = 256M/'             "${PHP_INI}"
sed -i 's/^memory_limit.*/memory_limit = 512M/'               "${PHP_INI}"
sed -i 's/^max_execution_time.*/max_execution_time = 600/'    "${PHP_INI}"
sed -i 's/^;date.timezone.*/date.timezone = Asia\/Irkutsk/'   "${PHP_INI}"
systemctl reload php8.2-fpm
ok "PHP-FPM настроен"

# ─── 7. Файрвол ─────────────────────────────────────────────────────────────
info "Настройка UFW..."
ufw --force reset
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow 'Nginx Full'
ufw --force enable
ok "UFW настроен"

# ─── Итог ───────────────────────────────────────────────────────────────────
CREDS_FILE="/root/opera-credentials.txt"
cat > "${CREDS_FILE}" << EOF
=== opera VPS credentials ===
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}
DB_ROOT_PASS=${DB_ROOT_PASS}
APP_DIR=${APP_DIR}
EOF
chmod 600 "${CREDS_FILE}"

echo ""
echo -e "${GREEN}══════════════════════════════════════════${NC}"
ok "VPS настроен! Данные сохранены в ${CREDS_FILE}"
echo ""
echo "Следующие шаги (с локальной машины):"
echo ""
echo "  1. Скопируй сайт:"
echo "       rsync -av --exclude='.git' --exclude='wp-old' --exclude='db' ./www/ root@${DOMAIN}:${APP_DIR}/"
echo ""
echo "  2. Скопируй дамп БД и импортируй:"
echo "       scp db/bitrix.sql.gz root@${DOMAIN}:/tmp/"
echo "       ssh root@${DOMAIN} \"gunzip -c /tmp/bitrix.sql.gz | mariadb -u ${DB_USER} -p'${DB_PASS}' ${DB_NAME}\""
echo ""
echo "  3. Настрой Bitrix DB connection в /srv/opera/www/bitrix/.settings.php"
echo "     host: localhost, login: ${DB_USER}, password: ${DB_PASS}, database: ${DB_NAME}"
echo ""
echo "  4. SSL:"
echo "       certbot --nginx -d ${DOMAIN}"
echo -e "${GREEN}══════════════════════════════════════════${NC}"
