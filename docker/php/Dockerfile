FROM php:8.4-cli

# System packages required by CakePHP, Composer and the plugin
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    sqlite3 \
    libicu-dev \
    libzip-dev \
    libpq-dev \
    default-mysql-client \
    postgresql-client \
    && docker-php-ext-install \
        intl \
        pdo \
        pdo_mysql \
        mysqli \
        pdo_pgsql \
        pgsql \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Git >= 2.35 refuses to operate on mounted repos with different ownership.
# Mark /app (the mounted project root) as a safe directory system-wide.
RUN git config --system --add safe.directory /app

# Install Composer from the official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Project root inside the container
WORKDIR /app
