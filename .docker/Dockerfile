FROM php:7.4.16-fpm-alpine

ARG LOCAL_ENV
ARG USER_ID
ARG GROUP_ID

RUN apk update && apk add --no-cache \
    git \
    curl \
    g++ \
    gcc \
    tar \
    zip \
    wget \
    unzip \
    openssh \
    libzip-dev \
    sqlite \
    sqlite-dev \
    shadow

RUN docker-php-ext-install \
    pdo_sqlite

RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug-3.0.3  \
    && docker-php-ext-enable xdebug;

RUN mkdir /db && chown -R ${USER_ID}:${GROUP_ID} /db && /usr/bin/sqlite3 /db/queue.db

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --version=2.0.11 --filename=composer

# Set IDs from our local user
RUN usermod -u ${USER_ID} www-data && groupmod -g ${GROUP_ID} www-data || true
USER "${USER_ID}:${GROUP_ID}"

COPY php.ini         /usr/local/etc/php/conf.d/php.ini

WORKDIR /app