#!/usr/bin/env bash
# Снимает дамп Bitrix-БД в db/bitrix.sql.gz
# Запускать из корня репозитория: bash db/export.sh
set -euo pipefail
cd "$(dirname "$0")/.."

if [ ! -f .env ]; then
    echo "Нет .env файла" >&2
    exit 1
fi
source .env

OUT="db/bitrix.sql.gz"
echo "Экспорт базы '${MYSQL_DATABASE}' → ${OUT} ..."

docker compose exec -T db mariadb-dump \
    -u "${MYSQL_USER}" -p"${MYSQL_PASSWORD}" \
    --single-transaction \
    --add-drop-table \
    --routines \
    --triggers \
    --default-character-set=utf8mb4 \
    "${MYSQL_DATABASE}" \
    | gzip -9 > "${OUT}"

SIZE=$(du -h "${OUT}" | cut -f1)
echo "Готово: ${OUT} (${SIZE})"
