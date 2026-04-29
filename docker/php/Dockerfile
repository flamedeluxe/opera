FROM php:8.2-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    libonig-dev \
    zlib1g-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        gd \
        mysqli \
        pdo_mysql \
        zip \
        mbstring \
        intl \
        opcache \
        bcmath \
        soap \
        exif \
    && rm -rf /var/lib/apt/lists/*

RUN { \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.validate_timestamps=1'; \
    echo 'upload_max_filesize=256M'; \
    echo 'post_max_size=256M'; \
    echo 'max_execution_time=300'; \
    echo 'memory_limit=512M'; \
    echo 'session.save_handler=files'; \
    } > /usr/local/etc/php/conf.d/bitrix.ini

WORKDIR /var/www/bitrix
