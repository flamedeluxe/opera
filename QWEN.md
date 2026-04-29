# QWEN.md — Бурятский театр оперы и балета (uuopera.ru)

## Обзор проекта

Сайт **Бурятского государственного театра оперы и балета** (uuopera.ru), работающий на CMS **1С-Битрикс**. Локальная среда развёрнута через **Docker Compose** (nginx + PHP 8.2-FPM + MariaDB 10.11 + Memcached).

Кастомный шаблон сайта — `uuopera`. Реализована система ЧПУ (человеко-понятные URL) через собственную систему диспетчеризации (`uuopera_dispatch*`).

## Структура каталогов

```
opera/
├── docker-compose.yml        # Конфигурация Docker-окружения
├── .env / .env.example       # Переменные окружения (порты, БД)
├── www/                      # Документ-рут сайта (Bitrix webroot)
│   ├── index.php             # Главная страница
│   ├── local/                # Кастомный код Битрикс
│   │   ├── php_interface/    # PHP-хелперы: диспетчеризация, афиша, инфоблоки
│   │   ├── templates/uuopera/# Шаблон сайта (компоненты, includes)
│   │   └── tools/            # CLI-скрипты установки/импорта данных
│   ├── afisha/               # Раздел «Афиша» (подразделы по жанрам)
│   ├── news/                 # Раздел «Новости»
│   ├── projects/             # Раздел «Проекты»
│   ├── contacts/             # Раздел «Контакты»
│   └── ...                   # Остальные разделы сайта
├── tpl/                      # Статические ассеты шаблона
│   ├── css/                  # CSS-файлы (common.css, page-index.css, и т.д.)
│   └── js/                   # JS-файлы (front.js, page-index.js, и т.д.)
├── scripts/                  # Вспомогательные Python-скрипты
│   ├── gen_route_indexes.py  # Генерация index.php для маршрутов
│   └── extract_html_main.py  # Извлечение контента из HTML
├── *.html                    # HTML-шаблоны/заготовки страниц (корень)
└── sprite.svg                # SVG-спрайт иконок
```

## Стек технологий

| Компонент | Технология |
|-----------|------------|
| CMS | 1С-Битрикс (неустановленная/локальная копия) |
| PHP | 8.2-FPM |
| Web-сервер | nginx 1.25-alpine |
| СУБД | MariaDB 10.11 |
| Кэш | Memcached 1.6 |
| Frontend | Tailwind CSS, jQuery 3.7, кастомный JS |
| Интеграции | Radario (продажа билетов), Яндекс.Метрика, CulturalTracking |

## Запуск и разработка

### Первоначальная настройка

```bash
# 1. Скопировать .env.example в .env и настроить пароли БД
cp .env.example .env

# 2. Запустить контейнеры
docker compose up -d

# 3. Сайт доступен на http://localhost:18080/
#    До установки Битрикс открыть /bitrixsetup.php
```

### Установка Битрикс

1. Открыть `http://localhost:18080/bitrixsetup.php`
2. В мастере установки указать:
   - **Хост БД:** `db`
   - **Имя БД / логин / пароль:** из `.env` (`MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`)
3. После установки: **Настройки → Сайты** → выбрать шаблон сайта `uuopera`
4. Если установщик перезаписал `www/index.php` — восстановить из репозитория

### Одноразовые скрипты настройки (запускать в контейнере php)

```bash
# Мегаменю (инфоблок):
docker compose exec php php local/tools/uuopera_megamenu_iblock_install.php

# Афиша — установка инфоблока + импорт событий:
docker compose exec php php local/tools/uuopera_afisha_events_install.php
docker compose exec php php local/tools/uuopera_afisha_bulk_import_uuopera.php
```

### Полезные команды

```bash
# Логи контейнеров
docker compose logs -f nginx
docker compose logs -f php

# Доступ в контейнер php
docker compose exec php bash

# Перезапуск после изменений
docker compose restart
```

## Архитектура маршрутизации

### Система ЧПУ

Маршрутизация реализована через кастомную систему диспетчеризации:

- **`www/local/php_interface/uuopera_dispatch.php`** — основной диспетчер, маппинг URL → шаблоны страниц
- **`www/local/php_interface/uuopera_page.php`** — хелпер рендеринга страницы (заголовок, include шаблона, CSS/JS)
- **`www/uuopera_route.php`** — единая точка входа для `bitrix/urlrewrite.php`
- **`www/afisha/_route.php`** — специфичный роутер для раздела «Афиша»

### Генерация route-файлов

Скрипт `scripts/gen_route_indexes.py` сканирует шаблоны на наличие `href="..."` и автоматически создаёт `index.php` файлы с вызовом `uuopera_dispatch_from_script()` для сохранения ЧПУ.

## Разделы сайта

| Раздел | URL | Описание |
|--------|-----|----------|
| Главная | `/` | Лендинг с баннером, новостями, афишей |
| Афиша | `/afisha/` | Представления, фестивали, экскурсии, онлайн |
| Проекты | `/projects/` | Национальная опера, Национальный балет |
| Новости | `/category/news/` | Новостная лента |
| О театре | `/missiya-i-cennosti/` | Миссия и ценности |
| Платные услуги | `/services/` | Информация об услугах |
| Контакты | `/contacts/` | Контактная информация |

## Интеграции

- **Radario** — виджет покупки билетов (кнопка «Купить билеты» в шапке)
- **Яндекс.Метрика** — аналитика (ID: 53733301)
- **CulturalTracking** — пиксель культурных событий
- **BVI** — виджет версии для слабовидящих

## Переменные окружения (.env)

| Переменная | По умолчанию | Описание |
|------------|--------------|----------|
| `HTTP_PORT` | `18080` | Порт для nginx |
| `TZ` | `UTC` | Часовой пояс |
| `BITRIX_DEBUG` | `0` | Режим отладки PHP (только разработка) |
| `MYSQL_ROOT_PASSWORD` | `change_me` | Пароль root MySQL |
| `MYSQL_DATABASE` | `bitrix` | Имя базы данных |
| `MYSQL_USER` | `bitrix` | Пользователь БД |
| `MYSQL_PASSWORD` | `change_me` | Пароль пользователя БД |

## Примечания

- HTML-файлы в корне проекта (`index.html`, `afisha.html`, `news.html` и т.д.) — это шаблоны/заготовки, используемые при разработке
- Директория `www/bitrix/` содержит ядро Битрикс (не редактировать вручную, обновляется CMS)
- `tpl/css/` и `tpl/js/` — статические ассеты, подключённые к шаблону `uuopera`
- Версия для слабовидящих реализована через модуль BVI
