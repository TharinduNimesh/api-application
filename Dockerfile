# Stage 1: Build dependencies
FROM php:8.3-apache as builder

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

# Create necessary directories and set permissions
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Copy the entire application first
COPY . .

# Install Composer dependencies and run optimization
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    && composer dump-autoload -o

# Install npm dependencies and build assets
RUN npm ci

# Generate app key if not set
RUN php artisan key:generate

# Run Laravel optimization commands
RUN php artisan config:cache \
    && php artisan event:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan optimize

# Stage 2: Final production image
FROM php:8.3-apache

# Install production PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev \
    && docker-php-ext-install pdo_mysql bcmath zip pcntl \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www

# Create non-root user
RUN useradd --create-home --uid 1000 laravel

# Create necessary directories
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    bootstrap/cache

# Copy application from builder
COPY --from=builder --chown=laravel:laravel /var/www/vendor ./vendor
COPY --from=builder --chown=laravel:laravel /var/www/public ./public
COPY --from=builder --chown=laravel:laravel /var/www/storage ./storage
COPY --from=builder --chown=laravel:laravel /var/www/bootstrap ./bootstrap
COPY --from=builder --chown=laravel:laravel /var/www/config ./config
COPY --from=builder --chown=laravel:laravel /var/www/database ./database
COPY --from=builder --chown=laravel:laravel /var/www/routes ./routes
COPY --from=builder --chown=laravel:laravel /var/www/app ./app
COPY --from=builder --chown=laravel:laravel /var/www/artisan .

# Copy cached files from builder
COPY --from=builder --chown=laravel:laravel /var/www/bootstrap/cache/*.php ./bootstrap/cache/
COPY --from=builder --chown=laravel:laravel /var/www/storage/framework/views/*.php ./storage/framework/views/

# Copy environment file if it exists
COPY --from=builder --chown=laravel:laravel /var/www/.env.example ./.env.example

# Copy Apache configuration
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

# Set permissions
RUN chown -R laravel:laravel /var/www \
    && chmod -R 775 storage bootstrap/cache public

# Switch to non-root user
USER laravel

EXPOSE 80

CMD ["apache2-foreground"]