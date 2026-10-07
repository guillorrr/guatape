# Base pinned by digest: a mutable tag (php:8.4-fpm) moves upstream and silently
# rebuilds every layer below it. To upgrade on purpose:
#   docker buildx imagetools inspect docker.io/library/php:8.4-fpm
FROM php:8.4-fpm@sha256:d1a0ec2160261211a060fcf652551f63b515095c66df62fa2f245aa113cbe51c

# System dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    libsodium-dev \
    zip \
    unzip \
    default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        sodium \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Composer, pinned by digest for the same reason as the base image.
COPY --from=composer:2@sha256:e8fdff913656c23e90ebbe0d7c55ab078c2aefdbb53ff79a73af5cc0921d5b81 /usr/bin/composer /usr/bin/composer

# PHP config (shared by dev and prod; dev-only overrides live in docker/api/php-dev.ini)
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
RUN echo "upload_max_filesize=64M" >> "$PHP_INI_DIR/php.ini" \
    && echo "post_max_size=64M" >> "$PHP_INI_DIR/php.ini" \
    && echo "memory_limit=256M" >> "$PHP_INI_DIR/php.ini"

# Working directory
WORKDIR /var/www/api

# User permissions
RUN groupadd -g 1000 www && useradd -u 1000 -ms /bin/bash -g www www
RUN chown -R www:www /var/www
USER www

EXPOSE 9000
CMD ["php-fpm"]
