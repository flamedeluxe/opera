# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Website for the Buryat State Opera and Ballet Theater (uuopera.ru), built on **1С-Bitrix CMS** with a custom PHP template and Docker infrastructure.

## Development Environment

```bash
# Start all services (nginx, php, mariadb, memcached)
docker compose up -d

# Access site: http://localhost:18080/
# Bitrix setup wizard: http://localhost:18080/bitrixsetup.php

# View logs
docker compose logs -f nginx
docker compose logs -f php

# Shell into PHP container
docker compose exec php bash
```

On **Windows**, Docker-команды задавай **из Bash в WSL**, а не одной строкой из PowerShell: сначала войди в оболочку, потом переход в каталог и команды по отдельности.

1. `wsl -d Ubuntu-22.04` — открывается сессия bash.
2. `cd ~/projects/opera` (или полный путь к клону).
3. Дальше `docker compose …` как в примерах ниже (`bash -lc 'cd … && …'` не нужен).

Environment: copy `.env.example` to `.env`. DB host is `db` (Docker service name).

After Bitrix setup, select the `uuopera` template in the admin panel. If Bitrix overwrites `www/index.php`, restore it from git.

## One-Time Setup Scripts

From project root **in Bash** (host), optionally run everything in sequence:

```bash
docker compose exec -T php bash -lc 'cd /var/www/bitrix && php local/tools/uuopera_full_setup.php'
```

Equivalent manual chain (inside `cd /var/www/bitrix` in the PHP container):

```bash
docker compose exec -T php bash -lc 'cd /var/www/bitrix && php local/tools/uuopera_cms_iblocks_install.php && php local/tools/uuopera_megamenu_iblock_install.php && php local/tools/uuopera_afisha_events_install.php && php local/tools/uuopera_seed_admin_iblock_sections.php'
```

Alternatively: `docker compose exec php bash`, then `cd /var/www/bitrix` and run each PHP line below.

```bash
php local/tools/uuopera_cms_iblocks_install.php
php local/tools/uuopera_megamenu_iblock_install.php
php local/tools/uuopera_afisha_events_install.php
php local/tools/uuopera_seed_admin_iblock_sections.php
php local/tools/uuopera_afisha_bulk_import_uuopera.php
php local/tools/uuopera_persone_iblock_install.php
php local/tools/uuopera_persone_sections_install.php
php local/tools/uuopera_persone_migrate_sections.php
php local/tools/uuopera_persone_remove_category_property.php
php local/tools/uuopera_megamenu_sync_personalii.php
```

Деплой на VPS (`ssh opera`): `./deploy/deploy.sh opera` (код + БД + `upload/` через `deploy/sync-upload.sh`). Только медиа Bitrix: `./deploy/sync-upload.sh opera`. Каталог `wp-content/` на сервере не перезаписывается.

## Utility Scripts

```bash
# Scan template href="" attributes and generate index.php stubs for discovered routes
python3 scripts/gen_route_indexes.py

# Extract page content from WordPress HTML exports (migration helper)
python3 scripts/extract_html_main.py
```

### WordPress MySQL dump (`wp-old/`)

The file `wp-old/u468291_operanew.sql` is a **full WordPress database** (`wp_posts`, `wp_postmeta`, terms, etc.). In it, `wp_posts.post_type` includes at least `post` (news), `page`, **`persone`**, **`event`**, **`project`**, and WP service types. That data is **not** inside the Bitrix schema: with Docker, `MYSQL_DATABASE` in `.env` is usually `bitrix`, so the dump is not consulted until you load it separately.

**To use it for migration:** import into a **second database** on the same MariaDB container (example name `wordpress`), then map rows into Bitrix iblocks.

В уже открытом bash после `cd` в корень проекта (и при необходимости `docker compose up -d`). Для пароля root MariaDB удобнее скопировать значение из `.env` или выполнить `export MYSQL_ROOT_PASSWORD=…` перед командами; иначе подставь пароль после `-p` вручную. Пример:

