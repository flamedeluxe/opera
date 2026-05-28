#!/usr/bin/env bash
# Синхронизация www/upload/ на VPS по частям (устойчивее длинного rsync).
# Использование: ./deploy/sync-upload.sh [ssh-host]
set -uo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SSH_HOST="${1:-opera}"
APP_DIR="/srv/opera/www"
UPLOAD="${ROOT}/www/upload"
RSYNC_SSH="ssh -o ServerAliveInterval=10 -o ServerAliveCountMax=600 -o TCPKeepAlive=yes"
RSYNC=(rsync -az --partial -e "${RSYNC_SSH}")

if [ ! -d "${UPLOAD}" ]; then
    echo "Нет каталога ${UPLOAD}" >&2
    exit 1
fi

FAILED=0

sync_dir() {
    local rel="$1"
    local src="${UPLOAD}/${rel}"
    local dst="${SSH_HOST}:${APP_DIR}/upload/${rel}/"
    if [ ! -d "${src}" ]; then
        return 0
    fi
    echo "▶ upload/${rel}/"
    if ! "${RSYNC[@]}" "${src}/" "${dst}"; then
        echo "Повтор upload/${rel}/..." >&2
        sleep 3
        if ! "${RSYNC[@]}" "${src}/" "${dst}"; then
            echo "✗ upload/${rel}/" >&2
            FAILED=1
        fi
    fi
}

echo "▶ upload/ (корневые файлы)"
"${RSYNC[@]}" "${UPLOAD}/" "${SSH_HOST}:${APP_DIR}/upload/" \
    --exclude='iblock/' --exclude='tmp/' --exclude='resize_cache/' --exclude='main/'

sync_dir main
sync_dir resize_cache
sync_dir tmp

if [ -d "${UPLOAD}/iblock" ]; then
    "${ROOT}/deploy/sync-upload-iblock-batches.sh" "${SSH_HOST}" || FAILED=1
fi

ssh "${SSH_HOST}" "chown -R www-data:www-data ${APP_DIR}/upload && find ${APP_DIR}/upload -type d -exec chmod 755 {} + && find ${APP_DIR}/upload -type f -exec chmod 644 {} + 2>/dev/null || true"
if [ "${FAILED}" -eq 0 ]; then
    echo "✓ upload синхронизирован"
else
    echo "⚠ upload: часть каталогов не синхронизирована, перезапустите ./deploy/sync-upload.sh" >&2
    exit 1
fi
