FROM php:7.4-fpm-alpine

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
    shadow

RUN docker-php-ext-install \
        mysqli

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set IDs from our local user
RUN usermod -u ${USER_ID} www-data && groupmod -g ${GROUP_ID} www-data || true
USER "${USER_ID}:${GROUP_ID}"

WORKDIR /app