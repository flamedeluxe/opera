#!/usr/bin/env bash
set -euo pipefail
APP_DIR="${APP_DIR:-/srv/opera/www}"
cd "${APP_DIR}/wp-content"
n=0
while IFS= read -r url; do
    [ -z "${url}" ] && continue
    path="${url#https://uuopera.ru/wp-content/}"
    path="${path#http://uuopera.ru/wp-content/}"
    mkdir -p "$(dirname "${path}")"
    if wget -q -nc -O "${path}" "${url}" 2>/dev/null; then
        n=$((n + 1))
    fi
done < /tmp/wp-uploads-urls.txt
chown -R www-data:www-data uploads
echo "Скачано файлов: ${n}"
du -sh uploads
