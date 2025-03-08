# Used for prod build.
FROM php:8.3-fpm-alpine as php

# Set environment variables
ENV PHP_OPCACHE_ENABLE=1
ENV PHP_OPCACHE_ENABLE_CLI=0
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=0
ENV PHP_OPCACHE_REVALIDATE_FREQ=0

# Install dependencies.
RUN apk add --no-cache \
    unzip \
    libzip-dev \
    curl-dev \
    openssl-dev \
    oniguruma-dev \
    nginx \
    # Node.js and npm
    nodejs \
    npm \
    # For MongoDB extension dependencies
    $PHPIZE_DEPS

# Install MongoDB PHP extension
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Install PHP extensions including OpenSSL
RUN docker-php-ext-install \
    bcmath \
    curl \
    opcache \
    mbstring \
    zip

# Copy composer executable.
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy configuration files.
COPY ./docker/php/php.ini /usr/local/etc/php/php.ini
COPY ./docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY ./docker/nginx/nginx.conf /etc/nginx/nginx.conf

# Set working directory to /var/www.
WORKDIR /var/www

# Copy files from current folder to container current folder (set in workdir).
COPY --chown=www-data:www-data . .

# Create laravel caching folders.
RUN mkdir -p /var/www/storage/framework
RUN mkdir -p /var/www/storage/framework/cache
RUN mkdir -p /var/www/storage/framework/testing
RUN mkdir -p /var/www/storage/framework/sessions
RUN mkdir -p /var/www/storage/framework/views

# Fix files ownership.
RUN chown -R www-data /var/www/storage
RUN chown -R www-data /var/www/storage/framework
RUN chown -R www-data /var/www/storage/framework/sessions
RUN chown -R www-data /var/www/storage/framework/views

# Set correct permission.
RUN chmod -R 755 /var/www/storage
RUN chmod -R 775 /var/www/storage/logs
RUN chmod -R 775 /var/www/storage/framework
RUN chmod -R 775 /var/www/storage/framework/sessions
RUN chmod -R 775 /var/www/storage/framework/views
RUN chmod -R 755 /var/www/bootstrap

# Adjust user permission & group for Alpine
RUN adduser -u 1000 -D -S -G www-data www-data

# Run the entrypoint file.
ENTRYPOINT [ "docker/entrypoint.sh" ]