```bash
docker compose exec -T db mariadb -u root -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS wordpress CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL ON wordpress.* TO 'bitrix'@'%'; FLUSH PRIVILEGES;"
docker compose exec -T db mariadb -u root -p"$MYSQL_ROOT_PASSWORD" wordpress < wp-old/u468291_operanew.sql
```

Set `.env` keys `WP_IMPORT_DB_*` / `WP_TABLE_PREFIX` if they differ from defaults (PHP container reads them via `docker-compose`; see `.env.example`).

Импорт в инфоблоки (те же условия: bash в WSL, `cd` в корень репозитория). Элементы: `page`→статические страницы, `post`→новости (`uuopera_news_iblock_id()`), `persone`→`/persone/{slug}`, `project`→«Проекты».

```bash
docker compose exec -T php php /var/www/bitrix/local/tools/uuopera_wp_mysql_import_iblocks.php --dry-run
docker compose exec -T php php /var/www/bitrix/local/tools/uuopera_wp_mysql_import_iblocks.php
```

Повторный запуск обновляет элементы по `XML_ID` (`uuopera_wp_page_*` и т.д.). Контент правится дальше в админке (инфоблоки CMS и новости).

**Тип `event`** (афиша) в этом скрипте не переносится — используйте `uuopera_afisha_*` или отдельный этап.

**Current repo behavior:** афиша с сайта — скриптами `uuopera_afisha_bulk_import_uuopera.php`; SQL не читается автоматически без шага создания БД и команды импорта выше.

## Architecture

### Routing

All requests hit `www/index.php` (home) or `www/uuopera_route.php` (all other URLs) via nginx `try_files`. The afisha section has its own fallback at `www/afisha/_route.php`.

`uuopera_route.php` calls `uuopera_dispatch_for_path()` defined in `www/local/php_interface/uuopera_dispatch.php`, which maps URL patterns (regex) to page includes and sets globals like `$page_title_text` and `$PAGE_TITLE_CALLBACK`.

### Page Rendering

`uuopera_page($include_path)` in `uuopera_page.php` wraps a page include with Bitrix prolog/epilog. The Bitrix template (`www/local/templates/uuopera/`) provides `header.php` and `footer.php`. Page-specific content lives in `www/local/templates/uuopera/includes/` (e.g., `main_home.php`, `page_afisha_item_dynamic.php`).

Per-page CSS/JS are injected via globals:
- `$GLOBALS['UUOPERA_EXTRA_CSS']` — additional `<link>` tags in `<head>`
- `$GLOBALS['UUOPERA_FOOTER_JS']` — `<script>` tags before `</body>`

### Content (Infoblocks)

Bitrix infoblocks are the CMS data layer. IDs are stored in `\Bitrix\Main\Config\Option` (not hardcoded) and retrieved via helper functions:

| Helper | Content |
|---|---|
| `uuopera_afisha_events_iblock_id()` | Afisha/Events |
| `uuopera_news_iblock_id()` | News |
| `uuopera_cms_projects_iblock_id()` | Projects |
| `uuopera_cms_static_pages_iblock_id()` | Static CMS pages |
| `uuopera_megamenu_iblock_id()` | Navigation menu |

Bootstrap / property setup: `www/local/php_interface/uuopera_cms_iblocks_bootstrap.php`; runtime getters: `uuopera_cms_data.php`, `init.php`, `uuopera_afisha_events.php`.

### Assets

Static assets live in `www/local/templates/uuopera/tpl/` (CSS and JS). The root-level `tpl/` directory is a symlink to the same location.

- `tpl/css/common.css` + page-specific CSS (e.g., `page-contacts.css`)
- `tpl/js/common.js` + page-specific JS (e.g., `page-single-event.js`)
- `tpl/` also contains BVI accessibility module and the icon sprite at `assets/sprite.svg`

### Naming Conventions

All custom PHP helpers use the `uuopera_` prefix. Page dispatch flow: URL → `uuopera_dispatch.php` → set globals → `uuopera_page()` → render include → header/footer inject assets.

### External Integrations

- **Yandex.Metrica** — analytics (ID 53733301)
- **Radario** — ticketing widget in header
- **BVI** — accessibility module (text-to-speech, low-vision)
