FROM php:8.3-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libzip-dev \
    curl \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev \
    && docker-php-ext-install pdo_mysql bcmath zip pcntl \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy composer files first to leverage Docker cache
COPY composer.json composer.lock ./

# Install Composer dependencies
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts

# Copy package.json and package-lock.json
COPY package.json package-lock.json ./

# Install npm dependencies
RUN npm ci

# Copy the rest of the application code
COPY . .

# Run post-install scripts
RUN php artisan clear-compiled || true \
    && composer dump-autoload \
    && php artisan optimize:clear || true \
    && npm run build

# Create non-root user and set permissions
RUN useradd --create-home --uid 1000 laravel \
    && chown -R laravel:laravel /var/www \
    && chmod -R 775 storage bootstrap/cache public

# Apache configuration
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

# Switch to non-root user
USER laravel

EXPOSE 80

CMD ["apache2-foreground"]