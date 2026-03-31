FROM php:8.4-fpm

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
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# PHP config
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
