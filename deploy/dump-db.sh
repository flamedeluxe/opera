#!/usr/bin/env bash
# Актуальный дамп Bitrix из локального Docker → db/bitrix.sql.gz
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "${ROOT}"

mkdir -p db

if ! docker compose ps --status running --format '{{.Name}}' 2>/dev/null | grep -q 'db'; then
    echo "Запусти Docker: docker compose up -d" >&2
    exit 1
fi

PASS="${MYSQL_PASSWORD:-bitrix}"
if ! docker compose exec -T db mariadb -u bitrix -p"${PASS}" -e "SELECT 1" >/dev/null 2>&1; then
    PASS="bitrix"
fi

docker compose exec -T db mariadb-dump -u bitrix -p"${PASS}" \
    --single-transaction --routines --triggers --default-character-set=utf8mb4 bitrix \
    | gzip -9 > db/bitrix.sql.gz

ls -lh db/bitrix.sql.gz
