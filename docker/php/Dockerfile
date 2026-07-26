FROM node:22-bookworm-slim AS vite-assets

WORKDIR /build

COPY package.json package-lock.json ./

RUN npm ci --include=dev --no-audit --no-fund

COPY . .

RUN rm -f public/hot \
    && npm run build \
    && test -s public/build/manifest.json

FROM php:8.2-fpm-bookworm

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libwebp-dev \
        libxml2-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        mysqli \
        opcache \
        pcntl \
        pdo_mysql \
        sockets \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-art-inpa.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/99-art-inpa.conf
COPY docker/php/entrypoint.sh /usr/local/bin/art-inpa-entrypoint
COPY docker/php/php-fpm-wrapper.sh /usr/local/sbin/php-fpm-wrapper
COPY --from=vite-assets /build/public/build /opt/art-inpa/public/build

RUN mv /usr/local/sbin/php-fpm /usr/local/sbin/php-fpm-real \
    && mv /usr/local/sbin/php-fpm-wrapper /usr/local/sbin/php-fpm \
    && chmod +x /usr/local/bin/art-inpa-entrypoint /usr/local/sbin/php-fpm

ENTRYPOINT ["art-inpa-entrypoint"]
CMD ["php-fpm", "-F"]
