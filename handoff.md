# Handoff: uuopera.ru — Bitrix CMS

Сайт Бурятского государственного театра оперы и балета. Миграция с WordPress на 1С-Bitrix CMS.

**Стек:** PHP + Bitrix, MariaDB, Nginx, Docker. Шаблон `uuopera`, роутинг через `uuopera_dispatch.php`.

---

## Что сделано

### 1. Афиша: галерея и состав исполнителей

**Проблема:** на страницах спектаклей не отображались галерея и состав актёров.

**Причины, которые были найдены:**
- Баг в `uuopera_afisha_event_get_data()`: массив `$gallery` инициализировался пустым и никогда не заполнялся из свойства GALLERY инфоблока (функция `uuopera_iblock_gallery_paths_from_file_property()` существовала, но не вызывалась).
- Инструмент импорта (`uuopera_afisha_bulk_import_uuopera.php`) не был запущен — инфоблок был пустым.

**Что исправлено:**
- Добавлено чтение свойства GALLERY из инфоблока в `uuopera_afisha_events.php`.
- Реализовано удаление дублирующихся слайдеров из `content_html` (lazyblock-image-slider-default / fullscreen), чтобы локальная галерея не конкурировала с галереей из HTML контента (размер HTML уменьшился с ~11 585 до ~4 637 символов).
- Ссылки на участников (состав) используют относительные URL.

**Как работает импорт:**
- Скрипт `uuopera_afisha_bulk_import_uuopera.php` скачивает HTML 29 страниц с `uuopera.ru/afisha/`, парсит их через `uuopera_afisha_parse_uuopera.php` и записывает данные в инфоблок Bitrix.
- Блок «Состав» парсится из секции `Состав` на страницах uuopera.ru.
- Галерея — из тегов `<img>` в swiper-wrapper с `src` на `uuopera.ru/wp-content/`.
- Изображения скачиваются и сохраняются в `/upload/iblock/`.
- Запуск: **29 событий, 0 ошибок**.

**Результат верификации:**
- Событие ID=110 (Красавица Ангара): GALLERY содержит 20 файлов, swiper рендерится с локальными путями `/upload/iblock/`.
- События без локальной галереи (например, Кармен): слайдер рендерится из `content_html` с внешними URL — ожидаемое поведение.
- Состав: если `Состав` не отображается на `uuopera.ru` для конкретного спектакля, парсер ничего не извлечёт — данные останутся пустыми.

**Схема инфоблока афиши** (IBLOCK_ID хранится в опциях Bitrix):
| Свойство | Тип | Назначение |
|---|---|---|
| PARTICIPANTS_HTML | T (текст) | HTML состава исполнителей |
| GALLERY | F (файл, множественное) | Фотографии галереи |
| SESSIONS_JSON | T | Расписание показов в JSON |
| RADARIO_* | S | Ключи интеграции с Radario |
| HERO_META_HTML | T | Мета-блок героя |
| CONTENT_HTML | T | Основной HTML контент |
| CATEGORY, LAYOUT, AGE | S | Категория, макет, возрастной рейтинг |

---

### 2. Новые страницы и шаблоны

Добавлены шаблоны страниц в `www/local/templates/uuopera/includes/`:

| Файл | Страница |
|---|---|
| `page_persone_detail.php` | Карточка артиста |
| `page_personalii.php` | Список артистов (персоналии) |
| `page_news_category.php` | Новости по категории |
| `page_for_visitors.php` | «Для посетителей» |
| `page_contacts.php` | Контакты |

Добавлены разделы сайта:
- `www/about/` — история, миссия, руководство, вакансии
- `www/contacts/` — обратная связь, реквизиты
- `www/services/` — услуги (corp, fiz, financialorg, smallbusiness)
- `www/balet_na_baikale/` — спецпроект «Балет на Байкале»

---

### 3. Инструменты (www/local/tools/)

| Скрипт | Назначение |
|---|---|
| `uuopera_full_setup.php` | Запускает все инсталляторы подряд |
| `uuopera_afisha_bulk_import_uuopera.php` | Импорт 29 спектаклей с uuopera.ru |
| `uuopera_persone_iblock_install.php` | Создаёт инфоблок «Артисты» |
| `uuopera_persone_seed.php` | Заполняет инфоблок артистами |
| `uuopera_about_seed.php` | Заполняет страницу «О театре» |
| `uuopera_projects_seed.php` | Заполняет проекты |
| `uuopera_services_seed.php` | Заполняет услуги |
| `uuopera_home_slides_seed.php` | Слайды главной страницы |
| `uuopera_wp_mysql_import_iblocks.php` | Импорт из WordPress MySQL в инфоблоки |
| `uuopera_news_sections_seed.php` | Создаёт разделы новостей |
| `uuopera_seed_admin_iblock_sections.php` | Разделы инфоблоков |

---

### 4. Маршрутизация

`uuopera_dispatch.php` расширен для новых URL-паттернов: артисты, категории новостей, страницы услуг, экскурсии. `urlrewrite.php` обновлён аналогично.

---

### 5. Инфраструктура

- `docker-compose.yml` и `docker/nginx/default.conf` обновлены.
- `CLAUDE.md` добавлен (инструкции для AI-ассистента).
- `.gitignore` обновлён: исключены `wp-old/`, `C:/`, `.claude/`, `www/wp-content/`, `www/wp-json/`, `db/*.sql`.
- `deploy/vps-init.sh` + `deploy/env.production.example` — скрипты деплоя на VPS.

---

## Запуск с нуля

```bash
cp .env.example .env
docker compose up -d
# Настройка Bitrix через http://localhost:18080/bitrixsetup.php
# Затем:
docker compose exec -T php bash -lc 'cd /var/www/bitrix && php local/tools/uuopera_full_setup.php'
docker compose exec -T php bash -lc 'cd /var/www/bitrix && php local/tools/uuopera_afisha_bulk_import_uuopera.php'
```

## Повторный импорт афиши

```bash
docker compose exec -T php bash -lc 'cd /var/www/bitrix && php local/tools/uuopera_afisha_bulk_import_uuopera.php'
```

Импорт идемпотентен: обновляет по `XML_ID` (code спектакля), не дублирует.

---

## Известные ограничения

- **Состав пустой у части спектаклей** — если на `uuopera.ru` страница спектакля не рендерит блок «Состав» (WordPress helpers не работают для этого события), парсер ничего не извлечёт. Данные в ACF/postmeta есть, но на сайте не отображаются → в Bitrix тоже пусто.
- **Импорт зависит от доступности uuopera.ru** — скрипт скрапит живой сайт. Если сайт недоступен или изменил HTML-структуру — парсинг сломается.
- **Инфоблок артистов (персоналии)** — `uuopera_persone_iblock_install.php` создаёт структуру, `uuopera_persone_seed.php` заполняет данными. Убедись что оба запущены.
- **WordPress MySQL** — импорт через `uuopera_wp_mysql_import_iblocks.php` требует предварительного создания БД `wordpress` и загрузки дампа (см. CLAUDE.md).
