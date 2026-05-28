#!/usr/bin/env bash
# Докачка upload/iblock/ мелкими батчами (обход обрыва SSH).
# ./deploy/sync-upload-iblock-batches.sh [ssh-host] [prefix]
# prefix: 0-9, a-f или пусто = все
set -uo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SSH_HOST="${1:-opera}"
PREFIX="${2:-}"
APP_DIR="/srv/opera/www"
SRC="${ROOT}/www/upload/iblock"
CHUNK_SIZE=40
RSYNC_SSH="ssh -o ServerAliveInterval=10 -o ServerAliveCountMax=120 -o TCPKeepAlive=yes"
RSYNC=(rsync -az --partial -e "${RSYNC_SSH}")

if [ ! -d "${SRC}" ]; then
    echo "Нет ${SRC}" >&2
    exit 1
fi

PREFIXES=()
if [ -n "${PREFIX}" ]; then
    PREFIXES=("${PREFIX}")
else
    PREFIXES=(0 1 2 3 4 5 6 7 8 9 a b c d e f)
fi

FAILED=0
for p in "${PREFIXES[@]}"; do
    shopt -s nullglob
    dirs=("${SRC}/${p}"*)
    shopt -u nullglob
    if [ "${#dirs[@]}" -eq 0 ]; then
        continue
    fi
    echo "▶ iblock [${p}*] всего ${#dirs[@]} dirs"
    chunk=()
    for d in "${dirs[@]}"; do
        chunk+=("${d}")
        if [ "${#chunk[@]}" -ge "${CHUNK_SIZE}" ]; then
            echo "  chunk ${#chunk[@]}..."
            if ! "${RSYNC[@]}" "${chunk[@]}" "${SSH_HOST}:${APP_DIR}/upload/iblock/"; then
                sleep 2
                "${RSYNC[@]}" "${chunk[@]}" "${SSH_HOST}:${APP_DIR}/upload/iblock/" || FAILED=1
            fi
            chunk=()
        fi
    done
    if [ "${#chunk[@]}" -gt 0 ]; then
        echo "  chunk ${#chunk[@]} (tail)..."
        if ! "${RSYNC[@]}" "${chunk[@]}" "${SSH_HOST}:${APP_DIR}/upload/iblock/"; then
            sleep 2
            "${RSYNC[@]}" "${chunk[@]}" "${SSH_HOST}:${APP_DIR}/upload/iblock/" || FAILED=1
        fi
    fi
done

ssh "${SSH_HOST}" "chown -R www-data:www-data ${APP_DIR}/upload 2>/dev/null || true"

if [ "${FAILED}" -eq 0 ]; then
    echo "✓ iblock batches done"
    ssh "${SSH_HOST}" "du -sh ${APP_DIR}/upload ${APP_DIR}/upload/iblock"
else
    echo "⚠ есть ошибки, перезапустите с нужным prefix" >&2
    exit 1
fi
