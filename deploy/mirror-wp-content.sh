#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SSH_HOST="${1:-opera}"
APP_DIR="/srv/opera/www"
URL_FILE="${ROOT}/deploy/wp-uploads-urls.txt"

if [ ! -f "${URL_FILE}" ]; then
    rg -o 'https?://uuopera\.ru/wp-content/uploads/\S+' "${ROOT}/www/local" "${ROOT}/www/balet_na_baikale" 2>/dev/null \
        | sed 's/^.*: //; s|^http://|https://|' | sort -u > "${URL_FILE}"
fi

COUNT="$(wc -l < "${URL_FILE}" | tr -d ' ')"
echo "URLs: ${COUNT}"

ssh "${SSH_HOST}" "mkdir -p ${APP_DIR}/wp-content/uploads && chown -R www-data:www-data ${APP_DIR}/wp-content"
scp "${URL_FILE}" "${SSH_HOST}:/tmp/wp-uploads-urls.txt"
scp "${ROOT}/deploy/mirror-wp-content-remote.sh" "${SSH_HOST}:/tmp/mirror-wp-content-remote.sh"
ssh "${SSH_HOST}" "chmod +x /tmp/mirror-wp-content-remote.sh && APP_DIR=${APP_DIR} nohup /tmp/mirror-wp-content-remote.sh > /var/log/wp-content-mirror.log 2>&1 &"
echo "Log: ssh ${SSH_HOST} tail -f /var/log/wp-content-mirror.log"
