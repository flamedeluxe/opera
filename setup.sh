#!/usr/bin/env bash
# Полное развёртывание сайта uuopera.ru с нуля.
# Фото грузятся автоматически с прод-сайта через nginx-прокси.
#
# Использование:
#   bash setup.sh            — полный запуск (дамп БД + контент)
#   bash setup.sh --skip-db  — пропустить импорт дампа (БД уже есть)
#   bash setup.sh --skip-content  — только БД, без импорта контента

set -euo pipefail
cd "$(dirname "$0")"

SKIP_DB=false
SKIP_CONTENT=false
for arg in "$@"; do
    case "$arg" in
        --skip-db)      SKIP_DB=true ;;
        --skip-content) SKIP_CONTENT=true ;;
    esac
done

# ─── Цвета ──────────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'
ok()   { echo -e "${GREEN}✓ $*${NC}"; }
info() { echo -e "${YELLOW}▶ $*${NC}"; }
err()  { echo -e "${RED}✗ $*${NC}" >&2; }

# ─── 1. .env ────────────────────────────────────────────────────────────────
if [ ! -f .env ]; then
    cp .env.example .env
    info ".env создан из .env.example — проверь пароли если нужно"
fi
source .env
HTTP_PORT="${HTTP_PORT:-18080}"

# ─── 2. Docker ──────────────────────────────────────────────────────────────
info "Запуск Docker-контейнеров..."
docker compose up -d
ok "Контейнеры запущены"

# ─── 3. Ждём MariaDB ────────────────────────────────────────────────────────
info "Ожидание MariaDB..."
for i in $(seq 1 40); do
    if docker compose exec -T db mariadb-admin ping -h localhost \
            -u "${MYSQL_USER}" -p"${MYSQL_PASSWORD}" --silent 2>/dev/null; then
        ok "MariaDB готова"
        break
    fi
    [ "$i" -eq 40 ] && { err "MariaDB не ответила за 80 секунд"; exit 1; }
    sleep 2
done

# ─── 4. Импорт дампа БД ─────────────────────────────────────────────────────
DUMP="db/bitrix.sql.gz"
if [ "$SKIP_DB" = false ]; then
    if [ ! -f "$DUMP" ]; then
        err "Дамп не найден: ${DUMP}"
        echo ""
        echo "Варианты:"
        echo "  а) Скопируй дамп (сделай на рабочей машине: bash db/export.sh)"
        echo "  б) Первичная установка Bitrix: http://localhost:${HTTP_PORT}/bitrixsetup.php"
        echo "     После установки: bash setup.sh --skip-db"
        exit 1
    fi

    info "Импорт базы данных из ${DUMP} (может занять минуту)..."
    gunzip -c "$DUMP" | docker compose exec -T db mariadb \
        -u "${MYSQL_USER}" -p"${MYSQL_PASSWORD}" "${MYSQL_DATABASE}"
    ok "База данных импортирована"
else
    info "Импорт БД пропущен (--skip-db)"
fi

# ─── Хелпер: запуск PHP-скрипта внутри контейнера ───────────────────────────
run_php() {
    local script="$1"
    shift
    local args="${*:-}"
    info "php local/tools/${script} ${args}"
    docker compose exec -T php bash -lc \
        "cd /var/www/bitrix && php local/tools/${script} ${args} 2>&1"
    ok "${script}"
}

# ─── 5. Контент ─────────────────────────────────────────────────────────────
if [ "$SKIP_CONTENT" = false ]; then

    echo ""
    info "=== Инфоблоки и структура ==="
    run_php uuopera_full_setup.php
    run_php uuopera_persone_iblock_install.php

    echo ""
    info "=== Импорт контента из WP ==="
    run_php uuopera_wp_import_static_pages.php
    run_php uuopera_wp_import_categories.php
    run_php uuopera_wp_mysql_import_iblocks.php "--with-thumb --only=persone,projects"

    echo ""
    info "=== Персоналии ==="
    run_php uuopera_persone_seed.php

    echo ""
    info "=== Афиша (загрузка с прод-сайта) ==="
    run_php uuopera_afisha_bulk_import_uuopera.php

else
    info "Импорт контента пропущен (--skip-content)"
fi

# ─── Готово ──────────────────────────────────────────────────────────────────
echo ""
echo -e "${GREEN}══════════════════════════════════════════${NC}"
ok "Развёртывание завершено!"
echo -e "${GREEN}Сайт:${NC}    http://localhost:${HTTP_PORT}/"
echo -e "${GREEN}Админка:${NC} http://localhost:${HTTP_PORT}/bitrix/admin/"
echo -e "${GREEN}══════════════════════════════════════════${NC}"
